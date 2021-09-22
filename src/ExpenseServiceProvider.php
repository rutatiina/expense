<?php

namespace Rutatiina\Expense;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Rutatiina\Expense\Traits\Recurring\Schedule as RecurringExpenseScheduleTrait;

class ExpenseServiceProvider extends ServiceProvider
{
    use RecurringExpenseScheduleTrait;

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__.'/routes/routes.php';
        //include __DIR__.'/routes/api.php';

        $this->loadViewsFrom(__DIR__.'/resources/views', 'expense');
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        //register the scheduled tasks
        $this->app->booted(function () {
            $this->recurringExpenseSchedule(app(Schedule::class));
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Rutatiina\Expense\Http\Controllers\ExpenseController');
    }
}
