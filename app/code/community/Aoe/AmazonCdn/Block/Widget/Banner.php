<?php
/**
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoe.com>
 */

class Aoe_AmazonCdn_Block_Widget_Banner extends Enterprise_Banner_Block_Widget_Banner
{
    /**
     * Prepare Content HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        $html = parent::_toHtml();

        /* @var $helper Aoe_AmazonCdn_Helper_Data */
        $helper = Mage::helper('aoe_amazoncdn');
        if ($helper->isConfigured()) {
            return $helper->replaceWysiwygUrls($html);
        } else {
            return $html;
        }
    }
}
