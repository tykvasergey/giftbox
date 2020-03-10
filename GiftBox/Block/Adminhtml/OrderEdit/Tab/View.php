<?php


namespace BroSolutions\GiftBox\Block\Adminhtml\OrderEdit\Tab;


class View extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_template = 'BroSolutions_GiftBox::tab/view/order_info.phtml';

    /**
     * @var \BroSolutions\GiftBox\Model\ResourceModel\GiftBoxMessage
     */
    protected $giftBoxMessage;

    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \BroSolutions\GiftBox\Model\ResourceModel\GiftBoxMessage $giftBoxMessage,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->giftBoxMessage = $giftBoxMessage;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMessages()
    {
        $result = [];
        $order = $this->getOrder();
        if($quoteId = $order->getQuoteId()) {
            $result = $this->giftBoxMessage->getMessagesByQuoteId($quoteId);
        }

        return $result;
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }

    /**
     * Retrieve order increment id
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Gift Box messages');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Gift Box messages');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

}