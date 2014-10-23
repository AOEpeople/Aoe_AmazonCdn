Aoe_AmazonCdn
=============

Onepica_ImageCdn fork (only S3 support is left) with some customizations and improvements

## Installing

Add something like the following to your composer.json:

```json
{
    "require": {
        "aoe/amazon-cdn": "*"
    },
    "minimum-stability": "dev",
    "extra": {
        "magento-root-dir": "htdocs/"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/danslo/Aoe_AmazonCdn.git"
        },
        {
            "type": "vcs",
            "url": "https://github.com/danslo/LibraryRewrite.git"
        },
        {
            "type": "vcs",
            "url": "https://github.com/aws/aws-sdk-php.git"
        },
        {
            "type": "composer",
            "url": "http://packages.firegento.com"
        }
    ]
}
```

Load the composer autoloader into Magento if you haven't done so yet.
You can do this by adding the following to ``<global />`` node of ``app/etc/local.xml``:

```xml
<composer_vendor_path><![CDATA[{{root_dir}}/vendor]]></composer_vendor_path>
```

Then issue the ``composer install`` command.

## TODO
- Finish refactoring (merge Aoe_AmazonCdn_Model_Cache_Facade and Aoe_AmazonCdn_Model_Cdn_Adapter, better ideas?)
- Tag all the things, to get rid of dev stability.
