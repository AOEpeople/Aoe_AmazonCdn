<?php

class Aoe_AmazonCdn_Block_Widget_Banner extends Enterprise_Banner_Block_Widget_Banner
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
