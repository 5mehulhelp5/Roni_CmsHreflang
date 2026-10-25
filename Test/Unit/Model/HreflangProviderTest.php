<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Test\Unit\Model;

use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roni\CmsHreflang\Api\Data\AlternateInterface;
use Roni\CmsHreflang\Model\Config;
use Roni\CmsHreflang\Model\HreflangProvider;
use Roni\CmsHreflang\Model\PageResolver;
use Roni\CmsHreflang\Model\StoreSelector;

class HreflangProviderTest extends TestCase
{
    /**
     * @var StoreSelector&MockObject
     */
    private StoreSelector $storeSelector;

    /**
     * @var PageResolver&MockObject
     */
    private PageResolver $pageResolver;

    /**
     * @var Config&MockObject
     */
    private Config $config;

    /**
     * @var HreflangProvider
     */
    private HreflangProvider $provider;

    /**
     * @var array<int, string>
     */
    private array $localeByStore = [];

    protected function setUp(): void
    {
        $this->storeSelector = $this->createMock(StoreSelector::class);
        $this->pageResolver = $this->createMock(PageResolver::class);
        $this->config = $this->createMock(Config::class);
        $this->provider = new HreflangProvider($this->storeSelector, $this->pageResolver, $this->config);
    }

    /**
     * @param int $id
     * @param string $locale
     * @param string $baseUrl
     * @return Store&MockObject
     */
    private function store(int $id, string $locale, string $baseUrl): Store
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn($id);
        $store->method('getBaseUrl')->willReturn($baseUrl);
        $this->localeByStore[$id] = $locale;

        return $store;
    }

    /**
     * Select the given stores for the page viewed in store 1, whose website has store $defaultStoreId as default.
     *
     * @param Store[] $stores
     * @param array<int, int> $pageIds
     * @param int $defaultStoreId
     */
    private function arrange(array $stores, array $pageIds, int $defaultStoreId): void
    {
        $this->storeSelector->method('getStores')->with(1)->willReturn($stores);
        $this->storeSelector->method('getDefaultStoreId')->with(1)->willReturn($defaultStoreId);
        $this->pageResolver->method('resolve')->willReturn($pageIds);
        $this->config->method('getLocale')->willReturnCallback(
            fn (int $storeId): string => $this->localeByStore[$storeId]
        );
    }

    /**
     * @param AlternateInterface[] $alternates
     * @return array<int, array{string, string, int}>
     */
    private static function describe(array $alternates): array
    {
        return array_map(
            static fn (AlternateInterface $a): array => [$a->getHreflang(), $a->getUrl(), $a->getPageId()],
            $alternates
        );
    }

    public function testOneAlternatePerStoreViewThenXDefaultOfTheDefaultStoreView(): void
    {
        $this->arrange(
            [
                $this->store(1, 'en_US', 'http://example.test/'),
                $this->store(2, 'pt_BR', 'http://example.test/pt/'),
                $this->store(3, 'es_ES', 'http://example.test/es/'),
            ],
            [1 => 5, 2 => 14, 3 => 15],
            1
        );

        $alternates = $this->provider->getForPage('about-us', 1);

        self::assertSame(
            [
                ['en-US', 'http://example.test/about-us', 5],
                ['pt-BR', 'http://example.test/pt/about-us', 14],
                ['es-ES', 'http://example.test/es/about-us', 15],
                ['x-default', 'http://example.test/about-us', 5],
            ],
            self::describe($alternates)
        );
    }

    public function testAskTheResolverForTheSameIdentifierInEveryStoreView(): void
    {
        $this->arrange(
            [$this->store(1, 'en_US', 'http://example.test/'), $this->store(2, 'pt_BR', 'http://example.test/pt/')],
            [],
            1
        );
        $this->pageResolver = $this->createMock(PageResolver::class);
        $this->pageResolver->expects(self::once())->method('resolve')
            ->with([1 => 'contact-us', 2 => 'contact-us'])
            ->willReturn([]);
        $provider = new HreflangProvider($this->storeSelector, $this->pageResolver, $this->config);

        self::assertSame([], $provider->getForPage('contact-us', 1));
    }

    public function testAStoreViewWithoutThePageIsLeftOut(): void
    {
        $this->arrange(
            [
                $this->store(1, 'en_US', 'http://example.test/'),
                $this->store(2, 'pt_BR', 'http://example.test/pt/'),
                $this->store(3, 'es_ES', 'http://example.test/es/'),
            ],
            [1 => 9, 2 => 9],
            1
        );

        self::assertSame(
            [
                ['en-US', 'http://example.test/partial', 9],
                ['pt-BR', 'http://example.test/pt/partial', 9],
                ['x-default', 'http://example.test/partial', 9],
            ],
            self::describe($this->provider->getForPage('partial', 1))
        );
    }

    public function testNoXDefaultWhenTheDefaultStoreViewDoesNotServeThePage(): void
    {
        $this->arrange(
            [$this->store(1, 'en_US', 'http://example.test/'), $this->store(2, 'pt_BR', 'http://example.test/pt/')],
            [2 => 10],
            1
        );

        self::assertSame(
            [['pt-BR', 'http://example.test/pt/pt-only', 10]],
            self::describe($this->provider->getForPage('pt-only', 1))
        );
    }

    public function testTheFirstStoreViewWinsARepeatedLocale(): void
    {
        $this->arrange(
            [
                $this->store(1, 'en_US', 'http://example.test/'),
                $this->store(2, 'pt_BR', 'http://example.test/pt/'),
                $this->store(5, 'en_US', 'http://other.test/'),
                $this->store(6, 'pt_BR', 'http://other.test/pt/'),
            ],
            [1 => 5, 2 => 5, 5 => 5, 6 => 5],
            1
        );

        self::assertSame(
            [
                ['en-US', 'http://example.test/about-us', 5],
                ['pt-BR', 'http://example.test/pt/about-us', 5],
                ['x-default', 'http://example.test/about-us', 5],
            ],
            self::describe($this->provider->getForPage('about-us', 1))
        );
    }

    public function testXDefaultSurvivesWhenTheDefaultStoreViewLosesItsLocaleToAnotherOne(): void
    {
        $this->arrange(
            [$this->store(1, 'en_US', 'http://example.test/uk/'), $this->store(2, 'en_US', 'http://example.test/us/')],
            [1 => 5, 2 => 5],
            2
        );

        self::assertSame(
            [
                ['en-US', 'http://example.test/uk/about-us', 5],
                ['x-default', 'http://example.test/us/about-us', 5],
            ],
            self::describe($this->provider->getForPage('about-us', 1))
        );
    }

    public function testALeadingSlashOfTheIdentifierIsNotRepeated(): void
    {
        $this->arrange([$this->store(1, 'en_US', 'http://example.test/')], [1 => 5], 1);

        self::assertSame(
            'http://example.test/about-us',
            $this->provider->getForPage('/about-us', 1)[0]->getUrl()
        );
    }

    public function testNothingWhenNoStoreViewServesThePage(): void
    {
        $this->arrange([$this->store(1, 'en_US', 'http://example.test/')], [], 1);

        self::assertSame([], $this->provider->getForPage('inactive-page', 1));
    }

    public function testTheHomePageIsTheBaseUrlOfEachStoreViewWithItsOwnHomeIdentifier(): void
    {
        $this->arrange(
            [$this->store(1, 'en_US', 'http://example.test/'), $this->store(2, 'pt_BR', 'http://example.test/pt/')],
            [],
            1
        );
        $this->config->method('getHomeIdentifier')->willReturnMap([[1, 'home'], [2, 'inicio']]);
        $this->pageResolver = $this->createMock(PageResolver::class);
        $this->pageResolver->expects(self::once())->method('resolve')
            ->with([1 => 'home', 2 => 'inicio'])
            ->willReturn([1 => 2, 2 => 30]);
        $provider = new HreflangProvider($this->storeSelector, $this->pageResolver, $this->config);

        self::assertSame(
            [
                ['en-US', 'http://example.test/', 2],
                ['pt-BR', 'http://example.test/pt/', 30],
                ['x-default', 'http://example.test/', 2],
            ],
            self::describe($provider->getForHome(1))
        );
    }
}
