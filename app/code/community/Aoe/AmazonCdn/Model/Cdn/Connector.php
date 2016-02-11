<?php

// This is a fresh rewrite of the previous S3 class using PHP 5.
// All transfers are done using PHP's native curl extension rather
// than piping everything to the command line as before. (That was
// a dirty hack in hindsight.) Copying S3 objects is now supported
// as well. If you'd like to access the previous version, you may do
// so here: http://code.google.com/p/php-aws/source/browse/branches/original-stable/

//INTEGRATION NOTE
//The normal constructor had to be changed to fit with Magento's Mage::getModel
//function that only allows on parameter passing that can be an array of values.
//
// Normal:   public function __construct($key, $private_key, $host = 's3.amazonaws.com')
// Modified: public function __construct($params)

//INTEGRATION NOTE
//The AWS class only worked with non-virtual host buckets (currently US only). The code
//was modified to handle EU and other virtual host buckets. Author suggest moving to http://tarzan-aws.com/

class Aoe_AmazonCdn_Model_Cdn_Connector
{
    /**
     * Max allowed number of keys deleted in one batch delete API request
     *
     * @link http://docs.aws.amazon.com/AmazonS3/latest/dev/DeletingMultipleObjectsUsingPHPSDK.html
     */
    const MAX_BATCH_DELETE_KEYS_NUMBER = 1000;

    /**
     * Multi object delete root XML template
     */
    const XML_MULTI_OBJECT_DELETE = '<?xml version="1.0" encoding="utf-8"?><Delete/>';

    protected static $mimeTypes = array(
        "323"     => "text/h323",
        "acx"     => "application/internet-property-stream",
        "ai"      => "application/postscript",
        "aif"     => "audio/x-aiff",
        "aifc"    => "audio/x-aiff",
        "aiff"    => "audio/x-aiff",
        "asf"     => "video/x-ms-asf",
        "asr"     => "video/x-ms-asf",
        "asx"     => "video/x-ms-asf",
        "au"      => "audio/basic",
        "avi"     => "video/quicktime",
        "axs"     => "application/olescript",
        "bas"     => "text/plain",
        "bcpio"   => "application/x-bcpio",
        "bin"     => "application/octet-stream",
        "bmp"     => "image/bmp",
        "c"       => "text/plain",
        "cat"     => "application/vnd.ms-pkiseccat",
        "cdf"     => "application/x-cdf",
        "cer"     => "application/x-x509-ca-cert",
        "class"   => "application/octet-stream",
        "clp"     => "application/x-msclip",
        "cmx"     => "image/x-cmx",
        "cod"     => "image/cis-cod",
        "cpio"    => "application/x-cpio",
        "crd"     => "application/x-mscardfile",
        "crl"     => "application/pkix-crl",
        "crt"     => "application/x-x509-ca-cert",
        "csh"     => "application/x-csh",
        "css"     => "text/css",
        "dcr"     => "application/x-director",
        "der"     => "application/x-x509-ca-cert",
        "dir"     => "application/x-director",
        "dll"     => "application/x-msdownload",
        "dms"     => "application/octet-stream",
        "doc"     => "application/msword",
        "dot"     => "application/msword",
        "dvi"     => "application/x-dvi",
        "dxr"     => "application/x-director",
        "eps"     => "application/postscript",
        "etx"     => "text/x-setext",
        "evy"     => "application/envoy",
        "exe"     => "application/octet-stream",
        "fif"     => "application/fractals",
        "flr"     => "x-world/x-vrml",
        "gif"     => "image/gif",
        "gtar"    => "application/x-gtar",
        "gz"      => "application/x-gzip",
        "h"       => "text/plain",
        "hdf"     => "application/x-hdf",
        "hlp"     => "application/winhlp",
        "hqx"     => "application/mac-binhex40",
        "hta"     => "application/hta",
        "htc"     => "text/x-component",
        "htm"     => "text/html",
        "html"    => "text/html",
        "htt"     => "text/webviewhtml",
        "ico"     => "image/x-icon",
        "ief"     => "image/ief",
        "iii"     => "application/x-iphone",
        "ins"     => "application/x-internet-signup",
        "isp"     => "application/x-internet-signup",
        "jfif"    => "image/pipeg",
        "jpe"     => "image/jpeg",
        "jpeg"    => "image/jpeg",
        "jpg"     => "image/jpeg",
        "js"      => "application/x-javascript",
        "latex"   => "application/x-latex",
        "lha"     => "application/octet-stream",
        "lsf"     => "video/x-la-asf",
        "lsx"     => "video/x-la-asf",
        "lzh"     => "application/octet-stream",
        "m13"     => "application/x-msmediaview",
        "m14"     => "application/x-msmediaview",
        "m3u"     => "audio/x-mpegurl",
        "man"     => "application/x-troff-man",
        "mdb"     => "application/x-msaccess",
        "me"      => "application/x-troff-me",
        "mht"     => "message/rfc822",
        "mhtml"   => "message/rfc822",
        "mid"     => "audio/mid",
        "mny"     => "application/x-msmoney",
        "mov"     => "video/quicktime",
        "movie"   => "video/x-sgi-movie",
        "mp2"     => "video/mpeg",
        "mp3"     => "audio/mpeg",
        "mpa"     => "video/mpeg",
        "mpe"     => "video/mpeg",
        "mpeg"    => "video/mpeg",
        "mpg"     => "video/mpeg",
        "mpp"     => "application/vnd.ms-project",
        "mpv2"    => "video/mpeg",
        "ms"      => "application/x-troff-ms",
        "mvb"     => "application/x-msmediaview",
        "nws"     => "message/rfc822",
        "oda"     => "application/oda",
        "p10"     => "application/pkcs10",
        "p12"     => "application/x-pkcs12",
        "p7b"     => "application/x-pkcs7-certificates",
        "p7c"     => "application/x-pkcs7-mime",
        "p7m"     => "application/x-pkcs7-mime",
        "p7r"     => "application/x-pkcs7-certreqresp",
        "p7s"     => "application/x-pkcs7-signature",
        "pbm"     => "image/x-portable-bitmap",
        "pdf"     => "application/pdf",
        "pfx"     => "application/x-pkcs12",
        "pgm"     => "image/x-portable-graymap",
        "pko"     => "application/ynd.ms-pkipko",
        "pma"     => "application/x-perfmon",
        "pmc"     => "application/x-perfmon",
        "pml"     => "application/x-perfmon",
        "pmr"     => "application/x-perfmon",
        "pmw"     => "application/x-perfmon",
        "png"     => "image/png",
        "pnm"     => "image/x-portable-anymap",
        "pot"     => "application/vnd.ms-powerpoint",
        "ppm"     => "image/x-portable-pixmap",
        "pps"     => "application/vnd.ms-powerpoint",
        "ppt"     => "application/vnd.ms-powerpoint",
        "prf"     => "application/pics-rules",
        "ps"      => "application/postscript",
        "pub"     => "application/x-mspublisher",
        "qt"      => "video/quicktime",
        "ra"      => "audio/x-pn-realaudio",
        "ram"     => "audio/x-pn-realaudio",
        "ras"     => "image/x-cmu-raster",
        "rgb"     => "image/x-rgb",
        "rmi"     => "audio/mid",
        "roff"    => "application/x-troff",
        "rtf"     => "application/rtf",
        "rtx"     => "text/richtext",
        "scd"     => "application/x-msschedule",
        "sct"     => "text/scriptlet",
        "setpay"  => "application/set-payment-initiation",
        "setreg"  => "application/set-registration-initiation",
        "sh"      => "application/x-sh",
        "shar"    => "application/x-shar",
        "sit"     => "application/x-stuffit",
        "snd"     => "audio/basic",
        "spc"     => "application/x-pkcs7-certificates",
        "spl"     => "application/futuresplash",
        "src"     => "application/x-wais-source",
        "sst"     => "application/vnd.ms-pkicertstore",
        "stl"     => "application/vnd.ms-pkistl",
        "stm"     => "text/html",
        "svg"     => "image/svg+xml",
        "sv4cpio" => "application/x-sv4cpio",
        "sv4crc"  => "application/x-sv4crc",
        "t"       => "application/x-troff",
        "tar"     => "application/x-tar",
        "tcl"     => "application/x-tcl",
        "tex"     => "application/x-tex",
        "texi"    => "application/x-texinfo",
        "texinfo" => "application/x-texinfo",
        "tgz"     => "application/x-compressed",
        "tif"     => "image/tiff",
        "tiff"    => "image/tiff",
        "tr"      => "application/x-troff",
        "trm"     => "application/x-msterminal",
        "tsv"     => "text/tab-separated-values",
        "txt"     => "text/plain",
        "uls"     => "text/iuls",
        "ustar"   => "application/x-ustar",
        "vcf"     => "text/x-vcard",
        "vrml"    => "x-world/x-vrml",
        "wav"     => "audio/x-wav",
        "wcm"     => "application/vnd.ms-works",
        "wdb"     => "application/vnd.ms-works",
        "wks"     => "application/vnd.ms-works",
        "wmf"     => "application/x-msmetafile",
        "wps"     => "application/vnd.ms-works",
        "wri"     => "application/x-mswrite",
        "wrl"     => "x-world/x-vrml",
        "wrz"     => "x-world/x-vrml",
        "xaf"     => "x-world/x-vrml",
        "xbm"     => "image/x-xbitmap",
        "xla"     => "application/vnd.ms-excel",
        "xlc"     => "application/vnd.ms-excel",
        "xlm"     => "application/vnd.ms-excel",
        "xls"     => "application/vnd.ms-excel",
        "xlt"     => "application/vnd.ms-excel",
        "xlw"     => "application/vnd.ms-excel",
        "xof"     => "x-world/x-vrml",
        "xpm"     => "image/x-xpixmap",
        "xwd"     => "image/x-xwindowdump",
        "z"       => "application/x-compress",
        "zip"     => "application/zip"
    );

    protected $key;
    protected $privateKey;
    protected $host;
    protected $date;
    protected $curlInfo;

    /**
     * @var array additional headers to be set while uploading
     */
    protected $headers = array();

    public function __construct($key, $privateKey, $host = 's3.amazonaws.com')
    {
        $this->key        = $key;
        $this->privateKey = $privateKey;
        $this->host       = $host;
        $this->date       = gmdate('D, d M Y H:i:s T');

        return true;
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

    public function getBucketLocation($name)
    {
        $request = array('verb' => 'GET', 'resource' => "/$name/?location");
        $result  = $this->sendRequest($request);
        $xml     = simplexml_load_string($result);

        if ($xml === false) {
            return false;
        }

        return (string)$xml->LocationConstraint;
    }

    public function getBucketContents($name, $prefix = null, $only_names = false, $max_keys = null, $marker = null,
        $delimiter = null
    ) {
        $contents = array();

        do {
            $q = array();
            if (!is_null($prefix)) {
                $q[] = 'prefix=' . $prefix;
            }
            if (!is_null($marker)) {
                $q[] = 'marker=' . $marker;
            }
            if (!is_null($delimiter)) {
                $q[] = 'delimeter=' . $delimiter;
            }
            if (!is_null($max_keys)) {
                $q[] = 'max-keys=' . $max_keys;
            }
            $q = implode('&', $q);
            if (strlen($q) > 0) {
                $q = '?' . $q;
            }

            $request = array('verb' => 'GET', 'resource' => "/$q", 'bucket' => $name);
            $result  = $this->sendRequest($request);
            $xml     = simplexml_load_string($result);

            if ($xml === false) {
                return false;
            }

            foreach ($xml->Contents as $item) {
                if ($only_names) {
                    array_push($contents, (string)$item->Key);
                } else {
                    $contents[(string)$item->Key] = array('LastModified' => (string)$item->LastModified,
                                                          'ETag'         => (string)$item->ETag,
                                                          'Size'         => (string)$item->Size
                    );
                }
            }

            $marker = (string)$xml->Marker;
        } while ((string)$xml->IsTruncated == 'true' && is_null($max_keys));

        return $contents;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function uploadFile($bucket_name, $s3_path, $fs_path, $web_accessible = false, $headers = array())
    {
        $logger = $this->_getHelper()->getLogger();

        // Some useful headers you can set manually by passing in an associative array...
        // Cache-Control
        // Content-Type
        // Content-Disposition (alternate filename to present during web download)
        // Content-Encoding
        // x-amz-meta-*
        // x-amz-acl (protected, public-read, public-read-write, authenticated-read)
        $this->setHeaders($headers);

        if (!is_file($fs_path)) {
            $logger->log("File $fs_path not found! Not uploaded!", Zend_Log::ERR);

            return false;
        }

        $request = array('verb'        => 'PUT',
                         'bucket'      => $bucket_name,
                         'resource'    => "/$s3_path",
                         'content-md5' => $this->base64(md5_file($fs_path))
        );

        $fh        = fopen($fs_path, 'r');
        $curl_opts = array('CURLOPT_PUT'           => true,
                           'CURLOPT_INFILE'        => $fh,
                           'CURLOPT_INFILESIZE'    => filesize($fs_path),
                           'CURLOPT_CUSTOMREQUEST' => 'PUT'
        );

        $headers = $this->getHeaders();

        $headers['Content-MD5'] = $request['content-md5'];

        if ($web_accessible === true && !isset($headers['x-amz-acl'])) {
            $headers['x-amz-acl'] = 'public-read';
        }

        if (!isset($headers['Content-Type'])) {
            $ext                     = pathinfo($fs_path, PATHINFO_EXTENSION);
            $headers['Content-Type'] = isset(self::$mimeTypes[$ext]) ? self::$mimeTypes[$ext] : 'application/octet-stream';
        }
        $request['content-type'] = $headers['Content-Type'];

        $result = $this->sendRequest($request, $headers, $curl_opts);
        fclose($fh);
        if ($this->curlInfo['http_code'] != '200') {
            $logger->log('Error while uploading file. Result is: ' . var_export($result, 1),
                Zend_Log::ERR
            );
        }

        return $this->curlInfo['http_code'] == '200';
    }

    public function deleteObject($bucket_name, $s3_path)
    {
        $request = array('verb' => 'DELETE', 'bucket' => $bucket_name, 'resource' => "/$s3_path");
        $this->sendRequest($request);

        return $this->curlInfo['http_code'] == '204';
    }

    public function deleteObjects($bucket_name, array $objects)
    {
        $request = array(
            'verb'         => 'POST',
            'bucket'       => $bucket_name,
            'resource'     => '/?delete',
            'content-type' => 'application/octet-stream'
        );
        $xml = new SimpleXMLElement(self::XML_MULTI_OBJECT_DELETE);

        // add the objects
        foreach ($objects as $object) {
            $xmlObject = $xml->addChild('Object');
            $node = $xmlObject->addChild('Key');
            $node[0] = $object;
        }
        $xml->addChild('Quiet', true);
        $request['body'] = $xml->asXML();
        $this->sendRequest($request);

        return $this->curlInfo['http_code'] == '200';
    }

    /**
     * @param string $bucket_name
     * @param string $prefix
     * @return bool
     */
    public function deleteFolder($bucket_name, $prefix)
    {
        while ($objects = $this->getBucketContents($bucket_name, $prefix, true, self::MAX_BATCH_DELETE_KEYS_NUMBER)) {
            $result = $this->deleteObjects($bucket_name, $objects);
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    public function copyObject($bucket_name, $s3_path, $dest_bucket_name, $dest_s3_path)
    {
        $request = array('verb' => 'PUT', 'bucket' => $dest_bucket_name, 'resource' => "/$dest_s3_path");
        $headers = array('x-amz-copy-source' => "/$bucket_name/$s3_path");
        $result  = $this->sendRequest($request, $headers);

        if ($this->curlInfo['http_code'] != '200') {
            return false;
        }

        $xml = simplexml_load_string($result);
        if ($xml === false) {
            return false;
        }

        return isset($xml->LastModified);
    }

    public function getObjectInfo($bucket_name, $s3_path)
    {
        $request   = array('verb' => 'HEAD', 'bucket' => $bucket_name, 'resource' => "/$s3_path");
        $curl_opts = array('CURLOPT_HEADER' => true, 'CURLOPT_NOBODY' => true);
        $result    = $this->sendRequest($request, null, $curl_opts);
        $xml       = @simplexml_load_string($result);

        if ($xml !== false) {
            return false;
        }

        preg_match_all('/^(\S*?): (.*?)$/ms', $result, $matches);
        $info = array();
        for ($i = 0; $i < count($matches[1]); $i++) {
            $info[$matches[1][$i]] = $matches[2][$i];
        }

        if (!isset($info['Last-Modified'])) {
            return false;
        }

        return $info;
    }

    public function downloadFile($bucket_name, $s3_path, $fs_path)
    {
        $request = array('verb' => 'GET', 'bucket' => $bucket_name, 'resource' => "/$s3_path");

        $fh        = fopen($fs_path, 'w');
        $curl_opts = array('CURLOPT_FILE' => $fh);

        $headers = array();

        $this->sendRequest($request, $headers, $curl_opts);
        fclose($fh);

        return $this->curlInfo['http_code'] == '200';
    }

    public function getAuthenticatedURLRelative($bucket_name, $s3_path, $seconds_till_expires = 3600)
    {
        return $this->getAuthenticatedURL($bucket_name, $s3_path, gmmktime() + $seconds_till_expires);
    }

    public function getAuthenticatedURL($bucket_name, $s3_path, $expires_on)
    {
        // $expires_on must be a GMT Unix timestamp

        $request   = array('verb' => 'GET', 'resource' => "/$bucket_name/$s3_path", 'date' => $expires_on);
        $signature = urlencode($this->signature($request));

        $url = sprintf("http://%s.s3.amazonaws.com/%s?AWSAccessKeyId=%s&Expires=%s&Signature=%s",
            $bucket_name,
            $s3_path,
            $this->key,
            $expires_on,
            $signature);

        return $url;
    }

    protected function sendRequest($request, $headers = null, $curl_opts = null)
    {
        if (is_null($headers)) {
            $headers = array();
        }

        $headers['Date']          = $this->date;
        if (isset($request['content-type'])) {
            $headers['Content-Type'] = $request['content-type'];
        }
        if (isset($request['body'])) {
            $request['content-md5'] = $this->base64(md5($request['body']));
            $headers['Content-MD5'] = $request['content-md5'];
            $headers['Content-Length'] = strlen($request['body']);
        }
        $headers['Authorization'] = 'AWS ' . $this->key . ':' . $this->signature($request, $headers);
        foreach ($headers as $k => $v) {
            $headers[$k] = "$k: $v";
        }

        $host = isset($request['bucket']) ? $request['bucket'] . '.' . $this->host : $this->host;

        $uri = 'http://' . $host . $request['resource'];
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        if ($request['verb'] == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request['verb']);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (isset($request['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request['body']);
        }

        if (is_array($curl_opts)) {
            foreach ($curl_opts as $k => $v) {
                curl_setopt($ch, constant($k), $v);
            }
        }

        $result         = curl_exec($ch);
        $this->curlInfo = curl_getinfo($ch);
        curl_close($ch);

        return $result;
    }

    protected function signature($request, $headers = null)
    {
        if (is_null($headers)) {
            $headers = array();
        }

        $CanonicalizedAmzHeadersArr = array();
        $CanonicalizedAmzHeadersStr = '';
        foreach ($headers as $k => $v) {
            $k = strtolower($k);

            if (substr($k, 0, 5) != 'x-amz') {
                continue;
            }

            if (isset($CanonicalizedAmzHeadersArr[$k])) {
                $CanonicalizedAmzHeadersArr[$k] .= ',' . trim($v);
            } else {
                $CanonicalizedAmzHeadersArr[$k] = trim($v);
            }
        }
        ksort($CanonicalizedAmzHeadersArr);

        foreach ($CanonicalizedAmzHeadersArr as $k => $v) {
            $CanonicalizedAmzHeadersStr .= "$k:$v\n";
        }

        if (isset($request['bucket'])) {
            $request['resource'] = '/' . $request['bucket'] . $request['resource'];
        }

        $str = $request['verb'] . "\n";
        $str .= isset($request['content-md5']) ? $request['content-md5'] . "\n" : "\n";
        $str .= isset($request['content-type']) ? $request['content-type'] . "\n" : "\n";
        $str .= isset($request['date']) ? $request['date'] . "\n" : $this->date . "\n";
        $str .= $CanonicalizedAmzHeadersStr . preg_replace('/(?(?=(?!\?delete))\?.*)/', '', $request['resource']);

        $sha1 = $this->hasher($str);

        return $this->base64($sha1);
    }

    // Algorithm adapted (stolen) from http://pear.php.net/package/Crypt_HMAC/)
    protected function hasher($data)
    {
        $key = $this->privateKey;
        if (strlen($key) > 64) {
            $key = pack('H40', sha1($key));
        }
        if (strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        }
        $ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
        $opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));

        return sha1($opad . pack('H40', sha1($ipad . $data)));
    }

    protected function base64($str)
    {
        $ret = '';
        for ($i = 0; $i < strlen($str); $i += 2) {
            $ret .= chr(hexdec(substr($str, $i, 2)));
        }

        return base64_encode($ret);
    }
}
