<?php
/**
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoe.com>
 */

/**
 * CDN extension to use custom GD lib
 */
class Aoe_AmazonCdn_Model_Varien_Image extends Varien_Image
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
     * Open an image and create image handle.
     * Must override parent class method because of file_exists() check in it :(
     */
    public function open()
    {
        if ($this->_getHelper()->isConfigured()) {
            $this->_getAdapter()->checkDependencies();
            $this->_getAdapter()->open($this->_fileName);
        } else {
            parent::open();
        }
    }

    /**
     * Hijack the normal method to add CDN hooks. Fail back to parent method as appropriate.
     *
     * @param string $adapter
     * @return Aoe_AmazonCdn_Model_Varien_Gd2
     */
    protected function _getAdapter($adapter = null)
    {
        if (!isset($this->_adapter)) {
            if ($this->_getHelper()->isConfigured()) {
                $this->_adapter = Mage::getModel('aoe_amazoncdn/varien_gd2');
            } else {
                parent::_getAdapter();
            }
        }

        return $this->_adapter;
    }
}
