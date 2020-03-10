<?php


namespace BroSolutions\GiftBox\Api\Data;


interface GiftBoxSearchResultInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \BroSolutions\GiftBox\Api\Data\GiftBoxInterface[]
     */
    public function getItems();

    /**
     * @param \BroSolutions\GiftBox\Api\Data\GiftBoxInterface[] $items
     * @return $this
     */
    public function setItems(array $items);

}