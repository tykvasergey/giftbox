<?php


namespace BroSolutions\GiftBox\Model\Order\Pdf\Items\Shipment;

use BroSolutions\GiftBox\Model\Product\Type\GiftBox;

class DefaultShipment extends \Magento\Sales\Model\Order\Pdf\Items\Shipment\DefaultShipment
{

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $taxData,
            $filesystem,
            $filterManager,
            $string,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Draw item line
     *
     * @return void
     */
    public function draw()
    {
        $order = $this->getOrder();
        $item = $this->getItem();
        $type = $item->getOrderItem()->getProductType();
        if($type == GiftBox::TYPE_GIFTBOX_PRODUCT) {
            $parentId = $order->getId();
            $gifts = $this->getGifts($order, $item);
        }

        $pdf = $this->getPdf();
        $page = $this->getPage();
        $lines = [];

        // draw Product name
        $lines[0] = [['text' => $this->string->split($item->getName(), 60, true, true), 'feed' => 100]];

        // draw QTY
        $lines[0][] = ['text' => $item->getQty() * 1, 'feed' => 35];

        // draw SKU
        $lines[0][] = [
            'text' => $this->string->split($this->getSku($item), 25),
            'feed' => 565,
            'align' => 'right',
        ];

        if(isset($gifts) && is_array($gifts) && count($gifts) > 0) {
            $lines[1] = [['text' => $this->string->split('Gifts:', 60, true, true), 'feed' => 100]];
            $i = 2;
            foreach ($gifts as $gift) {
                $lines[$i] = [['text' => $this->string->split($i - 1 .'. ' . $gift['name'] . ' ( ' . $gift['sku'] . ' ) ' , 60, true, true), 'feed' => 100]];
                $i++;
            }
        }

        // Custom options
        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                // draw options label
                $lines[][] = [
                    'text' => $this->string->split($this->filterManager->stripTags($option['label']), 70, true, true),
                    'font' => 'italic',
                    'feed' => 110,
                ];

                // draw options value
                if ($option['value']!= null) {
                    $printValue = isset(
                        $option['print_value']
                    ) ? $option['print_value'] : $this->filterManager->stripTags(
                        $option['value']
                    );
                    $values = explode(', ', $printValue);
                    foreach ($values as $value) {
                        $lines[][] = ['text' => $this->string->split($value, 50, true, true), 'feed' => 115];
                    }
                }
            }
        }

        $lineBlock = ['lines' => $lines, 'height' => 20];

        $page = $pdf->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $this->setPage($page);
    }

    /**
     * @param $order
     * @param $parentId
     * @return array
     */
    public function getGifts($order, $parentItem)
    {
        $orderItems = $order->getAllItems();
        $gifts = [];
        foreach ($orderItems as $item) {
            if(!empty($item->getParentItemId()) && $item->getParentItemId() == $parentItem->getOrderItemId()) {
                $gifts[] = ['name' => $item->getName(), 'sku' => $item->getSku()];
            }
        }

        return $gifts;
    }
}