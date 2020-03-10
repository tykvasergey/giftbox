<?php


namespace BroSolutions\GiftBox\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\ProductLinkRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use BroSolutions\GiftBox\Api\GiftBoxRepositoryInterface;
use BroSolutions\GiftBox\Api\Data\GiftBoxInterface;
use BroSolutions\GiftBox\Model\Product\Type\GiftBox as TypeGiftBox;


class GiftBox extends AbstractModifier
{
    const DATA_SCOPE = '';
    const GROUP_GIFTBOX = 'giftbox';
    const DATA_SCOPE_SMALL = 'small';
    const DATA_SCOPE_MEDIUM = 'medium';
    const DATA_SCOPE_LARGE = 'large';
    const GROUP_CONTENT = 'content';
    const SORT_ORDER = 100;


    /**
     * @var LocatorInterface
     * @since 101.0.0
     */
    protected $locator;

    /**
     * @var UrlInterface
     * @since 101.0.0
     */
    protected $urlBuilder;

    /**
     * @var ProductLinkRepositoryInterface
     * @since 101.0.0
     */
    protected $productLinkRepository;

    /**
     * @var ProductRepositoryInterface
     * @since 101.0.0
     */
    protected $productRepository;

    /**
     * @var ImageHelper
     * @since 101.0.0
     */
    protected $imageHelper;

    /**
     * @var Status
     * @since 101.0.0
     */
    protected $status;

    /**
     * @var AttributeSetRepositoryInterface
     * @since 101.0.0
     */
    protected $attributeSetRepository;

    /**
     * @var string
     * @since 101.0.0
     */
    protected $scopeName;

    /**
     * @var string
     * @since 101.0.0
     */
    protected $scopePrefix;

    /**
     * @var \Magento\Catalog\Ui\Component\Listing\Columns\Price
     */
    private $priceModifier;

    /**
     * GiftBox constructor.
     * @param LocatorInterface $locator
     * @param UrlInterface $urlBuilder
     * @param ProductLinkRepositoryInterface $productLinkRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ImageHelper $imageHelper
     * @param Status $status
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param GiftBoxRepositoryInterface $giftBoxRepository
     * @param string $scopeName
     * @param string $scopePrefix
     */
    public function __construct(
        LocatorInterface $locator,
        UrlInterface $urlBuilder,
        ProductLinkRepositoryInterface $productLinkRepository,
        ProductRepositoryInterface $productRepository,
        ImageHelper $imageHelper,
        Status $status,
        AttributeSetRepositoryInterface $attributeSetRepository,
        GiftBoxRepositoryInterface $giftBoxRepository,
        $scopeName = '',
        $scopePrefix = ''
    ) {
        $this->locator = $locator;
        $this->urlBuilder = $urlBuilder;
        $this->productLinkRepository = $productLinkRepository;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->status = $status;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->scopeName = $scopeName;
        $this->scopePrefix = $scopePrefix;
        $this->giftBoxRepository = $giftBoxRepository;
    }

    /**
     * {@inheritdoc}
     * @since 101.0.0
     */
    public function modifyMeta(array $meta)
    {

        if($this->locator->getProduct()->getTypeId() == TypeGiftBox::TYPE_GIFTBOX_PRODUCT) {

            $meta = array_replace_recursive(
                $meta,
                [
                    static::GROUP_GIFTBOX => [
                        'children' => [
                            $this->scopePrefix . static::DATA_SCOPE_SMALL => $this->getSmallFieldset(),
                            $this->scopePrefix . static::DATA_SCOPE_MEDIUM => $this->getMediumFieldset(),
                            $this->scopePrefix . static::DATA_SCOPE_LARGE => $this->getLargeFieldset(),
                        ],
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'label' => __('Gift box'),
                                    'collapsible' => true,
                                    'componentType' => Fieldset::NAME,
                                    'dataScope' => static::DATA_SCOPE,
                                    'sortOrder' =>
                                        $this->getNextGroupSortOrder(
                                            $meta,
                                            static::GROUP_CONTENT,
                                            static::SORT_ORDER
                                        ),
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        return $meta;
    }

    /**
     * {@inheritdoc}
     * @since 101.0.0
     */
    public function modifyData(array $data)
    {
        $modelId = $this->locator->getProduct()->getId();

        if($this->locator->getProduct()->getTypeId() == TypeGiftBox::TYPE_GIFTBOX_PRODUCT) {

            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->locator->getProduct();
            $productId = $product->getId();

            if (!$productId) {
                return $data;
            }

            $priceModifier = $this->getPriceModifier();
            /**
             * Set field name for modifier
             */
            $priceModifier->setData('name', 'price');

            foreach ($this->getDataScopes() as $dataScope) {
                $data[$productId]['giftbox'][$dataScope] = [];
                foreach ($this->giftBoxRepository->getList($product) as $relatedItem) {
                    if ($relatedItem->getTypeCode() != $dataScope) {
                        continue;
                    }

                    $relatedProduct = $this->productRepository->getById($relatedItem->getProductRelatedId());
                    $data[$productId]['giftbox'][$dataScope][] = $this->fillData($relatedProduct, $relatedItem);
                }

                if (!empty($data[$productId]['giftbox'][$dataScope])) {
                    $dataMap = $priceModifier->prepareDataSource([
                        'data' => [
                            'items' => $data[$productId]['giftbox'][$dataScope]
                        ]
                    ]);
                    $data[$productId]['giftbox'][$dataScope] = $dataMap['data']['items'];
                }
            }

            $data[$productId][self::DATA_SOURCE_DEFAULT]['current_product_id'] = $productId;
            $data[$productId][self::DATA_SOURCE_DEFAULT]['current_store_id'] = $this->locator->getStore()->getId();

        }

        return $data;
    }

    /**
     * Get price modifier
     *
     * @return \Magento\Catalog\Ui\Component\Listing\Columns\Price
     * @deprecated 101.0.0
     */
    private function getPriceModifier()
    {
        if (!$this->priceModifier) {
            $this->priceModifier = ObjectManager::getInstance()->get(
                \Magento\Catalog\Ui\Component\Listing\Columns\Price::class
            );
        }
        return $this->priceModifier;
    }

    /**
     * Prepare data column
     *
     * @param ProductInterface $relatedProduct
     * @param GiftBoxInterface $relatedItem
     * @return array
     * @since 101.0.0
     */
    protected function fillData(ProductInterface $relatedProduct, GiftBoxInterface $relatedItem)
    {

        $result = [
            'id' => $relatedProduct->getId(),
            'thumbnail' => $this->imageHelper->init($relatedProduct, 'product_listing_thumbnail')->getUrl(),
            'name' => $relatedProduct->getName(),
            'status' => $this->status->getOptionText($relatedProduct->getStatus()),
            'attribute_set' => $this->attributeSetRepository
                ->get($relatedProduct->getAttributeSetId())
                ->getAttributeSetName(),
            'sku' => $relatedProduct->getSku(),
            'price' => $relatedProduct->getPrice(),
            'position' => $relatedItem->getPosition(),
        ];

        return $result;
    }

    /**
     * Retrieve all data scopes
     *
     * @return array
     * @since 101.0.0
     */
    protected function getDataScopes()
    {
        return [
            static::DATA_SCOPE_SMALL,
            static::DATA_SCOPE_LARGE,
            static::DATA_SCOPE_MEDIUM,
        ];
    }

    /**
     * Prepares config for the Related products fieldset
     *
     * @return array
     * @since 101.0.0
     */
    protected function getSmallFieldset()
    {
        $content = __(
            'Small products are shown.'
        );

        $qtyName = $this->scopePrefix . static::DATA_SCOPE_SMALL . '_qty';

        return [
            'children' => [

                'button_set' => $this->getButtonSet(
                    $content,
                    __('Add Small Products'),
                    $this->scopePrefix . static::DATA_SCOPE_SMALL
                ),

                //'quantity' => $this->getQtyField('Quantity for choose on Front', $this->scopePrefix . static::DATA_SCOPE_SMALL . '_qty'),

                'modal' => $this->getGenericModal(
                    __('Add Small Products'),
                    $this->scopePrefix . static::DATA_SCOPE_SMALL
                ),
                static::DATA_SCOPE_SMALL => $this->getGrid($this->scopePrefix . static::DATA_SCOPE_SMALL),
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__fieldset-section',
                        'label' => __('Small Products'),
                        'collapsible' => false,
                        'componentType' => Fieldset::NAME,
                        'dataScope' => '',
                        'sortOrder' => 10,
                    ],
                ],
            ]
        ];
    }


    /**
     * @return array
     */
    protected function getQtyField($content, $scope)
    {

        return [

            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => 'container',
                        'componentType' => 'container',
                        'label' => false,
                        'template' => 'ui/form/components/complex',
                    ],
                ],
            ],

            'children' => [
                'scope' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label'             => $content,
                                'componentType'     => Field::NAME,
                                'formElement'       => Input::NAME,
                                'dataScope'         => '',
                                'dataType'          => Number::NAME,
                                'maxlength'         => 5,
                                'sortOrder' => 1,
                                'validation'        => [
                                    'validate-number'          => true,
                                    'validate-zero-or-greater' => true,
                                    'validate-integer'         => true
                                ]
                            ],
                        ],
                    ]
                ]
            ],
        ];
    }

    /**
     * Prepares config for the Up-Sell products fieldset
     *
     * @return array
     * @since 101.0.0
     */
    protected function getMediumFieldset()
    {
        $content = __(
            'Medium products.'
        );

        return [
            'children' => [
                'button_set' => $this->getButtonSet(
                    $content,
                    __('Add Medium Products'),
                    $this->scopePrefix . static::DATA_SCOPE_MEDIUM
                ),

                'modal' => $this->getGenericModal(
                    __('Add Medium Products'),
                    $this->scopePrefix . static::DATA_SCOPE_MEDIUM
                ),
                static::DATA_SCOPE_MEDIUM => $this->getGrid($this->scopePrefix . static::DATA_SCOPE_MEDIUM),
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__fieldset-section',
                        'label' => __('Medium Products'),
                        'collapsible' => false,
                        'componentType' => Fieldset::NAME,
                        'dataScope' => '',
                        'sortOrder' => 20,
                    ],
                ],
            ]
        ];
    }

    /**
     * Prepares config for the Cross-Sell products fieldset
     *
     * @return array
     * @since 101.0.0
     */
    protected function getLargeFieldset()
    {
        $content = __(
            'Large products.'
        );

        return [
            'children' => [
                'button_set' => $this->getButtonSet(
                    $content,
                    __('Add Large Products'),
                    $this->scopePrefix . static::DATA_SCOPE_LARGE
                ),

                'modal' => $this->getGenericModal(
                    __('Add Large Products'),
                    $this->scopePrefix . static::DATA_SCOPE_LARGE
                ),
                static::DATA_SCOPE_LARGE => $this->getGrid($this->scopePrefix . static::DATA_SCOPE_LARGE),
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__fieldset-section',
                        'label' => __('Large Products'),
                        'collapsible' => false,
                        'componentType' => Fieldset::NAME,
                        'dataScope' => '',
                        'sortOrder' => 30,
                    ],
                ],
            ]
        ];
    }

    /**
     * Retrieve button set
     *
     * @param Phrase $content
     * @param Phrase $buttonTitle
     * @param string $scope
     * @return array
     * @since 101.0.0
     */
    protected function getButtonSet(Phrase $content, Phrase $buttonTitle, $scope)
    {
        $modalTarget = $this->scopeName . '.' . static::GROUP_GIFTBOX . '.' . $scope . '.modal';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => 'container',
                        'componentType' => 'container',
                        'label' => false,
                        'content' => $content,
                        'template' => 'ui/form/components/complex',
                    ],
                ],
            ],
            'children' => [
                'button_' . $scope => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => 'container',
                                'componentType' => 'container',
                                'component' => 'Magento_Ui/js/form/components/button',
                                'actions' => [
                                    [
                                        'targetName' => $modalTarget,
                                        'actionName' => 'toggleModal',
                                    ],
                                    [
                                        'targetName' => $modalTarget . '.' . $scope . '_product_listing',
                                        'actionName' => 'render',
                                    ]
                                ],
                                'title' => $buttonTitle,
                                'provider' => null,
                            ],
                        ],
                    ],

                ],
            ],
        ];
    }

    /**
     * Prepares config for modal slide-out panel
     *
     * @param Phrase $title
     * @param string $scope
     * @return array
     * @since 101.0.0
     */
    protected function getGenericModal(Phrase $title, $scope)
    {
        $listingTarget = $scope . '_product_listing';

        $modal = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Modal::NAME,
                        'dataScope' => '',
                        'options' => [
                            'title' => $title,
                            'buttons' => [
                                [
                                    'text' => __('Cancel'),
                                    'actions' => [
                                        'closeModal'
                                    ]
                                ],
                                [
                                    'text' => __('Add Selected Products'),
                                    'class' => 'action-primary',
                                    'actions' => [
                                        [
                                            'targetName' => 'index = ' . $listingTarget,
                                            'actionName' => 'save'
                                        ],
                                        'closeModal'
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'children' => [
                $listingTarget => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => false,
                                'componentType' => 'insertListing',
                                'dataScope' => $listingTarget,
                                'externalProvider' => $listingTarget . '.' . $listingTarget . '_data_source',
                                'selectionsProvider' => $listingTarget . '.' . $listingTarget . '.product_columns.ids',
                                'ns' => $listingTarget,
                                'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink' => true,
                                'dataLinks' => [
                                    'imports' => false,
                                    'exports' => true
                                ],
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                                'imports' => [
                                    'productId' => '${ $.provider }:data.product.current_product_id',
                                    'storeId' => '${ $.provider }:data.product.current_store_id',
                                ],
                                'exports' => [
                                    'productId' => '${ $.externalProvider }:params.current_product_id',
                                    'storeId' => '${ $.externalProvider }:params.current_store_id',
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $modal;
    }

    /**
     * Retrieve grid
     *
     * @param string $scope
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @since 101.0.0
     */
    protected function getGrid($scope)
    {
        $dataProvider = $scope . '_product_listing';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__field-wide',
                        'componentType' => DynamicRows::NAME,
                        'label' => null,
                        'columnsHeader' => false,
                        'columnsHeaderAfterRender' => true,
                        'renderDefaultRecord' => false,
                        'template' => 'ui/dynamic-rows/templates/grid',
                        'component' => 'Magento_Ui/js/dynamic-rows/dynamic-rows-grid',
                        'addButton' => false,
                        'recordTemplate' => 'record',
                        'dataScope' => 'data.giftbox',
                        'deleteButtonLabel' => __('Remove'),
                        'dataProvider' => $dataProvider,
                        'map' => [
                            'id' => 'entity_id',
                            'name' => 'name',
                            'status' => 'status_text',
                            'attribute_set' => 'attribute_set_text',
                            'sku' => 'sku',
                            'price' => 'price',
                            'thumbnail' => 'thumbnail_src',
                        ],
                        'links' => [
                            'insertData' => '${ $.provider }:${ $.dataProvider }'
                        ],
                        'sortOrder' => 2,
                    ],
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => 'container',
                                'isTemplate' => true,
                                'is_collection' => true,
                                'component' => 'Magento_Ui/js/dynamic-rows/record',
                                'dataScope' => '',
                            ],
                        ],
                    ],
                    'children' => $this->fillMeta(),
                ],
            ],
        ];
    }

    /**
     * Retrieve meta column
     *
     * @return array
     * @since 101.0.0
     */
    protected function fillMeta()
    {
        return [
            'id' => $this->getTextColumn('id', false, __('ID'), 0),
            'thumbnail' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => Field::NAME,
                            'formElement' => Input::NAME,
                            'elementTmpl' => 'ui/dynamic-rows/cells/thumbnail',
                            'dataType' => Text::NAME,
                            'dataScope' => 'thumbnail',
                            'fit' => true,
                            'label' => __('Thumbnail'),
                            'sortOrder' => 10,
                        ],
                    ],
                ],
            ],
            'name' => $this->getTextColumn('name', false, __('Name'), 20),
            'status' => $this->getTextColumn('status', true, __('Status'), 30),
            'attribute_set' => $this->getTextColumn('attribute_set', false, __('Attribute Set'), 40),
            'sku' => $this->getTextColumn('sku', true, __('SKU'), 50),
            'price' => $this->getTextColumn('price', true, __('Price'), 60),
            'actionDelete' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'additionalClasses' => 'data-grid-actions-cell',
                            'componentType' => 'actionDelete',
                            'dataType' => Text::NAME,
                            'label' => __('Actions'),
                            'sortOrder' => 70,
                            'fit' => true,
                        ],
                    ],
                ],
            ],
            'position' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'dataType' => Number::NAME,
                            'formElement' => Input::NAME,
                            'componentType' => Field::NAME,
                            'dataScope' => 'position',
                            'sortOrder' => 80,
                            'visible' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Retrieve text column structure
     *
     * @param string $dataScope
     * @param bool $fit
     * @param Phrase $label
     * @param int $sortOrder
     * @return array
     * @since 101.0.0
     */
    protected function getTextColumn($dataScope, $fit, Phrase $label, $sortOrder)
    {
        $column = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Field::NAME,
                        'formElement' => Input::NAME,
                        'elementTmpl' => 'ui/dynamic-rows/cells/text',
                        'component' => 'Magento_Ui/js/form/element/text',
                        'dataType' => Text::NAME,
                        'dataScope' => $dataScope,
                        'fit' => $fit,
                        'label' => $label,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];

        return $column;
    }
}