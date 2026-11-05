<?php

use App\Http\Controllers\Accounts\AccountController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Budgets\BudgetController;
use App\Http\Controllers\Categories\CategoryController;
use App\Http\Controllers\Credit\CreditAccountController;
use App\Http\Controllers\Credit\InstallmentController;
use App\Http\Controllers\Currencies\CurrencyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Goals\GoalController;
use App\Http\Controllers\Members\MemberController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Recurring\RecurringScheduleController;
use App\Http\Controllers\Reminders\ReminderController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Spaces\SpaceController;
use App\Http\Controllers\Transactions\FinancialTransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/register/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('register.otp.show');
    Route::post('/register/verify-otp', [AuthController::class, 'verifyOtp'])->name('register.otp.verify');
    Route::post('/register/resend-otp', [AuthController::class, 'resendOtp'])->name('register.otp.resend');

    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetOtp'])->name('password.email');
    Route::get('/forgot-password/verify-otp', [ForgotPasswordController::class, 'showVerifyOtp'])->name('password.otp.show');
    Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyResetOtp'])->name('password.otp.verify');
    Route::post('/forgot-password/resend-otp', [ForgotPasswordController::class, 'resendOtp'])->name('password.otp.resend');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/clean-data', [ProfileController::class, 'cleanData'])->name('profile.clean-data');

    Route::get('/spaces', [SpaceController::class, 'index'])->name('spaces.index');
    Route::get('/spaces/create', [SpaceController::class, 'create'])->name('spaces.create');
    Route::post('/spaces', [SpaceController::class, 'store'])->name('spaces.store');
    Route::post('/spaces/{space}/switch', [SpaceController::class, 'switchSpace'])->name('spaces.switch');
    Route::get('/spaces/{space}/edit', [SpaceController::class, 'edit'])->name('spaces.edit');
    Route::put('/spaces/{space}', [SpaceController::class, 'update'])->name('spaces.update');
    Route::delete('/spaces/{space}', [SpaceController::class, 'destroy'])->name('spaces.destroy');

    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::get('/accounts/create', [AccountController::class, 'create'])->name('accounts.create');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::get('/accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');
    Route::get('/accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
    Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
    Route::post('/accounts/{account}/archive', [AccountController::class, 'archive'])->name('accounts.archive');
    Route::post('/accounts/{account}/unarchive', [AccountController::class, 'unarchive'])->name('accounts.unarchive');
    Route::post('/accounts/{account}/set-default', [AccountController::class, 'setDefault'])->name('accounts.setDefault');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::post('/categories/{category}/archive', [CategoryController::class, 'archive'])->name('categories.archive');
    Route::post('/categories/{category}/unarchive', [CategoryController::class, 'unarchive'])->name('categories.unarchive');

    Route::get('/transactions', [FinancialTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [FinancialTransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [FinancialTransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{transaction}', [FinancialTransactionController::class, 'show'])->name('transactions.show');
    Route::get('/transactions/{transaction}/edit', [FinancialTransactionController::class, 'edit'])->name('transactions.edit');
    Route::put('/transactions/{transaction}', [FinancialTransactionController::class, 'update'])->name('transactions.update');
    Route::post('/transactions/{transaction}/void', [FinancialTransactionController::class, 'void'])->name('transactions.void');

    Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::put('/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

    Route::get('/goals', [GoalController::class, 'index'])->name('goals.index');
    Route::get('/goals/create', [GoalController::class, 'create'])->name('goals.create');
    Route::post('/goals', [GoalController::class, 'store'])->name('goals.store');
    Route::get('/goals/{goal}', [GoalController::class, 'show'])->name('goals.show');
    Route::get('/goals/{goal}/edit', [GoalController::class, 'edit'])->name('goals.edit');
    Route::put('/goals/{goal}', [GoalController::class, 'update'])->name('goals.update');
    Route::delete('/goals/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');
    Route::post('/goals/{goal}/contribute', [GoalController::class, 'contribute'])->name('goals.contribute');
    Route::post('/goals/{goal}/withdraw', [GoalController::class, 'withdraw'])->name('goals.withdraw');
    Route::post('/goals/{goal}/toggle-status', [GoalController::class, 'toggleStatus'])->name('goals.toggle-status');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');

    Route::get('/currencies', [CurrencyController::class, 'index'])->name('currencies.index');
    Route::post('/currencies', [CurrencyController::class, 'store'])->name('currencies.store');
    Route::post('/currencies/sync', [CurrencyController::class, 'syncRates'])->name('currencies.sync');
    Route::post('/currencies/convert', [CurrencyController::class, 'convert'])->name('currencies.convert');
    Route::put('/currencies/{currency}', [CurrencyController::class, 'update'])->name('currencies.update');
    Route::delete('/currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.destroy');
    Route::post('/currencies/{currency}/toggle-active', [CurrencyController::class, 'toggleActive'])->name('currencies.toggleActive');
    Route::post('/currencies/{currency}/set-default', [CurrencyController::class, 'setDefault'])->name('currencies.setDefault');

    Route::get('/reminders', [ReminderController::class, 'index'])->name('reminders.index');
    Route::post('/reminders', [ReminderController::class, 'store'])->name('reminders.store');
    Route::put('/reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');
    Route::delete('/reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');
    Route::post('/reminders/{reminder}/toggle-active', [ReminderController::class, 'toggleActive'])->name('reminders.toggleActive');
    Route::post('/reminders/{reminder}/send-now', [ReminderController::class, 'sendNow'])->name('reminders.sendNow');
    Route::post('/reminders/test', [ReminderController::class, 'sendTest'])->name('reminders.sendTest');
    Route::post('/reminders/settings', [ReminderController::class, 'updateSettings'])->name('reminders.updateSettings');

    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

    Route::get('/recurring', [RecurringScheduleController::class, 'index'])->name('recurring.index');
    Route::get('/recurring/create', [RecurringScheduleController::class, 'create'])->name('recurring.create');
    Route::post('/recurring', [RecurringScheduleController::class, 'store'])->name('recurring.store');
    Route::get('/recurring/{recurring}', [RecurringScheduleController::class, 'show'])->name('recurring.show');
    Route::get('/recurring/{recurring}/edit', [RecurringScheduleController::class, 'edit'])->name('recurring.edit');
    Route::put('/recurring/{recurring}', [RecurringScheduleController::class, 'update'])->name('recurring.update');
    Route::delete('/recurring/{recurring}', [RecurringScheduleController::class, 'destroy'])->name('recurring.destroy');
    Route::post('/recurring/{recurring}/confirm', [RecurringScheduleController::class, 'confirm'])->name('recurring.confirm');
    Route::post('/recurring/{recurring}/skip', [RecurringScheduleController::class, 'skip'])->name('recurring.skip');

    Route::get('/credit', [CreditAccountController::class, 'index'])->name('credit.index');
    Route::get('/credit/accounts/{creditAccount}/statement', [CreditAccountController::class, 'statement'])->name('credit.accounts.statement');
    Route::post('/credit/accounts', [CreditAccountController::class, 'store'])->name('credit.accounts.store');
    Route::post('/credit/accounts/{creditAccount}/pay-bill', [CreditAccountController::class, 'payBill'])->name('credit.accounts.payBill');
    Route::put('/credit/accounts/{creditAccount}', [CreditAccountController::class, 'update'])->name('credit.accounts.update');
    Route::delete('/credit/accounts/{creditAccount}', [CreditAccountController::class, 'destroy'])->name('credit.accounts.destroy');
    Route::post('/credit/accounts/{creditAccount}/unarchive', [CreditAccountController::class, 'unarchive'])->name('credit.accounts.unarchive');

    Route::get('/credit/purchases/create', [InstallmentController::class, 'create'])->name('credit.purchases.create');
    Route::post('/credit/purchases', [InstallmentController::class, 'store'])->name('credit.purchases.store');
    Route::get('/credit/plans/{plan}', [InstallmentController::class, 'show'])->name('credit.plans.show');
    Route::post('/credit/installments/{installment}/pay', [InstallmentController::class, 'pay'])->name('credit.installments.pay');
    Route::post('/credit/installments/{installment}/mark-paid', [InstallmentController::class, 'markPaidWithoutCash'])->name('credit.installments.markPaidWithoutCash');
    Route::post('/credit/installments/{installment}/reset-to-pending', [InstallmentController::class, 'resetToPending'])->name('credit.installments.resetToPending');
    Route::post('/credit/plans/{plan}/mark-overdue-paid', [InstallmentController::class, 'markOverduePaidWithoutCash'])->name('credit.plans.markOverduePaidWithoutCash');
    Route::post('/credit/plans/{plan}/payoff', [InstallmentController::class, 'payoff'])->name('credit.plans.payoff');
    Route::post('/credit/plans/{plan}/void', [InstallmentController::class, 'void'])->name('credit.plans.void');
    Route::post('/credit/notes', [InstallmentController::class, 'storeNote'])->name('credit.notes.store');
});
