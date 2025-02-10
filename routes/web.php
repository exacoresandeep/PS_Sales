<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('storage/uploads/{filename}', function ($filename) {
    $path = storage_path('uploads/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
});
require __DIR__.'/admin.php';
