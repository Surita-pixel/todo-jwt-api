<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotaController;
Route::get('/pong', function () {
    return response()->json(['message' => 'pong'], 200);
});
// Rutas para la autenticación
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');;

Route::middleware('auth:api')->group(function () {
    Route::get('/notas', [NotaController::class, 'index']); // Obtener todas las notas
    Route::post('/notas', [NotaController::class, 'store']); // Crear una nueva nota
    Route::get('/notas/{nota}', [NotaController::class, 'show']); // Obtener una nota específica
    Route::put('/notas/{nota}', [NotaController::class, 'update']); // Actualizar una nota
    Route::delete('/notas/{nota}', [NotaController::class, 'destroy']); // Eliminar una nota
});