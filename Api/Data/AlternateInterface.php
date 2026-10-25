<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Api\Data;

/**
 * One hreflang alternate of a CMS page: the language-region (or x-default), the URL and the CMS page behind it.
 *
 * @api
 */
interface AlternateInterface
{
    public const X_DEFAULT = 'x-default';

    /**
     * Get the hreflang value: a language-region such as "pt-BR", or "x-default".
     *
     * @return string
     */
    public function getHreflang(): string;

    /**
     * Get the absolute URL of the alternate.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Get the id of the CMS page record that answers at the URL.
     *
     * @return int
     */
    public function getPageId(): int;
}
