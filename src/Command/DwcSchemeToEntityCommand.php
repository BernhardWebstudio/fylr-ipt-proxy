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
                    'description' => $this->extractDocumentation($element)
                ];
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
                'description' => $this->extractDocumentation($element)
            ];

            // Add to all entities as these are common
            foreach ($entities as &$entity) {
                $entity['recordLevelTerms'][] = $term;
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
                
                $entities[$entityName]['properties'][] = [
                    'name' => $name,
                    'type' => $type,
                    'description' => $this->extractDocumentation($element)
                ];
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

        // Add domain-specific properties
        foreach ($entityData['properties'] as $property) {
            $code .= $this->generatePropertyCode($property);
        }

        // Add record level terms
        foreach ($entityData['recordLevelTerms'] as $term) {
            $code .= $this->generatePropertyCode($term);
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
        $code .= "    #[ORM\\Column(name: '$name', type: '$doctrineType'";
        
        if (!$isIdentifier) {
            $code .= ", nullable: true";
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
        $camelCase = $this->snakeCaseToCamelCase($name);
        $pascalCase = ucfirst($camelCase);
        $phpType = $this->getPhpType($type, true);
        $returnType = $this->getPhpType($type, false);

        $code = "    public function get$pascalCase(): $phpType\n";
        $code .= "    {\n";
        $code .= "        return \$this->$camelCase;\n";
        $code .= "    }\n\n";

        $code .= "    public function set$pascalCase($returnType \$$camelCase): static\n";
        $code .= "    {\n";
        $code .= "        \$this->$camelCase = \$$camelCase;\n";
        $code .= "        return \$this;\n";
        $code .= "    }\n\n";

        return $code;
    }

    private function getDoctrineType(string $phpType): string
    {
        return match($phpType) {
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
