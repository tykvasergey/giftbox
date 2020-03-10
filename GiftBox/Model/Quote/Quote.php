<?php


namespace BroSolutions\GiftBox\Model\Quote;


use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total as AddressTotal;
use Magento\Sales\Model\Status;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\OrderIncrementIdChecker;
use \BroSolutions\GiftBox\Model\Product\Type\GiftBox;


class Quote extends \Magento\Quote\Model\Quote
{
    /**
     * @var \Magento\Sales\Model\OrderIncrementIdChecker
     */
    private $orderIncrementIdChecker;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        \Magento\Catalog\Helper\Product $catalogProduct,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollectionFactory,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Framework\Message\Factory $messageFactory,
        \Magento\Sales\Model\Status\ListFactory $statusListFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Quote\Model\Quote\PaymentFactory $quotePaymentFactory,
        \Magento\Quote\Model\ResourceModel\Quote\Payment\CollectionFactory $quotePaymentCollectionFactory,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Quote\Model\Quote\Item\Processor $itemProcessor,
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Magento\Quote\Model\Cart\CurrencyFactory $currencyFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Model\Quote\TotalsReader $totalsReader,
        \Magento\Quote\Model\ShippingFactory $shippingFactory,
        \Magento\Quote\Model\ShippingAssignmentFactory $shippingAssignmentFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        OrderIncrementIdChecker $orderIncrementIdChecker = null
    )
    {
        parent::__construct(
            $context,
        $registry,
        $extensionFactory,
        $customAttributeFactory,
        $quoteValidator,
        $catalogProduct,
        $scopeConfig,
        $storeManager,
        $config,
        $quoteAddressFactory,
        $customerFactory,
        $groupRepository,
        $quoteItemCollectionFactory,
        $quoteItemFactory,
        $messageFactory,
        $statusListFactory,
        $productRepository,
        $quotePaymentFactory,
        $quotePaymentCollectionFactory,
        $objectCopyService,
        $stockRegistry,
        $itemProcessor,
        $objectFactory,
        $addressRepository,
        $criteriaBuilder,
        $filterBuilder,
        $addressDataFactory,
        $customerDataFactory,
        $customerRepository,
        $dataObjectHelper,
        $extensibleDataObjectConverter,
        $currencyFactory,
        $extensionAttributesJoinProcessor,
        $totalsCollector,
        $totalsReader,
        $shippingFactory,
        $shippingAssignmentFactory,
        $resource,
        $resourceCollection,
        $data,
        $orderIncrementIdChecker
        );
        $this->orderIncrementIdChecker = $orderIncrementIdChecker ?: ObjectManager::getInstance();
    }

    /**
     * Advanced func to add product to quote - processing mode can be specified there.
     * Returns error message if product type instance can't prepare product.
     *
     * @param mixed $product
     * @param null|float|\Magento\Framework\DataObject $request
     * @param null|string $processMode
     * @return \Magento\Quote\Model\Quote\Item|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addProduct(
        \Magento\Catalog\Model\Product $product,
        $request = null,
        $processMode = \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_FULL
    ) {
        if ($request === null) {
            $request = 1;
        }
        if (is_numeric($request)) {
            $request = $this->objectFactory->create(['qty' => $request]);
        }
        if (!$request instanceof \Magento\Framework\DataObject) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We found an invalid request for adding product to quote.')
            );
        }

        if (!$product->isSalable()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Product that you are trying to add is not available.')
            );
        }

        $productType = $product->getData('type_id');
        if($productType == GiftBox::TYPE_GIFTBOX_PRODUCT) {
            if(empty($request->getData('chosenItems'))) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('GiftBox product has to include child products.')
                );
            } else {
                $cartCandidates[] = $this->productRepository->getById($request->getData('product'));

                $childrenGiftBox = $this->getChidrenGiftProducts($request->getData('chosenItems'));

                if(count($childrenGiftBox) == 0) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('GiftBox product has to include valid child products.')
                    );
                }

                // Add to candidates all chosen giftbox children
                foreach ($childrenGiftBox as $childId => $child) {
                    $cartCandidates[] = $this->productRepository->getById($childId);
                }
            }
            if(!is_array($cartCandidates) || (is_array($cartCandidates) && count($cartCandidates) < 2)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('GiftBox product has to include not less two child products.')
                );
            }
        } else {
            $cartCandidates = $product->getTypeInstance()->prepareForCartAdvanced($request, $product, $processMode);
        }

        /**
         * Error message
         */
        if (is_string($cartCandidates) || $cartCandidates instanceof \Magento\Framework\Phrase) {
            return (string)$cartCandidates;
        }

        /**
         * If prepare process return one object
         */
        if (!is_array($cartCandidates)) {
            $cartCandidates = [$cartCandidates];
        }

        $parentItem = null;
        $errors = [];
        $item = null;
        $items = [];
        foreach ($cartCandidates as $candidate) {
            // Child items can be sticked together only within their parent
            $stickWithinParent = $candidate->getParentProductId() ? $parentItem : null;
            $candidate->setStickWithinParent($stickWithinParent);

            if($productType == GiftBox::TYPE_GIFTBOX_PRODUCT) {
                if($candidate->getData('entity_id') != $request->getData('product')) {
                    $childProductId = $candidate->getData('entity_id');
                    $candidate->setParentProductId($candidate->getData('entity_id'));
                    $candidate->setCartQty($childrenGiftBox[$childProductId]);
                }
            }

            $item = $this->getItemByProduct($candidate);

            if (!$item) {
                $item = $this->itemProcessor->init($candidate, $request);
                $item->setQuote($this);
                $item->setOptions($candidate->getCustomOptions());
                $item->setProduct($candidate);

                // Add only item that is not in quote already
                $this->addItem($item);
            }
            $items[] = $item;

            /**
             * As parent item we should always use the item of first added product
             */
            if (!$parentItem) {
                $parentItem = $item;
            }
            if ($parentItem && $candidate->getParentProductId() && !$item->getParentItem()) {
                $item->setParentItem($parentItem);
            }

            $this->itemProcessor->prepare($item, $request, $candidate);

            // collect errors instead of throwing first one
            if ($item->getHasError()) {
                foreach ($item->getMessage(false) as $message) {
                    if (!in_array($message, $errors)) {
                        // filter duplicate messages
                        $errors[] = $message;
                    }
                }
            }
        }
        if (!empty($errors)) {
            throw new \Magento\Framework\Exception\LocalizedException(__(implode("\n", $errors)));
        }

        $this->_eventManager->dispatch('sales_quote_product_add_after', ['items' => $items]);
        return $parentItem;
    }

    /**
     * Remove quote item by item identifier
     *
     * @param   int $itemId
     * @return $this
     */
    public function removeItem($itemId)
    {
        $item = $this->getItemById($itemId);

        if ($item) {
            $item->setQuote($this);
            /**
             * If we remove item from quote - we can't use multishipping mode
             */
            $this->setIsMultiShipping(false);
            $item->isDeleted(true);
            if ($item->getHasChildren()) {
                foreach ($item->getChildren() as $child) {
                    $child->isDeleted(true);
                }
            }

            $parent = $item->getParentItem();
            if($parent && $parent->getProductType() && $parent->getProductType() != GiftBox::TYPE_GIFTBOX_PRODUCT) {
                $parent->isDeleted(true);
            } else {
                // check is deleted item is not a last


            }

            $this->_eventManager->dispatch('sales_quote_remove_item', ['quote_item' => $item]);
        }

        return $this;
    }

    /**
     * @param $types
     * @return array
     */
    protected function getChidrenGiftProducts($types)
    {
        $result = [];

        if(is_array($types) && count($types) > 0) {
            foreach ($types as $type => $children) {
                if(is_array($children) && count($children) > 0) {
                    foreach ($children as $id => $child) {
                        if (!isset($result[$id])) {
                            $result[$id] = 0;
                        }
                        $result[$id] += $child;
                    }
                }
            }
        }

        return $result;
    }
}