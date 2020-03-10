<?php


namespace BroSolutions\GiftBox\Helper;


use \Magento\Framework\App\Helper\Context;
use \Magento\Checkout\Model\Session as CheckoutSession;
use \BroSolutions\GiftBox\Model\Product\Type\GiftBox;
use  \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Framework\App\ResourceConnection;
use \Magento\Quote\Model\QuoteFactory;
use \Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;


class Cart extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var CheckoutSession */
    protected $checkoutSession;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @var QuoteCollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Item
     */
    protected $itemResourceModel;

    /**
     * @var array
     */
    public $giftBoxItems = [];

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * Cart constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     * @param ResourceConnection $resource
     * @param QuoteFactory $quoteFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item $itemResourceModel
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $itemCollectionFactory
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        ProductRepositoryInterface $productRepository,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        ResourceConnection $resource,
        QuoteFactory $quoteFactory,
        CartRepositoryInterface $quoteRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        QuoteCollectionFactory $quoteCollectionFactory,
        \Magento\Quote\Model\ResourceModel\Quote\Item $itemResourceModel,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $itemCollectionFactory,
        \Magento\Sales\Model\OrderRepository $orderRepository
    )
    {
        $this->itemResourceModel = $itemResourceModel;
        $this->quoteRepository = $quoteRepository;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->quoteFactory = $quoteFactory;
        $this->resource = $resource;
        $this->urlBuilder = $urlBuilder;
        $this->checkoutSession = $checkoutSession;
        $this->productRepository = $productRepository;
        $this->request = $request;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->orderRepository = $orderRepository;

        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getGiftBoxItems()
    {
        if(count($this->giftBoxItems) == 0) {
            $quote = $this->checkoutSession->getQuote();
            $giftBoxItems = [];
            $quoteItems = $quote->getItems();
            if($quoteItems) {
                foreach ($quoteItems as $quoteItem) {
                    if ($quoteItem->getProduct()->getData('type_id') == GiftBox::TYPE_GIFTBOX_PRODUCT) {
                        $parentId = $quoteItem->getProductId();
                        $giftBoxItems[$parentId] = [];
                        if ($quoteItem->getChildren()) {
                            foreach ($quoteItem->getChildren() as $child) {
                                if ($child->getProductId()) {
                                    $giftBoxItems[$parentId][] = $child->getProductId();
                                }
                            }
                        }
                    }
                }
            }

            $this->giftBoxItems = $giftBoxItems;
        }

        return $this->giftBoxItems;
    }

    /**
     * @return bool
     */
    public function issetGiftBoxItems()
    {
        return (count($this->giftBoxItems) > 0) ? true : false;

    }

    /**
     * @param $id
     * @return bool
     */
    public function isGiftBoxItemChild($id)
    {
        if($this->issetGiftBoxItems()) {
            foreach ($this->giftBoxItems as $giftBoxItem) {
                if(isset($giftBoxItem[$id])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $product
     * @return bool
     */
    public function isProductTypeGiftBox($product)
    {
        return $product->getTypeId() == GiftBox::TYPE_GIFTBOX_PRODUCT;
    }

    /**
     * @param $product
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getGiftBoxChildren($product)
    {
        $productId = $product->getId();
        $getGiftBoxItems = $this->getGiftBoxItems();

        $childrenIds = [];

        if(isset($getGiftBoxItems[$productId])) {
            $childrenIds = $getGiftBoxItems[$productId];
        }

        $children = [];
        foreach ($childrenIds as $childrenId) {
            $children[] = $this->productRepository->getById($childrenId);
        }

        return $children;
    }

    /**
     * @param $product
     * @return array|string
     */
    public function getChildImageUrl($product)
    {
        $imageUrl = ($product->getData('small_image')) ? $this->getMediaUrl() . 'catalog/product' . $product->getData('small_image') : '';
        $result = [
            'image_url'       => $imageUrl,
            'image_label' => $product->getData('small_image_label')
        ];

        return $imageUrl ? $result : '';
    }

    /**
     * @param $product
     * @return mixed
     */
    public function getChildProductName($product)
    {
        return $product->getName();
    }

    /**
     * @param $product
     * @return string
     */
    public function getChildProductUrl($product)
    {
        return $product->getProductUrl() ? $product->getProductUrl() : '';
    }

    /**
     * @return string
     */
    public function getMediaUrl() {
        return $this->urlBuilder->getBaseUrl(['_type' => $this->urlBuilder::URL_TYPE_MEDIA]);
    }

    /**
     * @param $item
     * @return array
     */
    public function getChilrenOnCurrentOrder($item)
    {
        $result = [];
        if($item->getProductType() == GiftBox::TYPE_GIFTBOX_PRODUCT) {
            $parentItemId = $item->getItemId();
            $orderCollection = $this->itemCollectionFactory->create();
            $orderCollection->filterByParent($parentItemId);

            foreach ($orderCollection->getItems() as $item) {
                $result[] = ['product_id' => $item->getProductId(),
                             'name' => $item->getName(),
                             'sku' => $item->getSku()
                ];
            }
        }

        return $result;
    }

    /**
     * @param array $children
     * @return string
     */
    public function getChilrenOnCurrentOrderHtml(array $children)
    {
        $result = '';
        if(count($children) > 0) {
            $i = 1;
            foreach ($children as $child) {
                if ($i == 1) {
                    $result = __("Gifrts: ") . "</br>";
                }
                $result .= $i . '. ' . $child['name'] . "</br>";
                $i++;
            }
        }

        return $result;
    }
}