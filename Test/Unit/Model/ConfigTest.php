<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roni\CmsHreflang\Model\Config;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface&MockObject
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Config
     */
    private Config $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfig);
    }

    public function testReadsTheWebsiteSwitchInTheWebsiteScope(): void
    {
        $this->scopeConfig->expects(self::once())
            ->method('isSetFlag')
            ->with('roni_cms_hreflang/general/include_other_websites', ScopeInterface::SCOPE_WEBSITE, 3)
            ->willReturn(true);

        self::assertTrue($this->config->isIncludeOtherWebsites(3));
    }

    public function testReadsTheLocaleInTheStoreScope(): void
    {
        $this->scopeConfig->expects(self::once())
            ->method('getValue')
            ->with('general/locale/code', ScopeInterface::SCOPE_STORE, 2)
            ->willReturn('pt_BR');

        self::assertSame('pt_BR', $this->config->getLocale(2));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function missingLocaleProvider(): array
    {
        return [
            'not configured' => [null],
            'empty string' => [''],
            'not a string' => [['pt_BR']],
        ];
    }

    /**
     * @param mixed $stored
     */
    #[DataProvider('missingLocaleProvider')]
    public function testFallsBackToEnglishWhenTheLocaleIsMissing(mixed $stored): void
    {
        $this->scopeConfig->method('getValue')->willReturn($stored);

        self::assertSame('en_US', $this->config->getLocale(1));
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function homeIdentifierProvider(): array
    {
        return [
            'plain identifier' => ['home', 'home'],
            'identifier with a page id' => ['inicio|2', 'inicio'],
            'not configured' => [null, ''],
            'not a string' => [['home'], ''],
        ];
    }

    /**
     * @param mixed $stored
     * @param string $expected
     */
    #[DataProvider('homeIdentifierProvider')]
    public function testHomeIdentifier(mixed $stored, string $expected): void
    {
        $this->scopeConfig->expects(self::once())
            ->method('getValue')
            ->with('web/default/cms_home_page', ScopeInterface::SCOPE_STORE, 5)
            ->willReturn($stored);

        self::assertSame($expected, $this->config->getHomeIdentifier(5));
    }
}
