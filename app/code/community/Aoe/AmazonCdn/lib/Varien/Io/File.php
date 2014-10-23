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

class Varien_Io_File extends Magento\Varien_Io_File
{

    /**
     * Check source is file.
     *
     * @param string $src
     * @return bool
     */
    protected function _checkSrcIsFile($src)
    {
        $result = false;

        // Fix for bug in core:
        // both is_readable() and is_file() emit E_WARNING if there is a null byte in $src
        if (is_string($src) && @is_readable($src) && @is_file($src)) {
            $result = true;
        }

        return $result;
    }

}
