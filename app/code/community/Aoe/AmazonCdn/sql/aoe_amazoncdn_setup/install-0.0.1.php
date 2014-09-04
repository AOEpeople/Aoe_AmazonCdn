<?php
/**
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoe.com>
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$tableName = $this->getTable('aoe_amazoncdn/cache');

$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($tableName)
    ->addColumn('cache_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
        'identity' => true,
        'primary'  => true,
        'unsigned' => true,
        'nullable' => false,
    ))
    ->addColumn('file_type', Varien_Db_Ddl_Table::TYPE_TINYINT, 1, array(
        'unsigned'  => true,
        'nullable' => false,
        'comment'   => 'File type - one of the class FILE_TYPE_XXX constants'
    ))
    ->addColumn('url', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
        'default'  => ''
    ))
    ->addColumn('last_checked', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
    ))
    ->addColumn('image_width', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
        'unsigned'  => true,
        'nullable'  => true,
        'default'   => '0',
        'comment'   => 'Image width'
    ))
    ->addColumn('image_height', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
        'unsigned'  => true,
        'nullable'  => true,
        'default'   => '0',
        'comment'   => 'Image height'
    ))
    ->addColumn('image_type', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
        'unsigned'  => true,
        'nullable'  => true,
        'default'   => '0',
        'comment'   => 'Image type - one of the IMAGETYPE_XXX constants'
    ))
    ->addIndex($installer->getIdxName($tableName, 'url'), 'url',
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
    ->addIndex($installer->getIdxName($tableName, 'file_type'), 'file_type',
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
    )
    ->addIndex($installer->getIdxName($tableName, 'last_checked'), 'last_checked',
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
    );

$installer->getConnection()->createTable($table);

$installer->endSetup();
