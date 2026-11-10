<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\DBAL\ParameterType;
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

        // Disable query logging middlewares on both connections to prevent memory buildup in bulk mirroring
        $this->disableQueryLogging($this->sourceConnection);
        $this->disableQueryLogging($this->mirrorConnection);

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
        $columns = null;

        while (true) {
            $rows = $this->fetchChunk($classMetadata, $primaryKeyColumn, $chunkSize, $lastSeenId, $sourcePlatform);
            if ($rows === []) {
                break;
            }

            if ($insertStatement === null) {
                $columns = array_keys($rows[0]);
                $insertStatement = $this->prepareUpsert($classMetadata, $primaryKeyColumn, $columns, $targetPlatform);
            }

            foreach ($rows as $row) {
                $normalizedRow = $this->normalizeRow($row);
                $paramIndex = 1;
                foreach ($columns as $column) {
                    $value = $normalizedRow[$column] ?? null;
                    $insertStatement->bindValue($paramIndex++, $value, $this->getParameterType($value));
                }
                $insertStatement->executeStatement();
            }

            $lastSeenId = $rows[array_key_last($rows)][$primaryKeyColumn] ?? null;
            $progress->advance(count($rows));
            unset($rows);
            gc_collect_cycles();
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
            ->orderBy($platform->quoteSingleIdentifier($primaryKeyColumn), 'ASC')
            ->setMaxResults($chunkSize);

        if ($lastSeenId !== null) {
            $qb->where($qb->expr()->gt($platform->quoteSingleIdentifier($primaryKeyColumn), ':lastId'))
                ->setParameter('lastId', $lastSeenId);
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param list<string> $columns
     */
    private function prepareUpsert(ClassMetadata $classMetadata, string $primaryKeyColumn, array $columns, AbstractPlatform $platform): Statement
    {
        $qualifiedTable = $this->qualifyTableName($classMetadata, $platform);

        $quotedColumns = array_map(static fn (string $column): string => $platform->quoteSingleIdentifier($column), $columns);
        $placeholders = array_fill(0, count($columns), '?');

        $updateAssignments = [];
        foreach ($columns as $column) {
            if ($column === $primaryKeyColumn) {
                continue;
            }
            $quoted = $platform->quoteSingleIdentifier($column);
            $updateAssignments[] = sprintf('%s = VALUES(%s)', $quoted, $quoted);
        }

        if ($updateAssignments === []) {
            $quotedPk = $platform->quoteSingleIdentifier($primaryKeyColumn);
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
        $tableName = $platform->quoteSingleIdentifier($classMetadata->getTableName());

        if ($classMetadata->getSchemaName()) {
            $schema = $platform->quoteSingleIdentifier($classMetadata->getSchemaName());
            return sprintf('%s.%s', $schema, $tableName);
        }

        return $tableName;
    }

    private function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_bool($value)) {
                $row[$key] = (int) $value;
            } elseif (is_array($value)) {
                $row[$key] = json_encode($value, JSON_THROW_ON_ERROR);
            }
        }

        return $row;
    }

    private function getParameterType(mixed $value): ParameterType
    {
        return match (true) {
            $value === null => ParameterType::NULL,
            is_int($value) => ParameterType::INTEGER,
            is_bool($value) => ParameterType::BOOLEAN,
            is_resource($value) => ParameterType::LARGE_OBJECT,
            default => ParameterType::STRING,
        };
    }

    private function disableQueryLogging(Connection $connection): void
    {
        $config = $connection->getConfiguration();
        if (method_exists($config, 'setSQLLogger')) {
            $config->setSQLLogger(null);
        }
        if (method_exists($config, 'getMiddlewares')) {
            $filteredMiddlewares = array_filter(
                $config->getMiddlewares(),
                fn($m) => !($m instanceof LoggingMiddleware)
            );
            $config->setMiddlewares($filteredMiddlewares);
        }
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
