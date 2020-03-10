<?php


namespace BroSolutions\GiftBox\Api\Data;


interface GiftBoxInterface
{

    /**#@+
     * Constants defined for keys of data array
     */
    const PRODUCT_ID = 'product_id';
    const TYPE_ID = 'type_id';
    const PRODUCT_RELATED_ID = 'product_related_id';
    const TYPE_CODE = 'type_code';
    const POSITION = 'position';

    /**#@-*/

    /**
     * @return int
     */
    public function getProductId();

    /**
     * @param int $productId
     *
     * @return \BroSolutions\GiftBox\Api\Data\GiftBoxInterface
     */
    public function setProductId($productId);

    /**
     * @return int|null
     */
    public function getTypeId();

    /**
     * @param int|null $typeId
     *
     * @return \BroSolutions\GiftBox\Api\Data\GiftBoxInterface
     */
    public function setTypeId($typeId);

    /**
     * @return int
     */
    public function getProductRelatedId();

    /**
     * @param int $productRelatedId
     *
     * @return \BroSolutions\GiftBox\Api\Data\GiftBoxInterface
     */
    public function setProductRelatedId($productRelatedId);

    /**
     * @return text
     */
    public function getTypeCode();

    /**
     * @param int $typeCode
     *
     * @return \BroSolutions\GiftBox\Api\Data\GiftBoxInterface
     */
    public function setTypeCode($typeCode);

    /**
     * @return text
     */
    public function getPosition();

    /**
     * @param int $position
     *
     * @return \BroSolutions\GiftBox\Api\Data\GiftBoxInterface
     */
    public function setPosition($position);
}