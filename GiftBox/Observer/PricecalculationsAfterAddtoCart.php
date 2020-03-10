<?php

namespace BroSolutions\GiftBox\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use \BroSolutions\GiftBox\Model\Product\Type\GiftBox;

class PricecalculationsAfterAddtoCart implements ObserverInterface
{

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var \BroSolutions\GiftBox\Helper\ProductList
     */
    protected $helperProductList;

    public function __construct(
        RequestInterface $request,
        \BroSolutions\GiftBox\Helper\ProductList $helperProductList
    ) {
        $this->request = $request;
        $this->helperProductList = $helperProductList;
    }

    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if($product && $product->getTypeId() == GiftBox::TYPE_GIFTBOX_PRODUCT) {
            $isMessagePay = $this->request->getParam('isMessagePay');
            $productId = $product->getEntityId();
            if($isMessagePay == TRUE) {
                $priceMsg = $this->helperProductList->getPriceMessageByProductId($productId);
                if(!empty($priceMsg)) {
                    $price = $product->getPrice();
                    $quote_item = $observer->getEvent()->getQuoteItem();
                    $price += $priceMsg;
                    $quote_item->setCustomPrice($price);
                    $quote_item->setOriginalCustomPrice($price);
                    $quote_item->getProduct()->setIsSuperMode(true);
                }
            }
        }
    }
}