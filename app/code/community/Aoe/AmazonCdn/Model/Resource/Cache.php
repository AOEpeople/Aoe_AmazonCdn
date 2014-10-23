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

class Aoe_AmazonCdn_Model_Resource_Cache extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('aoe_amazoncdn/cache', 'cache_id');
    }

    /**
     * Custom method to truncate the cache table
     */
    public function truncate()
    {
        $this->_getWriteAdapter()->truncateTable($this->getMainTable());
    }

    /**
     * Delete all rows where last_checked + $ttl * 60 < current time
     *
     * @param int $ttl
     */
    public function clearExpiredItems($ttl)
    {
        $this->_getWriteAdapter()->delete($this->getMainTable(),
            sprintf('last_checked + %d < NOW()', $ttl * 60)
        );
    }

    /**
     * Delete all rows where file_type = $fileType
     *
     * @param int $fileType
     */
    public function deleteItemsByType($fileType)
    {
        $this->_getWriteAdapter()->delete($this->getMainTable(),
            array('file_type = ?' => $fileType)
        );
    }
}
