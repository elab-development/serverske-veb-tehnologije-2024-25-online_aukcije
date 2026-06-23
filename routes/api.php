<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\AuctionExternalCatalogController;
use App\Http\Controllers\BidController;
use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/auction-external-catalog', AuctionExternalCatalogController::class);

Route::apiResource('categories', CategoryController::class)->only([
    'index',
    'show',
]);

Route::get('/categories/{category}/auctions', [CategoryController::class, 'auctions']);

Route::apiResource('categories', CategoryController::class)->only([
    'store',
    'update',
    'destroy',
])->middleware('auth:sanctum');

Route::get('/auctions/export', [AuctionController::class, 'exportCsv']);

Route::apiResource('auctions', AuctionController::class)->only([
    'index',
    'show',
]);

Route::apiResource('auctions', AuctionController::class)->only([
    'store',
    'update',
    'destroy',
])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bids', [BidController::class, 'store']);
    Route::get('/auctions/{auction}/bids', [BidController::class, 'index']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
