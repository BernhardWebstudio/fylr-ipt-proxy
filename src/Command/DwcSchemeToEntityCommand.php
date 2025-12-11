<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:dwc-scheme-to-entity',
    description: 'Convert Darwin Core (DwC) scheme to PHP doctrine entity classes',
)]
class DwcSchemeToEntityCommand extends Command
{
    private const SCHEME_DIR = __DIR__ . '/../../resources/schemes/';
    private const ENTITY_DIR = __DIR__ . '/../Entity/DarwinCore/';

    private SymfonyStyle $io;
    private array $xmlSchemas = [];
    private array $typeMapping = [
        'xs:string' => 'string',
        'xs:positiveInteger' => 'int',
        'xs:integer' => 'int',
        'xs:double' => 'float',
        'xs:decimal' => 'float',
        'xs:boolean' => 'bool',
        'xs:dateTime' => '\\DateTime',
        'xs:date' => '\\DateTime',
        'xs:time' => '\\DateTime',
        'dwc:nonEmptyString' => 'string',
        'dwc:positiveDouble' => 'float',
        'dwc:dayOfYearDataType' => 'int',
        'dwc:decimalLatitudeDataType' => 'float',
        'dwc:decimalLongitudeDataType' => 'float',
    ];

    private array $reservedWords = [
        'order', 'class', 'table', 'column', 'select', 'from', 'where', 'group', 'having', 'limit', 'offset'
    ];

    // This will be populated dynamically from XML parsing
    private array $entityRelationships = [];

    // Mapping of which identifiers belong to which domains (will be populated from XML)
    private array $domainIdentifiers = [];

    // Mapping of identifier names to their target entity names
    private array $identifierToEntity = [
        'occurrenceID' => 'Occurrence',
        'organismID' => 'Organism',
        'materialEntityID' => 'MaterialEntity',
        'materialSampleID' => 'MaterialSample',
        'eventID' => 'Event',
        'locationID' => 'Location',
        'geologicalContextID' => 'GeologicalContext',
        'identificationID' => 'Identification',
        'taxonID' => 'Taxon',
        'measurementID' => 'MeasurementOrFact',
        'resourceRelationshipID' => 'ResourceRelationship'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing entity files')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be generated without creating files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('Converting Darwin Core Scheme to Doctrine Entities');

        // Check if scheme files exist
        if (!is_dir(self::SCHEME_DIR)) {
            $this->io->error('Scheme directory not found. Please run app:download-dwc-scheme first.');
            return Command::FAILURE;
        }

        // Load XML schemas
        $this->loadXmlSchemas();

        // Create entity directory if it doesn't exist
        if (!$input->getOption('dry-run')) {
            if (!is_dir(self::ENTITY_DIR)) {
                mkdir(self::ENTITY_DIR, 0755, true);
                $this->io->success('Created entity directory: ' . self::ENTITY_DIR);
            }
        }

        // Parse and generate entities
        $entities = $this->parseSchemas();
        $this->generateEntities($entities, $input->getOption('force'), $input->getOption('dry-run'));

        $this->io->success('Darwin Core entity generation completed!');

        return Command::SUCCESS;
    }

    private function loadXmlSchemas(): void
    {
        $schemaFiles = [
            'tdwg_dwc_classes.xsd',
            'tdwg_dwc_class_terms.xsd',
            'tdwg_dwcterms.xsd',
            'tdwg_basetypes.xsd'
        ];

        foreach ($schemaFiles as $file) {
            $filePath = self::SCHEME_DIR . $file;
            if (file_exists($filePath)) {
                $xml = simplexml_load_file($filePath);
                if ($xml !== false) {
                    $xml->registerXPathNamespace('xs', 'http://www.w3.org/2001/XMLSchema');
                    $xml->registerXPathNamespace('dwc', 'http://rs.tdwg.org/dwc/terms/');
                    $this->xmlSchemas[$file] = $xml;
                    $this->io->note("Loaded schema: $file");
                } else {
                    $this->io->warning("Failed to load schema: $file");
                }
            }
        }

        // Parse domain identifiers from class definitions
        $this->parseDomainIdentifiers();
    }

    /**
     * Parse which identifiers belong to each domain by analyzing term groups
     * and applying Darwin Core semantic relationships
     */
    private function parseDomainIdentifiers(): void
    {
        // Based on Darwin Core specification, each identifier belongs to a specific domain
        $this->domainIdentifiers = [
            'Occurrence' => ['occurrenceID'],
            'Organism' => ['organismID'],
            'MaterialEntity' => ['materialEntityID'],
            'MaterialSample' => ['materialSampleID'],
            'Event' => ['eventID'],
            'Location' => ['locationID'],
            'GeologicalContext' => ['geologicalContextID'],
            'Identification' => ['identificationID'],
            'Taxon' => ['taxonID'],
            'MeasurementOrFact' => ['measurementID'],
            'ResourceRelationship' => ['resourceRelationshipID'],
            'ChronometricAge' => [],  // ChronometricAge doesn't have its own identifier in the standard
        ];

        // Parse which OTHER identifiers appear in domain-specific term groups
        // This builds cross-references explicitly defined in Darwin Core
        if (isset($this->xmlSchemas['tdwg_dwcterms.xsd'])) {
            $xml = $this->xmlSchemas['tdwg_dwcterms.xsd'];

            // Map domain term groups to entities
            $domainTermGroups = [
                'OccurrenceTerms' => 'Occurrence',
                'OrganismTerms' => 'Organism',
                'MaterialEntityTerms' => 'MaterialEntity',
                'MaterialSampleTerms' => 'MaterialSample',
                'EventTerms' => 'Event',
                'LocationTerms' => 'Location',
                'GeologicalContextTerms' => 'GeologicalContext',
                'IdentificationTerms' => 'Identification',
                'TaxonTerms' => 'Taxon',
                'MeasurementOrFactTerms' => 'MeasurementOrFact',
                'ResourceRelationshipTerms' => 'ResourceRelationship',
            ];

            // For each domain term group, check if it explicitly references any identifiers
            foreach ($domainTermGroups as $groupName => $entityName) {
                $group = $xml->xpath("//xs:group[@name='$groupName']");
                if (!empty($group)) {
                    $elements = $group[0]->xpath('.//xs:element[@ref]');
                    foreach ($elements as $element) {
                        $ref = (string) $element['ref'];
                        if (strpos($ref, 'dwc:') === 0) {
                            $fieldName = substr($ref, 4);
                            // Check if this field is an identifier reference to a different domain
                            foreach ($this->identifierToEntity as $idName => $idEntity) {
                                if ($fieldName === $idName && $idEntity !== $entityName) {
                                    // This domain explicitly references an identifier from another domain
                                    if (!in_array($idName, $this->domainIdentifiers[$entityName] ?? [])) {
                                        $this->domainIdentifiers[$entityName][] = $idName;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Apply implicit Darwin Core semantic relationships
        // These are relationships that make semantic sense even if not explicitly in term groups
        // They are based on the Darwin Core documentation and structure
        $implicitRelationships = [
            'Occurrence' => ['taxonID', 'eventID', 'organismID', 'materialEntityID', 'locationID', 'identificationID'],
            'Event' => ['locationID'],
            'MaterialEntity' => ['materialSampleID'],
            'Location' => ['geologicalContextID'],
            'MeasurementOrFact' => ['occurrenceID', 'eventID', 'taxonID', 'locationID'],
            'ResourceRelationship' => ['occurrenceID'],
        ];

        foreach ($implicitRelationships as $entityName => $identifiers) {
            if (isset($this->domainIdentifiers[$entityName])) {
                foreach ($identifiers as $idName) {
                    $targetEntity = $this->identifierToEntity[$idName] ?? null;
                    // Only add if targeting a different entity and not already in the list
                    if ($targetEntity && $targetEntity !== $entityName &&
                        !in_array($idName, $this->domainIdentifiers[$entityName])) {
                        $this->domainIdentifiers[$entityName][] = $idName;
                    }
                }
            }
        }
    }

    private function parseSchemas(): array
    {
        $entities = [];

        // Parse main classes from tdwg_dwc_class_terms.xsd
        if (isset($this->xmlSchemas['tdwg_dwc_class_terms.xsd'])) {
            $classes = $this->parseMainClasses();
            $entities = array_merge($entities, $classes);
        }

        // Parse terms from tdwg_dwcterms.xsd
        if (isset($this->xmlSchemas['tdwg_dwcterms.xsd'])) {
            $this->parseTerms($entities);
        }

        return $entities;
    }

    private function parseMainClasses(): array
    {
        $xml = $this->xmlSchemas['tdwg_dwc_class_terms.xsd'];
        $entities = [];

        // Find all main class elements
        $classElements = $xml->xpath('//xs:element[@substitutionGroup="dwc:anyClass"]');

        foreach ($classElements as $element) {
            $className = (string) $element['name'];
            $this->io->note("Found Darwin Core class: $className");

            $entities[$className] = [
                'name' => $className,
                'properties' => [],
                'identifiers' => [],
                'recordLevelTerms' => [],
                'relationships' => [],
                'description' => $this->extractDocumentation($element)
            ];
        }

        return $entities;
    }

    private function parseTerms(array &$entities): void
    {
        $xml = $this->xmlSchemas['tdwg_dwcterms.xsd'];

        // Parse different domain terms
        $domains = [
            'anyOccurrenceTerm' => 'Occurrence',
            'anyOrganismTerm' => 'Organism',
            'anyMaterialEntityTerm' => 'MaterialEntity',
            'anyMaterialSampleTerm' => 'MaterialSample',
            'anyEventTerm' => 'Event',
            'anyLocationTerm' => 'Location',
            'anyGeologicalContextTerm' => 'GeologicalContext',
            'anyIdentificationTerm' => 'Identification',
            'anyTaxonTerm' => 'Taxon',
            'anyMeasurementOrFactTerm' => 'MeasurementOrFact',
            'anyResourceRelationshipTerm' => 'ResourceRelationship'
        ];

        // Parse identifiers
        $identifiers = $xml->xpath('//xs:element[@substitutionGroup="dwc:anyIdentifier"]');
        foreach ($identifiers as $element) {
            $name = (string) $element['name'];
            $type = $this->mapXmlTypeToPhp((string) $element['type']);

            // Determine which entity this identifier belongs to
            $entityName = $this->identifierToEntity[$name] ?? null;
            if ($entityName && isset($entities[$entityName])) {
                $entities[$entityName]['identifiers'][] = [
                    'name' => $name,
                    'type' => $type,
                    'description' => $this->extractDocumentation($element),
                    'nullable' => false
                ];
            }
        }

        // Parse record level terms
        $recordLevelTermNames = [];
        $recordLevelTerms = $xml->xpath('//xs:element[@substitutionGroup="dwc:anyRecordLevelTerm"]');
        foreach ($recordLevelTerms as $element) {
            $name = (string) $element['name'];
            $type = $this->mapXmlTypeToPhp((string) $element['type']);

            $term = [
                'name' => $name,
                'type' => $type,
                'description' => $this->extractDocumentation($element),
                'nullable' => true
            ];

            // Add to all entities as these are common
            foreach ($entities as &$entity) {
                $entity['recordLevelTerms'][] = $term;
            }

            $recordLevelTermNames[] = $name;
        }

        // Parse record level terms included via the RecordLevelTerms group (e.g. dc/dcterms)
        $recordLevelGroupElements = $xml->xpath('//xs:group[@name="RecordLevelTerms"]//xs:element');
        foreach ($recordLevelGroupElements as $element) {
            $rawName = (string) ($element['name'] ?? '');
            $rawRef = (string) ($element['ref'] ?? '');
            $name = $rawName !== '' ? $rawName : ($rawRef !== '' ? $this->stripNamespacePrefix($rawRef) : '');
            if ($name === '') {
                continue;
            }

            // Skip if already added from substitutionGroup parsing
            if (in_array($name, $recordLevelTermNames, true)) {
                continue;
            }

            $typeAttr = (string) ($element['type'] ?? '');
            $type = $typeAttr !== '' ? $this->mapXmlTypeToPhp($typeAttr) : 'string';

            $term = [
                'name' => $name,
                'type' => $type,
                'description' => $this->extractDocumentation($element),
                'nullable' => true
            ];

            foreach ($entities as &$entity) {
                $entity['recordLevelTerms'][] = $term;
            }

            $recordLevelTermNames[] = $name;
        }

        // Parse domain-specific terms
        foreach ($domains as $substitutionGroup => $entityName) {
            if (!isset($entities[$entityName])) {
                continue;
            }

            $terms = $xml->xpath("//xs:element[@substitutionGroup='dwc:$substitutionGroup']");
            foreach ($terms as $element) {
                $name = (string) $element['name'];
                $type = $this->mapXmlTypeToPhp((string) $element['type']);

                $entities[$entityName]['properties'][] = [
                    'name' => $name,
                    'type' => $type,
                    'description' => $this->extractDocumentation($element),
                    'nullable' => true
                ];
            }
        }

        // Add dynamically parsed relationships for identifiers
        foreach ($entities as $entityName => &$entity) {
            // Add ManyToOne relationships for identifiers that reference other entities
            if (isset($this->domainIdentifiers[$entityName])) {
                foreach ($this->domainIdentifiers[$entityName] as $identifierName) {
                    // Skip self-references
                    if ($this->identifierToEntity[$identifierName] === $entityName) {
                        continue;
                    }

                    $targetEntity = $this->identifierToEntity[$identifierName];
                    if ($targetEntity && $targetEntity !== $entityName && isset($entities[$targetEntity])) {
                        $entity['relationships'][] = [
                            'name' => $identifierName,
                            'type' => 'string',
                            'target' => $targetEntity,
                            'relationshipType' => 'ManyToOne',
                            'targetProperty' => $identifierName,
                            'mappedBy' => $this->getPluralForm($entityName),
                            'cascade' => ['persist'],
                            'description' => 'Reference to ' . $targetEntity
                        ];
                    }
                }
            }
        }

        // Add OneToMany reverse relationships based on ManyToOne relationships
        $reverseRelationships = $this->buildReverseRelationships($entities);
        foreach ($reverseRelationships as $entityName => $relationships) {
            if (isset($entities[$entityName])) {
                foreach ($relationships as $relationship) {
                    $entities[$entityName]['relationships'][] = $relationship;
                }
            }
        }
    }

    /**
     * Build reverse OneToMany relationships based on ManyToOne relationships
     */
    private function buildReverseRelationships(array &$entities): array
    {
        $reverseMap = [];

        // Collect all ManyToOne relationships
        foreach ($entities as $entityName => &$entity) {
            foreach ($entity['relationships'] as $relationship) {
                if ($relationship['relationshipType'] === 'ManyToOne') {
                    $targetEntity = $relationship['target'];
                    $propertyName = $relationship['mappedBy'] ?? null;

                    if (!$propertyName) {
                        continue;
                    }

                    if (!isset($reverseMap[$targetEntity])) {
                        $reverseMap[$targetEntity] = [];
                    }

                    $reverseMap[$targetEntity][] = [
                        'sourceEntity' => $entityName,
                        'pluralProperty' => $propertyName
                    ];
                }
            }
        }

        // Generate OneToMany relationships
        $result = [];
        foreach ($reverseMap as $targetEntity => $sources) {
            if (!isset($result[$targetEntity])) {
                $result[$targetEntity] = [];
            }

            foreach ($sources as $source) {
                $propertyName = $source['pluralProperty'];
                $sourceEntity = $source['sourceEntity'];

                // Create OneToMany relationship
                $result[$targetEntity][] = [
                    'name' => $propertyName,
                    'type' => 'Collection',
                    'target' => $sourceEntity,
                    'relationshipType' => 'OneToMany',
                    'targetProperty' => $propertyName,
                    'mappedBy' => $this->getSingularForm($propertyName),
                    'description' => 'Reverse relationship from ' . $sourceEntity
                ];
            }
        }

        return $result;
    }

    /**
     * Get plural form of entity name for collection properties
     */
    private function getPluralForm(string $entityName): string
    {
        $plurals = [
            'Occurrence' => 'occurrences',
            'Organism' => 'organisms',
            'MaterialEntity' => 'materialEntities',
            'MaterialSample' => 'materialSamples',
            'Event' => 'events',
            'Location' => 'locations',
            'GeologicalContext' => 'geologicalContexts',
            'Identification' => 'identifications',
            'Taxon' => 'taxa',
            'MeasurementOrFact' => 'measurementOrFacts',
            'ResourceRelationship' => 'resourceRelationships'
        ];

        return $plurals[$entityName] ?? strtolower($entityName) . 's';
    }

    /**
     * Get singular form from a plural property name
     */
    private function getSingularForm(string $propertyName): string
    {
        // Simple singularization - in practice might need more rules
        $singulars = [
            'occurrences' => 'occurrence',
            'organisms' => 'organism',
            'materialEntities' => 'materialEntity',
            'materialSamples' => 'materialSample',
            'events' => 'event',
            'locations' => 'location',
            'geologicalContexts' => 'geologicalContext',
            'identifications' => 'identification',
            'taxa' => 'taxon',
            'measurementOrFacts' => 'measurementOrFact',
            'resourceRelationships' => 'resourceRelationship'
        ];

        return $singulars[$propertyName] ?? rtrim($propertyName, 's');
    }

    private function mapXmlTypeToPhp(string $xmlType): string
    {
        return $this->typeMapping[$xmlType] ?? 'string';
    }

    private function extractDocumentation(\SimpleXMLElement $element): string
    {
        $annotation = $element->xpath('.//xs:annotation/xs:documentation');
        if (!empty($annotation)) {
            return trim((string) $annotation[0]);
        }
        return '';
    }

    private function generateEntities(array $entities, bool $force, bool $dryRun): void
    {
        foreach ($entities as $entityData) {
            $fileName = self::ENTITY_DIR . $entityData['name'] . '.php';

            if (!$force && !$dryRun && file_exists($fileName)) {
                $this->io->warning("Entity {$entityData['name']} already exists. Use --force to overwrite.");
                continue;
            }

            $entityCode = $this->generateEntityCode($entityData);

            if ($dryRun) {
                $this->io->section("Would generate: {$entityData['name']}.php");
                $this->io->text($entityCode);
            } else {
                file_put_contents($fileName, $entityCode);
                $this->io->success("Generated entity: {$entityData['name']}.php");
            }
        }
    }

    private function generateEntityCode(array $entityData): string
    {
        $className = $entityData['name'];
        $tableName = $this->camelCaseToSnakeCase($className);

        $code = "<?php\n\nnamespace App\\Entity\\DarwinCore;\n\n";
        $code .= "use Doctrine\\ORM\\Mapping as ORM;\n";
        $code .= "use Doctrine\\Common\\Collections\\ArrayCollection;\n";
        $code .= "use Doctrine\\Common\\Collections\\Collection;\n";
        $code .= "use Symfony\\Component\\Validator\\Constraints as Assert;\n\n";

        if (!empty($entityData['description'])) {
            $code .= "/**\n";
            $code .= " * {$entityData['description']}\n";
            $code .= " */\n";
        }

        $code .= "#[ORM\\Entity]\n";
        $code .= "#[ORM\\Table(name: 'dwc_{$tableName}')]\n";
        $code .= "class $className\n{\n";

        // Add ID field
        $code .= "    #[ORM\\Id]\n";
        $code .= "    #[ORM\\GeneratedValue]\n";
        $code .= "    #[ORM\\Column(type: 'integer')]\n";
        $code .= "    private ?int \$id = null;\n\n";

        // Add identifier fields
        foreach ($entityData['identifiers'] as $identifier) {
            $code .= $this->generatePropertyCode($identifier, true);
        }

        // Add relationships
        foreach ($entityData['relationships'] as $relationship) {
            $code .= $this->generateRelationshipCode($relationship);
        }

        // Add domain-specific properties
        foreach ($entityData['properties'] as $property) {
            $code .= $this->generatePropertyCode($property);
        }

        // Add record level terms
        foreach ($entityData['recordLevelTerms'] as $term) {
            $code .= $this->generatePropertyCode($term);
        }

        // Add constructor if there are OneToMany relationships
        $hasCollections = false;
        foreach ($entityData['relationships'] as $relationship) {
            if ($relationship['relationshipType'] === 'OneToMany') {
                $hasCollections = true;
                break;
            }
        }

        if ($hasCollections) {
            $code .= "    public function __construct()\n";
            $code .= "    {\n";
            foreach ($entityData['relationships'] as $relationship) {
                if ($relationship['relationshipType'] === 'OneToMany') {
                    $propertyName = $this->snakeCaseToCamelCase($relationship['name']);
                    $code .= "        \$this->$propertyName = new ArrayCollection();\n";
                }
            }
            $code .= "    }\n\n";
        }

        // Add getter and setter methods
        $code .= "    public function getId(): ?int\n";
        $code .= "    {\n";
        $code .= "        return \$this->id;\n";
        $code .= "    }\n\n";

        // Generate getters and setters for all properties
        $allProperties = array_merge(
            $entityData['identifiers'],
            $entityData['properties'],
            $entityData['recordLevelTerms']
        );

        foreach ($allProperties as $prop) {
            $code .= $this->generateGetterSetter($prop);
        }

        // Generate getters and setters for relationships
        foreach ($entityData['relationships'] as $relationship) {
            $code .= $this->generateRelationshipGetterSetter($relationship);
        }

        $code .= "}\n";

        return $code;
    }

    private function generatePropertyCode(array $property, bool $isIdentifier = false): string
    {
        $name = $property['name'];
        $type = $property['type'];
        $description = $property['description'];
        $camelCase = $this->snakeCaseToCamelCase($name);

        $code = "";

        if (!empty($description)) {
            $code .= "    /**\n";
            $code .= "     * $description\n";
            $code .= "     */\n";
        }

        // Add validation constraints
        if ($isIdentifier) {
            $code .= "    #[Assert\\NotBlank]\n";
        }

        // Add Doctrine mapping
        $doctrineType = $this->getDoctrineType($type, $name);
        $columnName = in_array($name, $this->reservedWords) ? '"' . $name . '"' : $name;
        $code .= "    #[ORM\\Column(name: '$columnName', type: '$doctrineType'";

        if (!$isIdentifier) {
            $code .= ", nullable: true";
        } else {
            // Add unique constraint for identifier fields as they will be referenced by foreign keys
            $code .= ", unique: true";
        }

        $code .= ")]\n";

        $phpType = $this->getPhpType($type, !$isIdentifier);
        $code .= "    private $phpType \$$camelCase";

        if (!$isIdentifier) {
            $code .= " = null";
        }

        $code .= ";\n\n";

        return $code;
    }

    private function generateGetterSetter(array $property): string
    {
        $name = $property['name'];
        $type = $property['type'];
        $nullable = $property['nullable'] ?? true;
        $camelCase = $this->snakeCaseToCamelCase($name);
        $pascalCase = ucfirst($camelCase);
        $phpType = $this->getPhpType($type, $nullable);

        $code = "    public function get$pascalCase(): $phpType\n";
        $code .= "    {\n";
        $code .= "        return \$this->$camelCase;\n";
        $code .= "    }\n\n";

        $code .= "    public function set$pascalCase($phpType \$$camelCase): static\n";
        $code .= "    {\n";
        $code .= "        \$this->$camelCase = \$$camelCase;\n";
        $code .= "        return \$this;\n";
        $code .= "    }\n\n";

        return $code;
    }

    private function generateRelationshipCode(array $relationship): string
    {
        $name = $relationship['name'];
        $target = $relationship['target'];
        $relationshipType = $relationship['relationshipType'];
        $targetProperty = $relationship['targetProperty'] ?? $name;
        $description = $relationship['description'] ?? '';

        // For foreign key relationships, generate unique property names
        if ($relationshipType === 'ManyToOne' && str_ends_with($name, 'ID')) {
            // Remove 'ID' suffix and convert to camelCase for property name
            $baseName = substr($name, 0, -2);
            $propertyName = $this->snakeCaseToCamelCase($baseName);
        } else {
            $propertyName = $this->snakeCaseToCamelCase($name);
        }

        $code = "";

        if (!empty($description)) {
            $code .= "    /**\n";
            $code .= "     * $description\n";
            $code .= "     */\n";
        }

        if ($relationshipType === 'ManyToOne') {
            // Get the inversedBy property name for bidirectional relationships
            $inversedBy = $relationship['mappedBy'] ?? null;
            $inversedByAttr = $inversedBy ? ", inversedBy: '{$inversedBy}'" : '';

            // Add cascade if specified
            $cascade = $relationship['cascade'] ?? null;
            $cascadeAttr = $cascade ? ", cascade: ['persist']" : '';

            $code .= "    #[ORM\\ManyToOne(targetEntity: {$target}::class{$inversedByAttr}{$cascadeAttr})]\n";
            // Foreign keys should reference the primary key 'id', not the identifier columns
            $code .= "    #[ORM\\JoinColumn(name: '{$name}', referencedColumnName: 'id', nullable: true)]\n";
            $code .= "    private ?{$target} \${$propertyName} = null;\n\n";
        } elseif ($relationshipType === 'OneToMany') {
            $mappedBy = $relationship['mappedBy'] ?? $propertyName;
            $code .= "    #[ORM\\OneToMany(mappedBy: '{$mappedBy}', targetEntity: {$target}::class, cascade: ['persist'])]\n";
            $code .= "    private Collection \${$propertyName};\n\n";
        }

        return $code;
    }

    private function generateRelationshipGetterSetter(array $relationship): string
    {
        $name = $relationship['name'];
        $target = $relationship['target'];
        $relationshipType = $relationship['relationshipType'];

        // Use consistent property naming
        if ($relationshipType === 'ManyToOne' && str_ends_with($name, 'ID')) {
            // Remove 'ID' suffix and convert to camelCase for property name
            $baseName = substr($name, 0, -2);
            $propertyName = $this->snakeCaseToCamelCase($baseName);
        } else {
            $propertyName = $this->snakeCaseToCamelCase($name);
        }
        $pascalCase = ucfirst($propertyName);

        $code = "";

        if ($relationshipType === 'ManyToOne') {
            $code .= "    public function get$pascalCase(): ?$target\n";
            $code .= "    {\n";
            $code .= "        return \$this->$propertyName;\n";
            $code .= "    }\n\n";

            $code .= "    public function set$pascalCase(?$target \$$propertyName): static\n";
            $code .= "    {\n";
            $code .= "        \$this->$propertyName = \$$propertyName;\n";
            $code .= "        return \$this;\n";
            $code .= "    }\n\n";
        } elseif ($relationshipType === 'OneToMany') {
            $code .= "    /**\n";
            $code .= "     * @return Collection<int, $target>\n";
            $code .= "     */\n";
            $code .= "    public function get$pascalCase(): Collection\n";
            $code .= "    {\n";
            $code .= "        return \$this->$propertyName;\n";
            $code .= "    }\n\n";

            $singularTarget = rtrim($target, 's');
            $singularProperty = rtrim($propertyName, 's');
            $code .= "    public function add$singularTarget($target \$$singularProperty): static\n";
            $code .= "    {\n";
            $code .= "        if (!\$this->{$propertyName}->contains(\$$singularProperty)) {\n";
            $code .= "            \$this->{$propertyName}->add(\$$singularProperty);\n";
            $code .= "        }\n";
            $code .= "        return \$this;\n";
            $code .= "    }\n\n";

            $code .= "    public function remove$singularTarget($target \$$singularProperty): static\n";
            $code .= "    {\n";
            $code .= "        \$this->{$propertyName}->removeElement(\$$singularProperty);\n";
            $code .= "        return \$this;\n";
            $code .= "    }\n\n";
        }

        return $code;
    }

    /**
     * Returns Doctrine type for a property, using 'text' for known long fields.
     */
    private function getDoctrineType(string $phpType, string $propertyName = ''): string
    {
        // List of Darwin Core fields that should be mapped to Doctrine 'text' (unlimited length)
        $longTextFields = [
            'accessRights',
            'associatedMedia',
            'associatedOccurrences',
            'associatedReferences',
            'associatedTaxa',
            'bibliographicCitation',
            'dynamicProperties',
            'eventRemarks',
            'fieldNotes',
            'georeferenceRemarks',
            'identificationRemarks',
            'license',
            'locationRemarks',
            'measurementRemarks',
            'occurrenceRemarks',
            'otherCatalogNumbers',
            'references',
            'resourceRelationshipRemarks',
            'rightsHolder',
            'taxonRemarks',
            'verbatimCoordinates',
            'verbatimCoordinateSystem',
            'verbatimDepth',
            'verbatimElevation',
            'verbatimEventDate',
            'verbatimLatitude',
            'verbatimLocality',
            'verbatimLongitude',
            'verbatimSRS',
        ];
        if ($phpType === 'string' && in_array($propertyName, $longTextFields, true)) {
            return 'text';
        }
        return match ($phpType) {
            'int' => 'integer',
            'float' => 'float',
            'bool' => 'boolean',
            '\\DateTime' => 'datetime',
            default => 'string'
        };
    }

    private function getPhpType(string $type, bool $nullable): string
    {
        $phpType = $type;
        if ($nullable && $type !== 'string') {
            $phpType = "?$phpType";
        } elseif ($nullable && $type === 'string') {
            $phpType = "?string";
        }
        return $phpType;
    }

    private function camelCaseToSnakeCase(string $string): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }

    private function snakeCaseToCamelCase(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    private function stripNamespacePrefix(string $qualifiedName): string
    {
        return str_contains($qualifiedName, ':') ? substr($qualifiedName, strpos($qualifiedName, ':') + 1) : $qualifiedName;
    }
}
