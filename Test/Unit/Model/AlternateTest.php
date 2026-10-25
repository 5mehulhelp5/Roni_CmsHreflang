<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Roni\CmsHreflang\Model\Alternate;

class AlternateTest extends TestCase
{
    public function testExposesWhatItWasBuiltWith(): void
    {
        $alternate = new Alternate('pt-BR', 'http://example.test/pt/about-us', 14);

        self::assertSame('pt-BR', $alternate->getHreflang());
        self::assertSame('http://example.test/pt/about-us', $alternate->getUrl());
        self::assertSame(14, $alternate->getPageId());
    }
}
