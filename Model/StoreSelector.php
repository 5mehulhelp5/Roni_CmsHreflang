<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Model;

use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Chooses the store views that may be listed as alternates of a page viewed in a given store view.
 */
class StoreSelector
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config
    ) {
    }

    /**
     * Get the active store views that may be listed as alternates of a page viewed in the given store view.
     *
     * They are the store views of its website, plus those of the other websites when the website setting asks for it,
     * the current website first, then by website, sort order and id.
     *
     * @param int $storeId
     * @return Store[]
     */
    public function getStores(int $storeId): array
    {
        $websiteId = (int) $this->storeManager->getStore($storeId)->getWebsiteId();
        $includeOthers = $this->config->isIncludeOtherWebsites($websiteId);

        $stores = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ($store instanceof Store
                && $store->isActive()
                && ($includeOthers || (int) $store->getWebsiteId() === $websiteId)
            ) {
                $stores[] = $store;
            }
        }
        usort(
            $stores,
            fn (Store $a, Store $b): int => $this->getSortKey($a, $websiteId) <=> $this->getSortKey($b, $websiteId)
        );

        return $stores;
    }

    /**
     * Get the id of the default store view of the website of the given store view.
     *
     * @param int $storeId
     * @return int
     */
    public function getDefaultStoreId(int $storeId): int
    {
        $website = $this->storeManager->getWebsite($this->storeManager->getStore($storeId)->getWebsiteId());

        return (int) $this->storeManager->getGroup((string) $website->getDefaultGroupId())->getDefaultStoreId();
    }

    /**
     * Get the key that orders store views: current website first, then website, sort order and id.
     *
     * @param Store $store
     * @param int $currentWebsiteId
     * @return int[]
     */
    private function getSortKey(Store $store, int $currentWebsiteId): array
    {
        return [
            (int) $store->getWebsiteId() === $currentWebsiteId ? 0 : 1,
            (int) $store->getWebsiteId(),
            $this->toInt($store->getData('sort_order')),
            (int) $store->getId(),
        ];
    }

    /**
     * Cast a stored value to int, 0 when it is not numeric.
     *
     * @param mixed $value
     * @return int
     */
    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
