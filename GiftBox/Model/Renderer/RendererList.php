<?php


namespace BroSolutions\GiftBox\Model\Renderer;

use \Magento\Framework\View\Element\Context;
use \Magento\Framework\View\Element\BlockInterface;
use \BroSolutions\GiftBox\Model\Product\Type\GiftBox;
use \BroSolutions\GiftBox\Helper\Cart as cartHelper;

class RendererList extends \Magento\Framework\View\Element\RendererList
{
    public $cartHelper;

    public function __construct(
        Context $context,
        cartHelper $cartHelper,
        array $data = [])
    {
        $this->cartHelper = $cartHelper;
        parent::__construct(
            $context,
            $data
        );
    }

    public function getRenderer($type, $default = null, $rendererTemplate = null)
    {

        if($type == GiftBox::TYPE_GIFTBOX_PRODUCT) {
            $rendererTemplate = 'BroSolutions_GiftBox::cart/item.phtml';
        }

        /** @var \Magento\Framework\View\Element\Template $renderer */
        $renderer = $this->getChildBlock($type) ?: $this->getChildBlock($default);
        if (!$renderer instanceof BlockInterface) {
            throw new \RuntimeException('Renderer for type "' . $type . '" does not exist.');
        }
        $renderer->setRenderedBlock($this);

        if (!isset($this->rendererTemplates[$type])) {
            $this->rendererTemplates[$type] = $renderer->getTemplate();
        } else {
            $renderer->setTemplate($this->rendererTemplates[$type]);
        }

        if ($rendererTemplate) {
            $renderer->setTemplate($rendererTemplate);
        }
        return $renderer;
    }
}