<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace BroSolutions\GiftBox\Ui\DataProvider\Product;

/**
 * Class CrossSellDataProvider
 *
 * @api
 * @since 101.0.0
 */
class SmallDataProvider extends AbstractDataProvider
{
    /**
     * {@inheritdoc}
     * @since 101.0.0
     */
    protected function getLinkType()
    {
        return 'small';
    }
}
