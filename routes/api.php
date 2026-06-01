<?php

use App\Http\Controllers\Api\NoteController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->name('api.')->group(function (): void {
    Route::get('notes/search', [NoteController::class, 'search'])->name('notes.search');
    Route::post('notes/{note}/summary', [NoteController::class, 'summary'])->name('notes.summary');
    Route::apiResource('notes', NoteController::class);
});
