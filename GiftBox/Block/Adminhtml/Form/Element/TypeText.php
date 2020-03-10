<?php


namespace BroSolutions\GiftBox\Block\Adminhtml\Form\Element;


class TypeText extends \Magento\Framework\View\Element\AbstractBlock
{

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        array $data = []
    ){
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        $elId = $this->getInputId();
        $elName = $this->getInputName();
        $colName = $this->getColumnName();
        $column = $this->getColumn();

        /* disabled="disabled" */
        return '<input type="text" id="' . $elId .
            '"' .
            ' name="' .
            $elName .
            '" value="<%- ' .
            $colName .
            ' %>" ' .
            ($column['size'] ? 'size="' .
                $column['size'] .
                '"' : '') .
            ' class="' .
            (isset($column['class'])
                ? $column['class']
                : 'input-text') . '"' . (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') . '/>';
    }
}