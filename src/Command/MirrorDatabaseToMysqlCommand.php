<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:mirror:mysql',
    description: 'Mirror PostgreSQL data into the MySQL mirror database',
)]
final class MirrorDatabaseToMysqlCommand extends Command
{
    private const DEFAULT_CHUNK_SIZE = 500;

    public function __construct(
        #[Autowire(service: 'doctrine.orm.default_entity_manager')]
        private readonly EntityManagerInterface $sourceEntityManager,
        #[Autowire(service: 'doctrine.orm.mysql_mirror_entity_manager')]
        private readonly EntityManagerInterface $mirrorEntityManager,
        #[Autowire(service: 'doctrine.dbal.default_connection')]
        private readonly Connection $sourceConnection,
        #[Autowire(service: 'doctrine.dbal.mysql_mirror_connection')]
        private readonly Connection $mirrorConnection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('chunk-size', null, InputOption::VALUE_REQUIRED, 'Number of rows processed per batch', self::DEFAULT_CHUNK_SIZE)
            ->addOption('truncate-first', null, InputOption::VALUE_NONE, 'Truncate MySQL tables before mirroring (destructive but safer for schema drift)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $chunkSize = max(1, (int) $input->getOption('chunk-size'));
        $truncateFirst = (bool) $input->getOption('truncate-first');

        if (!$this->ensureConnections($io)) {
            return Command::FAILURE;
        }

        $metadata = $this->sourceEntityManager->getMetadataFactory()->getAllMetadata();
        if ($metadata === []) {
            $io->warning('No entity metadata found to mirror.');
            return Command::SUCCESS;
        }

        $io->section('Ensuring MySQL schema matches entity metadata');
        $schemaTool = new SchemaTool($this->mirrorEntityManager);
        $schemaTool->updateSchema($metadata, true);

        $io->section('Starting data mirror');
        $this->mirrorConnection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        try {
            if ($truncateFirst) {
                $this->truncateMirrorTables($metadata);
            }

            foreach ($metadata as $classMetadata) {
                $this->mirrorEntity($io, $classMetadata, $chunkSize);
            }
        } finally {
            $this->mirrorConnection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }

        $io->success('Mirroring completed successfully.');

        return Command::SUCCESS;
    }

    private function mirrorEntity(SymfonyStyle $io, ClassMetadata $classMetadata, int $chunkSize): void
    {
        $identifierColumns = $classMetadata->getIdentifierColumnNames();
        if ($identifierColumns === []) {
            $io->warning(sprintf('Skipping %s because no identifier column was detected.', $classMetadata->getName()));
            return;
        }

        $primaryKeyColumn = $identifierColumns[0];
        $sourcePlatform = $this->sourceConnection->getDatabasePlatform();
        $targetPlatform = $this->mirrorConnection->getDatabasePlatform();

        $io->section(sprintf('Mirroring table %s', $classMetadata->getTableName()));
        $progress = $io->createProgressBar();
        $progress->start();

        $lastSeenId = null;
        $insertStatement = null;

        while (true) {
            $rows = $this->fetchChunk($classMetadata, $primaryKeyColumn, $chunkSize, $lastSeenId, $sourcePlatform);
            if ($rows === []) {
                break;
            }

            $insertStatement ??= $this->prepareUpsert($classMetadata, $primaryKeyColumn, $rows[0], $targetPlatform);

            foreach ($rows as $row) {
                $insertStatement->executeStatement($this->normalizeRow($row));
            }

            $lastSeenId = $rows[array_key_last($rows)][$primaryKeyColumn] ?? null;
            $progress->advance(count($rows));
        }

        $progress->finish();
        $io->newLine(2);
    }

    private function fetchChunk(ClassMetadata $classMetadata, string $primaryKeyColumn, int $chunkSize, mixed $lastSeenId, AbstractPlatform $platform): array
    {
        $qualifiedTable = $this->qualifyTableName($classMetadata, $platform);

        $qb = $this->sourceConnection->createQueryBuilder();
        $qb->select('*')
            ->from($qualifiedTable)
            ->orderBy($platform->quoteIdentifier($primaryKeyColumn), 'ASC')
            ->setMaxResults($chunkSize);

        if ($lastSeenId !== null) {
            $qb->where($qb->expr()->gt($platform->quoteIdentifier($primaryKeyColumn), ':lastId'))
                ->setParameter('lastId', $lastSeenId);
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    private function prepareUpsert(ClassMetadata $classMetadata, string $primaryKeyColumn, array $sampleRow, AbstractPlatform $platform): Statement
    {
        $qualifiedTable = $this->qualifyTableName($classMetadata, $platform);
        $columns = array_keys($sampleRow);

        $quotedColumns = array_map(static fn (string $column): string => $platform->quoteIdentifier($column), $columns);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);

        $updateAssignments = [];
        foreach ($columns as $column) {
            if ($column === $primaryKeyColumn) {
                continue;
            }
            $quoted = $platform->quoteIdentifier($column);
            $updateAssignments[] = sprintf('%s = VALUES(%s)', $quoted, $quoted);
        }

        if ($updateAssignments === []) {
            $quotedPk = $platform->quoteIdentifier($primaryKeyColumn);
            $updateAssignments[] = sprintf('%s = %s', $quotedPk, $quotedPk);
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
            $qualifiedTable,
            implode(', ', $quotedColumns),
            implode(', ', $placeholders),
            implode(', ', $updateAssignments)
        );

        return $this->mirrorConnection->prepare($sql);
    }

    private function truncateMirrorTables(array $metadata): void
    {
        $platform = $this->mirrorConnection->getDatabasePlatform();

        foreach ($metadata as $classMetadata) {
            $qualifiedTable = $this->qualifyTableName($classMetadata, $platform);
            $this->mirrorConnection->executeStatement(sprintf('TRUNCATE TABLE %s', $qualifiedTable));
        }
    }

    private function qualifyTableName(ClassMetadata $classMetadata, AbstractPlatform $platform): string
    {
        $tableName = $platform->quoteIdentifier($classMetadata->getTableName());

        if ($classMetadata->getSchemaName()) {
            $schema = $platform->quoteIdentifier($classMetadata->getSchemaName());
            return sprintf('%s.%s', $schema, $tableName);
        }

        return $tableName;
    }

    private function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_bool($value)) {
                $row[$key] = (int) $value;
            }
        }

        return $row;
    }

    private function ensureConnections(SymfonyStyle $io): bool
    {
        try {
            $this->sourceConnection->executeQuery('SELECT 1');
            $this->mirrorConnection->executeQuery('SELECT 1');
        } catch (DBALException $exception) {
            $io->error(sprintf('Database connectivity check failed: %s', $exception->getMessage()));
            return false;
        }

        return true;
    }
}
