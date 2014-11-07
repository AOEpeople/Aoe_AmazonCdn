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

class Aoe_AmazonCdn_Helper_Logger
{
    /**
     * @var bool
     */
    protected $_debugMode = false;

    /**
     * Class constructor
     *
     * @param bool $debugMode
     */
    public function __construct($debugMode = false)
    {
        $this->_debugMode = (bool)$debugMode;
    }

    /**
     * Log message to log (debug mode only)
     *
     * @param string $message
     * @param int $level
     */
    public function log($message, $level = Zend_Log::DEBUG)
    {
        if ($this->_debugMode) {
            Mage::log($message, $level, 'aoe_amazoncdn.log');
        } else {
            switch ($level) {
                case Zend_Log::ERR:
                case Zend_Log::CRIT:
                case Zend_Log::ALERT:
                case Zend_Log::EMERG:
                    Mage::log($message, $level, 'aoe_amazoncdn.log');
                    break;
            }
        }
    }
}
