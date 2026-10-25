<?php
/**
 * Store views and CMS pages of the end-to-end scenario of Roni_CmsHreflang. Idempotent. Run it inside the php-fpm
 * container (`php seed-stores.php`), then run configure.sh to set locales and base URLs.
 *
 *   website base   default (en_US) | pt (pt_BR) | es (es_ES) | fr (fr_FR, DISABLED)
 *   website b_site b_en (en_US) | b_pt (pt_PT)
 *
 * CMS pages: about-us (sample data, All Store Views), partial (default + pt), pt-only (pt), inactive-page (disabled),
 * only-b (the two store views of site B) and three pages that share the identifier contact-us, one per language.
 */

declare(strict_types=1);

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\ResourceModel\Group as GroupResource;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\ResourceModel\Website as WebsiteResource;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\WebsiteFactory;

require '/var/www/html/app/bootstrap.php';
$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();
$om->get(State::class)->setAreaCode('adminhtml');

$websiteResource = $om->get(WebsiteResource::class);
$groupResource = $om->get(GroupResource::class);
$storeResource = $om->get(StoreResource::class);

// ---- website B
$website = $om->get(WebsiteFactory::class)->create();
$websiteResource->load($website, 'b_site', 'code');
if (!$website->getId()) {
    $website->setData(['code' => 'b_site', 'name' => 'Site B', 'sort_order' => 2]);
    $websiteResource->save($website);
}
$group = $om->get(GroupFactory::class)->create();
$groupResource->load($group, 'group_b', 'code');
if (!$group->getId()) {
    $group->setData(['website_id' => $website->getId(), 'name' => 'Group B', 'code' => 'group_b', 'root_category_id' => 2]);
    $groupResource->save($group);
}

$mainGroup = $om->get(GroupFactory::class)->create();
$groupResource->load($mainGroup, 1);

$stores = [
    ['pt', 'Portuguese (BR)', $mainGroup, 1, 20],
    ['es', 'Spanish (ES)', $mainGroup, 1, 30],
    ['fr', 'French (FR, disabled)', $mainGroup, 0, 40],
    ['b_en', 'Site B English', $group, 1, 10],
    ['b_pt', 'Site B Portuguese', $group, 1, 20],
];
foreach ($stores as [$code, $name, $parentGroup, $active, $sort]) {
    $store = $om->get(StoreFactory::class)->create();
    $storeResource->load($store, $code, 'code');
    if (!$store->getId()) {
        $store->setData([
            'code' => $code, 'name' => $name, 'website_id' => $parentGroup->getWebsiteId(),
            'group_id' => $parentGroup->getId(), 'sort_order' => $sort, 'is_active' => $active,
        ]);
        $storeResource->save($store);
    }
    if ($parentGroup === $group && !$group->getDefaultStoreId()) {
        $group->setDefaultStoreId($store->getId());
        $groupResource->save($group);
    }
}
if (!$website->getDefaultGroupId()) {
    $website->setDefaultGroupId($group->getId());
    $websiteResource->save($website);
}

// ---- CMS pages
$storeId = static function (string $code) use ($om, $storeResource): int {
    $store = $om->get(StoreFactory::class)->create();
    $storeResource->load($store, $code, 'code');
    return (int) $store->getId();
};
$default = $storeId('default');
$pages = [
    // identifier => [title, stores, active, one page per language with the same identifier?]
    ['partial', 'Partial (en + pt of the main website)', [$default, $storeId('pt')], 1],
    ['pt-only', 'Portuguese only', [$storeId('pt')], 1],
    ['inactive-page', 'Inactive page', [0], 0],
    ['only-b', 'Only site B', [$storeId('b_en'), $storeId('b_pt')], 1],
    ['contact-us', 'Contact us (EN)', [$default], 1],
    ['contact-us', 'Fale conosco (PT)', [$storeId('pt')], 1],
    ['contact-us', 'Contáctenos (ES)', [$storeId('es')], 1],
];
$repository = $om->get(PageRepositoryInterface::class);
$resource = $om->get(Magento\Cms\Model\ResourceModel\Page::class);
foreach ($pages as [$identifier, $title, $storeIds, $active]) {
    $existing = $om->get(PageFactory::class)->create();
    $select = $resource->getConnection()->select()->from($resource->getMainTable(), 'page_id')
        ->where('title = ?', $title);
    $id = $resource->getConnection()->fetchOne($select);
    if ($id) {
        continue;
    }
    $page = $om->get(PageFactory::class)->create();
    $page->setData([
        'title' => $title, 'identifier' => $identifier, 'page_layout' => '1column', 'is_active' => $active,
        'stores' => $storeIds, 'content_heading' => $title, 'content' => '<p>' . $title . '</p>',
    ]);
    $repository->save($page);
}
echo "stores: ", implode(',', array_map(static fn ($s) => $s->getCode() . '#' . $s->getId() . ($s->getIsActive() ? '' : '(off)'),
    $om->get(Magento\Store\Model\StoreManagerInterface::class)->getStores(true))), "\n";
$rows = $resource->getConnection()->fetchAll(
    'SELECT p.page_id, p.identifier, p.is_active, GROUP_CONCAT(s.store_id ORDER BY s.store_id) stores, p.title FROM cms_page p '
    . 'JOIN cms_page_store s ON s.page_id = p.page_id GROUP BY p.page_id ORDER BY p.page_id'
);
foreach ($rows as $row) {
    echo sprintf("page %2d  %-14s active=%d stores=[%s]  %s\n", $row['page_id'], $row['identifier'], $row['is_active'], $row['stores'], $row['title']);
}
