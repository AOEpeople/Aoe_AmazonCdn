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

class Aoe_AmazonCdn_Model_Source_Compression
{
    /**
     * JPEG/PNG compression options for the admin config dropdown
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array_merge(array(0 => '-- Use default --'), array_combine(range(1, 9), range(1, 9)));
    }
}
