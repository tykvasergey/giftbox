<?php


namespace BroSolutions\GiftBox\Model\Product\Type;


use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;

class GiftBox extends \Magento\Catalog\Model\Product\Type\AbstractType
{

    const TYPE_GIFTBOX_PRODUCT = 'giftbox';

    public static $relatedTypes = ['small' => 1, 'medium' => 2, 'large' => 3];

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * GiftBox constructor.
     * @param \Magento\Catalog\Model\Product\Option $catalogProductOption
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Catalog\Model\Product\Type $catalogProductType
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Psr\Log\LoggerInterface $logger
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     * @param \Magento\Framework\App\Request\Http $request
     * @param \BroSolutions\GiftBox\Model\ResourceModel\Product\Attribute\Backend\GiftBoxRelated $giftBoxRelated
     */
    public function __construct(
        \Magento\Catalog\Model\Product\Option $catalogProductOption,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Catalog\Model\Product\Type $catalogProductType,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Registry $coreRegistry,
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        \Magento\Framework\App\Request\Http $request,
        \BroSolutions\GiftBox\Model\ResourceModel\Product\Attribute\Backend\GiftBoxRelated $giftBoxRelated
    )
    {
        $this->request = $request;
        $this->giftBoxRelated = $giftBoxRelated;

        parent::__construct(
            $catalogProductOption,
            $eavConfig,
            $catalogProductType,
            $eventManager,
            $fileStorageDb,
            $filesystem,
            $coreRegistry,
            $logger,
            $productRepository,
            $serializer
        );
    }

    public function deleteTypeSpecificData(\Magento\Catalog\Model\Product $product) {}

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product\Type\AbstractType
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave($product)
    {
        try {
            $post = $this->request->getParams();

            $listProducts = [];

            if(array_key_exists('giftbox', $post) && array_key_exists('product', $post)) {
                if(empty($post['id'])) {
                    throw new \Magento\Framework\Exception\CouldNotSaveException(__("Please save product before adding GiftBox options !"));
                }

                $listProducts = [];
                 $types = array_keys($post['giftbox']);
                 if(is_array($types) && count($types) > 0) {
                     foreach ($post['giftbox'] as $key => $type) {
                         if(is_array($type) && count($type) > 0) {
                             foreach ($type as $item)
                             {
                                 $listProducts[] = [
                                     'product_id' => $post['id'],
                                     'type_id' => self::$relatedTypes[$key],
                                     'product_related_id' => $item['id']
                                 ];
                             }
                         }
                     }
                 }

                 $this->giftBoxRelated->insertElements($listProducts, $product);
            } else {
                $this->giftBoxRelated->insertElements($listProducts, $product);
            }
        } catch (Exception $e) {}

        return parent::beforeSave($product);
    }


    /**
     * @param DataObject $buyRequest
     * @param \Magento\Catalog\Model\Product $product
     * @param string $processMode
     * @return array|\Magento\Framework\Phrase|string
     */
    protected function _prepareProduct(DataObject $buyRequest, $product, $processMode)
    {
        $result = parent::_prepareProduct($buyRequest, $product, $processMode);

        if (is_string($result)) {
            return $result;
        }

        try {
            //$related = $this->_validate($buyRequest, $product, $processMode);
        } catch (\Exception $e) {
            return $e->getMessage();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return __('An error has occurred while preparing Gift Card.');
        }

        return $result;

    }

    /**
     * @param DataObject $buyRequest
     * @param $product
     * @param $processMode
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function _validate(DataObject $buyRequest, $product, $processMode)
    {
        $currentProduct = $this->productRepository->getById($product->getId());
        $relatedProducts = $this->getRelatedProducts($currentProduct);

    }

    private function getRelatedProducts($currentProduct) {}
}