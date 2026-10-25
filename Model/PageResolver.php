<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Model;

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Page;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Finds, for each store view, the CMS page that answers at an identifier.
 *
 * It follows the rule of the storefront: a page assigned to the store view itself wins over a page assigned to
 * "All Store Views", and an inactive page does not answer. One repository query is made per distinct identifier,
 * not per store view.
 */
class PageResolver
{
    private const ALL_STORE_VIEWS = 0;

    /**
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private readonly PageRepositoryInterface $pageRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    /**
     * Resolve the page of every store view.
     *
     * @param string[] $identifiers Identifier to look up, by store id
     * @return array<int, int> Id of the page that answers, by store id; store views without a page are left out
     */
    public function resolve(array $identifiers): array
    {
        $pageIds = [];
        foreach (array_unique($identifiers) as $identifier) {
            $assigned = $this->getAssignments($identifier);
            foreach (array_keys($identifiers, $identifier, true) as $storeId) {
                $pageId = $assigned[$storeId] ?? $assigned[self::ALL_STORE_VIEWS] ?? null;
                if ($pageId !== null) {
                    $pageIds[$storeId] = $pageId;
                }
            }
        }

        return $pageIds;
    }

    /**
     * Get the ids of the active pages with an identifier, by the store view each is assigned to.
     *
     * @param string $identifier
     * @return array<int, int>
     */
    private function getAssignments(string $identifier): array
    {
        if ($identifier === '') {
            return [];
        }
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('identifier', $identifier)
            ->addFilter('is_active', 1)
            ->create();

        $assigned = [];
        foreach ($this->pageRepository->getList($criteria)->getItems() as $page) {
            if ($page instanceof Page) {
                foreach ($page->getStores() as $storeId) {
                    $assigned[(int) $storeId] = (int) $page->getId();
                }
            }
        }

        return $assigned;
    }
}
