<?php


namespace BroSolutions\GiftBox\Model\Quote;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Catalog\Api\ProductRepositoryInterface;

class GiftBoxQuote
{

    /**
     * @var \BroSolutions\GiftBox\Model\ResourceModel\Quote\Item
     */
    protected $giftBoxCardItem;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    public function __construct(
        \BroSolutions\GiftBox\Model\ResourceModel\Quote\Item $giftBoxCardItem,
        CheckoutSession $checkoutSession,
        ProductRepositoryInterface $productRepository
    ) {
        $this->giftBoxCardItem = $giftBoxCardItem;
        $this->checkoutSession = $checkoutSession;
        $this->productRepository = $productRepository;
    }


    /**
     * @param $parentQuoteItemId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getGiftBoxCard($parentQuoteItemId)
    {
        $quoteId = $this->checkoutSession->getQuote()->getId();
        $parent = $this->giftBoxCardItem->getGiftBoxItemInfo($parentQuoteItemId);
        $children = [];
        if(!empty($parent['id'])) {
            $children = $this->getChildren($parent['id']);
        }

        return ['parent' => $parent, 'children' => $children, 'quote' => $quoteId];
    }

    /**
     * @param $parentId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getChildren($parentId)
    {
        $children = $this->giftBoxCardItem->getChildrenGiftBoxItems($parentId);

        $types = [];
        if ($children && is_array($children)) {
            foreach ($children as $child) {
                $types[$child['type_id']][$child['product_id']] = (int)$child['qty'];
            }
        }

        return $types;
    }

    /**
     * @param $parentId
     * @return array
     */
    public function getChildrenProductIds($parentId)
    {
        $result = [];
        $quote = $this->checkoutSession->getQuote();
        $quoteItems = $quote->getAllItems();
        foreach ($quoteItems as $quoteItem) {
            if($quoteItem->getParentItemId() == $parentId) {
                $result[] = ['id' => $quoteItem->getProductId(), 'qty' => $quoteItem->getQty()];
            }
        }

        return $result;
    }

    /**
     * @param $productIds
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getChildrenProductsInfo($parentQuoteItemId)
    {
        $result = [];
        $productIds = $this->getChildrenProductIds($parentQuoteItemId);

        if(!empty($productIds) && is_array($productIds) && count($productIds) > 0) {
            foreach ($productIds as $productId) {
                $product = $this->productRepository->getById($productId['id']);
                $result[] = [
                    'product_id' => $product->getId(),
                    'url' => $product->getProductUrl(),
                    'name' => $product->getName() . ' - ' . $productId['qty'],
                    'qty' => $productId['qty']
                ];

            }
        }

        return $result;
    }
}