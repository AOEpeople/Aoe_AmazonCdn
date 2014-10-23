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

use Aws\S3\S3Client;

class Aoe_AmazonCdn_Model_Cdn_Connector
{

    /**
     * Access key.
     *
     * @var string
     */
    protected $_key;

    /**
     * Secret ID.
     *
     * @var string.
     */
    protected $_secret;

    /**
     * Cached S3 client.
     *
     * @var S3Client
     */
    protected $_client = null;

    /**
     * Sets up our credentials.
     *
     * @param string $key
     * @param string $secret
     */
    public function __construct($key, $secret)
    {
        $this->_key    = $key;
        $this->_secret = $secret;
    }

    /**
     * Gets the cached client object.
     *
     * @return S3Client
     */
    protected function _getClient()
    {
        if ($this->_client === null) {
            $this->_client = S3Client::factory(array(
                'key'    =>  $this->_key,
                'secret' =>  $this->_secret
            ));
        }
        return $this->_client;
    }

    /**
     * Get helper.
     *
     * @return Aoe_AmazonCdn_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('aoe_amazoncdn');
    }

    /**
     * List buckets.
     *
     * @return array
     */
    public function listBuckets()
    {
        $result = $this->_getClient()->listBuckets();
        $buckets = array();
        foreach ($result['Buckets'] as $bucket) {
            $buckets[] = $bucket['Name'];
        }
        return $buckets;
    }

    /**
     * Upload a single file.
     *
     * @param string $bucketName
     * @param string $key
     * @param string $filePath
     * @param boolean $public
     * @return boolean
     */
    public function uploadFile($bucketName, $key, $filePath, $public = false)
    {
        $logger = $this->_getHelper()->getLogger();
        if (!file_exists($filePath)) {
            $logger->log("File $filePath not found! Not uploaded!", Zend_Log::ERR);
            return false;
        }
        try {
            $this->_getClient()->upload(
                $bucketName,
                $key,
                fopen($filePath, 'r+'),
                $public ? 'public-read' : 'private'
            );
        } catch (Exception $e) {
            $logger->log('Error while uploading file. Result is: ' . $e->getMessage(),
                Zend_Log::ERR
            );
            return false;
        }
        return true;
    }

    /**
     * Lists the contents of a bucket with an optional prefix.
     *
     * @param string $name
     * @param string|null $prefix
     * @param boolean $onlyNames
     * @param int|null $maxKeys
     * @param string|null $marker
     * @param string|null $delimiter
     * @return array
     */
    public function getBucketContents($name, $prefix = null, $onlyNames = false, $maxKeys = null,
        $marker = null, $delimiter = null)
    {
        $files = array();
        try {
            $result = $this->_getClient()->listObjects(array(
                'Bucket'    => $name,
                'Prefix'    => $prefix,
                'MaxKeys'   => $maxKeys,
                'Delimiter' => $delimiter,
                'Marker'    => $marker
            ));
        } catch (Exception $e) {
            $this->_getHelper()->getLogger()
                ->log(sprintf("Could not list objects for '%s': %s", $name, $e->getMessage()));
            return $files;
        }

        $data = $result->toArray();
        foreach ($data['Contents'] as $item) {
            if ($onlyNames) {
                array_push($files, $item['Key']);
            } else {
                $files[$item['Key']] = array(
                    'LastModified' => $item['LastModified'],
                    'ETag'         => $item['ETag'],
                    'Size'         => $item['Size']
                );
            }
        }
        return $files;
    }

    /**
     * Deletes an object.
     *
     * @param type $bucketName
     * @param type $key
     * @return boolean
     */
    public function deleteObject($bucketName, $key)
    {
        try {
            $this->_getClient()->deleteObject(array(
                'Bucket' => $bucketName,
                'Key'    => $key,
            ));
        } catch (Exception $e) {
            $this->_getHelper()->getLogger()
                ->log(sprintf("Could not delete object '%s': %s", $key, $e->getMessage()));
            return false;
        }
        return true;
    }

    /**
     * Deletes a folder.
     *
     * @param string $bucketName
     * @param string $key
     * @return boolean
     */
    public function deleteFolder($bucketName, $key)
    {
        return $this->deleteObject($bucketName, $key);
    }

    /**
     * Downloads a file.
     *
     * @param string $bucketName
     * @param string $key
     * @param string $filePath
     * @return boolean
     */
    public function downloadFile($bucketName, $key, $filePath)
    {
        try {
            $this->_getClient()->getObject(array(
                'Bucket' => $bucketName,
                'Key'    => $key,
                'SaveAs' => $filePath
            ));
        } catch (Exception $e) {
            $this->_getHelper()->getLogger()
                ->log(sprintf("Could not download file '%s': %s", $key, $e->getMessage()));
            return false;
        }
        return true;
    }

}
