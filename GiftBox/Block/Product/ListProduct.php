<?php


namespace BroSolutions\GiftBox\Block\Product;

use Magento\Framework\View\Element\Template;
use BroSolutions\GiftBox\Model\ResourceModel\GiftBox\Collection as giftBoxCollection;
use BroSolutions\GiftBox\Helper\GiftBoxItem;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use BroSolutions\GiftBox\Model\Product\Type\GiftBox;


class ListProduct extends \Magento\Framework\View\Element\Template
{
    const GIFTBOX_PRODUCT_RELATED = 'brosolutions_giftbox_product_related';

    /**
     * @var giftBoxCollection
     */
    public $giftBoxCollection;

    /**
     * @var GiftBoxItem
     */
    public $giftBoxItem;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    public $productRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public $productCollection;

    /**
     * @var
     */
    public $urlBuilder;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resource;

    /**
     * @var array
     */
    public $productsQty = [];

    /**
     * ListProduct constructor.
     * @param Template\Context $context
     * @param giftBoxCollection $giftBoxCollection
     * @param GiftBoxItem $giftBoxItem
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param ImageHelper $imageHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        giftBoxCollection $giftBoxCollection,
        GiftBoxItem $giftBoxItem,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Framework\App\ResourceConnection $resource,
        ImageHelper $imageHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    )
    {
        $this->productCollection = $productCollection;
        $this->productRepository = $productRepository;
        $this->giftBoxCollection = $giftBoxCollection;
        $this->giftBoxItem = $giftBoxItem;
        $this->urlBuilder = $urlBuilder;
        $this->resource = $resource;
        $this->imageHelper = $imageHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerSession = $customerSession;

        parent::__construct(
            $context,
            $data
        );
    }

    public function getProductCollection()
    {
        $result = $this->productCollection->addAttributeToSelect('*','type_id')
                    ->addAttributeToFilter('type_id', array('eq' => 'giftbox'));

        return $result;

    }

    /**
     * Get data about adit giftbox
     *
     * @return mixed
     */
    public function getEditItemInfo()
    {
        $result = '';

        if(!empty($this->getEditGiftbox())) {
            $result = $this->getEditGiftbox();
        }

        return $result;
    }

    /**
     * Get parents product list
     *
     * @return giftBoxCollection
     */
    public function getProductListParents()
    {
        $result = [];
        $configBoxItems = $this->giftBoxItem->getDbGiftBoxItems();
        $productIds = [];

        $data = $this->giftBoxCollection;
        if($data) {
            foreach ($this->giftBoxCollection as $item) {
                $productIds[] = $item['product_related_id'];
                $result[$item['product_id']][$item['id']] = [
                    'id'                 => $item['id'],
                    'product_id'         => $item['product_id'],
                    'type_id'            => $item['type_id'],
                    'product_related_id' => $item['product_related_id'],
                    'position'           => $item['position'],
                    'type_code'          => $item['type_code'],
                    'type_label'         => (isset($configBoxItems[$item['type_id']])) ? $configBoxItems[$item['type_id']]['label'] : '',
                    'type_qty'           => (isset($configBoxItems[$item['type_id']])) ? $configBoxItems[$item['type_id']]['qty'] : 0
                ];
            }
        }

        return $result;
    }

    /**
     * @param $id
     * @param $type_id
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuantityByParentId($id, $type_code)
    {
        if(!isset($this->productsQty[$id][$type_code])) {
            $product = $this->productRepository->getById($id);
            $smallQty = $product->getCustomAttribute('small_qty')->getValue() ;
            $mediumQty = $product->getCustomAttribute('medium_qty')->getValue();
            $largeQty = $product->getCustomAttribute('large_qty')->getValue();

            $this->productsQty[$id] = [
                'small' => $smallQty,
                'medium' => $mediumQty,
                'large' => $largeQty
            ];
        }

        return $this->productsQty[$id][$type_code];
    }


    /**
     * Get product list on parent
     *
     * @return array
     */
    public function getProductListConfig()
    {
        $result = [];
        $configBoxItems = $this->giftBoxItem->getDbGiftBoxItems();

        $data = $this->giftBoxCollection;
        if($data) {
            foreach ($this->giftBoxCollection as $item) {
                $result[$item['product_id']][$item['type_id']][$item['product_related_id']] = [
                    'id'                 => $item['id'],
                    'product_id'         => $item['product_id'],
                    'type_id'            => $item['type_id'],
                    'product_related_id' => $item['product_related_id'],
                    'position'           => $item['position'],
                    'type_code'          => $item['type_code'],
                    'type_label'         => (isset($configBoxItems[$item['type_id']])) ? $configBoxItems[$item['type_id']]['label'] : '',
                    'type_qty'           => (isset($configBoxItems[$item['type_id']])) ? $configBoxItems[$item['type_id']]['qty'] : 0
                ];
            }
        }

        return $result;
    }


    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQtyConfig()
    {
        $result = [];
        $giftBoxList = $this->giftBoxCollection->getData();

        if($giftBoxList) {
            $parents =  array_unique(array_column($giftBoxList, 'product_id'));
            foreach ($this->giftBoxCollection as $item) {
                $result[$item['product_id']][$item['type_id']] = $this->getQuantityByParentId($item['product_id'], $item['type_code']);
            }
        }

        return $result;
    }

    /**
     * Get giftbox children products on types
     *
     * @return array
     */
    public function getProductListChildren()
    {
        $result = [];
        $configBoxItems = $this->giftBoxItem->getDbGiftBoxItems();

        $data = $this->giftBoxCollection->getData();
        if($data) {
            foreach ($this->giftBoxCollection as $item) {

                $result[$item['type_id']][$item['product_related_id']] = [
                    'id'                 => $item['id'],
                    'product_id'         => $item['product_id'],
                    'type_id'            => $item['type_id'],
                    'product_related_id' => $item['product_related_id'],
                    'position'           => $item['position'],
                    'type_code'          => $item['type_code'],
                    'type_label'         => (isset($configBoxItems[$item['type_id']])) ? $configBoxItems[$item['type_id']]['label'] : '',
                    'type_qty'           => (isset($configBoxItems[$item['type_id']])) ? $configBoxItems[$item['type_id']]['qty'] : 0
                ];
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getQtyOnTypes()
    {
        $result = [];
        $connection = $this->resource->getConnection();

        $tableName = $this->resource->getTableName(self::GIFTBOX_PRODUCT_RELATED);

        $select = $connection->select()->from($connection->getTableName($tableName),
            ['product_id', 'type_id', 'qty' => new \Zend_Db_Expr('COUNT(*)')])
            ->group(['product_id', 'type_id']);
        $products = $connection->fetchAll($select);

        foreach ($products as $product) {
            if(!isset($result[$product['product_id']][$product['type_id']])) {
                $result[$product['product_id']][$product['type_id']] = $product['qty'];
            }
        }

        return $result;
    }

    /**
     * @param $id
     * @return int
     */
    public function getGiftBoxItemById($id)
    {
        return ($this->giftBoxItem[$id]) ? $this->giftBoxItem[$id] : 0;
    }

    /**
     * @param $id
     * @return |null
     */
    public function getGiftBoxItemsById($id)
    {
        $result = [];
        $configBoxItems = $this->giftBoxItem->getDbGiftBoxItems();

        return $configBoxItems[$id] ? $configBoxItems[$id] : null;
    }


    /**
     * @param $id
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductNameById($id)
    {
        $product = $this->productRepository->getById($id);
        return $product->getName();
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductDescriptionById($id)
    {
        $product = $this->productRepository->getById($id);
        return $product->getShortDescription();
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->giftBoxItem->getDefaultGiftBoxItems();
    }

    /**
     * @param $id
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductImgUrlByProductId($id)
    {
        $product = $this->productRepository->getById($id);

        $imageUrl = ($product->getData('image')) ? $this->getMediaUrl() . 'catalog/product/' . $product->getData('image') : '';

        // $img = $this->imageHelper->init($product, 'product_listing_thumbnail')->getUrl();
        // $this->getImage($product, 'mini_cart_product_thumbnail')->toHtml();

        $result = [
            'image_url'       => $imageUrl,
            'image_label' => $product->getData('image_label')
        ];

        return $result;
    }

    /**
     * @return string
     */
    public function getMediaUrl() {
        return $this->urlBuilder->getBaseUrl(['_type' => $this->urlBuilder::URL_TYPE_MEDIA]);
    }
}