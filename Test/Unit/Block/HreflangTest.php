<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Test\Unit\Block;

use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roni\CmsHreflang\Block\Hreflang;
use Roni\CmsHreflang\ViewModel\Hreflang as HreflangViewModel;

class HreflangTest extends TestCase
{
    public function testHandsOverTheCacheTagsOfItsViewModel(): void
    {
        $viewModel = $this->createMock(HreflangViewModel::class);
        $viewModel->method('getIdentities')->willReturn(['cms_p_5', 'cms_p_14']);

        $block = new Hreflang($this->createMock(Context::class), ['view_model' => $viewModel]);

        self::assertSame(['cms_p_5', 'cms_p_14'], $block->getIdentities());
    }

    public function testNoCacheTagsWithoutAViewModel(): void
    {
        $block = new Hreflang($this->createMock(Context::class));

        self::assertSame([], $block->getIdentities());
    }
}
