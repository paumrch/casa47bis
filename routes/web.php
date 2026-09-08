<?php

declare(strict_types=1);

use App\Http\Controllers\Portal\CallController;
use App\Http\Controllers\Portal\HomeController;
use App\Http\Controllers\Portal\PageController;
use App\Http\Controllers\Portal\PropertyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal público
|--------------------------------------------------------------------------
|
| Rutas en castellano y legibles: la URL es parte de la interfaz y la gente la
| comparte, la escribe y la lee en voz alta por teléfono.
|
| Todo lo de aquí se sirve como HTML completo, sin autenticación y sin una sola
| línea de JavaScript. Los filtros del catálogo son un formulario GET: funcionan
| con el navegador apagado, se pueden marcar como favorito, se pueden compartir y
| el botón de volver hace lo que se espera.
|
*/

Route::get('/', HomeController::class)->name('inicio');

Route::get('/viviendas', [PropertyController::class, 'index'])->name('viviendas.index');
Route::get('/viviendas/{property:reference_code}', [PropertyController::class, 'show'])->name('viviendas.show');

Route::get('/convocatorias', [CallController::class, 'index'])->name('convocatorias.index');
Route::get('/convocatorias/{call:reference_code}', [CallController::class, 'show'])->name('convocatorias.show');

Route::get('/requisitos', [PageController::class, 'requisitos'])->name('requisitos');
Route::get('/accesibilidad', [PageController::class, 'accesibilidad'])->name('accesibilidad');
Route::get('/aviso-legal', [PageController::class, 'avisoLegal'])->name('aviso-legal');
Route::get('/privacidad', [PageController::class, 'privacidad'])->name('privacidad');
