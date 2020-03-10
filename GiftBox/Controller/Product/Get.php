<?php


namespace BroSolutions\GiftBox\Controller\Product;

use Magento\Framework\App\Action\Action;

class Get extends Action
{

    private $resultJsonFactory;

    protected $helperProductList;

    protected $_currency;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \BroSolutions\GiftBox\Helper\ProductList $helperProductList,
        \Magento\Directory\Model\Currency $currency
    ) {
        $this->currency = $currency;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helperProductList = $helperProductList;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if(!empty($params['parentProduct'])) {
            $parentProductId = (int)$params['parentProduct'];
            $listProduct = $this->helperProductList->getChildrenByParentId($parentProductId);
            $priceMsg = $this->helperProductList->getPriceMessageByProductId($parentProductId);
        }

        $data = [
            'products'       => $listProduct,
            'price_message'  => $priceMsg,
            'currency_symbol' => $this->currency->getCurrencySymbol()
            ];

        return $this->resultJsonFactory->create()->setData($data);
    }

}