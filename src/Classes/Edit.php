<?php

namespace Rutatiina\Expense\Classes;

use Rutatiina\Tax\Models\Tax;

use Rutatiina\Expense\Classes\Read as TxnRead;

use Rutatiina\Expense\Traits\Init as TxnTraitsInit;

class Edit
{
    use TxnTraitsInit;

    public function __construct()
    {
    }

    public function run($id)
    {
        $taxes = Tax::all()->keyBy('code');

        $TxnRead = new TxnRead();
        $attributes = $TxnRead->run($id);

        //print_r($attributes); exit;

        $attributes['_method'] = 'PATCH';

        $attributes['contact']['currency'] = $attributes['contact']['currency_and_exchange_rate'];
        $attributes['contact']['currencies'] = $attributes['contact']['currencies_and_exchange_rates'];

        $attributes['contact_id'] = $attributes['debit_contact_id'];
        $attributes['taxes'] = json_decode('{}');
        $attributes['isRecurring'] = false;
        $attributes['recurring'] = [
            'date_range' => [],
            'day_of_month' => '*',
            'month' => '*',
            'day_of_week' => '*',
        ];
        $attributes['contact_notes'] = null;
        $attributes['terms_and_conditions'] = null;

        unset($attributes['txn_entree_id']); //!important
        unset($attributes['txn_type_id']); //!important

        foreach ($attributes['items'] as $key => $item)
        {
            $selectedItem = [
                'id' => $item['type_id'],
                'name' => $item['name'],
                'type' => $item['type'],
                'description' => $item['description'],
                'rate' => $item['rate'],
                'tax_method' => 'inclusive',
                'account_type' => null,
            ];

            $attributes['items'][$key]['selectedItem'] = $selectedItem; #required
            $attributes['items'][$key]['selectedTaxes'] = []; #required
            $attributes['items'][$key]['displayTotal'] = 0; #required

            foreach ($item['taxes'] as $itemTax)
            {
                $attributes['items'][$key]['selectedTaxes'][] = $taxes[$itemTax['tax_code']];
            }

            $attributes['items'][$key]['rate'] = floatval($item['rate']);
            $attributes['items'][$key]['quantity'] = floatval($item['quantity']);
            $attributes['items'][$key]['total'] = floatval($item['total']);
            $attributes['items'][$key]['displayTotal'] = $item['total']; #required
        };

        return $attributes;

    }

}
