<?php


namespace BroSolutions\GiftBox\Plugin\Magento\Quote\Model\Quote\Item;

use \BroSolutions\GiftBox\Model\Product\Type\GiftBox;

class AbstractItem
{
    public function afterIsChildrenCalculated($subject, $result)
    {
        $parentProductType = null;
        $parentProduct = $subject->getParentItem();
        if($parentProduct) {
            $parentProductType = $parentProduct->getData('product_type');
        }

        if(($parentProductType && $parentProductType == GiftBox::TYPE_GIFTBOX_PRODUCT) ||
            ($subject->getData('product_type') && $subject->getData('product_type') == GiftBox::TYPE_GIFTBOX_PRODUCT)) {
            return false;
        } else {
            return $result;
        }
    }
}