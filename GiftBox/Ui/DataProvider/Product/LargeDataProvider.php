<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace BroSolutions\GiftBox\Ui\DataProvider\Product;

/**
 * Class UpSellDataProvider
 *
 * @api
 * @since 101.0.0
 */
class LargeDataProvider extends AbstractDataProvider
{
    /**
     * {@inheritdoc}
     * @since 101.0.0
     */
    protected function getLinkType()
    {
        return 'large';
    }
}
