<?php

namespace Rutatiina\Expense\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Rutatiina\Tenant\Scopes\TenantIdScope;
use Cron\CronExpression;

class RecurringExpenseProperty extends Model
{
    use LogsActivity;

    protected static $logName = 'TxnRecurring';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_expense_recurring_expense_properties';

    protected $primaryKey = 'id';

    protected $appends = ['recur', 'date_range', 'next_run_date'];

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

    public function getNextRunDateAttribute()
    {
        /*
         *      *    *    *    *    *
         *      -    -    -    -    -
         *      |    |    |    |    |
         *      |    |    |    |    |
         *      |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
         *      |    |    |    +---------- month (1 - 12)
         *      |    |    +--------------- day of month (1 - 31)
         *      |    +-------------------- hour (0 - 23)
         *      +------------------------- min (0 - 59)
         */

        $cronFormat = '* * '.$this->day_of_month.' '.$this->month.' '.$this->day_of_week;

        //$cron = CronExpression::factory('@daily');
        $cron = CronExpression::factory($cronFormat);
        $cron->isDue();
        return $cron->getNextRunDate()->format('Y-m-d');
        //echo $cron->getPreviousRunDate()->format('Y-m-d H:i:s');
    }

    public function getRecurAttribute()
    {
        $todayStrtotime = strtotime(date('Y-m-d'));

        $collection = collect([
            [
                'start_date' => strtotime($this->start_date),
                'end_date' => strtotime($this->end_date),
                'last_processed' => strtotime($this->last_processed),
            ]
        ]);

        $filterByStartDate = $collection->where('start_date', '<', $todayStrtotime);
        $filterByEndDate = $collection->where('end_date', '>', $todayStrtotime);
        $filterByLastProcessed = $collection->where('last_processed', '<', $todayStrtotime);

        if($filterByStartDate->isEmpty()) {
            return false;
        }

        if($filterByEndDate->isEmpty()) {
            return false;
        }

        if($filterByLastProcessed->isEmpty()) {
            return false;
        }

        return true;
    }

    public function getDateRangeAttribute()
    {
        return [
            $this->start_date,
            $this->end_date
        ];
    }

    public function tenant()
    {
        return $this->hasOne('Rutatiina\Tenant\Models\Tenant', 'id', 'tenant_id');
    }

}
