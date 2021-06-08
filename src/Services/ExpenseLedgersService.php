<?php

namespace Rutatiina\Expense\Services;

use Rutatiina\Expense\Models\ExpenseLedger;

class ExpenseLedgersService
{
    public static $errors = [];

    public function __construct()
    {
        //
    }

    public static function store($data)
    {
        foreach ($data['ledgers'] as &$ledger)
        {
            $ledger['expense_id'] = $data['id'];
            ExpenseLedger::create($ledger);
        }
        unset($ledger);

    }

}
