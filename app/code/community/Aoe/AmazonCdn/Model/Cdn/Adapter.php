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

class Aoe_AmazonCdn_Model_Cdn_Adapter
{
    /**@+
     * Cache directories (relative to Mage::getBaseDir('media')
     *
     * @see Mage_Catalog_Model_Product_Image::clearCache()
     * @see Aoe_JsCssTstamp_Model_Package::clearCache()
     *
     * @var string
     */
    const CACHE_DIRECTORY_MEDIA = '/catalog/product/cache/';
    const CACHE_DIRECTORY_CSS   = '/css/';
    const CACHE_DIRECTORY_JS    = '/js/';
    /**@-*/

    /**
     * The maximum number of seconds to allow cURL functions to execute
     */
    const CURLOPT_TIMEOUT = 10;

    /**
     * Sets the value for cURL's CURLOPT_FOLLOWLOCATION. This has been an issue
     * with servers that use the open_basedir PHP config setting. Not all adapters
     * need this functionality, so we can turn it off one-by-one with this value.
     *
     * @return bool
     */
    protected $_curlFollowLocation = false;

    /**
     * Connection handle
     *
     * @var Aoe_AmazonCdn_Model_Cdn_Connector
     */
    protected $_connector = null;

    /**
     * Bucket name
     *
     * @var string
     */
    protected $_bucket;

    /**
     * Amazon access key
     *
     * @var string
     */
    protected $_accessKeyId;

    /**
     * Amazon secret key
     *
     * @var string
     */
    protected $_secretAccessKey;

    /**
     * Class constructor
     *
     * @param string $bucket
     * @param string $accessKeyId
     * @param string $secretAccessKey
     */
    public function __construct($bucket, $accessKeyId, $secretAccessKey)
    {
        $this->_bucket          = $bucket;
        $this->_accessKeyId     = $accessKeyId;
        $this->_secretAccessKey = $secretAccessKey;
    }

    /**
     * Get helper
     *
     * @return Aoe_AmazonCdn_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('aoe_amazoncdn');
    }

    /**
     * Calls the adapter-specific save method and updates the cache
     *
     * @param string $filename path (with filename) from the remote root
     * @param string $tempFile temp file name to upload
     * @return bool
     */
    public function save($filename, $tempFile)
    {
        $relativeFilename = $this->_getRelativePath($filename);
        if ($this->_getConnector()->uploadFile($this->_bucket, $relativeFilename, $tempFile, true)) {
            $this->_getHelper()->getCacheFacade()->add($filename, $tempFile);
            $message = sprintf('Successfully uploaded file "%s" ("%s") to bucket "%s"',
                $relativeFilename,
                $tempFile,
                $this->_bucket
            );
            $this->_getHelper()->getLogger()->log($message);

            return true;
        } else {
            $message = sprintf('Failed uploading file "%s" ("%s") to bucket "%s"',
                $relativeFilename,
                $tempFile,
                $this->_bucket
            );
            $this->_getHelper()->getLogger()->log($message, Zend_Log::CRIT);
        }

        return false;
    }

    /**
     * Calls the adapter-specific remove method and updates the cache
     *
     * @param string $fileName path (with filename) from the remote root
     * @return bool
     */
    public function remove($fileName)
    {
        $relativeFileName = $this->_getRelativePath($fileName);
        if ($this->_getConnector()->deleteObject($this->_bucket, $relativeFileName)) {
            $this->_getHelper()->getCacheFacade()->remove($fileName);
            $message = sprintf('Successfully deleted file "%s" from bucket "%s"', $relativeFileName,
                $this->_bucket
            );
            $this->_getHelper()->getLogger()->log($message);

            return true;
        } else {
            $message = sprintf('Failed removing file "%s" from bucket "%s"', $relativeFileName, $this->_bucket);
            $this->_getHelper()->getLogger()->log($message, Zend_Log::CRIT);
        }

        return false;
    }

    /**
     * Takes a fairly raw file path (typically including the default media folder) and
     * converts it to a relative path. Also reduces double slashes to just one.
     *
     * @param string $fileName
     * @return string
     */
    protected function _getRelativePath($fileName)
    {
        $base     = str_replace('\\', '/', Mage::getBaseDir());
        $fileName = str_replace('\\', '/', $fileName);

        return ltrim(str_replace($base, '', $fileName), '/');
    }

    /**
     * Create Varien_Http_Adapter_Curl with default params
     *
     * @param string $url
     * @return Varien_Http_Adapter_Curl
     */
    protected function _createdCurlAdapterWithDefaultOptions($url)
    {
        $curlAdapter = new Varien_Http_Adapter_Curl();
        $curlAdapter->setConfig(array('timeout' => self::CURLOPT_TIMEOUT));
        $curlAdapter->addOption(CURLOPT_URL, $url);
        $curlAdapter->addOption(CURLOPT_RETURNTRANSFER, true);
        $curlAdapter->addOption(CURLOPT_FOLLOWLOCATION, $this->_curlFollowLocation);
        $curlAdapter->addOption(CURLOPT_SSL_VERIFYPEER, false);

        return $curlAdapter;
    }

    /**
     * Check whether file exists on remote
     *
     * @param string $filename
     * @param bool $verifySize
     * @return bool
     */
    public function fileExistOnRemote($filename, $verifySize)
    {
        $logger = $this->_getHelper()->getLogger();
        $url = $this->getUrl($filename);
        $logger->log(sprintf('Full url on remote for "%s" local file is: "%s"', $filename, $url));

        $curlAdapter = $this->_createdCurlAdapterWithDefaultOptions($url);
        $curlAdapter->addOption(CURLOPT_HEADER, true);
        $curlAdapter->addOption(CURLOPT_NOBODY, true);
        // using deprecated method because of bugs in Varien_Http_Adapter_Curl
        $curlAdapter->connect(null)->read();

        $httpCode = $curlAdapter->getInfo(CURLINFO_HTTP_CODE);
        $size     = $curlAdapter->getInfo(CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        $curlAdapter->close();

        if ($httpCode == 200 && (!$verifySize || $size)) {
            $logger->log(
                sprintf('Check if file "%s" exists: Found on remote.', $filename)
            );
            $result = true;
        } else {
            $logger->log(
                sprintf('Check if file "%s" exists: File not found on remote. (HTTP status code: %s)', $filename,
                    $httpCode
                )
            );
            $result = false;
        }

        return $result;
    }

    /**
     * Syncs file from a Amazon S3 bucket to the local file system
     *
     * @param string $path
     */
    public function downloadFolder($path)
    {
        $path   = rtrim($path, DS) . DS;
        $prefix = $this->_getRelativePath($path);
        $files  = $this->_getConnector()->getBucketContents($this->_bucket, $prefix, true);

        $this->_downloadFiles($files, $prefix, $path);
    }

    /**
     * Downloads multiple files from Amazon S3 bucket
     *
     * @param array $files array containing full paths to files on s3
     * @param string $prefix
     * @param string $localPath
     */
    protected function _downloadFiles($files, $prefix, $localPath)
    {
        $logger = $this->_getHelper()->getLogger();
        $io     = new Varien_Io_File();
        foreach ($files as $s3fileName) {
            if (substr($s3fileName, 0, strlen($prefix)) == $prefix) {
                // skip folders (which ends with /)
                if (substr($s3fileName, -1) != '/') {
                    $file          = substr($s3fileName, strlen($prefix));
                    $targetFile    = $localPath . $file;
                    $directoryName = dirname($targetFile);
                    if (!is_dir($directoryName)) {
                        if ($io->mkdir($directoryName)) {
                            $logger->log('Successfully created dir ' . $directoryName, Zend_Log::DEBUG);
                        } else {
                            $logger->log('Error while creating dir ' . $directoryName, Zend_Log::ALERT);
                        }
                    }
                    if (!is_file($targetFile)) {
                        $this->_getConnector()->downloadFile($this->_bucket, $s3fileName, $targetFile);
                        $logger->log('Downloading file ' . $targetFile, Zend_Log::DEBUG);
                    }
                }
            }
        }
    }

    /**
     * Write content to path (and create missing directories if needed)
     *
     * @param string $fileNameWithPath
     * @param string $content
     */
    protected function _writeFile($fileNameWithPath, $content)
    {
        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $io->dirname($fileNameWithPath)));
        $result = $io->write($fileNameWithPath, $content, 0664);
        if (!$result || !$io->fileExists($fileNameWithPath)) {
            Mage::throwException(sprintf('Error while writing content to file "%s".', $fileNameWithPath));
        }
    }

    /**
     * Download file with given $filename from remote and save it locally
     *
     * @param string $filename
     * @return bool
     */
    public function downloadFile($filename)
    {
        $logger = $this->_getHelper()->getLogger();
        $url = $this->getUrl($filename);
        $logger->log(sprintf('Downloading file "%s" from remote', $url));

        $curlAdapter = $this->_createdCurlAdapterWithDefaultOptions($url);
        $curlAdapter->addOption(CURLOPT_HEADER, false);

        // using deprecated method because of bugs in Varien_Http_Adapter_Curl
        $fileContent = $curlAdapter->connect(null)->read();

        $httpCode = $curlAdapter->getInfo(CURLINFO_HTTP_CODE);
        $size     = $curlAdapter->getInfo(CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        if ($httpCode == 200 && $size) {
            $logger->log(
                sprintf('Downloading file "%s" from remote: Downloaded from remote.', $filename)
            );
            $result = true;

            $this->_writeFile($filename, $fileContent);
            $this->_getHelper()->getCacheFacade()->add($filename);
        } else {
            $logger->log(
                sprintf('Downloading file "%s" from remote: File not found on remote. (HTTP status code: %s)',
                    $filename, $httpCode
                )
            );
            $result = false;
        }

        $curlAdapter->close();

        return $result;
    }

    /**
     * Verify Amazon CDN credentials
     *
     * @return Aoe_AmazonCdn_Model_Cdn_Connector
     */
    protected function _getConnector()
    {
        if ($this->_connector === null) {
            $error     = false;
            $connector = new Aoe_AmazonCdn_Model_Cdn_Connector($this->_accessKeyId, $this->_secretAccessKey);
            $buckets   = $connector->listBuckets();
            if ($buckets === false) {
                $error = sprintf("Can't connect to Amazon S3 with auth key '%s'", $this->_accessKeyId);
            } elseif (!in_array($this->_bucket, $buckets)) {
                $error = sprintf("Bucket '%s' doesn't exists or not enough rights to connect to it with auth key '%s'",
                    $this->_bucket, $this->_accessKeyId
                );
            } else {
                $this->_getHelper()->getLogger()->log(
                    sprintf("Successfully connected to bucket '%s' with auth key '%s'", $this->_bucket, $this->_accessKeyId)
                );
            }

            if ($error) {
                $this->_getHelper()->getLogger()->log($error, Zend_Log::EMERG);
                throw new InvalidArgumentException($error);
            }
            $this->_connector = $connector;
        }

        return $this->_connector;
    }

    /**
     * Delete folder $folder in bucket
     *
     * @param string $folder
     * @return bool
     */
    public function deleteFolder($folder)
    {
        if ($this->_getConnector()->deleteFolder($this->_bucket, $folder)) {
            $this->_getHelper()->getLogger()->log(
                sprintf('Deleted folder "%s" in bucket "%s"', $folder, $this->_bucket)
            );

            return true;
        } else {
            $this->_getHelper()->getLogger()->log(
                sprintf('Unable to delete folder "%s" in bucket "%s"', $folder, $this->_bucket)
            );
        }

        return false;
    }

    /**
     * Clear image cache
     */
    public function clearImageCache()
    {
        $mediaCacheFolder = $this->_getRelativePath(Mage::getBaseDir('media') . self::CACHE_DIRECTORY_MEDIA);
        if ($this->deleteFolder($mediaCacheFolder)) {
            $this->_getHelper()->getCacheFacade()->flushImages();

            return true;
        }

        return false;
    }

    /**
     * Clear css/js cache
     *
     * @return bool
     */
    public function clearCssJsCache()
    {
        $cssCacheFolder = $this->_getRelativePath(Mage::getBaseDir('media') . self::CACHE_DIRECTORY_CSS);
        $jsCacheFolder = $this->_getRelativePath(Mage::getBaseDir('media') . self::CACHE_DIRECTORY_JS);
        if ($this->deleteFolder($cssCacheFolder) && $this->deleteFolder($jsCacheFolder)) {
            $this->_getHelper()->getCacheFacade()->flushCssJs();

            return true;
        }

        return false;
    }

    /**
     * Just use CloudFront domain, which should be correctly set in web/secure/base_media_url and
     * web/unsecure/base_media_url
     *
     * @param string $filename path (with filename) from the CDN root
     * @return string
     */
    public function getUrl($filename)
    {
        $jsBaseDir = Mage::getBaseDir() . DS . 'js';
        if (strpos($filename, $jsBaseDir) === 0) {
            $path = str_replace($jsBaseDir . DS, "", $filename);

            return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS) . str_replace(DS, '/', $path);
        }

        $skinBaseDir = Mage::getBaseDir('skin');
        if (strpos($filename, $skinBaseDir) === 0) {
            $path = str_replace($skinBaseDir . DS, "", $filename);

            return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN) . str_replace(DS, '/', $path);
        }

        $mediaBaseDir = Mage::getBaseDir('media');
        $path    = str_replace($mediaBaseDir . DS, "", $filename);

        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . str_replace(DS, '/', $path);
    }
}
