<?php

/**
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoe.com>
 */
class Aoe_AmazonCdn_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Config paths to retrieve Aoe_AmazonCdn settings
     *
     * @var string
     */
    const XPATH_CONFIG_IS_ENABLED = 'aoe_amazoncdn/general/is_enabled';
    const XPATH_CONFIG_CACHE_CHECK_SIZE = 'aoe_amazoncdn/general/cache_check_size';
    const XPATH_CONFIG_CACHE_TTL = 'aoe_amazoncdn/general/cache_ttl';
    const XPATH_CONFIG_COMPRESSION = 'aoe_amazoncdn/general/compression';
    const XPATH_CONFIG_DEBUG_MODE = 'aoe_amazoncdn/general/debug_mode';
    const XPATH_CONFIG_STORE_CACHE_REMOTELY = 'aoe_amazoncdn/general/store_cache_remotely';
    const XPATH_CONFIG_STORE_CACHE_LOCALLY = 'aoe_amazoncdn/general/store_cache_locally';
    const XPATH_CONFIG_BUCKET_NAME = 'aoe_amazoncdn/amazons3/bucket';
    const XPATH_CONFIG_ACCESS_KEY_ID = 'aoe_amazoncdn/amazons3/access_key_id';
    const XPATH_CONFIG_SECRET_ACCESS_KEY = 'aoe_amazoncdn/amazons3/secret_access_key';

    /**
     * General on/off switcher
     *
     * @var bool
     */
    protected $_isEnabled;

    /**
     * Is CDN integration enabled and bucket credentials are configured properly
     *
     * @var bool
     */
    protected $_isConfigured;

    /**
     * Image compression level (1-9) for jpeg/png images
     *
     * @var int
     */
    protected $_compression;

    /**
     * Logger instance
     *
     * @var Aoe_AmazonCdn_Helper_Logger
     */
    protected $_logger;

    /**
     * Amazon CDN adapter instance
     *
     * @var Aoe_AmazonCdn_Model_Cdn_Adapter
     */
    protected $_cdnAdapter;

    /**
     * Get cache facade instance
     *
     * @var Aoe_AmazonCdn_Model_Cache_Facade
     */
    protected $_cacheFacade;

    /**
     * Check if extension is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->_isEnabled === null) {
            $this->_isEnabled = Mage::getStoreConfigFlag(self::XPATH_CONFIG_IS_ENABLED);
        }

        return $this->_isEnabled;
    }

    /**
     * Check if CDN integration is enabled and bucket credentials are configured properly
     *
     * @return bool
     */
    public function isConfigured()
    {
        if ($this->_isConfigured === null) {
            if ($this->isEnabled()) {
                $this->_isConfigured = false;
                try {
                    $this->getCdnAdapter();
                    $this->_isConfigured = true;
                } catch (Exception $e) {
                }
            } else {
                $this->_isConfigured = false;
            }
        }

        return $this->_isConfigured;
    }

    /**
     * Check if extension is enabled
     *
     * @return bool
     */
    public function getCompression()
    {
        if ($this->_compression === null) {
            $this->_compression = (int)Mage::getStoreConfig(self::XPATH_CONFIG_COMPRESSION);
        }

        return $this->_compression;
    }

    /**
     * Get logger
     *
     * @return Aoe_AmazonCdn_Helper_Logger
     */
    public function getLogger()
    {
        if ($this->_logger === null) {
            $debugMode = Mage::getStoreConfigFlag(self::XPATH_CONFIG_DEBUG_MODE);
            $this->_logger = new Aoe_AmazonCdn_Helper_Logger($debugMode);
        }

        return $this->_logger;
    }

    /**
     * Get cache facade instance
     *
     * @return Aoe_AmazonCdn_Model_Cache_Facade
     */
    public function getCacheFacade()
    {
        if ($this->_cacheFacade === null) {
            $this->_cacheFacade = new Aoe_AmazonCdn_Model_Cache_Facade(
                $this->getCdnAdapter(),
                Mage::getSingleton('aoe_amazoncdn/cache'),
                Mage::getStoreConfigFlag(self::XPATH_CONFIG_CACHE_CHECK_SIZE),
                Mage::getStoreConfig(self::XPATH_CONFIG_CACHE_TTL)
            );
        }
        return $this->_cacheFacade;
    }

    /**
     * Get Amazon CDN adapter
     *
     * @return Aoe_AmazonCdn_Model_Cdn_Adapter
     */
    public function getCdnAdapter()
    {
        if ($this->_cdnAdapter === null) {
            $this->_cdnAdapter = new Aoe_AmazonCdn_Model_Cdn_Adapter(
                Mage::getStoreConfig(self::XPATH_CONFIG_BUCKET_NAME),
                Mage::getStoreConfig(self::XPATH_CONFIG_ACCESS_KEY_ID),
                Mage::getStoreConfig(self::XPATH_CONFIG_SECRET_ACCESS_KEY)
            );
        }
        return $this->_cdnAdapter;
    }

    /**
     * Replace wysiwyg urls
     *
     * @param string $html
     * @return string
     */
    public function replaceWysiwygUrls($html)
    {
        return preg_replace_callback('/"(http[^"]*\/media\/wysiwyg\/[^"]*)"/', array($this, '_replaceCallback'), $html);
    }

    /**
     * Search for local files and replace them with cdn url.
     * If file is not find in cdn try to upload it.
     * This function is intended to be used as a callback for preg_replace_callback.
     *
     * @param array $match
     * @return string
     */
    private function _replaceCallback($match)
    {
        $nativeUrl = $match[1];

        $fileName = Mage::getBaseDir('media') . preg_replace('/(.*?)\/wysiwyg/', DS . 'wysiwyg', $nativeUrl);
        $url = $nativeUrl;
        $cdnUrl = $this->getCdnAdapter()->getUrl($fileName);
        if ($this->getCacheFacade()->get($fileName)) {
            $url = $cdnUrl;
        } elseif (is_file($fileName)) {
            if ($this->getCdnAdapter()->save($fileName, $fileName)) {
                $url = $cdnUrl;
                $this->getLogger()->log(sprintf('Copied previously uploaded wysiwyg file "%s" to cdn. Url "%s"', $fileName, $url), Zend_Log::DEBUG);
            } else {
                $this->getLogger()->log(sprintf('Did not copy uploaded wysiwyg file "%s" to cdn.', $fileName), Zend_Log::ERR);
            }
        } else {
            $this->getLogger()->log(sprintf('Could not find file "%s", neither local nor in cdn', $fileName), Zend_Log::ERR);
        }

        return '"' . $url . '"';
    }
}
