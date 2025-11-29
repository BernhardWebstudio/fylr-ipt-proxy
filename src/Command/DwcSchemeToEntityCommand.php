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

    private array $entityRelationships = [
        'Occurrence' => [
            'taxonID' => ['target' => 'Taxon', 'type' => 'ManyToOne', 'mappedBy' => 'occurrences', 'cascade' => ['persist']],
            'eventID' => ['target' => 'Event', 'type' => 'ManyToOne', 'mappedBy' => 'occurrences', 'cascade' => ['persist']],
            'organismID' => ['target' => 'Organism', 'type' => 'ManyToOne', 'mappedBy' => 'occurrences', 'cascade' => ['persist']],
            'materialEntityID' => ['target' => 'MaterialEntity', 'type' => 'ManyToOne', 'mappedBy' => 'occurrences', 'cascade' => ['persist']],
            'locationID' => ['target' => 'Location', 'type' => 'ManyToOne', 'mappedBy' => 'occurrences', 'cascade' => ['persist']],
            'identificationID' => ['target' => 'Identification', 'type' => 'ManyToOne', 'mappedBy' => 'occurrences', 'cascade' => ['persist']],
        ],
        'Event' => [
            'locationID' => ['target' => 'Location', 'type' => 'ManyToOne', 'mappedBy' => 'events'],
            'occurrences' => ['target' => 'Occurrence', 'type' => 'OneToMany', 'mappedBy' => 'event'],
        ],
        'Identification' => [
            'taxonID' => ['target' => 'Taxon', 'type' => 'ManyToOne', 'mappedBy' => 'identifications'],
            'occurrences' => ['target' => 'Occurrence', 'type' => 'OneToMany', 'mappedBy' => 'identification'],
        ],
        'MaterialEntity' => [
            'materialSampleID' => ['target' => 'MaterialSample', 'type' => 'ManyToOne', 'mappedBy' => 'materialEntities'],
            'occurrences' => ['target' => 'Occurrence', 'type' => 'OneToMany', 'mappedBy' => 'materialEntity'],
        ],
        'Location' => [
            'geologicalContextID' => ['target' => 'GeologicalContext', 'type' => 'ManyToOne', 'mappedBy' => 'locations'],
            'events' => ['target' => 'Event', 'type' => 'OneToMany', 'mappedBy' => 'location'],
            'occurrences' => ['target' => 'Occurrence', 'type' => 'OneToMany', 'mappedBy' => 'location'],
        ],
        'Taxon' => [
            'occurrences' => ['target' => 'Occurrence', 'type' => 'OneToMany', 'mappedBy' => 'taxon'],
            'identifications' => ['target' => 'Identification', 'type' => 'OneToMany', 'mappedBy' => 'taxon'],
            'measurementOrFacts' => ['target' => 'MeasurementOrFact', 'type' => 'OneToMany', 'mappedBy' => 'taxon'],
        ],
        'Organism' => [
            'occurrences' => ['target' => 'Occurrence', 'type' => 'OneToMany', 'mappedBy' => 'organism'],
        ],
        'GeologicalContext' => [
            'locations' => ['target' => 'Location', 'type' => 'OneToMany', 'mappedBy' => 'geologicalContext'],
        ],
        'MaterialSample' => [
            'materialEntities' => ['target' => 'MaterialEntity', 'type' => 'OneToMany', 'mappedBy' => 'materialSample'],
        ],
        'MeasurementOrFact' => [
            'occurrenceID' => ['target' => 'Occurrence', 'type' => 'ManyToOne'],
            'eventID' => ['target' => 'Event', 'type' => 'ManyToOne'],
            'taxonID' => ['target' => 'Taxon', 'type' => 'ManyToOne', 'mappedBy' => 'measurementOrFacts'],
            'locationID' => ['target' => 'Location', 'type' => 'ManyToOne'],
        ],
        'ResourceRelationship' => [
            'resourceID' => ['target' => 'Occurrence', 'type' => 'ManyToOne', 'property' => 'occurrenceID'],
            'relatedResourceID' => ['target' => 'Occurrence', 'type' => 'ManyToOne', 'property' => 'occurrenceID'],
        ],
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
            $entityName = $this->getEntityNameFromIdentifier($name);
            if ($entityName && isset($entities[$entityName])) {
                $entities[$entityName]['identifiers'][] = [
                    'name' => $name,
                    'type' => $type,
                    'description' => $this->extractDocumentation($element),
                    'nullable' => false
                ];
            }
        }

        // Parse all identifier references in IdentifierTerms group for potential relationships
        $identifierTerms = $xml->xpath('//xs:group[@name="IdentifierTerms"]//xs:element');
        $allIdentifierNames = [];
        foreach ($identifierTerms as $element) {
            $refName = (string) $element['ref'];
            if (strpos($refName, 'dwc:') === 0) {
                $allIdentifierNames[] = substr($refName, 4); // Remove 'dwc:' prefix
            }
        }

        // Parse record level terms
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
        }

        // Now process identifier terms that can be relationships
        foreach ($entities as $entityName => &$entity) {
            foreach ($allIdentifierNames as $identifierName) {
                // Check if this identifier should be a relationship for this entity
                if ($this->isRelationshipField($entityName, $identifierName)) {
                    $relationshipInfo = $this->entityRelationships[$entityName][$identifierName];
                    $entity['relationships'][] = [
                        'name' => $identifierName,
                        'type' => 'string',
                        'target' => $relationshipInfo['target'],
                        'relationshipType' => $relationshipInfo['type'],
                        'targetProperty' => $relationshipInfo['property'] ?? $identifierName,
                        'mappedBy' => $relationshipInfo['mappedBy'] ?? null,
                        'cascade' => $relationshipInfo['cascade'] ?? null,
                        'description' => 'Reference to ' . $relationshipInfo['target']
                    ];
                }
            }
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

                // Check if this is a relationship field
                if ($this->isRelationshipField($entityName, $name)) {
                    $relationshipInfo = $this->entityRelationships[$entityName][$name];
                    $entities[$entityName]['relationships'][] = [
                        'name' => $name,
                        'type' => $type,
                        'target' => $relationshipInfo['target'],
                        'relationshipType' => $relationshipInfo['type'],
                        'targetProperty' => $relationshipInfo['property'] ?? $name,
                        'mappedBy' => $relationshipInfo['mappedBy'] ?? null,
                        'cascade' => $relationshipInfo['cascade'] ?? null,
                        'description' => $this->extractDocumentation($element)
                    ];
                } else {
                    $entities[$entityName]['properties'][] = [
                        'name' => $name,
                        'type' => $type,
                        'description' => $this->extractDocumentation($element),
                        'nullable' => true
                    ];
                }
            }
        }

        // Add record-level terms that might be relationships
        foreach ($entities as $entityName => &$entity) {
            foreach ($entity['recordLevelTerms'] as $key => $term) {
                if ($this->isRelationshipField($entityName, $term['name'])) {
                    $relationshipInfo = $this->entityRelationships[$entityName][$term['name']];
                    $entity['relationships'][] = [
                        'name' => $term['name'],
                        'type' => $term['type'],
                        'target' => $relationshipInfo['target'],
                        'relationshipType' => $relationshipInfo['type'],
                        'targetProperty' => $relationshipInfo['property'] ?? $term['name'],
                        'mappedBy' => $relationshipInfo['mappedBy'] ?? null,
                        'cascade' => $relationshipInfo['cascade'] ?? null,
                        'description' => $term['description']
                    ];
                    // Remove from record level terms since it's now a relationship
                    unset($entity['recordLevelTerms'][$key]);
                }
            }
            // Re-index array after unsetting elements
            $entity['recordLevelTerms'] = array_values($entity['recordLevelTerms']);

            // Add configured relationships that are not from schema parsing (like OneToMany reverse relationships)
            if (isset($this->entityRelationships[$entityName])) {
                foreach ($this->entityRelationships[$entityName] as $relationshipName => $relationshipInfo) {
                    // Check if this relationship is not already added from schema parsing
                    $alreadyExists = false;
                    foreach ($entity['relationships'] as $existingRel) {
                        if ($existingRel['name'] === $relationshipName) {
                            $alreadyExists = true;
                            break;
                        }
                    }

                    if (!$alreadyExists && $relationshipInfo['type'] === 'OneToMany') {
                        $entity['relationships'][] = [
                            'name' => $relationshipName,
                            'type' => 'Collection',
                            'target' => $relationshipInfo['target'],
                            'relationshipType' => $relationshipInfo['type'],
                            'targetProperty' => $relationshipInfo['property'] ?? $relationshipName,
                            'mappedBy' => $relationshipInfo['mappedBy'] ?? null,
                            'description' => ''
                        ];
                    }
                }
            }
        }
    }

    private function getEntityNameFromIdentifier(string $identifier): ?string
    {
        $mapping = [
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

        return $mapping[$identifier] ?? null;
    }

    private function isRelationshipField(string $entityName, string $fieldName): bool
    {
        return isset($this->entityRelationships[$entityName]) &&
            isset($this->entityRelationships[$entityName][$fieldName]);
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
        $doctrineType = $this->getDoctrineType($type);
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

    private function getDoctrineType(string $phpType): string
    {
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
}
