<?php
/**
 * Aoe_AmazonCdn
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0), a
 * copy of which is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @package    Aoe_AmazonCdn
 * @author     Dmytro Zavalkin <dmytro.zavalkin@aoe.com>
 * @copyright  Copyright (c) 2014 AOE, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Aoe_AmazonCdn_Model_Observer
{
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
     * Get admin session
     *
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * Observer to clean the cache every so often since there could be URLs that aren't
     * being used anymore. Without this, those URLs would never leave the cache.
     */
    public function cleanCache()
    {
        if ($this->_getHelper()->isConfigured()) {
            $this->_getHelper()->getCacheFacade()->clearExpiredItems();
        }
    }

    /**
     * Return temp image url to show on admin grid just uploaded files.
     * Since we upload images to remote only on model save, we can't use url pointing to remote before save.
     *
     * @param Varien_Event_Observer $event
     */
    public function onGalleryUploadAction(Varien_Event_Observer $event)
    {
        if ($this->_getHelper()->isConfigured()) {
            /** @var Mage_Adminhtml_Catalog_Product_GalleryController $controllerAction */
            $controllerAction = $event->getData('controller_action');
            $coreHelper = Mage::helper('core');
            $data = $coreHelper->jsonDecode($controllerAction->getResponse()->getBody());

            if (isset($data['url'])) {
                $productMediaConfig = Mage::getSingleton('catalog/product_media_config');
                $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
                // check if there is need to fix url
                if (strpos($productMediaConfig->getBaseMediaUrl(), $baseUrl) !== 0) {
                    $mediaFolderPath = str_replace('\\', '/',
                        str_replace(Mage::getBaseDir(), '', Mage::getBaseDir('media'))
                    );
                    $tempMediaBaseUrl = rtrim($baseUrl, '/') . '/' . trim($mediaFolderPath, '/');
                    $data['url'] = str_replace(rtrim(Mage::getBaseUrl('media'), '/'), $tempMediaBaseUrl, $data['url']);
                    $controllerAction->getResponse()->setBody($coreHelper->jsonEncode($data));
                }
            }
        }
    }

    /**
     * When an admin config setting related to the this extension is changed, the cache
     * must be cleared because the cache usually isn't relevant anymore
     */
    public function onConfigChange()
    {
        if ($this->_getHelper()->isEnabled()) {
            if ($this->_getHelper()->isConfigured()) {
                $this->_getAdminSession()->addSuccess("Successfully connected to S3 bucket with provided credentials");
                $this->_getHelper()->getCacheFacade()->flush();
            } else {
                $this->_getAdminSession()->addError("Can't connect to S3 bucket with provided credentials");
            }
        }
    }

    /**
     * Upload original image files to cdn
     *
     * @param Varien_Event_Observer $event
     */
    public function catalogProductModelSaveAfter(Varien_Event_Observer $event)
    {
        if ($this->_getHelper()->isConfigured()) {
            $logger = $this->_getHelper()->getLogger();
            /* @var $product Mage_Catalog_Model_Product */
            $product = $event->getProduct();
            $baseDir = Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath();

            $uploadedFiles = array();
            $startTime     = time();

            $galleryData = $product->getData('media_gallery');
            $data        = is_array($galleryData['images']) ? $galleryData['images'] : array();
            /* @var $image array */
            foreach ($data as $image) {
                $localFile = $baseDir . $image['file'];
                if (!is_file($localFile)) {
                    $this->_getHelper()->getLogger()->log(
                        "Could not find local file {$localFile} for product id: " . $product->getId(),
                        Zend_Log::ERR
                    );
                    continue;
                }

                if (!$this->_getHelper()->getCacheFacade()->get($localFile)) {
                    $uploadedFiles[] = $localFile;
                    $logger->log("File {$localFile} does not exist. Uploading");
                    $this->_getHelper()->getCdnAdapter()
                        ->save($localFile, $localFile);
                } else {
                    $logger->log("File {$localFile} already exists. Not uploading");
                }
            }

            $duration = time() - $startTime;
            if (count($uploadedFiles)) {
                $this->_getAdminSession()
                    ->addSuccess("Uploaded product images to S3 (Duration: {$duration} sec):<br />"
                        . implode('<br />', $uploadedFiles)
                    );
            }
        }
    }

    /**
     * Upload original image files to cdn
     *
     * @param Varien_Event_Observer $event
     */
    public function catalogCategoryModelSaveAfter(Varien_Event_Observer $event)
    {
        if ($this->_getHelper()->isConfigured()) {
            $logger = $this->_getHelper()->getLogger();
            /** @var Mage_Catalog_Model_Category $category */
            $category = $event->getCategory();
            $baseDir = Mage::getBaseDir('media') . DS . 'catalog' . DS . 'category' . DS;

            $uploadedFiles = array();
            $startTime     = time();

            $data = array($category->getData('image'), $category->getData('thumbnail'));
            /* @var $image array */
            foreach ($data as $image) {
                $localFile = $baseDir . $image;
                if (!is_file($localFile)) {
                    $this->_getHelper()->getLogger()->log("Could not find local file {$localFile} for Category id:" . $category->getId(),
                        Zend_Log::ERR
                    );
                    continue;
                }

                if (!$this->_getHelper()->getCacheFacade()->get($localFile)) {
                    $uploadedFiles[] = $localFile;
                    $logger->log("File {$localFile} does not exist. Uploading");
                    $this->_getHelper()->getCdnAdapter()
                        ->save($localFile, $localFile);
                } else {
                    $logger->log("File {$localFile} already exists. Not uploading");
                }
            }

            $duration = time() - $startTime;
            if (count($uploadedFiles)) {
                $this->_getAdminSession()
                    ->addSuccess("Uploaded product images to S3 (Duration: {$duration} sec):<br />"
                        . implode('<br />', $uploadedFiles)
                    );
            }
        }
    }
}
