<?php

namespace App\Command;

use App\Service\EasydbApiService;
use App\Service\SpecimenImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(
    name: 'app:import-specimen',
    description: 'Import specimens from EasyDB into the local database',
)]
class ImportSpecimenCommand extends Command
{
    public function __construct(
        private readonly SpecimenImportService $specimenImportService,
        private readonly EasydbApiService $easydbApiService,
        #[AutowireIterator("app.easydb_dwc_mapping")] private readonly iterable $mappings,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('query', InputArgument::IS_ARRAY, 'Global object IDs (format: 123@abc-123) or search terms to find specimens')
            ->addOption('login', 'l', InputOption::VALUE_OPTIONAL, 'EasyDB login username')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'EasyDB login password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queries = $input->getArgument('query');
        $login = $input->getOption('login') ?? getenv('EASYDB_LOGIN');
        $password = $input->getOption('password') ?? getenv('EASYDB_PASSWORD');

        if (empty($queries)) {
            $io->warning('No query arguments provided. Nothing to import.');
            return Command::INVALID;
        }

        // Initialize EasyDB session with credentials if provided
        if ($login && $password) {
            if (!$this->easydbApiService->initializeFromLoginPassword($login, $password)) {
                $io->error('Failed to initialize EasyDB session with provided credentials.');
                return Command::FAILURE;
            }
            $io->info('Successfully authenticated with EasyDB.');
        } elseif (!$this->easydbApiService->hasValidSession()) {
            $io->error('No valid EasyDB session. Please provide --login and --password options.');
            return Command::FAILURE;
        }

        // Get supported object types from mappings
        $supportedObjectTypes = $this->getSupportedObjectTypes();

        $globalObjectIds = [];

        // Process each query to resolve global object IDs
        foreach ($queries as $query) {
            $resolved = $this->resolveQuery($query, $supportedObjectTypes, $io, $input, $output);
            if ($resolved === null) {
                return Command::FAILURE;
            }
            $globalObjectIds = array_merge($globalObjectIds, $resolved);
        }

        if (empty($globalObjectIds)) {
            $io->warning('No specimens found to import.');
            return Command::SUCCESS;
        }

        // Perform imports
        $successCount = 0;
        foreach ($globalObjectIds as $goi) {
            $io->section(sprintf('Importing: %s', $goi));
            try {
                $this->specimenImportService->importByGlobalObjectId($goi);
                $successCount++;
                $io->success(sprintf('Successfully imported %s', $goi));
            } catch (\Throwable $e) {
                $io->error(sprintf('Exception while importing %s: %s', $goi, $e->getMessage()));
                $io->writeln(sprintf('Successful imports before failure: %d', $successCount));
                return Command::FAILURE;
            }
        }

        $io->success(sprintf('All imports completed. Total successes: %d', $successCount));
        return Command::SUCCESS;
    }

    /**
     * Resolve a query string to one or more global object IDs
     *
     * @return array|null Array of global object IDs, or null on error
     */
    private function resolveQuery(string $query, array $supportedObjectTypes, SymfonyStyle $io, InputInterface $input, OutputInterface $output): ?array
    {
        // Check if query matches global object ID format (digits@alphanumeric-with-hyphens)
        if (preg_match('/^\d+@[a-zA-Z0-9\-]+$/', $query)) {
            $io->info(sprintf('Recognized as global object ID: %s', $query));
            return [$query];
        }

        // It's a search term - search for matching entities
        $io->info(sprintf('Searching for: "%s"', $query));

        try {
            $results = $this->easydbApiService->searchFulltext($query, $supportedObjectTypes, 0, 50);

            if (empty($results)) {
                $io->warning(sprintf('No results found for search term: "%s"', $query));
                return [];
            }

            $io->info(sprintf('Found %d result(s)', count($results)));

            // If only one result, use it automatically
            if (count($results) === 1) {
                $entity = $results[0];
                $goi = $entity['_global_object_id'] ?? null;
                if (!$goi) {
                    $io->error('Result missing global object ID');
                    return null;
                }

                $displayName = $this->getEntityDisplayName($entity);
                $io->info(sprintf('Auto-selecting: %s', $displayName));
                return [$goi];
            }

            // Multiple results - show selection menu
            $choices = [];
            $goiMap = [];
            foreach ($results as $index => $entity) {
                $goi = $entity['_global_object_id'] ?? null;
                if (!$goi) {
                    continue;
                }

                $displayName = $this->getEntityDisplayName($entity);
                $key = sprintf('[%d] %s', $index + 1, $displayName);
                $choices[$key] = $index;
                $goiMap[$index] = $goi;
            }

            $choices['[0] Import all'] = 'all';
            $choices['[Skip] Skip this search term'] = 'skip';

            $question = new ChoiceQuestion(
                sprintf('Multiple results for "%s". Select which to import:', $query),
                array_keys($choices),
                0 // default to "Import all"
            );
            $question->setMultiselect(true);

            $selectedKeys = $io->askQuestion($question);

            $selectedGois = [];
            foreach ($selectedKeys as $key) {
                $value = $choices[$key];
                if ($value === 'skip') {
                    continue;
                } elseif ($value === 'all') {
                    return array_values($goiMap);
                } else {
                    $selectedGois[] = $goiMap[$value];
                }
            }

            return $selectedGois;
        } catch (\Exception $e) {
            $io->error(sprintf('Error searching for "%s": %s', $query, $e->getMessage()));
            return null;
        }
    }

    /**
     * Get a display name for an entity
     */
    private function getEntityDisplayName(array $entity): string
    {
        $goi = $entity['_global_object_id'] ?? 'unknown';
        $type = $entity['_objecttype'] ?? 'unknown';

        // Try to extract a meaningful name from the entity
        $name = null;

        // Check common name fields
        if (isset($entity[$type])) {
            $data = $entity[$type];
            $name = $data['name'] ?? $data['title'] ?? $data['catalogNumber'] ?? null;
        }

        if ($name) {
            return sprintf('%s (%s, %s)', $name, $type, $goi);
        }

        return sprintf('%s (%s)', $type, $goi);
    }

    /**
     * Get all supported object types from mappings
     */
    private function getSupportedObjectTypes(): array
    {
        $objectTypes = [];
        foreach ($this->mappings as $mapping) {
            $objectTypes = array_merge($objectTypes, $mapping->supportsPools());
        }
        return array_unique($objectTypes);
    }
}
