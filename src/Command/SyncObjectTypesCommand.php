<?php

namespace App\Command;

use App\Entity\EasydbObjectType;
use App\Repository\EasydbObjectTypeRepository;
use App\Service\EasydbApiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-object-types',
    description: 'Loads the object types from EasyDB and updates the local database accordingly.',
)]
class SyncObjectTypesCommand extends Command
{
    public function __construct(
        private EasydbObjectTypeRepository $entityPoolRepository,
        private EasydbApiService $easydbApiService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate the command without making any changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('dry-run')) {
            // Simulate the command without making any changes
            $io->note('Dry run mode is enabled. No changes will be made.');
        }

        try {
            $objectTypes = $this->easydbApiService->fetchObjectTypes();
        } catch (\Exception $e) {
            $io->error('Failed to fetch object types from EasyDB: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $existingPools = $this->entityPoolRepository->findAll();
        $existingPoolNames = array_map(fn($pool) => $pool->getName(), $existingPools);
        $fetchedPoolNames = array_map(fn($type) => $type['name'], $objectTypes);
        $toAdd = array_diff($fetchedPoolNames, $existingPoolNames);
        $toRemove = array_diff($existingPoolNames, $fetchedPoolNames);

        if (!$input->getOption('dry-run')) {
            // Perform the actual sync operations
            foreach ($toAdd as $name) {
                $newEntity = new EasydbObjectType();
                $newEntity->setName($name);
                $this->entityPoolRepository->add($newEntity);
            }

            foreach ($toRemove as $name) {
                $existingEntity = $this->entityPoolRepository->findOneBy(['name' => $name]);
                if ($existingEntity) {
                    $this->entityPoolRepository->remove($existingEntity);
                } else {
                    $io->warning("EntityPool with name '$name' not found for removal.");
                }
            }
        } else {
            $io->note('Dry run mode is enabled. No changes will be made.');
        }

        $io->success('Fetched object types: ' . implode(', ', $fetchedPoolNames));
        $io->success('Existing object types: ' . implode(', ', $existingPoolNames));
        $io->success('Object types to add: ' . implode(', ', $toAdd));
        $io->success('Object types to remove: ' . implode(', ', $toRemove));
        return Command::SUCCESS;
    }
}
