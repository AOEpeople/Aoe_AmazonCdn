<?php
/**
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoe.com>
 */

class Aoe_AmazonCdn_Model_Cache_Facade
{
    /**
     * Cache for fileExists() method - no need to query db or remote each time
     *
     * @var array
     */
    protected static $_cache = array();

    /**
     * Should we test to see if file size greater than zero?
     *
     * @var bool
     */
    protected $_verifySize;

    /**
     * Cache life time
     *
     * @var float
     */
    protected $_cacheTtl;

    /**
     * Cache life time
     *
     * @var Aoe_AmazonCdn_Model_Cdn_Adapter
     */
    protected $_cdnAdapter;

    /**
     * @var Aoe_AmazonCdn_Model_Cache
     */
    protected $_cacheModel;

    /**
     * Class constructor
     *
     * @param Aoe_AmazonCdn_Model_Cdn_Adapter $adapter
     * @param Aoe_AmazonCdn_Model_Cache $cacheModel
     * @param bool $verifySize
     * @param float $cacheTtl
     */
    public function __construct(Aoe_AmazonCdn_Model_Cdn_Adapter $adapter, Aoe_AmazonCdn_Model_Cache $cacheModel,
        $verifySize, $cacheTtl
    ) {
        $this->_cdnAdapter = $adapter;
        $this->_cacheModel = $cacheModel;
        $this->_verifySize = (bool)$verifySize;
        $this->_cacheTtl   = (float)$cacheTtl;
    }

    /**
     * Get cached information about file if it is present and not expired
     *
     * @param string $filename
     * @return array|bool
     */
    public function get($filename)
    {
        if (isset(self::$_cache[$filename])) {
            return self::$_cache[$filename];
        }

        $url        = $this->_cdnAdapter->getUrl($filename);
        $cachedInDb = $this->_cacheModel->get($url);

        if ($cachedInDb) {
            $ttlSeconds = $this->_cacheTtl * 60;
            $cacheTtl   = rand(intval($ttlSeconds * 0.9), intval($ttlSeconds * 1.1));
            $maxTime    = $cachedInDb['last_checked'] + $cacheTtl * 60;

            if (time() < $maxTime) {
                self::$_cache[$filename] = $cachedInDb;
                return $cachedInDb;
            } else {
                return $this->_fileExistsOnRemote($filename);
            }
        } else {
            return $this->_fileExistsOnRemote($filename);
        }
    }

    /**
     * Check whether file exists on remote
     *
     * @param string $filename
     * @return bool
     */
    protected function _fileExistsOnRemote($filename)
    {
        if ($this->_cdnAdapter->fileExistOnRemote($filename, $this->_verifySize)) {
            self::$_cache[$filename] = true;

            return true;
        }

        return false;
    }

    /**
     * Add file to cache
     *
     * @param string $filename
     * @param string $tempFile
     */
    public function add($filename, $tempFile = null)
    {
        if ($tempFile == null) {
            $tempFile = $filename;
        }
        list($width, $height, $type) = getimagesize($tempFile);
        $url = $this->_cdnAdapter->getUrl($filename);
        self::$_cache[$filename] = $this->_cacheModel->add($url, $width, $height, $type);
    }

    /**
     * Remove file from cache if it is present
     *
     * @param string $filename
     * @return bool
     */
    public function remove($filename)
    {
        $this->_cacheModel->remove($this->_cdnAdapter->getUrl($filename));
        unset(self::$_cache[$filename]);
    }

    /**
     * Walk through the entire cache to find and delete all expired entries
     */
    public function clearExpiredItems()
    {
        $this->_cacheModel->clearExpiredItems($this->_cacheTtl);
    }

    /**
     * Delete the entire cache
     */
    public function flush()
    {
        self::$_cache = array();
        $this->_cacheModel->flush();
    }

    /**
     * Delete css/js cache
     */
    public function flushCssJs()
    {
        self::$_cache = array();
        $this->_cacheModel->flushCssJs();
    }

    /**
     * Delete images cache
     */
    public function flushImages()
    {
        self::$_cache = array();
        $this->_cacheModel->flushImages();
    }
}
