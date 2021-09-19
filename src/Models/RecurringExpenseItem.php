<?php

namespace Rutatiina\Expense\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Rutatiina\Tenant\Scopes\TenantIdScope;

class RecurringExpenseItem extends Model
{
    use LogsActivity;

    protected static $logName = 'TxnItem';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_expense_recurring_expense_items';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new TenantIdScope);
    }

    public function getTaxesAttribute($value)
    {
        $_array_ = json_decode($value);
        if (is_array($_array_)) {
            return $_array_;
        } else {
            return [];
        }
    }

    public function recurring_expense()
    {
        return $this->belongsTo('Rutatiina\Expense\Models\RecurringExpense', 'recurring_expense_id');
    }

    public function taxes()
    {
        return $this->hasMany('Rutatiina\Expense\Models\RecurringExpenseItemTax', 'recurring_Expense_item_id', 'id');
    }

}
