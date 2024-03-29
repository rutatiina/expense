<?php

namespace Rutatiina\Expense\Services;

use Rutatiina\Item\Models\Item;
use Rutatiina\Contact\Models\Contact;
use Illuminate\Support\Facades\Validator;
use Rutatiina\Invoice\Models\InvoiceRecurringSetting;

class RecurringExpenseValidateService
{
    public static $errors = [];

    public static function run($requestInstance)
    {
        //$request = request(); //used for the flash when validation fails
        $user = auth()->user();


        // >> data validation >>------------------------------------------------------------

        //validate the data
        $customMessages = [
            'con_day_of_month.required_if' => "The day of month to recur is required",
            'con_month.required_if' => "The month to recur is required",
            'con_day_of_week.required_if' => "The day of week to recur is required",

            'credit_financial_account_code' => "Tax account to credit is required",
            'items.*.debit_financial_account_code' => "Tax account to debit is required",
            'items.*.taxes.*.code.required' => "Tax code is required",
            'items.*.taxes.*.total.required' => "Tax total is required",
            //'items.*.taxes.*.exclusive.required' => "Tax exclusive amount is required",
        ];

        $rules = [
            'profile_name' => 'required|string|max:250',
            'contact_id' => 'required|numeric',
            'credit_financial_account_code' => 'required|numeric',
            'base_currency' => 'required',
            'payment_mode' => 'required',
            'contact_notes' => 'string|nullable',

            'frequency' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'con_day_of_month' => 'required_if:frequency,custom|string',
            'con_month' => 'required_if:frequency,custom|string',
            'con_day_of_week' => 'required_if:frequency,custom|string',

            'items' => 'required|array',
            'items.*.description' => 'required',
            'items.*.amount' => 'required|numeric',
            'items.*.taxes' => 'array|nullable',

            'items.*.taxes.*.code' => 'required',
            'items.*.taxes.*.amount' => 'required|numeric'
        ];

        $validator = Validator::make($requestInstance->all(), $rules, $customMessages);

        if ($validator->fails())
        {
            self::$errors = $validator->errors()->all();
            return false;
        }

        // << data validation <<------------------------------------------------------------

        $contact = Contact::findOrFail($requestInstance->contact_id);

        $data['id'] = $requestInstance->input('id', null); //for updating the id will always be posted
        $data['user_id'] = $user->id;
        $data['tenant_id'] = $user->tenant->id;
        $data['created_by'] = $user->name;
        $data['app'] = 'web';
        $data['profile_name'] = $requestInstance->input('profile_name');
        $data['debit_financial_account_code'] = $requestInstance->debit_financial_account_code;
        $data['credit_financial_account_code'] = $requestInstance->credit_financial_account_code;
        $data['contact_id'] = $requestInstance->contact_id;
        $data['contact_name'] = $contact->name;
        $data['contact_address'] = trim($contact->shipping_address_street1 . ' ' . $contact->shipping_address_street2);
        $data['base_currency'] =  $requestInstance->input('base_currency');
        $data['quote_currency'] =  $requestInstance->input('quote_currency', $data['base_currency']);
        $data['exchange_rate'] = $requestInstance->input('exchange_rate', 1);
        $data['branch_id'] = $requestInstance->input('branch_id', null);
        $data['store_id'] = $requestInstance->input('store_id', null);
        $data['terms_and_conditions'] = $requestInstance->input('terms_and_conditions', null);
        $data['contact_notes'] = $requestInstance->input('contact_notes', null);
        $data['payment_mode'] = $requestInstance->input('payment_mode', null);

        $data['status'] = $requestInstance->input('status', null);
        $data['frequency'] = $requestInstance->input('frequency', null);
        $data['start_date'] = $requestInstance->input('start_date', null);
        $data['end_date'] = $requestInstance->input('end_date', null);
        $data['cron_day_of_month'] = $requestInstance->input('cron_day_of_month', null);
        $data['cron_month'] = $requestInstance->input('cron_month', null);
        $data['cron_day_of_week'] = $requestInstance->input('cron_day_of_week', null);


        //set the transaction total to zero
        $txnTotal = 0;
        $taxableAmount = 0;

        //Formulate the DB ready items array
        $data['items'] = [];
        foreach ($requestInstance->items as $key => $item)
        {
            $itemTaxes = $requestInstance->input('items.'.$key.'.taxes', []);

            $txnTotal           += ($item['amount']);
            $taxableAmount      += ($item['amount']);
            $itemTaxableAmount   = ($item['amount']); //calculate the item taxable amount

            foreach ($itemTaxes as $itemTax)
            {
                $txnTotal           += $itemTax['exclusive'];
                $taxableAmount      -= $itemTax['inclusive'];
                $itemTaxableAmount  -= $itemTax['inclusive']; //calculate the item taxable amount more by removing the inclusive amount
            }

            //get the item
            $itemModel = Item::find($item['item_id']);

            $data['items'][] = [
                'tenant_id' => $data['tenant_id'],
                'created_by' => $data['created_by'],
                'item_id' => optional($itemModel)->id, //$item['item_id'], use internal ID to verify data so that from here one the item_id value is LEGIT
                'contact_id' => $item['contact_id'],
                'description' => $item['description'],
                'amount' => $txnTotal,
                'taxable_amount' => $itemTaxableAmount,
                'taxes' => $itemTaxes,
            ];
        }

        $data['taxable_amount'] = $taxableAmount;
        $data['total'] = $txnTotal;

        $data['recurring']  = $requestInstance->input('recurring', []);

        //print_r($data); exit;

        return $data;

    }

}
