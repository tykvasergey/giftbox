<?php


namespace BroSolutions\GiftBox\Model\ResourceModel\Product\Attribute\Backend;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use \Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Model\StoreManagerInterface;

class GiftBoxRelated extends AbstractDb
{
    const TABLE_NAME = 'brosolutions_giftbox_product_related';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Initialize connection and define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, 'id');
    }

    public function __construct(
        Context $context,
        $connectionName = null
    )
    {
        parent::__construct(
            $context,
            $connectionName
        );
    }

    /**
     * @param $listProducts
     * @param $product
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function insertElements($listProducts, $product)
    {
        $productId = $product->getId();

        if($productId) {
            if($listProducts && is_array($listProducts) && count($listProducts) > 0) {
                $types = array_unique(array_column($listProducts, 'type_id'));
                $productRelatedIds = array_column($listProducts, 'product_related_id');

                $select = $this->getConnection()->select()->from(
                    $this->getTable(self::TABLE_NAME),
                    ['id']
                )->where('type_id IN (?)', $types
                )->where('product_id = ?', (int)$productId
                )->where('product_related_id NOT IN (?)', $productRelatedIds);

                $deleteRows = $this->getConnection()->fetchCol($select);

                if (is_array($deleteRows) && count($deleteRows) > 0) {
                    $whereDelete = ['id IN (?)' => $deleteRows];
                    $this->getConnection()->delete($this->getTable(self::TABLE_NAME), $whereDelete);
                }

                $this->getConnection()->insertOnDuplicate($this->getMainTable(), $listProducts);

            } else {
                $whereDelete = ['product_id = ?' => $productId];
                $this->getConnection()->delete($this->getTable(self::TABLE_NAME), $whereDelete);
            }
        }

        return $this;
    }
}