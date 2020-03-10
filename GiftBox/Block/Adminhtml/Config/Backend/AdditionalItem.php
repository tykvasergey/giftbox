<?php


namespace BroSolutions\GiftBox\Block\Adminhtml\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;

class AdditionalItem extends ConfigValue
{

    protected $serializer;

    /**
     * AdditionalItem constructor.
     * @param SerializerInterface $serializer
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param \BroSolutions\GiftBox\Helper\GiftBoxItem $boxItemHelper
     * @param array $data
     */
    public function __construct(
        SerializerInterface $serializer,
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        \BroSolutions\GiftBox\Helper\GiftBoxItem $boxItemHelper,
        array $data = []
    ) {
        $this->boxItemHelper = $boxItemHelper;
        $this->resourceCollection = $resourceCollection;
        $this->serializer = $serializer;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $value = $this->getValue();
        unset($value['__empty']);
        $encodedValue = $this->serializer->serialize($value);

        $this->setValue($encodedValue);
    }

    protected function _afterLoad()
    {
        /** @var string $value */
        $value = $this->getValue();
        $decodedValue = [];

        if($value) {
            $decodedValue = $this->serializer->unserialize($value);
        } else {
            $types = $this->boxItemHelper->getDbGiftBoxItems();
            foreach ($types as $type) {
                $decodedValue[$type['type_id']] = [
                    'typeitems' => $type['label'],
                    'quantity' => $type['qty']
                ];
            }
        }
        $this->setValue($decodedValue);

    }
}