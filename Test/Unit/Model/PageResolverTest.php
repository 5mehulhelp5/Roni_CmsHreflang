<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Test\Unit\Model;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\Data\PageSearchResultsInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Page;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roni\CmsHreflang\Model\PageResolver;

class PageResolverTest extends TestCase
{
    /**
     * @var PageRepositoryInterface&MockObject
     */
    private PageRepositoryInterface $repository;

    /**
     * @var SearchCriteriaBuilder&MockObject
     */
    private SearchCriteriaBuilder $criteriaBuilder;

    /**
     * @var PageResolver
     */
    private PageResolver $resolver;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PageRepositoryInterface::class);
        $this->criteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->resolver = new PageResolver($this->repository, $this->criteriaBuilder);
    }

    /**
     * @param int $id
     * @param array<int|string> $storeIds
     * @return Page&MockObject
     */
    private function page(int $id, array $storeIds): Page
    {
        $page = $this->createMock(Page::class);
        $page->method('getId')->willReturn($id);
        $page->method('getStores')->willReturn($storeIds);

        return $page;
    }

    /**
     * Make the repository answer with the given pages whatever the criteria.
     *
     * @param PageInterface[] $pages
     */
    private function repositoryReturns(array $pages): void
    {
        $this->criteriaBuilder->method('addFilter')->willReturnSelf();
        $this->criteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteria::class));
        $results = $this->createMock(PageSearchResultsInterface::class);
        $results->method('getItems')->willReturn($pages);
        $this->repository->method('getList')->willReturn($results);
    }

    public function testSearchesActivePagesByIdentifier(): void
    {
        $calls = [];
        $this->criteriaBuilder->method('addFilter')->willReturnCallback(
            function (string $field, mixed $value) use (&$calls) {
                $calls[] = [$field, $value];

                return $this->criteriaBuilder;
            }
        );
        $this->criteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteria::class));
        $results = $this->createMock(PageSearchResultsInterface::class);
        $results->method('getItems')->willReturn([]);
        $this->repository->expects(self::once())->method('getList')->willReturn($results);

        $this->resolver->resolve([1 => 'about-us', 2 => 'about-us']);

        self::assertSame([['identifier', 'about-us'], ['is_active', 1]], $calls);
    }

    public function testAPageForAllStoreViewsServesEveryStoreView(): void
    {
        $this->repositoryReturns([$this->page(5, [0])]);

        $resolved = $this->resolver->resolve([1 => 'about-us', 2 => 'about-us', 3 => 'about-us']);

        self::assertSame([1 => 5, 2 => 5, 3 => 5], $resolved);
    }

    public function testAPageOfTheStoreViewWinsOverTheOneForAllStoreViews(): void
    {
        $this->repositoryReturns([$this->page(5, [0]), $this->page(14, [2])]);

        self::assertSame([1 => 5, 2 => 14], $this->resolver->resolve([1 => 'contact-us', 2 => 'contact-us']));
    }

    public function testStoreViewsWithoutAPageAreLeftOut(): void
    {
        $this->repositoryReturns([$this->page(9, [1, 2])]);

        self::assertSame([1 => 9, 2 => 9], $this->resolver->resolve([1 => 'partial', 2 => 'partial', 3 => 'partial']));
    }

    public function testStoreIdsComeAsStringsFromTheDatabase(): void
    {
        $this->repositoryReturns([$this->page(9, ['2', '4'])]);

        self::assertSame([4 => 9], $this->resolver->resolve([4 => 'partial', 7 => 'partial']));
    }

    public function testIgnoresItemsThatAreNotPageModels(): void
    {
        $this->repositoryReturns([$this->createMock(PageInterface::class)]);

        self::assertSame([], $this->resolver->resolve([1 => 'about-us']));
    }

    public function testAnEmptyIdentifierResolvesNothingAndSearchesNothing(): void
    {
        $this->repository->expects(self::never())->method('getList');

        self::assertSame([], $this->resolver->resolve([1 => '']));
    }

    public function testEachDistinctIdentifierIsSearchedOnce(): void
    {
        $this->criteriaBuilder->method('addFilter')->willReturnSelf();
        $this->criteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteria::class));
        $results = $this->createMock(PageSearchResultsInterface::class);
        $results->method('getItems')->willReturnOnConsecutiveCalls([$this->page(2, [0])], [$this->page(30, [3])]);
        $this->repository->expects(self::exactly(2))->method('getList')->willReturn($results);

        $resolved = $this->resolver->resolve([1 => 'home', 2 => 'home', 3 => 'inicio']);

        self::assertSame([1 => 2, 2 => 2, 3 => 30], $resolved);
    }
}
