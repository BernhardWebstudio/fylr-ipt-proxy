<?php

namespace App\Command;

use App\Service\EasydbApiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-easydb-api',
    description: 'Test EasyDB API queries with various parameters',
)]
class TestEasydbApiCommand extends Command
{
    public function __construct(
        private readonly EasydbApiService $easydbApiService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('query-type', InputArgument::REQUIRED, 'Type of query to test: global-object-id, uuid-system-object-id, pool, fulltext, tags, object-types, by-tag, search-entities')
            ->addArgument('query-params', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Parameters for the query (depends on query-type)')
            ->addOption('login', 'l', InputOption::VALUE_OPTIONAL, 'EasyDB login username')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'EasyDB login password')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit for paginated queries', 10)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset for paginated queries', 0)
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output results as JSON')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queryType = $input->getArgument('query-type');
        $queryParams = $input->getArgument('query-params');
        $login = $input->getOption('login') ?? $_ENV['EASYDB_LOGIN'] ?? getenv('EASYDB_LOGIN');
        $password = $input->getOption('password') ?? $_ENV['EASYDB_PASSWORD'] ?? getenv('EASYDB_PASSWORD');
        $limit = (int) $input->getOption('limit');
        $offset = (int) $input->getOption('offset');
        $outputJson = $input->getOption('json');

        // Initialize EasyDB session
        if ($login && $password) {
            if (!$this->easydbApiService->initializeFromLoginPassword($login, $password)) {
                $io->error('Failed to initialize EasyDB session with provided credentials.');
                return Command::FAILURE;
            }
            $io->info('✓ Successfully authenticated with EasyDB');
        } elseif (!$this->easydbApiService->hasValidSession()) {
            $io->error('No valid EasyDB session. Please provide --login and --password options.');
            return Command::FAILURE;
        }

        try {
            $result = match ($queryType) {
                'global-object-id' => $this->testLoadEntityByGlobalObjectID($queryParams, $io),
                'uuid-system-object-id' => $this->testLoadEntityByUUIDAndSystemObjectID($queryParams, $io),
                'pool' => $this->testLoadEntitiesForPool($queryParams, $limit, $offset, $io),
                'fulltext' => $this->testSearchFulltext($queryParams, $limit, $offset, $io),
                'tags' => $this->testFetchTags($io),
                'object-types' => $this->testFetchObjectTypes($io),
                'by-tag' => $this->testSearchByTag($queryParams, $limit, $offset, $io),
                'search-entities' => $this->testSearchEntities($queryParams, $limit, $offset, $io),
                default => throw new \InvalidArgumentException(sprintf('Unknown query type: %s', $queryType)),
            };

            if ($outputJson) {
                $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $this->displayResult($result, $queryType, $io);
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error(sprintf('Error executing query: %s', $e->getMessage()));
            if ($input->getOption('verbose')) {
                $io->error($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    private function testLoadEntityByGlobalObjectID(array $params, SymfonyStyle $io): array
    {
        if (empty($params)) {
            throw new \InvalidArgumentException('global-object-id query requires one parameter: global-object-id');
        }

        $globalObjectId = $params[0];
        $io->section(sprintf('Testing: loadEntityByGlobalObjectID("%s")', $globalObjectId));

        $result = $this->easydbApiService->loadEntityByGlobalObjectID($globalObjectId);

        if ($result === null) {
            $io->warning('No entity found with this global object ID.');
            return ['found' => false, 'global_object_id' => $globalObjectId];
        }

        return [
            'found' => true,
            'global_object_id' => $globalObjectId,
            'entity' => $result,
        ];
    }

    private function testLoadEntityByUUIDAndSystemObjectID(array $params, SymfonyStyle $io): array
    {
        if (count($params) < 2) {
            throw new \InvalidArgumentException('uuid-system-object-id query requires two parameters: uuid system-object-id');
        }

        $uuid = $params[0];
        $systemObjectId = (int) $params[1];
        $io->section(sprintf('Testing: loadEntityByUUIDAndSystemObjectID("%s", %d)', $uuid, $systemObjectId));

        $result = $this->easydbApiService->loadEntityByUUIDAndSystemObjectID($uuid, $systemObjectId);

        if ($result === null) {
            $io->warning('No entity found with this UUID and system object ID.');
            return ['found' => false, 'uuid' => $uuid, 'system_object_id' => $systemObjectId];
        }

        return [
            'found' => true,
            'uuid' => $uuid,
            'system_object_id' => $systemObjectId,
            'entity' => $this->extractBasicInfo($result),
        ];
    }

    private function testLoadEntitiesForPool(array $params, int $limit, int $offset, SymfonyStyle $io): array
    {
        if (empty($params)) {
            throw new \InvalidArgumentException('pool query requires one parameter: pool-type');
        }

        $poolType = $params[0];
        $io->section(sprintf('Testing: loadEntitiesForPool("%s", offset=%d, limit=%d)', $poolType, $offset, $limit));

        $result = $this->easydbApiService->loadEntitiesForPool($poolType, $offset, $limit);

        return [
            'pool_type' => $poolType,
            'offset' => $offset,
            'limit' => $limit,
            'count' => count($result),
            'entities' => array_map([$this, 'extractBasicInfo'], $result),
        ];
    }

    private function testSearchFulltext(array $params, int $limit, int $offset, SymfonyStyle $io): array
    {
        if (empty($params)) {
            throw new \InvalidArgumentException('fulltext query requires at least one parameter: search-term [object-type...]');
        }

        $searchTerm = $params[0];
        $objectTypes = count($params) > 1 ? array_slice($params, 1) : [];
        $io->section(sprintf('Testing: searchFulltext("%s", types=[%s], offset=%d, limit=%d)',
            $searchTerm,
            implode(', ', $objectTypes),
            $offset,
            $limit
        ));

        $objectType = count($objectTypes) === 1 ? $objectTypes[0] : (count($objectTypes) > 1 ? $objectTypes : 'fungarium');
        $result = $this->easydbApiService->searchFulltext($searchTerm, $objectType, $offset, $limit);

        return [
            'search_term' => $searchTerm,
            'object_types' => is_array($objectType) ? $objectType : [$objectType],
            'offset' => $offset,
            'limit' => $limit,
            'count' => $result['count'] ?? count($result),
            'entities' => array_map([$this, 'extractBasicInfo'], $result['objects'] ?? $result),
        ];
    }

    private function testFetchTags(SymfonyStyle $io): array
    {
        $io->section('Testing: fetchTags()');

        $result = $this->easydbApiService->fetchTags();

        return [
            'count' => count($result),
            'tags' => array_map(static function ($tag) {
                return [
                    'id' => $tag['id'] ?? $tag['_id'] ?? null,
                    'displayname' => $tag['displayname'] ?? null,
                ];
            }, $result),
        ];
    }

    private function testFetchObjectTypes(SymfonyStyle $io): array
    {
        $io->section('Testing: fetchObjectTypes()');

        $result = $this->easydbApiService->fetchObjectTypes();

        return [
            'count' => count($result),
            'object_types' => array_map(static function ($type) {
                return [
                    'name' => $type['name'] ?? null,
                    'displayname' => $type['displayname'] ?? null,
                ];
            }, $result),
        ];
    }

    private function testSearchByTag(array $params, int $limit, int $offset, SymfonyStyle $io): array
    {
        $tagId = !empty($params) && is_numeric($params[0]) ? (int) $params[0] : null;
        $objectType = count($params) > 1 ? $params[1] : null;

        $io->section(sprintf('Testing: searchByTag(tagId=%s, objectType=%s, offset=%d, limit=%d)',
            $tagId ?? 'null',
            $objectType ?? 'null',
            $offset,
            $limit
        ));

        $result = $this->easydbApiService->searchByTag($tagId, $objectType, $offset, $limit);

        return [
            'tag_id' => $tagId,
            'object_type' => $objectType,
            'offset' => $offset,
            'limit' => $limit,
            'count' => $result['count'] ?? count($result),
            'entities' => array_map([$this, 'extractBasicInfo'], $result['objects'] ?? $result),
        ];
    }

    private function testSearchEntities(array $params, int $limit, int $offset, SymfonyStyle $io): array
    {
        $globalObjectId = null;
        $tagId = null;
        $objectType = null;

        // Parse parameters: [global-object-id] [tag-id] [object-type]
        $paramIndex = 0;
        if (isset($params[$paramIndex]) && !is_numeric($params[$paramIndex])) {
            $globalObjectId = $params[$paramIndex++];
        }
        if (isset($params[$paramIndex]) && is_numeric($params[$paramIndex])) {
            $tagId = (int) $params[$paramIndex++];
        }
        if (isset($params[$paramIndex])) {
            $objectType = $params[$paramIndex];
        }

        $io->section(sprintf('Testing: searchEntities(globalObjectId=%s, tagId=%s, objectType=%s, offset=%d, limit=%d)',
            $globalObjectId ?? 'null',
            $tagId ?? 'null',
            $objectType ?? 'null',
            $offset,
            $limit
        ));

        $result = $this->easydbApiService->searchEntities($globalObjectId, $tagId, $objectType, $offset, $limit);

        return [
            'global_object_id' => $globalObjectId,
            'tag_id' => $tagId,
            'object_type' => $objectType,
            'offset' => $offset,
            'limit' => $limit,
            'count' => $result['count'] ?? count($result),
            'entities' => array_map([$this, 'extractBasicInfo'], $result['objects'] ?? $result),
        ];
    }

    private function extractBasicInfo(array $entity): array
    {
        return [
            'global_object_id' => $entity['_global_object_id'] ?? null,
            'uuid' => $entity['_uuid'] ?? null,
            'system_object_id' => $entity['_system_object_id'] ?? null,
            'objecttype' => $entity['_objecttype'] ?? null,
            'created' => $entity['_created'] ?? null,
            'last_modified' => $entity['_last_modified'] ?? null,
        ];
    }

    private function displayResult(array $result, string $queryType, SymfonyStyle $io): void
    {
        $io->writeln('');
        $io->section(sprintf('Results for query type: %s', $queryType));

        // Show summary
        if (isset($result['count'])) {
            $io->info(sprintf('Found %d entities', $result['count']));
        } elseif (isset($result['found'])) {
            $io->info($result['found'] ? 'Entity found' : 'No entity found');
        }

        // Show entities table if available
        if (!empty($result['entities'])) {
            $entities = $result['entities'];
            if (count($entities) > 0 && is_array($entities[0])) {
                $rows = array_map(static function ($entity) {
                    return [
                        $entity['global_object_id'] ?? '-',
                        $entity['uuid'] ?? '-',
                        $entity['system_object_id'] ?? '-',
                        $entity['objecttype'] ?? '-',
                    ];
                }, $entities);

                $io->table(
                    ['Global Object ID', 'UUID', 'System Object ID', 'Object Type'],
                    $rows
                );
            }
        }

        // Show single entity if available
        if (isset($result['entity']) && is_array($result['entity'])) {
            $io->writeln('');
            $io->writeln('<info>Entity Details:</info>');
            foreach ($result['entity'] as $key => $value) {
                $io->writeln(sprintf('  <fg=blue>%s</>: %s', $key, $value ?? 'null'));
            }
        }

        // Show tags if available
        if (isset($result['tags']) && !empty($result['tags'])) {
            $io->writeln('');
            $rows = array_map(static function ($tag) {
                return [$tag['id'] ?? '-', $tag['displayname'] ?? '-'];
            }, $result['tags']);

            $io->table(['ID', 'Display Name'], $rows);
        }

        // Show object types if available
        if (isset($result['object_types']) && !empty($result['object_types'])) {
            $io->writeln('');
            $rows = array_map(static function ($type) {
                return [$type['name'] ?? '-', $type['displayname'] ?? '-'];
            }, $result['object_types']);

            $io->table(['Name', 'Display Name'], $rows);
        }
    }
}
