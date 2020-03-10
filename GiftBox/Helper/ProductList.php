<?php


namespace BroSolutions\GiftBox\Helper;

use Magento\Framework\App\Helper\Context;
use BroSolutions\GiftBox\Model\GiftBoxRepository;
use Magento\Catalog\Api\ProductRepositoryInterface;
use BroSolutions\GiftBox\Helper\GiftBoxItem;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\Filter;
use Magento\Catalog\Helper\Image as ImageHelper;

class ProductList extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var GiftBoxRepository
     */
    protected $giftBoxRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \BroSolutions\GiftBox\Helper\GiftBoxItem
     */
    protected $giftBoxItem;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var
     */
    public $urlBuilder;

    /**
     * ProductList constructor.
     * @param Context $context
     * @param GiftBoxRepository $giftBoxRepository
     * @param ProductRepositoryInterface $productRepository
     * @param \BroSolutions\GiftBox\Helper\GiftBoxItem $giftBoxItem
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ImageHelper $imageHelper
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     */
    public function __construct(
        Context $context,
        GiftBoxRepository $giftBoxRepository,
        ProductRepositoryInterface $productRepository,
        GiftBoxItem $giftBoxItem,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ImageHelper $imageHelper,
        \Magento\Backend\Model\UrlInterface $urlBuilder
    ) {
        $this->giftBoxRepository = $giftBoxRepository;
        $this->productRepository = $productRepository;
        $this->giftBoxItem = $giftBoxItem;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->imageHelper = $imageHelper;
        $this->urlBuilder = $urlBuilder;

        parent::__construct($context);
    }

    /**
     * @param $parentId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getChildrenByParentId($parentId)
    {

        $result = [];
        $productIds = [];

        $productParent = $this->productRepository->getById($parentId);
        $children = $this->giftBoxRepository->getList($productParent);

        foreach ($children as $child) {
            $typeId = $child->getData('type_id');
            $productRelatedIid = $child->getData('product_related_id');

            $result[$typeId][$productRelatedIid] = ['type_id' => $typeId, 'product_id' => $productRelatedIid];
            $productIds[] = $productRelatedIid;
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $productIds, 'in')
            ->create();

        $childrenProducts = $this->productRepository->getList($searchCriteria)->getItems();

        foreach ($childrenProducts as $product) {
            $productId = $product->getId();
            foreach ($result as $typeId => $types) {
                if(isset($result[$typeId][$productId])) {
                    $imageUrl = ($product->getData('image')) ? $this->getMediaUrl() . 'catalog/product/' . $product->getData('image') : '';

                    $result[$typeId][$productId]['description'] = $product->getShortDescription();
                    $result[$typeId][$productId]['name'] = $product->getName();
                    $result[$typeId][$productId]['image'] = $imageUrl;
                    $result[$typeId][$productId]['url_key'] = $product->getUrlKey();
                }
            }
        }

        return $result;
    }

    /**
     * @param $id
     * @return int|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPriceMessageByProductId($id)
    {
        $attributePriceMsg = 'bro_price_message';
        $result = 0;
        if($id) {
            $product = $this->productRepository->getById($id);
            if($product->getCustomAttribute($attributePriceMsg)) {
                $result = $product->getCustomAttribute($attributePriceMsg)->getValue();
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getMediaUrl() {
        return $this->urlBuilder->getBaseUrl(['_type' => $this->urlBuilder::URL_TYPE_MEDIA]);
    }
}