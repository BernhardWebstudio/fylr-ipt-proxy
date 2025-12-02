<?php

namespace App\Command;

use App\Entity\DarwinCore\Occurrence;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:diagnose-media-mapping',
    description: 'Diagnose missing associatedMedia in occurrences',
)]
class DiagnoseMediaMappingCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('sample-size', 's', InputOption::VALUE_OPTIONAL, 'Number of samples to show', 5)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sampleSize = (int) ($input->getOption('sample-size') ?? 5);

        $io->title('Diagnosing Associated Media Mapping');

        // Count total occurrences
        $totalCount = $this->entityManager->getRepository(Occurrence::class)->count([]);
        $io->info(sprintf('Total occurrences in database: %d', $totalCount));

        // Count occurrences with media
        $qb = $this->entityManager->createQueryBuilder();
        $withMediaCount = $qb->select('COUNT(o.id)')
            ->from(Occurrence::class, 'o')
            ->where('o.associatedMedia IS NOT NULL')
            ->andWhere("o.associatedMedia != ''")
            ->getQuery()
            ->getSingleScalarResult();

        $io->info(sprintf('Occurrences with associatedMedia: %d (%.1f%%)',
            $withMediaCount,
            $totalCount > 0 ? ($withMediaCount / $totalCount * 100) : 0
        ));

        // Count occurrences with references
        $qb = $this->entityManager->createQueryBuilder();
        $withReferencesCount = $qb->select('COUNT(o.id)')
            ->from(Occurrence::class, 'o')
            ->where('o.associatedReferences IS NOT NULL')
            ->andWhere("o.associatedReferences != ''")
            ->getQuery()
            ->getSingleScalarResult();

        $io->info(sprintf('Occurrences with associatedReferences: %d (%.1f%%)',
            $withReferencesCount,
            $totalCount > 0 ? ($withReferencesCount / $totalCount * 100) : 0
        ));

        // Show sample WITH media
        $io->section('Sample Occurrences WITH Associated Media');
        $withMedia = $this->entityManager->getRepository(Occurrence::class)
            ->createQueryBuilder('o')
            ->where('o.associatedMedia IS NOT NULL')
            ->andWhere("o.associatedMedia != ''")
            ->setMaxResults($sampleSize)
            ->getQuery()
            ->getResult();

        if (empty($withMedia)) {
            $io->warning('No occurrences with associated media found!');
        } else {
            $table = [];
            foreach ($withMedia as $occurrence) {
                $media = $occurrence->getAssociatedMedia();
                $table[] = [
                    $occurrence->getOccurrenceID(),
                    $occurrence->getCatalogNumber() ?? 'N/A',
                    strlen($media) > 60 ? substr($media, 0, 57) . '...' : $media,
                ];
            }
            $io->table(['Occurrence ID', 'Catalog Number', 'Associated Media (truncated)'], $table);
        }

        // Show sample WITHOUT media
        $io->section('Sample Occurrences WITHOUT Associated Media');
        $withoutMedia = $this->entityManager->getRepository(Occurrence::class)
            ->createQueryBuilder('o')
            ->where('o.associatedMedia IS NULL OR o.associatedMedia = :empty')
            ->setParameter('empty', '')
            ->setMaxResults($sampleSize)
            ->getQuery()
            ->getResult();

        if (empty($withoutMedia)) {
            $io->success('All occurrences have associated media!');
        } else {
            $table = [];
            foreach ($withoutMedia as $occurrence) {
                $table[] = [
                    $occurrence->getOccurrenceID(),
                    $occurrence->getCatalogNumber() ?? 'N/A',
                    $occurrence->getAssociatedReferences() ?? 'N/A',
                    $occurrence->getInstitutionCode() ?? 'N/A',
                ];
            }
            $io->table(
                ['Occurrence ID', 'Catalog Number', 'Associated References', 'Institution'],
                $table
            );
        }

        // Provide recommendations
        $io->section('Recommendations');

        if ($withMediaCount === 0 && $totalCount > 0) {
            $io->error('⚠️  CRITICAL: No occurrences have associated media!');
            $io->text([
                'This indicates the media mapping is not working at all.',
                '',
                'Recommended action:',
                '  1. Run: bin/console app:refresh-all-imported-specimen --force',
                '  2. This will re-map all specimens using the corrected mapping code.',
            ]);
        } elseif ($withMediaCount < $totalCount * 0.5) {
            $io->warning('⚠️  Less than 50% of occurrences have associated media.');
            $io->text([
                'Some specimens may be missing media, or not all specimens have media in EasyDB.',
                '',
                'To force re-mapping of all specimens:',
                '  bin/console app:refresh-all-imported-specimen --force',
            ]);
        } else {
            $io->success('✓ Associated media mapping appears to be working correctly.');
        }

        // Check export filtering issue
        if ($withMediaCount > 0) {
            $io->section('Export Column Filtering');
            $io->text([
                'Note: The CSV export filters out columns that have ALL empty values.',
                'If you see associatedMedia in the database but not in exports:',
                '  - Some occurrences have media (shown above)',
                '  - The export should include the associatedMedia column',
                '  - If it doesn\'t appear, there may be an export service bug',
            ]);
        }

        return Command::SUCCESS;
    }
}
