<?php


namespace BroSolutions\GiftBox\Model\Quote;


use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\Item\Repository as QuoteItemRepository;
use BroSolutions\GiftBox\Model\Product\Type\GiftBox;
use Magento\Quote\Model\QuoteRepository as QuoteRepository;
use BroSolutions\GiftBox\Model\ResourceModel\Quote\Item as GiftBoxQuoteItem;
use BroSolutions\GiftBox\Model\Quote\GiftBoxQuote;
use BroSolutions\GiftBox\Model\ResourceModel\GiftBoxMessage;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;


class Item
{

    /** @var CheckoutSession */
    protected $checkoutSession;

    protected $productRepository;

    protected $quoteItemRepository;

    protected $productList;

    protected $cartHelper;

    protected $quoteRepository;

    /**
     * @var
     */
    protected $giftBoxQuoteItem;


    /**
     * @var GiftBoxMessage
     */
    protected $giftBoxMessage;


    /**
     * @var \BroSolutions\GiftBox\Model\Quote\GiftBoxQuote
     */
    protected $giftBoxQuote;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    public function __construct(
        CheckoutSession $checkoutSession,
        ResourceConnection $resource,
        CartItemInterface $cartItem,
        ProductRepositoryInterface $productRepository,
        QuoteItemRepository $quoteItemRepository,
        \BroSolutions\GiftBox\Helper\ProductList $productList,
        \BroSolutions\GiftBox\Helper\Cart $cartHelper,
        \Magento\Checkout\Model\Cart $cart,
        QuoteRepository $quoteRepository,
        GiftBoxQuoteItem $giftBoxQuoteItem,
        GiftBoxQuote $giftBoxQuote,
        GiftBoxMessage $giftBoxMessage,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->resource = $resource;
        $this->cartItem = $cartItem;
        $this->productRepository = $productRepository;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->productList = $productList;
        $this->cartHelper = $cartHelper;
        $this->cart = $cart;
        $this->quoteRepository = $quoteRepository;
        $this->giftBoxQuoteItem = $giftBoxQuoteItem;
        $this->giftBoxQuote = $giftBoxQuote;
        $this->giftBoxMessage = $giftBoxMessage;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param $params
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function editGiftBox($params)
    {
        $isMessagePay = ($params['isMessagePay'] == "true") ? true : false;
        $giftMsg = (!empty($params['giftMsg']) && mb_strlen($params['giftMsg']) > 0) ? $params['giftMsg'] : '';
        $parentQuoteItemId = $params['editQuoteItemId'];
        $parentProductId = $params['product'];
        $parentQuoteItem = null;

        // change parent quote item
        $quote = $this->cart->getQuote();
        if($quote) {
            $quoteItems = $quote->getAllItems();
            foreach ($quoteItems as $quoteItem) {
                if($quoteItem->getItemId() == $parentQuoteItemId) {
                    $parentQuoteItem = $quoteItem;
                }
            }
        }

        if(isset($parentQuoteItem) &&
            $parentQuoteItem->getProductId() != $parentProductId) {

            $parentQuoteItem = $this->updateQuoteItem($parentQuoteItem, $parentProductId);
        }

        //message
        $hasMsg = $this->giftBoxMessage->hasMsg($parentQuoteItemId);
        if(isset($parentQuoteItem)) {

            $productId = $parentQuoteItem->getProductId();
            $product = $this->productRepository->getById($productId);

            $price = $product->getPrice();
            $priceMsg = $this->productList->getPriceMessageByProductId($productId);

            if ($isMessagePay == true) {

                $price += $priceMsg;
                if ($hasMsg == true) {
                    $this->giftBoxMessage->updateMessage($parentQuoteItemId, $giftMsg);
                } else {
                    $this->giftBoxMessage->updateMessage($parentQuoteItemId, $giftMsg);
                }
            } else {
                if ($hasMsg == true) {
                    $this->giftBoxMessage->deleteMessage($parentQuoteItemId);
                }
            }

            $parentQuoteItem->setPrice($price);
            $parentQuoteItem->setCustomPrice($price);
            $parentQuoteItem->setOriginalCustomPrice($price);
            $parentQuoteItem->getProduct()->setIsSuperMode(true);
        }

        // change children
        $children = $this->giftBoxQuote->getGiftBoxCard($params['editQuoteItemId']);
        $quoteId = $this->checkoutSession->getQuote()->getId();

        foreach (GiftBox::$relatedTypes as $type) {
            $res1 = [];
            $res2 = [];

            $arr1 = (empty($params['chosenItems'][$type])) ? [] : $params['chosenItems'][$type];
            $arr2 = (empty($children['children'][$type])) ? [] : $children['children'][$type];

            // add quote item
            $res1 = array_diff_key($arr1, $arr2);
            if(!empty($res1) && is_array($res1) && count($res1) > 0) {
               $this->addListQuoteItems($quoteId, $parentQuoteItem, $res1, $type);
            }

            // delete quote item
            $res2 = array_diff_key($arr2, $arr1);
            if(!empty($res2) && is_array($res2) && count($res2) > 0) {
                $this->deleteListQuoteItems($parentQuoteItemId, $res2, $type);
            }
        }

        if(isset($parentQuoteItem)) {
            $parentQuoteItem->save();
            $this->quoteItemRepository->save($quoteItem);
        }

        // change qty
        $childrenAfter = $this->giftBoxQuote->getGiftBoxCard($params['editQuoteItemId']);
        $arr1 = (empty($params['chosenItems'])) ? [] : $params['chosenItems'];
        $arr2 = (empty($childrenAfter['children'])) ? [] : $childrenAfter['children'];

        if(is_array($arr1) && count($arr1) > 0) {
            foreach ($arr1 as $typeId => $types) {
                if(is_array($types) && count($types) > 0) {
                    foreach ($types as $productId => $qty) {
                        if(isset($arr1[$typeId][$productId]) && isset($arr2[$typeId][$productId])) {
                            $qtyBefore = (int)$arr2[$typeId][$productId];
                            $qtyAfter = (int)$arr1[$typeId][$productId];
                            if($qtyBefore != $qtyAfter) {
                                $this->updateQty($productId, $qtyAfter);
                            }
                        }
                    }
                }

            }
        }
    }

    /**
     * @param $productId
     * @param $qty
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function updateQty($productId, $qty)
    {
        $quote = $this->cart->getQuote();
        if($quote) {
            $quoteItems = $quote->getAllItems();
            foreach ($quoteItems as $quoteItem) {
                if($quoteItem->getProductId() == $productId) {
                    $quoteItem->setQty($qty);
                    $quoteItem->save();
                    $this->quoteItemRepository->save($quoteItem);
                }
            }
        }
    }

    public function updateQuoteItem($quoteItem, $newParentProductId)
    {
        $product = $this->productRepository->getById($newParentProductId);

        $quoteItem->setProductId($newParentProductId);

        $quoteItem->setSku($product->getSku());
        $quoteItem->setName($product->getName());
        $quoteItem->setWeight($product->getWeight());
        $price = $product->getPrice();
        $quoteItem->setPrice($price);
        $quoteItem->setCustomPrice($price);
        $quoteItem->setOriginalCustomPrice($price);
        $quoteItem->getProduct()->setIsSuperMode(true);

        $quoteItem->save();
        $this->quoteItemRepository->save($quoteItem);

        return $quoteItem;
    }

    /**
     * @param $parentQuoteItemId
     * @param $productIds
     * @param $type
     * @return bool
     */
    public function deleteListQuoteItems($parentQuoteItemId, $productIds, $type)
    {
        if(!empty($productIds) && is_array($productIds) && count($productIds) > 0) {
            foreach ($productIds as $productId => $qty) {
                $this->deleteQuoteItem($parentQuoteItemId, $productId, $type);
            }
        } else {
            return false;
        }
    }

    /**
     * @param $parentQuoteItemId
     * @param $productId
     * @param $type
     * @return bool
     */
    public function deleteQuoteItem($parentQuoteItemId, $productId, $type)
    {
        try {
            $quoteItemId = $this->giftBoxQuoteItem->getQuoteItemId($parentQuoteItemId, $productId, $type);
            if(!empty($quoteItemId)) {
                // $quote = $result->getQuote()
                $result = $this->cart->removeItem($quoteItemId)->save();
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $quoteId
     * @param $parentQuoteItem
     * @param $productIds
     * @param $type
     * @return bool
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function addListQuoteItems($quoteId, $parentQuoteItem, $productIds, $type)
    {
        if(!empty($productIds) && is_array($productIds) && count($productIds) > 0) {
            foreach ($productIds as $productId => $qty) {
                $this->addQuoteItem($quoteId, $parentQuoteItem, $productId, $qty, $type);
            }
        } else {
            return false;
        }
    }

    /**
     * @param $quoteId
     * @param $productId
     * @param $qty
     * @param $type
     * @param $message
     * @param $isMessagePay
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addQuoteItem($quoteId, $parentQuoteItem = null, $productId, $qty = 1, $type)
    {
        $parentQuoteItemId = $parentQuoteItem->getId();
        $storeId = $this->_storeManager->getStore()->getId();

        $product = $this->productRepository->getById($productId);

        $cartItem = $this->cartItem;
        $cartItem->setSku($product->getSku());
        $cartItem->setProduct($product);
        $cartItem->setStoreId($storeId);
        $cartItem->setQuoteId($quoteId);
        $cartItem->setParentItemId($parentQuoteItemId);
        $cartItem->setParentItem($parentQuoteItem);
        $cartItem->setQty($qty);
        $result = $cartItem->save();

        if($result) {
            $id = $this->giftBoxQuoteItem->getParentId($parentQuoteItemId);
            if(!empty($id)) {
                $this->giftBoxQuoteItem->addItem($result->getItemId(), $id, $type);
            }
        }
    }
}