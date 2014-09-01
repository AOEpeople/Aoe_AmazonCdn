<?php

class Aoe_AmazonCdn_Block_Block extends Mage_Cms_Block_Block
{
    /**
     * Prepare Content HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        /* @var $helper Aoe_AmazonCdn_Helper_Data */
        $helper = Mage::helper('aoe_amazoncdn');

        return $helper->replaceWysiwygUrls(parent::_toHtml());
    }
}
