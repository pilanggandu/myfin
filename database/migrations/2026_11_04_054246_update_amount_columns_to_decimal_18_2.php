<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transaction_entries')) {
            Schema::table('transaction_entries', function (Blueprint $table) {
                $table->decimal('amount_signed', 18, 2)->change();
            });
        }

        if (Schema::hasTable('accounts')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->decimal('opening_balance', 18, 2)->change();
            });
        }

        if (Schema::hasTable('installment_plans')) {
            Schema::table('installment_plans', function (Blueprint $table) {
                $table->decimal('total_amount', 18, 2)->change();
                if (Schema::hasColumn('installment_plans', 'admin_fee')) {
                    $table->decimal('admin_fee', 18, 2)->default(0)->change();
                }
            });
        }

        if (Schema::hasTable('installments')) {
            Schema::table('installments', function (Blueprint $table) {
                $table->decimal('principal_amount', 18, 2)->change();
                $table->decimal('interest_amount', 18, 2)->default(0)->change();
                $table->decimal('fee_amount', 18, 2)->default(0)->change();
                $table->decimal('total_amount', 18, 2)->change();
            });
        }

        if (Schema::hasTable('credit_payments')) {
            Schema::table('credit_payments', function (Blueprint $table) {
                $table->decimal('principal_amount', 18, 2)->change();
                $table->decimal('interest_amount', 18, 2)->default(0)->change();
                $table->decimal('fee_amount', 18, 2)->default(0)->change();
                $table->decimal('total_amount', 18, 2)->change();
            });
        }

        if (Schema::hasTable('credit_debit_notes')) {
            Schema::table('credit_debit_notes', function (Blueprint $table) {
                $table->decimal('amount', 18, 2)->change();
            });
        }

        if (Schema::hasTable('credit_accounts')) {
            Schema::table('credit_accounts', function (Blueprint $table) {
                $table->decimal('credit_limit', 18, 2)->default(0)->change();
            });
        }

        if (Schema::hasTable('budgets')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->decimal('limit_amount', 18, 2)->change();
            });
        }

        if (Schema::hasTable('recurring_schedules')) {
            Schema::table('recurring_schedules', function (Blueprint $table) {
                $table->decimal('amount', 18, 2)->nullable()->change();
            });
        }

        if (Schema::hasTable('recurring_occurrences')) {
            Schema::table('recurring_occurrences', function (Blueprint $table) {
                $table->decimal('amount', 18, 2)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transaction_entries')) {
            Schema::table('transaction_entries', function (Blueprint $table) {
                $table->bigInteger('amount_signed')->change();
            });
        }

        if (Schema::hasTable('accounts')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->bigInteger('opening_balance')->change();
            });
        }

        if (Schema::hasTable('installment_plans')) {
            Schema::table('installment_plans', function (Blueprint $table) {
                $table->bigInteger('total_amount')->change();
                if (Schema::hasColumn('installment_plans', 'admin_fee')) {
                    $table->bigInteger('admin_fee')->default(0)->change();
                }
            });
        }

        if (Schema::hasTable('installments')) {
            Schema::table('installments', function (Blueprint $table) {
                $table->bigInteger('principal_amount')->change();
                $table->bigInteger('interest_amount')->default(0)->change();
                $table->bigInteger('fee_amount')->default(0)->change();
                $table->bigInteger('total_amount')->change();
            });
        }

        if (Schema::hasTable('credit_payments')) {
            Schema::table('credit_payments', function (Blueprint $table) {
                $table->bigInteger('principal_amount')->change();
                $table->bigInteger('interest_amount')->default(0)->change();
                $table->bigInteger('fee_amount')->default(0)->change();
                $table->bigInteger('total_amount')->change();
            });
        }

        if (Schema::hasTable('credit_debit_notes')) {
            Schema::table('credit_debit_notes', function (Blueprint $table) {
                $table->bigInteger('amount')->change();
            });
        }

        if (Schema::hasTable('credit_accounts')) {
            Schema::table('credit_accounts', function (Blueprint $table) {
                $table->bigInteger('credit_limit')->default(0)->change();
            });
        }

        if (Schema::hasTable('budgets')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->bigInteger('limit_amount')->change();
            });
        }

        if (Schema::hasTable('recurring_schedules')) {
            Schema::table('recurring_schedules', function (Blueprint $table) {
                $table->bigInteger('amount')->nullable()->change();
            });
        }

        if (Schema::hasTable('recurring_occurrences')) {
            Schema::table('recurring_occurrences', function (Blueprint $table) {
                $table->bigInteger('amount')->nullable()->change();
            });
        }
    }
};
