<?php

namespace Rutatiina\Expense\Services;

use Rutatiina\Expense\Models\ExpenseItem;
use Rutatiina\Expense\Models\ExpenseItemTax;

class ExpenseItemService
{
    public static $errors = [];

    public function __construct()
    {
        //
    }

    public static function store($data)
    {
        //print_r($data['items']); exit;

        //Save the items >> $data['items']
        foreach ($data['items'] as &$item)
        {
            $item['expense_id'] = $data['id'];

            $itemTaxes = (is_array($item['taxes'])) ? $item['taxes'] : [] ;
            unset($item['taxes']);

            $itemModel = ExpenseItem::create($item);

            foreach ($itemTaxes as $tax)
            {
                //save the taxes attached to the item
                $itemTax = new ExpenseItemTax;
                $itemTax->tenant_id = $item['tenant_id'];
                $itemTax->expense_id = $item['expense_id'];
                $itemTax->expense_item_id = $itemModel->id;
                $itemTax->tax_code = $tax['code'];
                $itemTax->amount = $tax['total'];
                $itemTax->taxable_amount = $tax['total']; //todo >> this is to be updated in future when taxes are propelly applied to receipts
                $itemTax->inclusive = $tax['inclusive'];
                $itemTax->exclusive = $tax['exclusive'];
                $itemTax->save();
            }
            unset($tax);
        }
        unset($item);

    }

}
