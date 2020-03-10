<?php


namespace BroSolutions\GiftBox\Model;

use BroSolutions\GiftBox\Api\Data\GiftBoxInterface;
use Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Model\ResourceModel\AbstractResource;

class GiftBox extends AbstractModel implements GiftBoxInterface
{

    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @return int|mixed
     */
    public function getProductId()
    {
        return $this->_getData(GiftBoxInterface::PRODUCT_ID);
    }

    /**
     * @param int $productId
     * @return GiftBoxInterface|void
     */
    public function setProductId($productId)
    {
        $this->setData(GiftBoxInterface::PRODUCT_ID, $productId);
    }

    /**
     * @return int|void|null
     */
    public function getTypeId()
    {
        return $this->_getData(GiftBoxInterface::TYPE_ID);
    }

    /**
     * @param int|null $typeId
     * @return GiftBoxInterface|void
     */
    public function setTypeId($typeId)
    {
        $this->setData(GiftBoxInterface::TYPE_ID, $typeId);
    }

    /**
     * @return int|void
     */
    public function getProductRelatedId()
    {
        return $this->_getData(GiftBoxInterface::PRODUCT_RELATED_ID);
    }

    /**
     * @param int $productRelatedId
     * @return GiftBoxInterface|void
     */
    public function setProductRelatedId($productRelatedId)
    {
        $this->setData(GiftBoxInterface::PRODUCT_RELATED_ID, $productRelatedId);
    }

    /**
     * @return text
     */
    public function getTypeCode()
    {
        return $this->_getData(GiftBoxInterface::TYPE_CODE);
    }

    /**
     * @param int $typeCode
     * @return GiftBoxInterface|void
     */
    public function setTypeCode($typeCode)
    {
        $this->setData(GiftBoxInterface::TYPE_CODE, $typeCode);
    }

    /**
     * @return \BroSolutions\GiftBox\Api\Data\text|mixed
     */
    public function getPosition()
    {
        return $this->_getData(GiftBoxInterface::POSITION);
    }

    /**
     * @param int $position
     * @return GiftBoxInterface|void
     */
    public function setPosition($position)
    {
        $this->setData(GiftBoxInterface::POSITION, $position);
    }
}