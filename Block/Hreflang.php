<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Block;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Roni\CmsHreflang\ViewModel\Hreflang as HreflangViewModel;

/**
 * Renders the hreflang links. It holds no logic: it exists because the full page cache only reads cache tags from
 * blocks, so it hands over the ones of its ViewModel.
 */
class Hreflang extends Template implements IdentityInterface
{
    /**
     * Get the cache tags of the CMS pages the links point to.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $viewModel = $this->getData('view_model');

        return $viewModel instanceof HreflangViewModel ? $viewModel->getIdentities() : [];
    }
}
