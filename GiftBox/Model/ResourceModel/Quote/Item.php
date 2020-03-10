<?php

namespace BroSolutions\GiftBox\Model\ResourceModel\Quote;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;
use BroSolutions\GiftBox\Model\ResourceModel\GiftBoxMessage;
use Magento\Framework\Exception\CouldNotSaveException;

class Item extends AbstractDb
{

    const TABLE_NAME = 'brosolutions_giftbox_quote';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, 'id');
    }

    /**
     * Get Child Quote item id by params: parent Quote item id, product id, type id
     *
     * @param $parentQuoteItemId
     * @param $productId
     * @param $type
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQuoteItemId($parentQuoteItemId, $productId, $type)
    {
        $quoteItemTable = $this->_resources->getTableName('quote_item');

        $query = $this->getConnection()->select()
            ->from(['bgq' => $this->getMainTable()], ['quote_item_id'])
            ->joinLeft(['qi' => $quoteItemTable], 'bgq.quote_item_id = qi.item_id')
            ->where('bgq.parent_id IS NOT NULL')
            ->where('bgq.type_id=:type_id')
            ->where('qi.parent_item_id=:parent_item_id')
            ->where('qi.product_id=:product_id');

        $bindParams = ['type_id' => $type,
            'parent_item_id' => $parentQuoteItemId,
            'product_id' => $productId
        ];

        return $this->getConnection()->fetchOne($query, $bindParams);
    }


    /**
     * Get info about Quote item
     *
     * @param $quoteItemId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getGiftBoxItemInfo($quoteItemId)
    {
        $result = [];

        $quoteItemTable = $this->_resources->getTableName('quote_item');
        $messageTable = $this->_resources->getTableName(GiftBoxMessage::TABLE_NAME);
        $query = $this->getConnection()->select()
            ->from(['bgq' => $this->getMainTable()], ['id' => 'bgq.id'])
            ->joinLeft(['bgm' => $messageTable],
                'bgq.quote_item_id = bgm.quote_item_id',
                ['message' => 'bgm.message'])
            ->joinLeft(['qi' => $quoteItemTable],
                'bgq.quote_item_id = qi.item_id',
                ['product_id' => 'qi.product_id'])
            ->where('bgq.quote_item_id=:quote_item_id');

        $bindParams = ['quote_item_id' => $quoteItemId];
        $result = $this->getConnection()->fetchRow($query, $bindParams);

        return $result;
    }

    /**
     * Get children items GiftBox by parent id
     *
     * @param $id
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getChildrenGiftBoxItems($id)
    {
        $quoteItemTable = $this->_resources->getTableName('quote_item');
        $query = $this->getConnection()->select()
            ->from(['bgq' => $this->getMainTable()],
                ['type_id' => 'bgq.type_id'])
            ->joinLeft(['qi' => $quoteItemTable],
                'bgq.quote_item_id = qi.item_id',
                ['product_id' => 'qi.product_id', 'qty' => 'qi.qty'])
            ->where('bgq.parent_id=:parent_id');

        $bindParams = ['parent_id' => $id];
        $children = $this->getConnection()->fetchAll($query, $bindParams);

        return $children;
    }

    /**
     * @param $quoteItemId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getParentId($quoteItemId)
    {
        $typeId = 0;
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->getMainTable()),
            ['id']
        )->where('quote_item_id = ?', $quoteItemId
        )->where('parent_id IS NULL'
        )->where('type_id = ?', $typeId);

        $id = $this->getConnection()->fetchOne($select);

        return $id;
    }

    public function addItem($quoteItemId, $parentId, $typeId)
    {
        try {
            $this->getConnection()->insert($this->getMainTable(), [
                    'quote_item_id' => $quoteItemId,
                    'parent_id' => $parentId,
                    'type_id' => $typeId
                ]
            );
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __(
                    'Unable to save quote for GiftBox. Error: %1',
                    [$e->getMessage()]
                )
            );
        }
    }
}