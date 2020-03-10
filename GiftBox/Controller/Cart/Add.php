<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Cart
 */
namespace BroSolutions\GiftBox\Controller\Cart;


use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;

class Add extends \Magento\Checkout\Controller\Cart\Add
{

    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $cartHelper;

    /**
     * @var \BroSolutions\GiftBox\Model\Quote\Item
     */
    protected $quoteItem;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \BroSolutions\GiftBox\Model\Quote\Item $quoteItem
    )
    {
        $this->cartHelper = $cartHelper;
        $this->quoteItem = $quoteItem;
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $productRepository
        );
    }

    public function execute()
    {

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(
                __('Your session has expired')
            );
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $params = $this->getRequest()->getParams();

        if(!$this->validateChildren($params)) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        // Check for is it edit of existing Quote
        if(isset($params['isEdit']) &&
            $params['isEdit'] == true &&
            !empty($params['editQuoteItemId'])) {

            $this->quoteItem->editGiftBox($params);
            return $this->goBack();
        }

        try {
            if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get(
                        \Magento\Framework\Locale\ResolverInterface::class
                    )->getLocale()]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                return $this->goBack();
            }

            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }

            $this->cart->save();

            /**
             * @todo remove wishlist observer \Magento\Wishlist\Observer\AddToCart
             */
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                if (!$this->cart->getQuote()->getHasError()) {
                    $message = __(
                        'You added %1 to your shopping cart.',
                        $product->getName()
                    );
                    $this->messageManager->addSuccessMessage($message);
                }
                return $this->goBack(null, $product);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNotice(
                    $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addError(
                        $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($message)
                    );
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);

            if (!$url) {
                $url = $this->_redirect->getRedirectUrl($this->cartHelper->getCartUrl());
            }

            return $this->goBack($url);
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            return $this->goBack();
        }
    }

    /**
     * @param $params
     * @return bool
     */
    private function validateChildren($params)
    {
        $result = false;

        if(isset($params['chosenItems']) &&
            is_array($params['chosenItems']) &&
            count($params['chosenItems']) > 0) {

            foreach ($params['chosenItems'] as $type => $children) {
                if(is_array($children) && count($children) > 0) {
                    $result = true;
                    $wrongArray = array_filter($children, function($number) {
                        return empty($number);
                    });

                    if(is_array($wrongArray) && count($wrongArray) > 0) {
                        $result = false;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param $params
     */
    private function validateGiftBoxMessage($params)
    {
        if(!empty($params['giftMsg'])) {
            $msg = trim(strip_tags($params['giftMsg']));
        }
    }
}
