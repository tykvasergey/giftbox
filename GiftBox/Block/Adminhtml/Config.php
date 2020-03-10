<?php

namespace BroSolutions\GiftBox\Block\Adminhtml;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;

class Config extends AbstractFieldArray
{
    protected $_typeblockOptions;
    protected $_cmsblockOptions;
    protected $_template = 'BroSolutions_GiftBox::system/config/form/field/array.phtml';
    private $typeRenderer;

    public function __construct(
        Context $context,
        \BroSolutions\GiftBox\Block\Adminhtml\Form\Element\TypeText $typeText,
        array $data = []
    ) {
        $this->typeText = $typeText;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'typeitems',
            [
                'label' => __('Type Item ( Label )'),
                'size' => '250px',
                'class' => 'required-entry',
                'renderer' => $this->typeText
            ]
        );

        $this->addColumn(
            'quantity',
            [
                'label' => __('Quantity'),
                'size' => '50px',
                'class' => 'required-entry'
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}