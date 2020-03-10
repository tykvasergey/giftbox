<?php


namespace BroSolutions\GiftBox\Plugin\Magento\Checkout\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;
use BroSolutions\GiftBox\Model\Product\Type\GiftBox;

class DefaultConfigProvider
{

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var QuoteItemRepository
     */
    protected $quoteItemRepository;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productRepository;

    /**
     * @var \BroSolutions\GiftBox\Model\Quote\GiftBoxQuote
     */
    protected $giftBoxQuote;

    /**
     * DefaultConfigProvider constructor.
     * @param CheckoutSession $checkoutSession
     * @param QuoteItemRepository $quoteItemRepository
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \BroSolutions\GiftBox\Model\Quote\GiftBoxQuote $giftBoxQuote
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        QuoteItemRepository $quoteItemRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \BroSolutions\GiftBox\Model\Quote\GiftBoxQuote $giftBoxQuote
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->productRepository = $productRepository;
        $this->giftBoxQuote = $giftBoxQuote;
    }

    public function afterGetConfig(
        \Magento\Checkout\Model\DefaultConfigProvider $subject,
        array $result
    ) {
        $items = $this->getQuoteItemData();
        foreach ($items as $index => $item) {
            $product = $this->productRepository->getById($item->getProductId());

            if($product->getTypeId() != GiftBox::TYPE_GIFTBOX_PRODUCT) {
                continue;
            } else {
                $quoteItemId = $item->getItemId();
                $giftchildren = $this->giftBoxQuote->getChildrenProductsInfo($quoteItemId);

                if($giftchildren) {
                    $result['quoteItemData'][$index]['gifts'] = $giftchildren;
                }
            }
        }
        return $result;
    }

    /**
     * @return \Magento\Quote\Api\Data\CartItemInterface[]|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getQuoteItemData()
    {
        $quoteItemData = [];
        $quoteId = $this->checkoutSession->getQuote()->getId();
        if ($quoteId) {
            return $quoteItems = $this->quoteItemRepository->getList($quoteId);
        }
        else{
            return;
        }
    }
}