<?php
/**
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoe.com>
 */

class Aoe_AmazonCdn_Model_Cms_Wysiwyg_Images_Storage extends Mage_Cms_Model_Wysiwyg_Images_Storage
{
    /**
     * WYSIWYG cache key prefix
     */
    const CACHE_KEY_PREFIX_WYSIWYG = 's3download_wysiwyg_';

    /**
     * Get helper
     *
     * @return Aoe_AmazonCdn_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('aoe_amazoncdn');
    }

    /**
     * Update local files from S3
     */
    protected function _construct()
    {
        if ($this->_getHelper()->isConfigured()) {
            $cacheKey = self::CACHE_KEY_PREFIX_WYSIWYG . Mage::app()->getRequest()->getServer('SERVER_ADDR');
            $lastSyncTime = (int) Mage::app()->loadCache($cacheKey);

            if (time() - $lastSyncTime > 120) {
                $this->_getHelper()->getCdnAdapter()
                    ->downloadFolder(Mage::helper('cms/wysiwyg_images')->getStorageRoot());
                Mage::app()->saveCache(time(), $cacheKey);
            }
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
        if ($this->_getHelper()->isConfigured()) {
            $logger = $this->_getHelper()->getLogger();
            $result = parent::uploadFile($targetPath, $type);

            // get image path
            $fileName = rtrim($result['path'], DS) . DS . $result['file'];
            if ($this->_getHelper()->getCdnAdapter()->save($fileName, $fileName)) {
                $url = $this->_getHelper()->getCdnAdapter()->getUrl($fileName);
                $logger->log(sprintf('Copied uploaded wysiwyg file "%s" to cdn. Url "%s"', $fileName, $url),
                    Zend_Log::DEBUG
                );
            } else {
                $logger->log(sprintf('Did not copy uploaded wysiwyg file "%s" to cdn.', $fileName), Zend_Log::ERR);
            }

            return $result;
        } else {
            return parent::uploadFile($targetPath, $type);
        }
    }
}
