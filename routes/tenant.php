<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Tenants\AboutController;
use App\Http\Controllers\Api\Tenants\Master\CategoryController;
use App\Http\Controllers\Api\Tenants\Master\MemberController;
use App\Http\Controllers\Api\Tenants\Master\Product\StockController;
use App\Http\Controllers\Api\Tenants\Master\ProductController;
use App\Http\Controllers\Api\Tenants\PaymentMethodController;
use App\Http\Controllers\Api\Tenants\ProfileController;
use App\Http\Controllers\Api\Tenants\Reports\CashierReportController;
use App\Http\Controllers\Api\Tenants\Settings\SecureInitialPriceController;
use App\Http\Controllers\Api\Tenants\Transaction\CashDrawerController;
use App\Http\Controllers\Api\Tenants\Transaction\DashboardController;
use App\Http\Controllers\Api\Tenants\Transaction\SellingController;
use App\Http\Controllers\Api\Tenants\UploadController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\PrinterController;
use App\Livewire\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])
    ->group(function () {
        Route::get('/reset-password/{token}', ResetPassword::class)
            ->middleware('guest')
            ->name('reset-password.index');
    });

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])
    ->prefix('api')
    ->group(function () {
        Route::get('check', function () {
            return response()->json([
                'tenant' => tenant('id'),
                'tenant_email' => tenant()->tenancy_db_profile_email,
            ]);
        });
        Route::group(['prefix' => 'auth'], function () {
            Route::post('/login', [AuthenticatedSessionController::class, 'store'])
                ->name('login');

            Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
                ->middleware('guest')
                ->name('password.email');

            Route::get('/verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
                ->name('verification.verify');

            Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware(['auth', 'throttle:6,1'])
                ->name('verification.send');

            Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
                ->middleware('auth')
                ->name('logout');

            Route::get('/me', [ProfileController::class, 'index'])
                ->middleware('auth:sanctum')
                ->name('me');

            Route::put('/me', [ProfileController::class, 'update'])
                ->middleware('auth:sanctum')
                ->name('profile.update');
        });

        Route::group(['prefix' => 'temp', 'middleware' => 'auth:sanctum'], function () {
            Route::post('upload', UploadController::class);
        });

        Route::group(['prefix' => 'master', 'middleware' => 'auth:sanctum'], function () {
            Route::group(['prefix' => '/category'], function () {
                Route::get('/', [CategoryController::class, 'index'])->can('read category');
                Route::post('/', [CategoryController::class, 'store'])->can('create category');
                Route::get('/{category}', [CategoryController::class, 'show'])->can('read category');
                Route::put('/{category}', [CategoryController::class, 'update'])->can('update category');
                Route::delete('/{category}', [CategoryController::class, 'destroy'])->can('delete category');
            });

            Route::group(['prefix' => '/product'], function () {
                Route::get('/', [ProductController::class, 'index'])->can('read product');
                Route::post('/', [ProductController::class, 'store'])->can('create product');
                Route::get('/{product}', [ProductController::class, 'show'])->can('read product');
                Route::put('/{product}', [ProductController::class, 'update'])->can('update product');
                Route::delete('/{product}', [ProductController::class, 'destroy'])->can('delete product');
                Route::get('/{product}/stock', [StockController::class, 'index'])->can('read product stock');
                Route::post('/{product}/stock', [StockController::class, 'store'])->can('create product stock');
                Route::delete('/{product}/stock/{stock}', [StockController::class, 'destroy'])->can('delete product stock');
            });

            Route::resource('member', MemberController::class)
                ->middleware('method_and_permission:index@read member|store@create member|show@read member|destroy@delete member|update@update member');

            Route::group(['prefix' => 'payment-method', 'middleware' => 'auth:sanctum'], function () {
                Route::get('/', [PaymentMethodController::class, 'index'])->can('read payment method');
            });

        });

        Route::group(['prefix' => 'about', 'middleware' => 'auth:sanctum'], function () {
            Route::get('/', [AboutController::class, 'index'])
                ->name('about');
            Route::put('/', [AboutController::class, 'update'])
                ->name('about.update');
        });

        Route::group(['prefix' => 'transaction', 'middleware' => 'auth:sanctum'], function () {
            Route::get('/dashboard/total-revenue', [DashboardController::class, 'totalRevenue'])
                ->name('transaction.dashboard.total-revenue');
            Route::get('/dashboard/total-gross-profit', [DashboardController::class, 'totalGrossProfit'])
                ->name('transaction.dashboard.total-gross-profit');
            Route::get('/dashboard/total-sales', [DashboardController::class, 'totalSales'])
                ->name('transaction.dashboard.total-sales');

            Route::get('/selling', [SellingController::class, 'index'])->can('read selling');
            Route::post('/selling', [SellingController::class, 'store'])->can('create selling');
            Route::get('/selling/{selling}', [SellingController::class, 'show'])->can('read selling');
            Route::group(['prefix' => 'cash-drawer', 'middleware' => 'auth:sanctum'], function () {
                Route::get('/', [CashDrawerController::class, 'show'])->can('read cash drawer');
                Route::post('/', [CashDrawerController::class, 'store'])->can('open cash drawer');
                Route::post('/close', [CashDrawerController::class, 'close'])->can('close cash drawer');
            });
        });

        // @TODO: this is should be using can permission
        Route::get('setting/{key}', [App\Http\Controllers\Api\Tenants\SettingController::class, 'show'])
            ->middleware('auth:sanctum')
            ->name('setting.show');
        Route::post('setting', [App\Http\Controllers\Api\Tenants\SettingController::class, 'store'])
            ->middleware('auth:sanctum')
            ->name('setting.store');

        Route::post('setting/secure-initial-price', [SecureInitialPriceController::class, 'store'])
            ->middleware('auth:sanctum')
            ->name('setting.secure-initial-price.store')
            ->can('read using setting enable secure initial price');

        Route::post('setting/secure-initial-price/verify', [SecureInitialPriceController::class, 'verify'])
            ->middleware('auth:sanctum')
            ->name('setting.secure-initial-price.verify')
            ->can('read using setting enable secure initial price');

        Route::group(['prefix' => 'report', 'middleware' => 'auth:sanctum'], function () {
            Route::post('/cashier', CashierReportController::class)
                ->can('generate cashier report');
            // Route::post('/selling', SellingReportController::class)
            //     ->can('generate selling report');
            // Route::post('/product', [App\Http\Controllers\Api\Tenants\Report\ProductReportController::class, 'index'])
            //     ->can('generate product report');
        });

        Route::group(['prefix' => 'printer', 'middleware' => 'auth:sanctum'], function () {
            Route::get('/', [PrinterController::class, 'index'])
                ->can('read printer');
            Route::post('/', [PrinterController::class, 'store'])
                ->can('create printer');
            Route::put('/{printer}', [PrinterController::class, 'update'])
                ->can('update printer');
            Route::delete('/{printer}', [PrinterController::class, 'destroy'])
                ->can('delete printer');
        });

        Route::get('/', function () {
            return ['Laravel' => app()->version()];
        });

        Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
            return $request->user();
        });
    });