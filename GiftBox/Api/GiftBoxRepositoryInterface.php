<?php


namespace BroSolutions\GiftBox\Api;


interface GiftBoxRepositoryInterface
{
    /**
     * Save
     *
     * @param \BroSolutions\GiftBox\Api\Data\GiftBoxInterface $giftBox
     * @return \BroSolutions\GiftBox\Api\Data\GiftBoxInterface
     */
    public function save(\BroSolutions\GiftBox\Api\Data\GiftBoxInterface $giftBox);

    /**
     * Get by id
     *
     * @param int $giftBoxId
     * @return \BroSolutions\GiftBox\Api\Data\GiftBoxInterface
     */
    public function getById($giftBoxId);

    /**
     * Delete
     *
     * @param \BroSolutions\GiftBox\Api\Data\GiftBoxInterface $giftBox
     * @return bool true on success
     */
    public function delete(\BroSolutions\GiftBox\Api\Data\GiftBoxInterface $giftBox);

    /**
     * Delete by id
     *
     * @param int $giftBoxId
     * @return bool true on success
     */
    public function deleteById($giftBoxId);

    /**
     * Lists
     *
     * @return \BroSolutions\GiftBox\Api\Data\GiftBoxInterface[] Array of items.
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     */
    public function getList(\Magento\Catalog\Api\Data\ProductInterface $product);

}