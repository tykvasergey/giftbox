<?php


namespace BroSolutions\GiftBox\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;

class GiftBoxMessage extends AbstractDb
{
    const TABLE_NAME = 'brosolutions_giftbox_message';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, 'id');
    }

    /**
     * @param $quoteId
     * @param $productId
     * @param $msg
     */
    public function addMessage($parentQuoteItemId, $msg)
    {
        try {
            $this->getConnection()->insert($this->getMainTable(), [
                'quote_item_id' => $parentQuoteItemId,
                'message' => $msg
                ]
            );
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __(
                    'Unable to save message for GiftBox. Error: %2',
                    [$e->getMessage()]
                )
            );
        }

        return true;

    }

    /**
     * @param $quoteId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMessagesByQuoteId($quoteId)
    {
        $result = [];
        try {
            $quoteItemTable = $this->_resources->getTableName('quote_item');
            $query = $this->getConnection()->select()
                ->from(['bgm' => $this->getMainTable()],
                    ['quote_item_id', 'message'])
                ->joinLeft(['qi' => $quoteItemTable],
                    'bgm.quote_item_id = qi.item_id',
                    ['product_id', 'quote_id'])
                ->where('qi.quote_id = ?', (int)$quoteId);

            $result = $this->getConnection()->fetchAll($query);

            return $result;

        } catch (NoSuchEntityException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Can\'t get warehouse'), $e);
        }

        return $result;
    }

    /**
     * @param $quoteItemId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function hasMsg($quoteItemId)
    {
        $query = $this->getConnection()->select()
            ->from($this->getMainTable(), ['message'])
            ->where('quote_item_id=:quote_item_id');

        $bindParams = ['quote_item_id' => $quoteItemId];
        $result = $this->getConnection()->fetchOne($query, $bindParams);

        return (!empty($result) && mb_strlen($result) > 0) ? true : false;
    }

    /**
     * @param $quoteItemId
     * @param $msg
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateMessage($quoteItemId,  $msg)
    {
        $result = $this->getConnection()->update(
            $this->getMainTable(),
            ['message' => $msg],
            ['quote_item_id = ?' => $quoteItemId]
        );

        return $result;
    }

    /**
     * @param $quoteItemId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteMessage($quoteItemId)
    {
        try {
            $result = $this->getConnection()->delete($this->getMainTable(), ['quote_item_id =?' => $quoteItemId]);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the GiftBox message: %1',
                $exception->getMessage()
            ));
        }

        return true;
    }
}