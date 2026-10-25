<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Test\Unit\ViewModel;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\App\Request\Http;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roni\CmsHreflang\Api\HreflangProviderInterface;
use Roni\CmsHreflang\Model\Alternate;
use Roni\CmsHreflang\ViewModel\Hreflang;

class HreflangTest extends TestCase
{
    /**
     * @var HreflangProviderInterface&MockObject
     */
    private HreflangProviderInterface $provider;

    /**
     * @var StoreManagerInterface&MockObject
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var PageInterface&MockObject
     */
    private PageInterface $page;

    /**
     * @var Http&MockObject
     */
    private Http $request;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(HreflangProviderInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn('2');
        $this->storeManager->method('getStore')->willReturn($store);
        $this->page = $this->createMock(PageInterface::class);
        $this->request = $this->createMock(Http::class);
        $this->request->method('getFullActionName')->willReturn('cms_page_view');
    }

    private function viewModel(): Hreflang
    {
        return new Hreflang($this->provider, $this->storeManager, $this->page, $this->request);
    }

    public function testAsksForTheAlternatesOfThePageBeingViewedInTheCurrentStoreView(): void
    {
        $alternate = new Alternate('pt-BR', 'http://example.test/pt/about-us', 14);
        $this->page->method('getIdentifier')->willReturn('about-us');
        $this->provider->expects(self::once())->method('getForPage')->with('about-us', 2)->willReturn([$alternate]);

        $viewModel = $this->viewModel();

        self::assertSame([$alternate], $viewModel->getAlternates());
    }

    public function testAsksForTheHomePageWhenBuiltForIt(): void
    {
        $alternate = new Alternate('pt-BR', 'http://example.test/pt/', 30);
        $this->provider->expects(self::once())->method('getForHome')->with(2)->willReturn([$alternate]);
        $this->provider->expects(self::never())->method('getForPage');
        $this->request = $this->createMock(Http::class);
        $this->request->method('getFullActionName')->willReturn('cms_index_index');

        $viewModel = $this->viewModel();

        self::assertSame([$alternate], $viewModel->getAlternates());
    }

    public function testNoAlternatesOnTheNoRoutePage(): void
    {
        $this->page->method('getIdentifier')->willReturn('no-route');
        $this->provider->expects(self::never())->method('getForPage');
        $this->provider->expects(self::never())->method('getForHome');
        $this->request = $this->createMock(Http::class);
        $this->request->method('getFullActionName')->willReturn('cms_noroute_index');

        self::assertSame([], $this->viewModel()->getAlternates());
    }

    public function testNoAlternatesWhenThePageHasNoIdentifier(): void
    {
        $this->page->method('getIdentifier')->willReturn(null);
        $this->provider->expects(self::never())->method('getForPage');

        $viewModel = $this->viewModel();

        self::assertSame([], $viewModel->getAlternates());
    }

    public function testTheAlternatesAreCalculatedOnce(): void
    {
        $this->page->method('getIdentifier')->willReturn('about-us');
        $this->provider->expects(self::once())->method('getForPage')->willReturn([]);

        $viewModel = $this->viewModel();
        $viewModel->getAlternates();
        $viewModel->getAlternates();
    }

    public function testCacheTagsAreThoseOfTheDistinctPagesBehindTheAlternates(): void
    {
        $this->page->method('getIdentifier')->willReturn('about-us');
        $this->provider->method('getForPage')->willReturn([
            new Alternate('en-US', 'http://example.test/about-us', 5),
            new Alternate('pt-BR', 'http://example.test/pt/about-us', 14),
            new Alternate('x-default', 'http://example.test/about-us', 5),
        ]);

        $viewModel = $this->viewModel();

        self::assertSame(['cms_p_5', 'cms_p_14'], $viewModel->getIdentities());
    }
}
