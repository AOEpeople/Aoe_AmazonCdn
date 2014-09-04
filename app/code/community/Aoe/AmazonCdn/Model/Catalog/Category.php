<?php
/**
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoe.com>
 */

/**
 * Class Aoe_AmazonCdn_Model_Catalog_Category
 *
 * @method string getImage()
 */
class Aoe_AmazonCdn_Model_Catalog_Category extends Mage_Catalog_Model_Category
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
     * Provides the URL to the image on the CDN or fails back to the parent method as appropriate
     *
     * @return string
     */
    public function getImageUrl()
    {
        if ($this->_getHelper()->isConfigured()) {
            $filename = false;
            if ($image = $this->getImage()) {
                $filename = Mage::getBaseDir('media') . '/catalog/category/' . $image;
            }

            if ($filename) {
                if (!$this->_getHelper()->getCacheFacade()->get($filename)) {
                    $this->_getHelper()->getCdnAdapter()->save($filename, $filename);
                }
                $url = $this->_getHelper()->getCdnAdapter()->getUrl($filename);
                if ($url) {
                    return $url;
                }
            }
        }

        return parent::getImageUrl();
    }
}
