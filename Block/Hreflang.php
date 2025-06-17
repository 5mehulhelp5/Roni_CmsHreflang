<?php
/**
 * Hreflang Block
 *
 * Generates hreflang meta tags for CMS pages based on store views.
 *
 * @category  Roni
 * @package   Roni_CmsHreflang
 * @author    Roni Clei J Santos <roneclay@gmail.com>
 * @copyright Copyright (c) 2025 Roni Clei
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Roni\CmsHreflang\Block;

use Exception;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;

/**
 * Block responsible for generating hreflang meta tags for CMS pages
 */
class Hreflang extends Template
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var PageRepositoryInterface
     */
    private PageRepositoryInterface $pageRepository;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var PageCollectionFactory
     */
    private PageCollectionFactory $pageCollectionFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param PageRepositoryInterface $pageRepository
     * @param ResourceConnection $resource
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param PageCollectionFactory $pageCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context                 $context,
        StoreManagerInterface   $storeManager,
        PageRepositoryInterface $pageRepository,
        ResourceConnection      $resource,
        ScopeConfigInterface    $scopeConfig,
        LoggerInterface $logger,
        PageCollectionFactory   $pageCollectionFactory,
        array                   $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $storeManager;
        $this->pageRepository = $pageRepository;
        $this->resource = $resource;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->pageCollectionFactory = $pageCollectionFactory;
    }

    /**
     * Render hreflang tags only for CMS pages
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function _toHtml(): string
    {
        $request = $this->getRequest();

        if (!method_exists($request, 'getFullActionName') || $request->getFullActionName() !== 'cms_page_view') {
            return '';
        }

        $pageIdentifier = $this->getPageIdentifier();

        if (!$pageIdentifier || !$this->isPageActive($pageIdentifier, $this->getCurrentStoreId())) {
            return '';
        }

        return $this->generateHreflangTags($pageIdentifier);
    }

    /**
     * Generate hreflang tags for all store views of a CMS page
     *
     * @param string $pageIdentifier
     * @return string
     */
    private function generateHreflangTags(string $pageIdentifier): string
    {
        $output = [];

        $pageId = $this->getPageIdByIdentifier($pageIdentifier);
        if (!$pageId) {
            return '';
        }

        $storeIds = $this->getStoresForPage($pageId);

        foreach ($storeIds as $storeId) {
            try {
                $store = $this->storeManager->getStore($storeId);

                if (!$this->isPageActive($pageIdentifier, $store->getId())) {
                    continue;
                }

                // Get locale code (e.g., en_US -> en-us)
                $locale = $this->scopeConfig->getValue(
                    'general/locale/code',
                    ScopeInterface::SCOPE_STORE,
                    $store->getId()
                ) ?: 'en_US';

                $locale = str_replace('_', '-', $locale);

                // Get store base URL
                $baseUrl = rtrim(
                    $store->getBaseUrl(),
                    '/'
                );

                // Check if store code is included in URLs
                $useStoreCodeInUrl = $this->scopeConfig->isSetFlag(
                    'web/url/use_store',
                    ScopeInterface::SCOPE_STORE,
                    $store->getId()
                );

                // Build URL path
                $urlParts = [];
                if ($useStoreCodeInUrl) {
                    $urlParts[] = $store->getCode();
                }
                $urlParts[] = ltrim($pageIdentifier, '/');

                // Final page URL
                $url = $baseUrl . '/' . implode('/', $urlParts);

                // Build hreflang tag
                $output[] = sprintf(
                    '<link rel="alternate" hreflang="%s" href="%s"/>',
                    htmlspecialchars($locale, ENT_QUOTES),
                    htmlspecialchars($url, ENT_QUOTES)
                );
            } catch (Exception $e) {
                // Log the exception error message
                $this->logger->error(
                    sprintf('Error generating hreflang tag for store ID %d and page %s: %s',
                        $storeId, $pageIdentifier, $e->getMessage()
                    )
                );

                continue;
            }
        }

        return implode(PHP_EOL, $output) . PHP_EOL;
    }

    /**
     * Get all store IDs associated with a CMS page
     *
     * @param int $pageId
     * @return array
     */
    private function getStoresForPage(int $pageId): array
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('cms_page_store');

        $select = $connection->select()
            ->from($table, ['store_id'])
            ->where('page_id = ?', $pageId);

        $storeIds = $connection->fetchCol($select);

        // If store_id = 0, it means "All Store Views"
        if (in_array('0', $storeIds)) {
            return array_map(
                static fn($store) => $store->getId(),
                $this->storeManager->getStores()
            );
        }

        return array_map('intval', $storeIds);
    }

    /**
     * Get page ID by its identifier
     *
     * @param string $identifier
     * @return int|null
     */
    private function getPageIdByIdentifier(string $identifier): ?int
    {
        try {
            $page = $this->pageRepository->getById($identifier);
            return (int)$page->getId();
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('Error retrieving page ID for identifier "%s": %s', $identifier, $e->getMessage())
            );

            return null;
        }
    }

    /**
     * Check if a CMS page is active in a specific store view
     *
     * @param string $pageIdentifier
     * @param int $storeId
     * @return bool
     */
    private function isPageActive(string $pageIdentifier, int $storeId): bool
    {
        try {
            $collection = $this->pageCollectionFactory->create()
                ->addFieldToFilter('identifier', $pageIdentifier)
                ->addStoreFilter($storeId)
                ->setPageSize(1);

            $page = $collection->getFirstItem();

            return $page && $page->getId() && $page->isActive();
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('Error checking if page "%s" is active in store ID %d: %s',
                    $pageIdentifier, $storeId, $e->getMessage()
                )
            );

            return false;
        }
    }


    /**
     * Get current store ID
     *
     * @return int
     * @throws NoSuchEntityException
     */
    private function getCurrentStoreId(): int
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Get current CMS page identifier from layout
     *
     * @return string
     * @throws LocalizedException
     */
    private function getPageIdentifier(): string
    {
        $cmsPageBlock = $this->getLayout()->getBlock('cms_page');

        if ($cmsPageBlock && $cmsPageBlock->getPage()) {
            return $cmsPageBlock->getPage()->getIdentifier();
        }

        return '';
    }
}
