<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Serve the frontend
Route::get('/{any?}', function () {
    return redirect('/index.html');
})->where('any', '.*');
