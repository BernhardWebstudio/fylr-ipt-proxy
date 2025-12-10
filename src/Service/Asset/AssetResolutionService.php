<?php

namespace App\Service\Asset;

use App\Service\EasydbApiService;
use Psr\Log\LoggerInterface;

/**
 * Service responsible for resolving and retrieving original asset URLs from EasyDB.
 *
 * This service abstracts the complexity of fetching asset metadata from the EasyDB API,
 * allowing mapping services to request original-resolution assets without being concerned
 * with the technical details of the EasyDB asset structure.
 */
class AssetResolutionService
{
    public function __construct(
        private EasydbApiService $easydbApiService,
        private LoggerInterface $logger
    ) {}

    /**
     * Resolve original download URLs for assets from the EasyDB API.
     *
     * Given a list of asset IDs, fetches the detailed asset information from EasyDB
     * and returns the download URLs for the original (highest resolution) versions.
     *
     * @param int[] $assetIds Array of EasyDB asset IDs to resolve
     * @return string[] Array of original asset download URLs, in order of the input IDs
     */
    public function resolveOriginalAssetUrls(array $assetIds): array
    {
        if (empty($assetIds)) {
            return [];
        }

        return $this->fetchAssetUrls($assetIds);
    }

    /**
     * Fetch asset metadata from EasyDB API and extract download URLs.
     *
     * @param int[] $assetIds Array of EasyDB asset IDs
     * @return string[] Array of original download URLs
     */
    private function fetchAssetUrls(array $assetIds): array
    {
        // Build the EAS API URL with asset IDs
        $assetIdsList = implode(',', $assetIds);
        $easUrl = sprintf('eas?ids=[%s]&format=short', $assetIdsList);

        // Create a temporary session context if needed
        try {
            $response = $this->easydbApiService->requestUrl($easUrl);
            return $this->extractDownloadUrls($response);
        } catch (\Exception $e) {
            $this->logger->error('EasyDB API request failed', [
                'url' => $easUrl,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract original download URLs from EasyDB asset response.
     *
     * @param array $easResponse The EAS API response containing asset metadata
     * @return string[] Array of original download URLs
     */
    private function extractDownloadUrls(array $easResponse): array
    {
        $urls = [];

        foreach ($easResponse as $assetId => $assetData) {
            $originalUrl = $this->extractOriginalUrl($assetData);
            if ($originalUrl) {
                $urls[$assetId] = $originalUrl;
            }
        }

        return array_values($urls); // Return indexed array
    }

    /**
     * Extract the original download URL from a single asset's metadata.
     *
     * @param array $assetData The asset metadata from EasyDB
     * @return string|null The original download URL, or null if not found
     */
    private function extractOriginalUrl(array $assetData): ?string
    {
        // The asset structure includes a 'versions' key with different resolution variants
        // We want the 'original' version for highest resolution
        if (!isset($assetData['versions']) || !is_array($assetData['versions'])) {
            return null;
        }

        // Prefer 'original' version first
        if (isset($assetData['versions']['original']['download_url'])) {
            return $assetData['versions']['original']['download_url'];
        }

        // Fall back to the first available version if 'original' is not available
        foreach ($assetData['versions'] as $version) {
            if (isset($version['download_url'])) {
                return $version['download_url'];
            }
        }

        return null;
    }
}
