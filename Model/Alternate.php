<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Model;

use Roni\CmsHreflang\Api\Data\AlternateInterface;

/**
 * Immutable hreflang alternate.
 */
class Alternate implements AlternateInterface
{
    /**
     * @param string $hreflang
     * @param string $url
     * @param int $pageId
     */
    public function __construct(
        private readonly string $hreflang,
        private readonly string $url,
        private readonly int $pageId
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getHreflang(): string
    {
        return $this->hreflang;
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @inheritDoc
     */
    public function getPageId(): int
    {
        return $this->pageId;
    }
}
