<?php

class Aoe_AmazonCdn_Model_Storage extends Mage_Cms_Model_Wysiwyg_Images_Storage
{
    /**
     * WYSIWYG cache key prefix
     */
    const CACHE_KEY_PREFIX_WYSIWYG = 's3download_wysiwyg_';

    /**
     * Update local files from S3
     */
    protected function _construct()
    {
        $cacheKey     = self::CACHE_KEY_PREFIX_WYSIWYG . Mage::app()->getRequest()->getServer('SERVER_ADDR');
        $lastSyncTime = (int)Mage::app()->loadCache($cacheKey);

        if (time() - $lastSyncTime > 120) {
            /* @var $helper Aoe_AmazonCdn_Helper_Data */
            $helper = Mage::helper('aoe_amazoncdn');
            $helper->downloadFolders(
                array('media/wysiwyg/' => Mage::helper('cms/wysiwyg_images')->getStorageRoot())
            );
            Mage::app()->saveCache(time(), $cacheKey);
        }

        parent::_construct();
    }

    /**
     * Upload and resize new file
     *
     * @param string $targetPath Target directory
     * @param string $type Type of storage, e.g. image, media etc.
     * @return array File info Array
     */
    public function uploadFile($targetPath, $type = null)
    {
        $result = parent::uploadFile($targetPath, $type);

        // get image path
        $path = rtrim($result['path'], DS) . DS . $result['file'];

        /* @var $helper Aoe_AmazonCdn_Helper_Data */
        $helper = Mage::helper('aoe_amazoncdn');
        $url = $helper->storeInCdn($path);
        if ($url) {
            OnePica_ImageCdn_Helper_Data::log(sprintf('Copied uploaded wysiwyg file "%s" to cdn. Url "%s"', $path, $url), Zend_Log::DEBUG);
        } else {
            OnePica_ImageCdn_Helper_Data::log(sprintf('Did not copy uploaded wysiwyg file "%s" to cdn.', $path), Zend_Log::ERR);
        }

        return $result;
    }
}
