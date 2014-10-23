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
        }
    ]
}
```

Then issue the ``composer install`` command.

## TODO
- Finish refactoring (merge Aoe_AmazonCdn_Model_Cache_Facade and Aoe_AmazonCdn_Model_Cdn_Adapter, better ideas?)
- Switch from custom implementation of Amazon S3 interaction in Aoe_AmazonCdn_Model_Cdn_Connector
and Aoe_AmazonCdn_Model_Cdn_Adapter to official Amazon SDK. Because custom implementation is less reliable then official
SDK and can stop working basically at any time in the future
