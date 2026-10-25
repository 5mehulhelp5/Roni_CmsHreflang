<?php
/**
 * Copyright © Roni Clei Santos. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Roni\CmsHreflang\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Reads the settings the module depends on. Every read passes its scope: locale and home page are set per store view,
 * the switch of this module per website.
 */
class Config
{
    public const XML_PATH_INCLUDE_OTHER_WEBSITES = 'roni_cms_hreflang/general/include_other_websites';
    public const XML_PATH_LOCALE = 'general/locale/code';
    public const XML_PATH_HOME_PAGE = 'web/default/cms_home_page';

    public const DEFAULT_LOCALE = 'en_US';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    /**
     * Whether the store views of the other websites are listed too.
     *
     * @param int $websiteId
     * @return bool
     */
    public function isIncludeOtherWebsites(int $websiteId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_INCLUDE_OTHER_WEBSITES,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get the locale of a store view (for example pt_BR).
     *
     * @param int $storeId
     * @return string
     */
    public function getLocale(int $storeId): string
    {
        $locale = $this->scopeConfig->getValue(self::XML_PATH_LOCALE, ScopeInterface::SCOPE_STORE, $storeId);

        return is_string($locale) && $locale !== '' ? $locale : self::DEFAULT_LOCALE;
    }

    /**
     * Get the identifier of the CMS home page of a store view (an old "identifier|page id" value is cut at the pipe).
     *
     * @param int $storeId
     * @return string
     */
    public function getHomeIdentifier(int $storeId): string
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_HOME_PAGE, ScopeInterface::SCOPE_STORE, $storeId);

        return is_string($value) ? explode('|', $value)[0] : '';
    }
}
