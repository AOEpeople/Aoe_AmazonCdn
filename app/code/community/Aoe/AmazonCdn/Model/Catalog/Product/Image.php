<?php
/**
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoe.com>
 */

/**
 * Extends various methods to use CDN
 */
class Aoe_AmazonCdn_Model_Catalog_Product_Image extends Mage_Catalog_Model_Product_Image
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
     * Set the image processor to the CDN version of varien_image and call parent method to return it.
     *
     * @return Aoe_AmazonCdn_Model_Varien_Image
     */
    public function getImageProcessor()
    {
        if (!$this->_processor) {
            if ($this->_getHelper()->isConfigured()) {
                $this->_processor = Mage::getModel('aoe_amazoncdn/varien_image', $this->getBaseFile());
            }
        }

        return parent::getImageProcessor();
    }

    /**
     * Provides the URL to the image on the CDN or fails back to the parent method as appropriate.
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->_getHelper()->isConfigured()) {
            $url = $this->_getHelper()->getCdnAdapter()->getUrl($this->getNewFile());
            if ($url) {
                return $url;
            }
        }

        return parent::getUrl();
    }

    /**
     * Clears the images on the CDN and the local cache.
     *
     * @return string
     */
    public function clearCache()
    {
        parent::clearCache();
        if ($this->_getHelper()->isConfigured()) {
            $this->_getHelper()->getCdnAdapter()->clearImageCache();
        }
    }

    /**
     * Set file name for base file and new file
     * Must override parent class method because of file_exists() check in it :(
     *
     * @param string $file
     * @return Mage_Catalog_Model_Product_Image
     * @throws Exception
     */
    public function setBaseFile($file)
    {
        if ($this->_getHelper()->isConfigured()) {
            $this->_isBaseFilePlaceholder = false;

            if ($file && 0 !== strpos($file, '/', 0)) {
                $file = '/' . $file;
            }
            $baseDir = Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath();

            if ('/no_selection' == $file) {
                $file = null;
            }
            if ($file) {
                if (!$this->_fileExists($baseDir . $file) || !$this->_checkMemory($baseDir . $file)) {
                    $file = null;
                }
            }
            if (!$file) {
                // check if placeholder defined in config
                $isConfigPlaceholder
                                   = Mage::getStoreConfig("catalog/placeholder/{$this->getDestinationSubdir()}_placeholder");
                $configPlaceholder = '/placeholder/' . $isConfigPlaceholder;
                if ($isConfigPlaceholder && $this->_fileExists($baseDir . $configPlaceholder)) {
                    $file = $configPlaceholder;
                } else {
                    // replace file with skin or default skin placeholder
                    $skinBaseDir     = Mage::getDesign()->getSkinBaseDir();
                    $skinPlaceholder = "/images/catalog/product/placeholder/{$this->getDestinationSubdir()}.jpg";
                    $file            = $skinPlaceholder;
                    if (file_exists($skinBaseDir . $file)) {
                        $baseDir = $skinBaseDir;
                    } else {
                        $baseDir = Mage::getDesign()->getSkinBaseDir(array('_theme' => 'default'));
                        if (!file_exists($baseDir . $file)) {
                            $baseDir = Mage::getDesign()->getSkinBaseDir(array(
                                        '_theme'   => 'default',
                                        '_package' => 'base'
                                    ));
                        }
                    }
                }
                $this->_isBaseFilePlaceholder = true;
            }

            $baseFile = $baseDir . $file;

            $this->_baseFile = $baseFile;

            // build new filename (most important params)
            $path = array(
                Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath(),
                'cache',
                Mage::app()->getStore()->getId(),
                $path[] = $this->getDestinationSubdir()
            );
            if (!empty($this->_width) || !empty($this->_height)) {
                $path[] = "{$this->_width}x{$this->_height}";
            }

            // add misc params as a hash
            $miscParams = array(
                ($this->_keepAspectRatio ? '' : 'non') . 'proportional',
                ($this->_keepFrame ? '' : 'no') . 'frame',
                ($this->_keepTransparency ? '' : 'no') . 'transparency',
                ($this->_constrainOnly ? 'do' : 'not') . 'constrainonly',
                $this->_rgbToString($this->_backgroundColor),
                'angle' . $this->_angle,
                'quality' . $this->_quality
            );

            // if has watermark add watermark params to hash
            if ($this->getWatermarkFile()) {
                $miscParams[] = $this->getWatermarkFile();
                $miscParams[] = $this->getWatermarkImageOpacity();
                $miscParams[] = $this->getWatermarkPosition();
                $miscParams[] = $this->getWatermarkWidth();
                $miscParams[] = $this->getWatermarkHeigth();
            }

            $path[] = md5(implode('_', $miscParams));

            // append prepared filename
            $this->_newFile = implode(DS, $path) . $file; // the $file contains heading slash

            return $this;
        } else {
            return parent::setBaseFile($file);
        }
    }

    /**
     * Check if file exists in CDN
     *
     * @param string $filename
     * @return bool
     */
    protected function _fileExists($filename)
    {
        if ($this->_getHelper()->isConfigured()) {
            return (bool) $this->_getHelper()->getCacheFacade()->get($filename);
        }

        return parent::_fileExists($filename);
    }
}
