<?php

namespace Rutatiina\Expense\Classes\Recurring;

use Rutatiina\Expense\Models\ExpenseRecurring;
use Rutatiina\Expense\Traits\Recurring\Init as TxnTraitsInit;

class Read
{
    use TxnTraitsInit;

    public function __construct()
    {}

    public function run($id)
    {
        $Txn = ExpenseRecurring::find($id);

        if ($Txn) {
            //txn has been found so continue normally
        } else {
            $this->errors[] = 'Transaction not found';
            return false;
        }

        $Txn->load('recurring', 'contact', 'debit_account', 'credit_account', 'items');
        $Txn->setAppends(['taxes']);

        foreach ($Txn->items as &$item) {

            if (empty($item->name)) {
                $txnDescription[] = $item->description;
            }
            else {
                $txnDescription[] = (empty($item->description)) ? $item->name : $item->name . ': ' . $item->description;
            }

            /*/If item is a transaction, get the transaction details
            if ($item->type == 'txn') {
                $item->transaction = Txn::with('type', 'debit_account', 'credit_account')->find($item->type_id);
            }
            */
        }

        $Txn->description = implode(',', $txnDescription);

        $f = new \NumberFormatter( locale_get_default(), \NumberFormatter::SPELLOUT );
        $Txn->total_in_words = ucfirst($f->format($Txn->total));

        return $Txn->toArray();

    }

}
