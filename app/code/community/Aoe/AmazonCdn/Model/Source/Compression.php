<?php
/**
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoe.com>
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
