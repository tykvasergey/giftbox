<?php

namespace BroSolutions\GiftBox\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use BroSolutions\GiftBox\Model\Product\Type\GiftBox;
use Magento\Catalog\Model\Product;

class InstallData implements InstallDataInterface
{
    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var
     */
    protected $_pageFactory;

    /**
     * InstallData constructor.
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param BroSolutions\GiftBox\Helper\GiftBoxItem $boxItemHelper
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup,
        \BroSolutions\GiftBox\Helper\GiftBoxItem $boxItemHelper,
        \Magento\Cms\Model\PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->boxItemHelper = $boxItemHelper;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $fieldList = [
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'minimal_price',
            'cost',
            'tier_price',
            'weight',
            'price_type',
            'sku_type',
            'weight_type',
            'price_view',
            'shipment_type',
        ];

        $productType = GiftBox::TYPE_GIFTBOX_PRODUCT;

        foreach ($fieldList as $field)
        {
            $applyTo = explode(
                ',',
                $eavSetup->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $field, 'apply_to')
            );
            if (!in_array($productType, $applyTo)) {
                $applyTo[] = $productType;
                $eavSetup->updateAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $field,
                    'apply_to',
                    implode(',', $applyTo)
                );
            }
        }

        /**
         * Install product types
         */

        $defaultTypes = $this->boxItemHelper->getDefaultGiftBoxItems();
        $data = [];
        foreach ($defaultTypes as $key => $defaultType) {
            $data[] = ['type_id' => $key, 'code' => $defaultType['code']];
        }

        foreach ($data as $bind) {
            $this->moduleDataSetup->getConnection()->insertForce(
                $this->moduleDataSetup->getTable(
                    'brosolutions_giftbox_type'
                ),
                $bind
            );
        }

        /**
         * Create CMS page
         */
        $page = $this->_pageFactory->create();
        $page->setTitle('Gift Box')
            ->setIdentifier('giftbox')
            ->setIsActive(true)
            ->setPageLayout('1column')
            ->setStores(array(0))
            ->setContent('')
            ->save();


        /**
         *  Create product attributes for giftbox
         */
        $defaultTypes = $this->boxItemHelper->getDefaultGiftBoxItems();
        foreach ($defaultTypes as $type) {
            if($type['active'] && $type['code']) {

                $nameAttribute = $type['code'] . '_qty';
                $eavSetup->addAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $nameAttribute,
                    [
                        'group' => 'Product Details',
                        'type' => 'int',
                        'label' => 'Quantity for choose on Front for ' . $type['code'],
                        'input' => 'text',
                        'frontend_class' => 'validate-length maximum-length-30',
                        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'required' => false,
                        'is_user_defined' => 1,
                        'default' => 0,
                        'searchable' => false,
                        'filterable' => true,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'used_in_product_listing' => true,
                        'unique' => false,
                        'apply_to'=> GiftBox::TYPE_GIFTBOX_PRODUCT
                    ]
                );
            }
        }

        /**
         * Attribute price on message for product GiftBox
         */
        $codeAttribute = 'bro_price_message';
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->removeAttribute(Product::ENTITY, $codeAttribute);
        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        $attribute = $eavSetup->getAttribute($entityTypeId, $codeAttribute);

        if (!isset($attribute['attribute_id'])) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                $codeAttribute,
                [
                    'group' => 'General',
                    'type' => 'decimal',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Price for message',
                    'input' => 'price',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => '0',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => true,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'apply_to' => GiftBox::TYPE_GIFTBOX_PRODUCT
                ]
            );
        }
    }
}
