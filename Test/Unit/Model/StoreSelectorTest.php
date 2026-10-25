<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Test\Unit\Model;

use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roni\CmsHreflang\Model\Config;
use Roni\CmsHreflang\Model\StoreSelector;

class StoreSelectorTest extends TestCase
{
    /**
     * @var StoreManagerInterface&MockObject
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var Config&MockObject
     */
    private Config $config;

    /**
     * @var StoreSelector
     */
    private StoreSelector $selector;

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->config = $this->createMock(Config::class);
        $this->selector = new StoreSelector($this->storeManager, $this->config);
    }

    /**
     * @param int $id
     * @param int $websiteId
     * @param bool $active
     * @param int $sortOrder
     * @return Store&MockObject
     */
    private function store(int $id, int $websiteId, bool $active = true, int $sortOrder = 0): Store
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn($id);
        $store->method('getWebsiteId')->willReturn($websiteId);
        $store->method('isActive')->willReturn($active);
        $store->method('getData')->with('sort_order')->willReturn($sortOrder);

        return $store;
    }

    /**
     * @param array<int, StoreInterface> $stores
     * @param int $currentStoreId
     * @param int $currentWebsiteId
     * @param bool $includeOthers
     */
    private function arrange(array $stores, int $currentStoreId, int $currentWebsiteId, bool $includeOthers): void
    {
        $current = $this->createMock(Store::class);
        $current->method('getWebsiteId')->willReturn($currentWebsiteId);
        $this->storeManager->method('getStore')->with($currentStoreId)->willReturn($current);
        $this->storeManager->method('getStores')->willReturn($stores);
        $this->config->method('isIncludeOtherWebsites')->with($currentWebsiteId)->willReturn($includeOthers);
    }

    /**
     * @param Store[] $stores
     * @return int[]
     */
    private static function ids(array $stores): array
    {
        return array_map(static fn (Store $store): int => (int) $store->getId(), $stores);
    }

    public function testOnlyTheActiveStoreViewsOfTheCurrentWebsiteByDefault(): void
    {
        $this->arrange(
            [1 => $this->store(1, 1), 2 => $this->store(2, 1), 3 => $this->store(3, 1, false), 5 => $this->store(5, 2)],
            2,
            1,
            false
        );

        self::assertSame([1, 2], self::ids($this->selector->getStores(2)));
    }

    public function testOtherWebsitesFollowTheCurrentOneWhenTheSettingIsOn(): void
    {
        $this->arrange(
            [1 => $this->store(1, 1), 5 => $this->store(5, 2), 6 => $this->store(6, 2), 7 => $this->store(7, 3, false)],
            5,
            2,
            true
        );

        self::assertSame([5, 6, 1], self::ids($this->selector->getStores(5)));
    }

    public function testStoreViewsOfAWebsiteFollowTheirSortOrderThenTheirId(): void
    {
        $this->arrange(
            [1 => $this->store(1, 1, true, 20), 2 => $this->store(2, 1, true, 10), 3 => $this->store(3, 1, true, 10)],
            1,
            1,
            false
        );

        self::assertSame([2, 3, 1], self::ids($this->selector->getStores(1)));
    }

    public function testAMissingSortOrderCountsAsZero(): void
    {
        $a = $this->store(1, 1, true, 5);
        $b = $this->createMock(Store::class);
        $b->method('getId')->willReturn(2);
        $b->method('getWebsiteId')->willReturn(1);
        $b->method('isActive')->willReturn(true);
        $b->method('getData')->willReturn(null);
        $this->arrange([1 => $a, 2 => $b], 1, 1, false);

        self::assertSame([2, 1], self::ids($this->selector->getStores(1)));
    }

    public function testIgnoresEntriesThatAreNotStoreModels(): void
    {
        $this->arrange([1 => $this->createMock(StoreInterface::class)], 1, 1, false);

        self::assertSame([], $this->selector->getStores(1));
    }

    public function testDefaultStoreOfTheWebsiteOfTheStoreView(): void
    {
        $current = $this->createMock(Store::class);
        $current->method('getWebsiteId')->willReturn(2);
        $this->storeManager->method('getStore')->with(5)->willReturn($current);
        $website = $this->createMock(WebsiteInterface::class);
        $website->method('getDefaultGroupId')->willReturn('9');
        $this->storeManager->method('getWebsite')->with(2)->willReturn($website);
        $group = $this->createMock(GroupInterface::class);
        $group->method('getDefaultStoreId')->willReturn('5');
        $this->storeManager->method('getGroup')->with('9')->willReturn($group);

        self::assertSame(5, $this->selector->getDefaultStoreId(5));
    }
}
