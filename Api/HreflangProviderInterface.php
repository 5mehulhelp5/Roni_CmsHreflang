<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Api;

use Roni\CmsHreflang\Api\Data\AlternateInterface;

/**
 * Finds the hreflang alternates of a CMS page.
 *
 * Only active store views whose CMS page is active are listed, one entry per hreflang value (the store views of the
 * current website first), followed by an x-default entry when the default store view of the current website serves
 * the page.
 *
 * @api
 */
interface HreflangProviderInterface
{
    /**
     * Get the alternates of the CMS page that has the given identifier, as seen from a store view.
     *
     * Every store view looks the page up by the identifier, so pages that share an identifier across languages are
     * linked to each other.
     *
     * @param string $identifier URL key of the page, for example "about-us"
     * @param int $storeId Store view the page is being viewed in
     * @return AlternateInterface[]
     */
    public function getForPage(string $identifier, int $storeId): array;

    /**
     * Get the alternates of the CMS home page, as seen from a store view.
     *
     * Each store view answers at its base URL with the page configured as its home page.
     *
     * @param int $storeId Store view the home page is being viewed in
     * @return AlternateInterface[]
     */
    public function getForHome(int $storeId): array;
}
