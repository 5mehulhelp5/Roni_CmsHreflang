<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\ViewModel;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Model\Page;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Roni\CmsHreflang\Api\Data\AlternateInterface;
use Roni\CmsHreflang\Api\HreflangProviderInterface;

/**
 * Hreflang alternates of the CMS page being viewed, or of the CMS home page.
 *
 * The page is the one the CMS controller loaded for the request (the same shared instance the CMS page block reads).
 * The CMS home page (cms_index_index), a page viewed at its own URL (cms_page_view) and the 404 page
 * (cms_noroute_index) all add the `cms_page_view` layout handle. The full action name tells them apart, and the 404
 * page gets no links.
 */
class Hreflang implements ArgumentInterface
{
    private const HOME_ACTION = 'cms_index_index';
    private const PAGE_ACTION = 'cms_page_view';

    /**
     * @var AlternateInterface[]|null
     */
    private ?array $alternates = null;

    /**
     * @param HreflangProviderInterface $provider
     * @param StoreManagerInterface $storeManager
     * @param PageInterface $page
     * @param Http $request
     */
    public function __construct(
        private readonly HreflangProviderInterface $provider,
        private readonly StoreManagerInterface $storeManager,
        private readonly PageInterface $page,
        private readonly Http $request
    ) {
    }

    /**
     * Get the alternates of the current page, calculated once per request.
     *
     * @return AlternateInterface[]
     */
    public function getAlternates(): array
    {
        if ($this->alternates === null) {
            $storeId = (int) $this->storeManager->getStore()->getId();
            $identifier = (string) $this->page->getIdentifier();

            $this->alternates = match ($this->request->getFullActionName()) {
                self::HOME_ACTION => $this->provider->getForHome($storeId),
                self::PAGE_ACTION => $identifier !== '' ? $this->provider->getForPage($identifier, $storeId) : [],
                default => [],
            };
        }

        return $this->alternates;
    }

    /**
     * Get the cache tags of the CMS pages behind the alternates, so that editing any of them refreshes the cached page.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $tags = [];
        foreach ($this->getAlternates() as $alternate) {
            $tags[Page::CACHE_TAG . '_' . $alternate->getPageId()] = true;
        }

        return array_map('strval', array_keys($tags));
    }
}
