<?php

class Aoe_AmazonCdn_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * OnePica_ImageCdn Amazon S3 bucket name config key
     */
    const XPATH_CONFIG_AMAZON_S3_BUCKET_NAME = 'imagecdn/amazons3/bucket';

    /**
     * @var OnePica_ImageCdn_Model_Adapter_AmazonS3
     */
    protected $adapter;

    /**
     * Get Amazon S3 bucket name from the OnePica_ImageCdn settings
     *
     * @param mixed $store
     * @return string
     */
    public function getAmazonS3BucketName($store = null)
    {
        return (string)Mage::getStoreConfig(self::XPATH_CONFIG_AMAZON_S3_BUCKET_NAME, $store);
    }

    /**
     * Get Amazon S3 CDN adapter (from OnePica_ImageCdn)
     * Requires module OnePica_ImageCdn to be available and enabled.
     * Amazon S3 CDN should be selected in the OnePica_ImageCdn settings and Use CDN .
     *
     * @return false|OnePica_ImageCdn_Model_Adapter_AmazonS3
     */
    public function getAdapter()
    {
        if (is_null($this->adapter)) {
            $this->adapter = false;
            if ($this->isModuleEnabled('OnePica_ImageCdn')) {
                /* @var $imageCdnHelper OnePica_ImageCdn_Helper_Data */
                $imageCdnHelper = Mage::helper('imagecdn');
                /* @var $adapter OnePica_ImageCdn_Model_Adapter_Abstract */
                $adapter = $imageCdnHelper->factory();
                if ($adapter instanceof OnePica_ImageCdn_Model_Adapter_AmazonS3) {
                    $this->adapter = $adapter;
                }
            }
        }

        return $this->adapter;
    }

    /**
     * Clears css/js cache in S3 bucket
     *
     * @return bool
     */
    public function clearCssJsCache()
    {
        $adapter = $this->getAdapter();
        if ($adapter) {
            return $adapter->clearCssJsCache();
        }

        return false;
    }

    /**
     * Get Amazon S3 adapter wrapper
     *
     * @return false|OnePica_ImageCdn_Model_Adapter_AmazonS3_Wrapper
     */
    public function getAdapterWrapper()
    {
        $adapter = $this->getAdapter();
        if ($adapter) {
            $adapterWrapper = $adapter->auth();

            if ($adapterWrapper === false) {
                OnePica_ImageCdn_Helper_Data::log("Can not connect to the bucket:" . $this->getAmazonS3BucketName(), Zend_Log::ERR);
            } else {
                return $adapterWrapper;
            }
        }

        return false;
    }

    /**
     * Checks if a given path is available in the cdn and returns the url
     *
     * @param string $filename
     * @return false|string cdn url or false if no cdn is available
     */
    public function getCdnUrl($filename)
    {
        $cdnUrl     = false;
        $cdnAdapter = $this->getAdapter();
        if ($cdnAdapter && $cdnAdapter->useCdn() && Mage::getSingleton('imagecdn/cache_facade')->get($filename)) {
            $cdnUrl = $cdnAdapter->getUrl($filename);
        }

        return $cdnUrl;
    }

    /**
     * Stores file in cdn and return the cdn url
     *
     * @param string $filename
     * @param string $tempFile
     * @return false|string cdn url or false if no cdn is available
     */
    public function storeInCdn($filename, $tempFile = null)
    {
        $cdnUrl  = false;
        $adapter = $this->getAdapter();
        if ($adapter) {
            if ($tempFile == null) {
                $tempFile = $filename;
            }

            $adapter->save($filename, $tempFile);
            $cdnUrl = $adapter->getUrl($filename);
        }

        return $cdnUrl;
    }

    /**
     * Syncs file from a Amazon S3 bucket to the local file system
     *
     * @param array $pathMapping array(<prefix> => <localPath>,...)
     */
    public function downloadFolders(array $pathMapping)
    {
        $adapterWrapper = $this->getAdapterWrapper();
        if ($adapterWrapper) {
            $bucketName = $this->getAmazonS3BucketName();
            foreach ($pathMapping as $prefix => $localPath) {
                $files = array_keys($adapterWrapper->getBucketContents($bucketName, $prefix));

                $localPath = rtrim($localPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                $this->downloadFiles($files, $prefix, $localPath);
            }
        }
    }

    /**
     * Downloads multiple files from Amazon S3 bucket
     *
     * @param array $files array containing full paths to files on s3
     * @param string $prefix
     * @param string $localPath
     * @return array
     */
    public function downloadFiles($files, $prefix, $localPath)
    {
        $downloadedFiles = array();
        $adapterWrapper  = $this->getAdapterWrapper();
        if ($adapterWrapper) {
            $bucketName = $this->getAmazonS3BucketName();
            $io         = new Varien_Io_File();
            foreach ($files as $s3fileName) {
                if (substr($s3fileName, 0, strlen($prefix)) == $prefix) {
                    $file          = substr($s3fileName, strlen($prefix));
                    $targetFile    = $localPath . $file;
                    $directoryName = dirname($targetFile);
                    if (!is_dir($directoryName)) {
                        $res = $io->mkdir($directoryName);
                        if ($res) {
                            OnePica_ImageCdn_Helper_Data::log('Successfully created dir ' . $directoryName, Zend_Log::DEBUG);
                        } else {
                            OnePica_ImageCdn_Helper_Data::log('Error while creating dir ' . $directoryName, Zend_Log::ERR);
                        }
                    }
                    if (!is_file($targetFile)) {
                        $adapterWrapper->downloadFile($bucketName, $s3fileName, $targetFile);
                        $downloadedFiles[] = $s3fileName;
                        OnePica_ImageCdn_Helper_Data::log('Downloading file ' . $targetFile, Zend_Log::DEBUG);
                    }
                }
            }
        }

        return $downloadedFiles;
    }

    /**
     * Replace wysiwyg urls
     *
     * @param string $html
     * @return string
     */
    public function replaceWysiwygUrls($html)
    {
        $adapter = $this->getAdapter();
        if ($adapter) {
            $html = preg_replace_callback('/"(http[^"]*\/media\/wysiwyg\/[^"]*)"/', array($this, '_replaceCallback'),
                $html
            );
        }

        return $html;
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
        $file = $match[1];

        $relative = preg_replace('/(.*)\/wysiwyg/', '/wysiwyg', $file);
        $url      = $this->getCdnUrl($relative);

        if (!$url) {
            $localFile = Mage::getBaseDir('media') . $relative;
            if (is_file($localFile)) {
                $url = $this->storeInCdn($localFile);
                if ($url) {
                    OnePica_ImageCdn_Helper_Data::log(
                        sprintf('Copied previously uploaded wysiwyg file "%s" to cdn. Url "%s"', $localFile, $url),
                        Zend_Log::DEBUG
                    );
                } else {
                    OnePica_ImageCdn_Helper_Data::log(sprintf('Did not copy uploaded wysiwyg file "%s" to cdn.', $localFile), Zend_Log::ERR);
                }
            } else {
                OnePica_ImageCdn_Helper_Data::log(sprintf('Could not find file "%s", neither local nor in cdn', $relative), Zend_Log::ERR);
            }
        }

        return '"' . $url . '"';
    }
}
