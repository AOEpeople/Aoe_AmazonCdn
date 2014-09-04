<?php
/**
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoe.com>
 */

$fixMediaUrls = function ($content) {
    $updatedContent = str_replace("{media url='/", "{media url='", $content);
    $updatedContent = str_replace('{media url="/', '{media url="', $updatedContent);

    return $updatedContent;
};

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

// fix skin urls in all cms pages
$cmsPageCollection = Mage::getResourceModel('cms/page_collection');

if ($cmsPageCollection->count() > 0) {
    foreach ($cmsPageCollection as $page) {
        if ($page->getUnderVersionControl()) {
            // get last published revision
            $revision = Mage::getModel('enterprise_cms/page_revision');
            $revision->load($page->getPublishedRevisionId());

            $content = $revision->getContent();
        } else {
            $content = $page->getContent();
        }

        if ($content) {
            $updatedContent = $fixMediaUrls($content);

            if ($updatedContent !== $content) {
                if ($page->getUnderVersionControl()) {
                    $revision->setContent($updatedContent)
                        ->setUserId(117) // AOE user id
                        ->save();
                    $revision->publish();
                } else {
                    $page->setContent($updatedContent)
                        ->save();
                }
            }
        }
    }
}

// fix skin urls in all cms blocks
$cmsBlockCollection = Mage::getResourceModel('cms/block_collection');

if ($cmsBlockCollection->count() > 0) {
    foreach ($cmsBlockCollection as $block) {
        $content = $block->getContent();

        if ($content) {
            $updatedContent = $fixMediaUrls($content);
            if ($updatedContent !== $content) {
                $stores = array_merge((array)$block->getStores(), array(Mage_Core_Model_App::ADMIN_STORE_ID));
                $block->setContent($updatedContent)
                    ->setStores($stores)
                    ->save();
            }
        }
    }
}

$installer->endSetup();
