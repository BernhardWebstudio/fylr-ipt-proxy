<?php

namespace App\Service\Mapping;

use App\Entity\DarwinCore\Occurrence;
use App\Entity\OccurrenceImport;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.easydb_dwc_mapping')]
interface EasydbDwCMappingInterface
{
    public function mapOccurrence(array $source, OccurrenceImport $target): void;

    public function supportsPools(): array;
}
