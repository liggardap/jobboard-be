<?php

use App\Actions\Search\SearchJobs;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/jobs', SearchJobs::class);
});
