<?php


namespace BroSolutions\GiftBox\Plugin\Magento\Checkout\CustomerData;

use BroSolutions\GiftBox\Model\Product\Type\GiftBox;

class Cart
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var \BroSolutions\GiftBox\Model\Quote\GiftBoxQuote
     */
    protected $giftBoxQuote;

    /**
     * Cart constructor.
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \BroSolutions\GiftBox\Model\Quote\GiftBoxQuote $giftBoxQuote
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlInterface,
        \BroSolutions\GiftBox\Model\Quote\GiftBoxQuote $giftBoxQuote
    ) {
        $this->urlInterface = $urlInterface;
        $this->giftBoxQuote = $giftBoxQuote;
    }

    /**
     * @param \Magento\Checkout\CustomerData\AbstractItem $subject
     * @param $result
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetItemData( \Magento\Checkout\CustomerData\AbstractItem $subject,
                                      $result,
                                      \Magento\Quote\Model\Quote\Item $item)
    {
        $data = [];

        if($result['product_type'] == GiftBox::TYPE_GIFTBOX_PRODUCT) {
            $quoteItemId = $result['item_id'];
            $productId = $result['product_id'];
            $giftchildren = $this->giftBoxQuote->getChildrenProductsInfo($quoteItemId);

            if($giftchildren) {
                $data['giftchildren'] = $giftchildren;
                $data['giftbox_edit_url'] = $this->urlInterface->getUrl('giftbox/edit/item', ['id'=>$quoteItemId]);
            }
        }

        if($data && is_array($data) && count($data) > 0) {
            return array_merge($result, $data);
        } else {
            return $result;
        }
    }
}