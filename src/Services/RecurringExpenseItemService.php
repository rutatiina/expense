<?php

namespace Rutatiina\Expense\Services;

use Rutatiina\Expense\Models\RecurringExpenseItem;
use Rutatiina\Expense\Models\RecurringExpenseItemTax;
use Rutatiina\Invoice\Models\InvoiceRecurringItem;
use Rutatiina\Invoice\Models\InvoiceRecurringItemTax;

class RecurringExpenseItemService
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
            $item['recurring_expense_id'] = $data['id'];

            $itemTaxes = (is_array($item['taxes'])) ? $item['taxes'] : [] ;
            unset($item['taxes']);

            $itemModel = RecurringExpenseItem::create($item);

            foreach ($itemTaxes as $tax)
            {
                //save the taxes attached to the item
                $itemTax = new RecurringExpenseItemTax;
                $itemTax->tenant_id = $item['tenant_id'];
                $itemTax->recurring_expense_id = $item['recurring_expense_id'];
                $itemTax->recurring_expense_item_id = $itemModel->id;
                $itemTax->tax_code = $tax['code'];
                $itemTax->amount = $tax['total'];
                $itemTax->inclusive = $tax['inclusive'];
                $itemTax->exclusive = $tax['exclusive'];
                $itemTax->save();
            }
            unset($tax);
        }
        unset($item);

    }

}
