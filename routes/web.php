<?php

use App\Http\Controllers\NotePageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [NotePageController::class, 'index']);
Route::post('notes/{note}/summary', [NotePageController::class, 'summary'])->name('notes.summary');
Route::resource('notes', NotePageController::class);
