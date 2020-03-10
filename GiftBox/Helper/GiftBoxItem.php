<?php

namespace BroSolutions\GiftBox\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \BroSolutions\GiftBox\Model\Product\Type\GiftBox;

class GiftBoxItem extends \Magento\Framework\App\Helper\AbstractHelper
{
    const PATH_CATALOG_GIFTBOX_CONFIG = 'catalog/giftbox/config';

    public function __construct(
        Context $context,
        SerializerInterface $serializer,
        ResourceConnection $resource,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->resource = $resource;
        $this->serializer = $serializer;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getDefaultGiftBoxItems()
    {
        return [
            1 => ['type_id' => 1,
                  'code'    => 'small',
                  'label'   => 'small',
                  'qty'     => 4,
                  'active'  => 1],
            2 => ['type_id' => 2,
                  'code'    => 'medium',
                  'label'   => 'medium',
                  'qty'     => 8,
                  'active'  => 1],
            3 => ['type_id' => 3,
                  'code'    => 'large',
                  'label'   => 'large',
                  'qty'     => 12,
                  'active'  => 1],
            4 => ['type_id' => 4,
                  'code'    => 'X-large',
                  'label'   => 'X-large',
                  'qty' => 16,
                  'active'  => 0]
        ];
    }

    /**
     * @return array
     */
    public function getCongigGiftBoxItems()
    {
        $result = [];
        $data = $this->scopeConfig->getValue(
            self::PATH_CATALOG_GIFTBOX_CONFIG,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        if($data) {
            $result = $this->serializer->unserialize($data);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getDbGiftBoxItems()
    {
        $result = [];
        $types = $this->getDbGiftBoxTypes();
        $items = $this->getCongigGiftBoxItems();
        if(is_array($types) && count($types)> 0) {
            if(is_array($items) && count($items)> 0) {
                foreach ($types as $key => $type) {
                    if (isset($items[$key])) {
                        $result[$key] = [
                            'type_id' => $key,
                            'code' => $type['code'],
                            'label' => $items[$key]['typeitems'],
                            'qty' => $items[$key]['quantity']
                        ];
                    } else {
                        continue;
                    }
                }
            } else {
                $drfConfigItems = $this->getDefaultGiftBoxItems();
                foreach ($types as $key => $type) {
                    if(isset($drfConfigItems[$key])) {
                        $result[$key] = [
                            'type_id' => $key,
                            'code' => $type['code'],
                            'label' => $drfConfigItems[$key]['label'],
                            'qty' => $drfConfigItems[$key]['qty'],
                        ];
                    } else {
                        continue;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getDbGiftBoxTypes()
    {
        $result = [];
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('brosolutions_giftbox_type');
        $select = $connection->select()->from($connection->getTableName($tableName), ['type_id', 'code']);
        $types = $connection->fetchAll($select);

        foreach ($types as $type) {
            $result[$type['type_id']] = $type;
        }

        return $result;
    }
}