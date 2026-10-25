<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Model;

use Magento\Store\Model\Store;
use Roni\CmsHreflang\Api\Data\AlternateInterface;
use Roni\CmsHreflang\Api\HreflangProviderInterface;

/**
 * Builds the hreflang alternates of a CMS page or of the CMS home page.
 */
class HreflangProvider implements HreflangProviderInterface
{
    /**
     * @param StoreSelector $storeSelector
     * @param PageResolver $pageResolver
     * @param Config $config
     */
    public function __construct(
        private readonly StoreSelector $storeSelector,
        private readonly PageResolver $pageResolver,
        private readonly Config $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getForPage(string $identifier, int $storeId): array
    {
        $stores = $this->storeSelector->getStores($storeId);
        $identifiers = [];
        foreach ($stores as $store) {
            $identifiers[(int) $store->getId()] = $identifier;
        }

        return $this->build($stores, $identifiers, $storeId, false);
    }

    /**
     * @inheritDoc
     */
    public function getForHome(int $storeId): array
    {
        $stores = $this->storeSelector->getStores($storeId);
        $identifiers = [];
        foreach ($stores as $store) {
            $identifiers[(int) $store->getId()] = $this->config->getHomeIdentifier((int) $store->getId());
        }

        return $this->build($stores, $identifiers, $storeId, true);
    }

    /**
     * Build the alternates: one per hreflang value (the first store view wins), then x-default.
     *
     * @param Store[] $stores Candidates, in the order they win a hreflang value
     * @param string[] $identifiers Identifier to look up, by store id
     * @param int $currentStoreId
     * @param bool $home Whether the alternate is the base URL of the store view instead of its page URL
     * @return AlternateInterface[]
     */
    private function build(array $stores, array $identifiers, int $currentStoreId, bool $home): array
    {
        $pageIds = $this->pageResolver->resolve($identifiers);
        $defaultStoreId = $this->storeSelector->getDefaultStoreId($currentStoreId);

        $alternates = [];
        $xDefault = null;
        foreach ($stores as $store) {
            $storeId = (int) $store->getId();
            if (!isset($pageIds[$storeId])) {
                continue;
            }
            $url = $store->getBaseUrl() . ($home ? '' : ltrim($identifiers[$storeId], '/'));
            if ($storeId === $defaultStoreId) {
                $xDefault = new Alternate(AlternateInterface::X_DEFAULT, $url, $pageIds[$storeId]);
            }
            $hreflang = str_replace('_', '-', $this->config->getLocale($storeId));
            if (!isset($alternates[$hreflang])) {
                $alternates[$hreflang] = new Alternate($hreflang, $url, $pageIds[$storeId]);
            }
        }
        if ($xDefault !== null) {
            $alternates[AlternateInterface::X_DEFAULT] = $xDefault;
        }

        return array_values($alternates);
    }
}
