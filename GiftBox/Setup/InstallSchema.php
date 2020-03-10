<?php

namespace BroSolutions\GiftBox\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'brosolutions_giftbox_type'
         */

        $table = $installer->getConnection()->newTable(
            $installer->getTable('brosolutions_giftbox_type')
        )->addColumn(
            'type_id',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'identity' => true, 'nullable' => false, 'primary' => true]
        )->addColumn(
            'code',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => '']
        )->addIndex(
            $installer->getIdxName(
                'brosolutions_giftbox_type',
                ['type_id', 'code'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['type_id', 'code'],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'brosolutions_giftbox_product_related'
         */

        $table = $installer->getConnection()->newTable(
            $installer->getTable('brosolutions_giftbox_product_related')
        )->addColumn(
            'id',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'identity' => true, 'nullable' => false, 'primary' => true]
        )->addColumn(
            'product_id',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        )->addColumn(
            'type_id',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => true]
        )->addColumn(
            'product_related_id',
            Table::TYPE_INTEGER,
            5,
            ['unsigned' => true, 'nullable' => false]
        )->addColumn(
            'position',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => false, 'nullable' => true, 'identity'=> false]
        )->addIndex(
            $installer->getIdxName(
                'brosolutions_giftbox_product_related',
                ['product_id', 'type_id', 'product_related_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['product_id', 'type_id', 'product_related_id'],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        )->addForeignKey(
            $installer->getFkName('brosolutions_giftbox_product_related',
                                 'type_id',
                                  'brosolutions_giftbox_type',
                                 'type_id'),
            'type_id',
            $installer->getTable('brosolutions_giftbox_type'),
            'type_id',
            Table::ACTION_CASCADE
        )->addForeignKey(
            $installer->getFkName('brosolutions_giftbox_product_related',
                                'product_id',
                                'catalog_product_entity',
                                'entity_id'),
            'product_id',
            $installer->getTable('catalog_product_entity'),
            'entity_id',
            Table::ACTION_CASCADE
        )->addForeignKey(
            $installer->getFkName('brosolutions_giftbox_product_related',
                'product_related_id',
                'catalog_product_entity',
                'entity_id'),
            'product_related_id',
            $installer->getTable('catalog_product_entity'),
            'entity_id',
            Table::ACTION_CASCADE
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'brosolutions_giftbox_quote'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('brosolutions_giftbox_quote')
        )->addColumn(
            'id',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'identity' => true, 'nullable' => false, 'primary' => true]
        )->addColumn(
            'quote_item_id',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        )->addColumn(
            'parent_id',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => true, 'default' => null]
        )->addColumn(
            'type_id',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        )->addForeignKey(
            $installer->getFkName('brosolutions_giftbox_quote',
                'quote_item_id',
                'quote_item',
                'item_id'),
            'quote_item_id',
            $installer->getTable('quote_item'),
            'item_id',
            Table::ACTION_CASCADE
        );

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'brosolutions_giftbox_message'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('brosolutions_giftbox_message')
        )->addColumn(
            'quote_item_id',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false, 'primary' => true]
        )->addColumn(
            'message',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => ''],
            'Message'
        )->addForeignKey(
            $installer->getFkName('brosolutions_giftbox_message',
                'quote_item_id',
                'quote_item',
                'item_id'),
            'quote_item_id',
            $installer->getTable('quote_item'),
            'item_id',
            Table::ACTION_CASCADE
        );

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
