<?php


namespace BroSolutions\GiftBox\Model\ResourceModel\GiftBox;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('BroSolutions\GiftBox\Model\GiftBox', 'BroSolutions\GiftBox\Model\ResourceModel\GiftBox');
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
    }

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    )
    {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }

    public function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->joinLeft(
            ['giftbox_type' => $this->getTable('brosolutions_giftbox_type')],
            "main_table.type_id = giftbox_type.type_id",
            [])
            ->columns(['type_code' => 'giftbox_type.code']);

        return $this;
    }
}