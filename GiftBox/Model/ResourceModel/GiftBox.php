<?php


namespace BroSolutions\GiftBox\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;

class GiftBox extends AbstractDb
{
    const TABLE_NAME = 'brosolutions_giftbox_product_related';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, 'id');
    }

    protected function _getLoadSelect($field, $value, $object)
    {
        if (is_array($field) || is_array($value)) {
            if (is_array($field) && is_array($value)) {
                $listFieldsValues = array_combine($field, $value);
            } elseif (is_array($field)) {
                $listFieldsValues = $field;
            } else {
                $listFieldsValues = $value;
            }

            $select = $this->getConnection()->select()
                ->from($this->getMainTable());
            foreach ($listFieldsValues as $field => $value) {
                $field  = $this->getConnection()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), $field));
                $select->where($field . '=?', $value);
            }
        } else {
            $select = parent::_getLoadSelect($field, $value, $object);
        }

        return $select;
    }

}