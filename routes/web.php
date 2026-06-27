<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PasswordResetController;

// Route::get('/', function () {
//     return view('welcome');
// });

// Ruta para mostrar el formulario de reset de contraseña
Route::get('/reset-password', [PasswordResetController::class, 'showResetForm']);
