<?php


namespace BroSolutions\GiftBox\Controller\Edit;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Item extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var \BroSolutions\GiftBox\Model\Quote\GiftBoxQuote
     */
    protected $giftBoxQuote;

    /**
     * Item constructor.
     * @param Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \BroSolutions\GiftBox\Helper\Cart $cartHelper
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \BroSolutions\GiftBox\Helper\Cart $cartHelper,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \BroSolutions\GiftBox\Model\Quote\GiftBoxQuote $giftBoxQuote
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->cartHelper = $cartHelper;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->giftBoxQuote = $giftBoxQuote;
        parent::__construct($context);
    }

    public function execute()
    {
        $data = [];
        $quoteItemId = $this->getRequest()->getParam('id');
        if($quoteItemId) {
            $cartItemInfo = $this->giftBoxQuote->getGiftBoxCard($quoteItemId);

            if($cartItemInfo) {
                $activeParentId = $cartItemInfo['parent']['product_id'];
                $chosenItems = $cartItemInfo['children'];
                $message = $cartItemInfo['parent']['message'];
                $quote = ['quote_id' => $cartItemInfo['quote'], 'parent_quote_item_id' => $quoteItemId];

                $data = [
                    'activeParentId' => $activeParentId,
                    'chosenItems' => $chosenItems,
                    'message' => $message,
                    'quote' => $quote
                ];
            }
        }

        if(empty($data)) {
            return $this->resultRedirectFactory->create()->setPath('giftbox');
        } else {
            $page = $this->resultPageFactory->create();
            $page->getLayout()
                ->getBlock('brosolutions.giftbox.block.product.listproduct')
                ->setData('edit_giftbox', $data);

            return $page;
        }
    }
}
