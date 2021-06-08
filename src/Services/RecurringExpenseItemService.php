<?php

namespace Rutatiina\Invoice\Services;

use Rutatiina\Invoice\Models\InvoiceRecurringItem;
use Rutatiina\Invoice\Models\InvoiceRecurringItemTax;

class InvoiceRecurringItemService
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
            $item['invoice_recurring_id'] = $data['id'];

            $itemTaxes = (is_array($item['taxes'])) ? $item['taxes'] : [] ;
            unset($item['taxes']);

            $itemModel = InvoiceRecurringItem::create($item);

            foreach ($itemTaxes as $tax)
            {
                //save the taxes attached to the item
                $itemTax = new InvoiceRecurringItemTax;
                $itemTax->tenant_id = $item['tenant_id'];
                $itemTax->invoice_recurring_id = $item['invoice_recurring_id'];
                $itemTax->retainer_invoice_item_id = $itemModel->id;
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
