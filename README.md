Aoe_AmazonCdn
=============

Onepica_ImageCdn fork (only S3 support is left) with some customizations and improvements

## TODO
- Finish refactoring (merge Aoe_AmazonCdn_Model_Cache_Facade and Aoe_AmazonCdn_Model_Cdn_Adapter, better ideas?)
- Fix bug with generating thumbnails for downloaded images in wysiwyg -> select image window.
Currently thumbnails are generated randomly (like on 2-3 page load)
- Switch from custom implementation of Amazon S3 interaction in Aoe_AmazonCdn_Model_Cdn_Connector
and Aoe_AmazonCdn_Model_Cdn_Adapter to official Amazon SDK. Because custom implementation is less reliable then official
SDK and can stop working basically at any time in the future. (Check this for an SDK integration: https://github.com/AOEpeople/Mage_AwsSdk)
- Add shell command that traverses all products in the DB finds their local images and adds them to S3 (as initialization). That's probably a lot cleaner and more convenient than uploading the media folder manually and gives you the opportunity to get rid of unused images.
