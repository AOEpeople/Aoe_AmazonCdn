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

/**
 * Class Aoe_AmazonCdn_Model_Cache
 *
 * @method Aoe_AmazonCdn_Model_Resource_Cache _getResource()
 * @method string getUrl()
 * @method Aoe_AmazonCdn_Model_Cache setUrl(string $url)
 * @method int getFileType()
 * @method Aoe_AmazonCdn_Model_Cache setFileType(int $fileType)
 * @method string getLastChecked()
 * @method Aoe_AmazonCdn_Model_Cache setLastChecked(string $lastChecked)
 * @method int getImageWidth()
 * @method Aoe_AmazonCdn_Model_Cache setImageWidth(int $width)
 * @method int getImageHeight()
 * @method Aoe_AmazonCdn_Model_Cache setImageHeight(int $height)
 * @method int getImageType()
 * @method Aoe_AmazonCdn_Model_Cache setImageType(int $imageType)
 */
class Aoe_AmazonCdn_Model_Cache extends Mage_Core_Model_Abstract
{
    /**@+
     * File types
     *
     * @var int
     */
    const FILE_TYPE_IMAGE  = 0;
    const FILE_TYPE_CSS_JS = 1;
    /**@-*/

    /**
     * Internal constructor not depended on params
     */
    protected function _construct()
    {
        $this->_init('aoe_amazoncdn/cache', 'cache_id');
    }

    /**
     * Get cached information about url if it is present and not expired
     *
     * @param string $url
     * @return array|bool
     */
    public function get($url)
    {
        $this->unsetData();
        $this->load($url, 'url');

        if ($this->getId()) {
            return array(
                'image_width'  => $this->getImageWidth(),
                'image_height' => $this->getImageHeight(),
                'image_type'   => $this->getImageType(),
                'last_checked' => strtotime($this->getLastChecked())
            );
        } else {
            return false;
        }
    }

    /**
     * Record a newly verified URL
     *
     * @param string $url
     * @param int $width
     * @param int $height
     * @param int $imageType
     * @return array
     */
    public function add($url, $width, $height, $imageType)
    {
        $this->unsetData();

        $this->load($url, 'url');

        $fileType = self::FILE_TYPE_IMAGE;

        if (substr($url, -2) == 'js' || substr($url, -3) == 'css') {
            $fileType = self::FILE_TYPE_CSS_JS;
        }

        $this->setUrl($url)
            ->setFileType($fileType)
            ->setImageWidth($width)
            ->setImageHeight($height)
            ->setImageType($imageType)
            ->setLastChecked(date('Y-m-d H:i:s'))
            ->save();

        return array(
            'image_width'  => $this->getImageWidth(),
            'image_height' => $this->getImageHeight(),
            'image_type'   => $this->getImageType(),
            'last_checked' => strtotime($this->getLastChecked())
        );
    }

    /**
     * Remove cached information about url if it is present
     *
     * @param string $url
     * @return bool
     */
    public function remove($url)
    {
        $this->load($url, 'url');
        if ($this->getId()) {
            $this->delete();

            return true;
        }

        return false;
    }

    /**
     * Delete all expired entries
     *
     * @param float $cacheTtl
     */
    public function clearExpiredItems($cacheTtl)
    {
        $this->_getResource()->clearExpiredItems($cacheTtl);
    }

    /**
     * Delete the entire cache
     */
    public function flush()
    {
        $this->_getResource()->truncate();
    }

    /**
     * Delete all entries of image type
     */
    public function flushImages()
    {
        $this->_getResource()->deleteItemsByType(self::FILE_TYPE_IMAGE);
    }

    /**
     * Delete all entries of image type
     */
    public function flushCssJs()
    {
        $this->_getResource()->deleteItemsByType(self::FILE_TYPE_CSS_JS);
    }
}
