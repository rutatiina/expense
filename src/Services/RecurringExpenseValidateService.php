<?php

namespace Rutatiina\Expense\Services;

use Illuminate\Support\Facades\Validator;
use Rutatiina\Contact\Models\Contact;
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
            //'total.in' => "Item total is invalid:\nItem total = item rate x item quantity",

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

            'items' => 'required|array',
            'items.*.debit_financial_account_code' => 'required|numeric',
            'items.*.description' => 'required',
            'items.*.amount' => 'required|numeric',
            'items.*.taxes' => 'array|nullable',

            'items.*.taxes.*.code' => 'required',
            'items.*.taxes.*.amount' => 'required|numeric',
            //'items.*.taxes.*.exclusive' => 'required|numeric',

            'recurring.frequency' => 'required|string',
            'recurring.start_date' => 'required|date',
            'recurring.end_date' => 'required|date',
            'recurring.day_of_month' => 'required|string',
            'recurring.month' => 'required|string',
            'recurring.day_of_week' => 'required|string',
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
        $data['credit_financial_account_code'] = $requestInstance->credit_financial_account_code;
        $data['contact_id'] = $requestInstance->contact_id;
        $data['contact_name'] = $contact->name;
        $data['contact_address'] = trim($contact->shipping_address_street1 . ' ' . $contact->shipping_address_street2);
        $data['reference'] = $requestInstance->input('reference', null);
        $data['base_currency'] =  $requestInstance->input('base_currency');
        $data['quote_currency'] =  $requestInstance->input('quote_currency', $data['base_currency']);
        $data['exchange_rate'] = $requestInstance->input('exchange_rate', 1);
        $data['branch_id'] = $requestInstance->input('branch_id', null);
        $data['store_id'] = $requestInstance->input('store_id', null);
        $data['terms_and_conditions'] = $requestInstance->input('terms_and_conditions', null);
        $data['contact_notes'] = $requestInstance->input('contact_notes', null);
        $data['status'] = $requestInstance->input('status', null);
        $data['payment_mode'] = $requestInstance->input('payment_mode', null);


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

            $data['items'][] = [
                'tenant_id' => $data['tenant_id'],
                'created_by' => $data['created_by'],
                'contact_id' => $item['contact_id'],
                'debit_financial_account_code' => $item['debit_financial_account_code'],
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
