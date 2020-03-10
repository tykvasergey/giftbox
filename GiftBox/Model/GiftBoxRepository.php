<?php


namespace BroSolutions\GiftBox\Model;

use BroSolutions\GiftBox\Api\Data\GiftBoxInterface;
use BroSolutions\GiftBox\Api\GiftBoxRepositoryInterface;
use BroSolutions\GiftBox\Model\GiftBoxFactory;
use BroSolutions\GiftBox\Model\ResourceModel\GiftBox;
use BroSolutions\GiftBox\Model\ResourceModel\GiftBox\CollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;


class GiftBoxRepository implements GiftBoxRepositoryInterface
{

    /**
     * @var \BroSolutions\GiftBox\Model\GiftBoxFactory
     */
    private $giftBoxFactory;

    /**
     * @var GiftBox
     */
    private $giftBoxResource;

    /**
     * @var array
     */
    private $giftBoxes;

    /**
     * @var CollectionFactory
     */
    private $giftBoxCollectionFactory;


    public function __construct(
        GiftBoxFactory $giftBoxFactory,
        GiftBox $giftBoxResource,
        CollectionFactory $giftBoxCollectionFactory
    ) {
        $this->giftBoxFactory = $giftBoxFactory;
        $this->giftBoxResource = $giftBoxResource;
        $this->giftBoxCollectionFactory = $giftBoxCollectionFactory;
    }

    public function save(GiftBoxInterface $giftBox)
    {
        try {
            $this->customerCardResource->save($giftBox);
        } catch (\Exception $e) {
            if ($giftBox->getCustomerCardId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save customerCard with ID %1. Error: %2',
                        [$giftBox->getCustomerCardId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new customerCard. Error: %1', $e->getMessage()));
        }

        return $giftBox;
    }

    /**
     * @param int $giftBoxId
     * @return GiftBoxInterface
     * @throws NoSuchEntityException
     */
    public function getById($giftBoxId)
    {
        if (!isset($this->giftBoxes[$giftBoxId])) {
            /** @var \BroSolutions\GiftBox\Model\GiftBox $giftBox */
            $giftBox = $this->giftBoxFactory->create();
            $this->giftBoxResource->load($giftBox, $giftBoxId);
            if (!$giftBox->getCustomerCardId()) {
                throw new NoSuchEntityException(__('giftBox with specified ID "%1" not found.', $giftBoxId));
            }
            $this->customerCards[$giftBoxId] = $giftBox;
        }

        return $this->customerCards[$giftBoxId];
    }

    /**
     * @param GiftBoxInterface $giftBox
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(GiftBoxInterface $giftBox)
    {
        try {
            $this->giftBoxResource->delete($giftBox);
            unset($this->giftBoxes[$giftBox->getGiftBoxId()]);
        } catch (\Exception $e) {
            if ($giftBox->getGiftBoxId()) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove giftBox with ID %1. Error: %2',
                        [$giftBox->getGiftBoxId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove giftBox. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($giftBoxId)
    {
        $giftBoxModel = $this->getById($giftBoxId);
        $this->delete($giftBoxModel);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(\Magento\Catalog\Api\Data\ProductInterface $product)
    {

        /** @var \BroSolutions\GiftBox\Model\ResourceModel\GiftBox\Collection $giftBoxCollection */
        $giftBoxCollection = $this->giftBoxCollectionFactory->create();

        $giftBoxCollection->addFilter('product_id', $product->getId());

        return $giftBoxCollection->getItems();

    }
}