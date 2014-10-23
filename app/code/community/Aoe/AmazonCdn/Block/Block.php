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

class Aoe_AmazonCdn_Block_Block extends Mage_Cms_Block_Block
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
