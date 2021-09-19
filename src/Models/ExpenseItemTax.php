<?php

namespace Rutatiina\Expense\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Rutatiina\Tenant\Scopes\TenantIdScope;

class ExpenseItemTax extends Model
{
    use LogsActivity;

    protected static $logName = 'TxnItem';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_expense_item_taxes';

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

    public function tax()
    {
        return $this->hasOne('Rutatiina\Tax\Models\Tax', 'code', 'tax_code');
    }

    public function expense()
    {
        return $this->belongsTo('Rutatiina\Expense\Models\Expense', 'expense_id', 'id');
    }

    public function expense_item()
    {
        return $this->belongsTo('Rutatiina\Expense\Models\ExpenseItem', 'expense_item_id', 'id');
    }

}
