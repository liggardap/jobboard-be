<?php

use App\Actions\Auth\ForgotPassword;
use App\Actions\Auth\Login;
use App\Actions\Auth\Logout;
use App\Actions\Auth\RefreshToken;
use App\Actions\Auth\Register;
use App\Actions\Auth\ResetPassword;
use App\Actions\Company\CreateCompany;
use App\Actions\Company\GetCompany;
use App\Actions\Company\ListCompanies;
use App\Actions\Company\UpdateCompany;
use App\Actions\Search\SearchJobs;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/jobs', SearchJobs::class);

    Route::prefix('companies')->middleware('auth:api')->group(function () {
        Route::get('/', ListCompanies::class);
        Route::get('/{id}', GetCompany::class)->whereNumber('id');
        Route::post('/', CreateCompany::class)->middleware('role:company');
        Route::patch('/{id}', UpdateCompany::class)->whereNumber('id')->middleware('role:company,admin');
    });

    Route::prefix('auth')->group(function () {
        Route::post('register', Register::class);
        Route::post('login', Login::class);
        Route::post('forgot-password', ForgotPassword::class);
        Route::post('reset-password', ResetPassword::class);

        Route::middleware('auth:api')->group(function () {
            Route::post('logout', Logout::class);
            Route::post('refresh', RefreshToken::class);
        });
    });
});
