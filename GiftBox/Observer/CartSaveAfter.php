<?php


namespace BroSolutions\GiftBox\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use \BroSolutions\GiftBox\Model\Product\Type\GiftBox;
use Magento\Framework\Exception\CouldNotSaveException;

class CartSaveAfter implements ObserverInterface
{
    const BROSOLUTION_GIFTBOX_QUOTE = 'brosolutions_giftbox_quote';

    /**
     * @var CheckoutSession
     */
    private $_checkoutSession;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @var \BroSolutions\GiftBox\Model\ResourceModel\GiftBoxMessage
     */
    protected $giftBoxMessage;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    public function __construct(
        CheckoutSession $checkoutSession,
        \Magento\Framework\App\ResourceConnection $resource,
        \BroSolutions\GiftBox\Model\ResourceModel\GiftBoxMessage $giftBoxMessage,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_resource = $resource;
        $this->giftBoxMessage = $giftBoxMessage;
        $this->customerSession = $customerSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $request = $observer->getData('request');
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getDataByKey('product');
        /** @var \Magento\Quote\Model\Quote\Item $item */
        $item = $this->_checkoutSession->getQuote()->getItemByProduct($product);

        $quoteItems = $this->_checkoutSession->getQuote()->getAllItems();
        $parentQuoteItemId = $item->getId();

        if($item->getProductType() == GiftBox::TYPE_GIFTBOX_PRODUCT) {

            $childrenGiftBox = $this->getChidrenGiftProducts($request->getParam('chosenItems'));

            if($request->getParam('product') &&
                count($childrenGiftBox) > 0) {

                $tableName = $this->_resource->getTableName(self::BROSOLUTION_GIFTBOX_QUOTE);
                $insertParentData = ['quote_item_id' => $parentQuoteItemId, 'parent_id' => NULL];
                $connection = $this->_resource->getConnection();
                $connection->insert($connection->getTableName($tableName), $insertParentData);
                $insertedParentId = $connection->lastInsertId($connection->getTableName($tableName));

                if(!$insertedParentId) {
                    return false;
                }

                $insertData = [];
                foreach ($childrenGiftBox as $type_id => $type) {
                    foreach ($type as $product_id => $child) {
                        if($quoteId = $this->getQuoteIdByParentQuoteId($product_id, $quoteItems, $parentQuoteItemId)) {
                            $insertData[] = [
                                'quote_item_id' => $quoteId,
                                'parent_id'     => $insertedParentId,
                                'type_id'       => $type_id
                            ];
                        }
                    }
                }

                try {
                    $affectedRows = $connection->insertMultiple($connection->getTableName($tableName), $insertData);
                } catch (\Exception $e) {
                    throw new CouldNotSaveException(
                        __(
                            'Unable to save children for GiftBox with parent ID %1. Error: %2',
                            [$insertedParentId, $e->getMessage()]
                        )
                    );
                }

                // Add new gift box message
                if(!empty($affectedRows)) {
                    $msg = $this->getGiftBoxMsg($request);
                    if($msg) {
                        $quoteId = $this->_checkoutSession->getQuote()->getId();
                        $productId = $product->getId();
                        $this->giftBoxMessage->addMessage($parentQuoteItemId, $msg);
                    }
                }
            }
        }
    }

    /**
     * @param $types
     * @return array
     */
    protected function getChidrenGiftProducts($types)
    {
        $result = [];

        if(is_array($types) && count($types) > 0) {
            foreach ($types as $type_id => $children) {
                if(is_array($children) && count($children) > 0) {
                    foreach ($children as $id => $child) {

                        if (!isset($result[$id])) {
                            $result[$type_id][$id] = 0;
                        }

                        $result[$type_id][$id] += $child;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param $request
     * @return string
     */
    public function getGiftBoxMsg($request)
    {
        $msg = '';
        if(!empty($request->getParam('giftMsg'))) {
            $msg = trim(strip_tags($request->getParam('giftMsg')));
        }

        return $msg;
    }

    /**
     * @param $child
     * @param $quoteItems
     * @param $parentQuoteId
     * @return bool
     */
    protected function getQuoteIdByParentQuoteId($child, $quoteItems, $parentQuoteId)
    {
        if(!$quoteItems) {
            return false;
        } elseif (!is_array($quoteItems)) {
            return false;
        } elseif (count($quoteItems) == 0) {
            return false;
        }

        foreach ($quoteItems as $quoteItem) {
            if($quoteItem->getParentItemId() == $parentQuoteId &&
                $quoteItem->getProductId() == $child) {
                return $quoteItem->getId();
            }
        }
        return false;
    }
}