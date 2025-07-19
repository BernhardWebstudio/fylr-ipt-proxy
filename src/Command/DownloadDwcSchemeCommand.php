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
    name: 'app:download-dwc-scheme',
    description: 'Download an up-to-date version of the Darwin Core (DwC) scheme',
)]
class DownloadDwcSchemeCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        // $this
        //     ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
        //     ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        // ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Downloading Darwin Core Scheme');
        $urlsToDownload = ["https://dwc.tdwg.org/xml/tdwg_dwc_classes.xsd"];
        $targetDirectory = __DIR__ . '/../../resources/schemes/';

        // Ensure the target directory exists
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
            $io->success("Created directory: $targetDirectory");
        }

        $processedUrls = [];

        while (!empty($urlsToDownload)) {
            $url = array_shift($urlsToDownload);

            // Skip if already processed
            if (in_array($url, $processedUrls)) {
                continue;
            }

            $fileName = basename($url);
            $targetPath = $targetDirectory . $fileName;

            $io->note(sprintf('Downloading %s to %s', $url, $targetPath));
            $content = @file_get_contents($url);
            if ($content === false) {
                $io->error(sprintf('Failed to download %s', $url));
                $processedUrls[] = $url;
                continue;
            }

            file_put_contents($targetPath, $content);
            $io->success(sprintf('Downloaded %s to %s', $url, $targetPath));
            $processedUrls[] = $url;

            // parse the XML, find include/import directives, and download them
            $xml = simplexml_load_string($content);
            if ($xml === false) {
                $io->error(sprintf('Failed to parse XML from %s', $url));
                continue;
            }

            // Register namespaces for XPath
            $xml->registerXPathNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

            // Find import directives
            $imports = $xml->xpath('//xs:import[@schemaLocation]');
            foreach ($imports as $import) {
                $schemaLocation = (string) $import['schemaLocation'];
                if ($schemaLocation && !str_starts_with($schemaLocation, 'http')) {
                    // Resolve relative URL
                    $baseUrl = dirname($url) . '/';
                    $includeUrl = $baseUrl . $schemaLocation;
                    if (!in_array($includeUrl, $urlsToDownload) && !in_array($includeUrl, $processedUrls)) {
                        $urlsToDownload[] = $includeUrl;
                        $io->note(sprintf('Found import: %s', $includeUrl));
                    }
                }
            }

            // Find include directives
            $includes = $xml->xpath('//xs:include[@schemaLocation]');
            foreach ($includes as $include) {
                $schemaLocation = (string) $include['schemaLocation'];
                if ($schemaLocation && !str_starts_with($schemaLocation, 'http')) {
                    // Resolve relative URL
                    $baseUrl = dirname($url) . '/';
                    $includeUrl = $baseUrl . $schemaLocation;
                    if (!in_array($includeUrl, $urlsToDownload) && !in_array($includeUrl, $processedUrls)) {
                        $urlsToDownload[] = $includeUrl;
                        $io->note(sprintf('Found include: %s', $includeUrl));
                    }
                }
            }
        }

        return Command::SUCCESS;
    }
}
