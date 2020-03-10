<?php


namespace BroSolutions\GiftBox\Model\Product\Type;

use \BroSolutions\GiftBox\Model\Product\Type\GiftBox;

class Price extends \Magento\Catalog\Model\Product\Type\Price
{

    public function getFinalPrice($qty=null, $product)
    {
        if($product->getTypeId() != GiftBox::TYPE_GIFTBOX_PRODUCT){
           // return 0;
        }
        $finalPrice = $product->getPrice();
        // $finalPrice = $this->_applyOptionsPrice($product, $qty, $finalPrice);

        $product->setData('final_price', $finalPrice);
        return max(0, $product->getData('final_price'));
    }
}