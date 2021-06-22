<?php

namespace Rutatiina\Expense\Http\Controllers;

use Rutatiina\Expense\Models\RecurringExpenseSetting;
use Rutatiina\Expense\Services\RecurringExpenseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Rutatiina\Expense\Models\RecurringExpense;
use Rutatiina\FinancialAccounting\Traits\FinancialAccountingTrait;
use Rutatiina\Contact\Traits\ContactTrait;

class RecurringExpenseController extends Controller
{
    use FinancialAccountingTrait;
    use ContactTrait;

    // >> get the item attributes template << !!important

    public function __construct()
    {
        $this->middleware('permission:recurring-expenses.view');
        $this->middleware('permission:recurring-expenses.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:recurring-expenses.update', ['only' => ['edit', 'update']]);
        $this->middleware('permission:recurring-expenses.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $query = RecurringExpense::query();

        if ($request->contact)
        {
            $query->where(function ($q) use ($request)
            {
                $q->where('contact_id', $request->contact);
            });
        }

        $txns = $query->latest()->paginate($request->input('per_page', 20));

        $txns->load('debit_financial_account');
        $txns->load('credit_financial_account');

        return [
            'tableData' => $txns
        ];
    }

    public function create()
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $settings = RecurringExpenseSetting::has('financial_account_to_debit')->with(['financial_account_to_debit'])->firstOrFail();

        $tenant = Auth::user()->tenant;

        $txnAttributes = (new RecurringExpense())->rgGetAttributes();

        $txnAttributes['status'] = 'active';
        $txnAttributes['contact_id'] = '';
        $txnAttributes['contact'] = json_decode('{"currencies":[]}'); #required
        $txnAttributes['base_currency'] = $tenant->base_currency;
        $txnAttributes['quote_currency'] = $tenant->base_currency;
        $txnAttributes['taxes'] = json_decode('{}');
        $txnAttributes['payment_mode'] = optional($settings)->payment_mode_default;
        $txnAttributes['credit_financial_account_code'] = optional($settings)->financial_account_to_credit->code;
        $txnAttributes['contact_notes'] = null;
        $txnAttributes['terms_and_conditions'] = null;
        $txnAttributes['items'] = [[
            'selectedTaxes' => [], #required
            'selectedItem' => json_decode('{}'), #required
            'displayTotal' => 0,
            'description' => '',
            'rate' => 0,
            'quantity' => 1,
            'total' => 0,
            'taxes' => [],
            'contact_id' => '',
        ]];

        $data = [
            'pageTitle' => 'Create Recurring Expense', #required
            'pageAction' => 'Create', #required
            'txnUrlStore' => '/recurring-expenses', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        return $data;

    }

    public function store(Request $request)
    {
        $storeService = RecurringExpenseService::store($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => RecurringExpenseService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Recurring Expense saved'],
            'number' => 0,
            'callback' => route('recurring-expenses.show', [$storeService->id], false)
        ];

    }

    public function show($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $txn = RecurringExpense::findOrFail($id);
        $txn->load('contact', 'items.taxes');
        $txn->setAppends([
            'taxes',
            'number_string',
            'total_in_words',
        ]);

        return $txn->toArray();
    }

    public function edit($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $txnAttributes = RecurringExpenseService::edit($id);

        $data = [
            'pageTitle' => 'Edit Recurring expense', #required
            'pageAction' => 'Edit', #required
            'txnUrlStore' => '/recurring-expenses/' . $id, #required
            'txnAttributes' => $txnAttributes, #required
        ];

        return $data;
    }

    public function update(Request $request)
    {
        $storeService = RecurringExpenseService::update($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => RecurringExpenseService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Recurring Expense updated'],
            'callback' => route('recurring-expenses.show', $request->id, false)
        ];
    }

    public function destroy($id)
    {
        $destroy = RecurringExpenseService::destroy($id);

        if ($destroy)
        {
            return [
                'status' => true,
                'messages' => ['Recurring expense deleted'],
                'callback' => route('recurring-expenses.index', [], false)
            ];
        }
        else
        {
            return [
                'status' => false,
                'messages' => RecurringExpenseService::$errors
            ];
        }
    }

    #-----------------------------------------------------------------------------------

    public function activate($id)
    {
        $approve = RecurringExpenseService::activate($id);

        if ($approve == false)
        {
            return [
                'status' => false,
                'messages' => RecurringExpenseService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Recurring Expense activated'],
        ];

    }

    public function copy($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $txnAttributes = RecurringExpenseService::copy($id);

        $data = [
            'pageTitle' => 'Copy Recurring expense', #required
            'pageAction' => 'Copy', #required
            'txnUrlStore' => '/recurring-expenses', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        return $data;
    }
}
