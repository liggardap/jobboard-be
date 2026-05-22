<?php

use App\Actions\Application\ApplyToJob;
use App\Actions\Application\ListJobApplications;
use App\Actions\Application\ListMyApplications;
use App\Actions\Application\WithdrawApplication;
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
use App\Actions\Job\CreateJob;
use App\Actions\Job\DeleteJob;
use App\Actions\Job\GetJob;
use App\Actions\Job\ListJobs;
use App\Actions\Job\UpdateJob;
use App\Actions\Me\GetMe;
use App\Actions\Me\UpdateProfile;
use App\Actions\Search\SearchJobs;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/jobs', SearchJobs::class);

    Route::get('/jobs/{id}', GetJob::class)->whereNumber('id');
    Route::middleware(['auth:api', 'role:company,admin'])->group(function () {
        Route::post('/jobs', CreateJob::class)->middleware('role:company');
        Route::patch('/jobs/{id}', UpdateJob::class)->whereNumber('id');
        Route::delete('/jobs/{id}', DeleteJob::class)->whereNumber('id');
    });
    Route::get('/companies/{id}/jobs', ListJobs::class)->whereNumber('id')->middleware('auth:api');

    Route::prefix('companies')->middleware('auth:api')->group(function () {
        Route::get('/', ListCompanies::class);
        Route::get('/{id}', GetCompany::class)->whereNumber('id');
        Route::post('/', CreateCompany::class)->middleware('role:company');
        Route::patch('/{id}', UpdateCompany::class)->whereNumber('id')->middleware('role:company,admin');
    });

    Route::middleware('auth:api')->group(function () {
        Route::post('/jobs/{id}/apply', ApplyToJob::class)->whereNumber('id')->middleware('role:candidate');
        Route::get('/jobs/{id}/applications', ListJobApplications::class)->whereNumber('id')->middleware('role:company');
        Route::get('/me/applications', ListMyApplications::class);
        Route::delete('/applications/{id}', WithdrawApplication::class)->whereNumber('id');
        Route::get('/me', GetMe::class);
        Route::patch('/me', UpdateProfile::class);
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
