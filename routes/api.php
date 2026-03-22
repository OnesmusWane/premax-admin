<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    DashboardController,
    CustomerController,
    VehicleController,
    JobCardController,
    InventoryController,
    InvoiceController,
    ReportsController,
    SettingsController,
    ChecklistController,
    BookingsController,
    MpesaController,
    UserController,
    ServiceController,
    ServiceCategoryController,
    ProfileController
};


// ── Public ────────────────────────────────────────────────────────────────────
Route::post('/admin/login', [AuthController::class, 'login']);

// ── Protected (Sanctum) ───────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {

    // Auth
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout',[AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    //bookings     
    Route::get('/bookings/statuses',    [BookingsController::class, 'statuses']);
    Route::get('/bookings',             [BookingsController::class, 'index']);
    Route::post('/bookings',            [BookingsController::class, 'store']);
    Route::get('/bookings/{booking}',   [BookingsController::class, 'show']);
    Route::patch('/bookings/{booking}', [BookingsController::class, 'update']);
    Route::delete('/bookings/{booking}',[BookingsController::class, 'destroy']);
    Route::get('/booking-sources',    [BookingsController::class, 'sources']);
    

    //services
     Route::get('/services',    [BookingsController::class, 'services']);

    // Customers
    Route::get('/customers',                          [CustomerController::class, 'index']);
    Route::post('/customers',                         [CustomerController::class, 'store']);
    Route::get('/customers/{customer}',               [CustomerController::class, 'show']);
    Route::put('/customers/{customer}',               [CustomerController::class, 'update']);
    Route::get('/customers/{customer}/service-history',[CustomerController::class,'serviceHistory']);
    Route::get('/customers/{customer}/invoices',      [CustomerController::class, 'invoices']);

    // Vehicles
    Route::get('/vehicles',                          [VehicleController::class, 'index']);
    Route::post('/vehicles',                         [VehicleController::class, 'store']);
    Route::get('/vehicles/{vehicle}',                [VehicleController::class, 'show']);
    Route::put('/vehicles/{vehicle}',                [VehicleController::class, 'update']);
    Route::post('/vehicles/{vehicle}/inspections',   [VehicleController::class, 'addInspection']);
    Route::get('/vehicles/{vehicle}/history',        [VehicleController::class, 'history']);

    // Job Cards (Kanban)
    Route::get('/job-cards',                            [JobCardController::class, 'index']);
    Route::post('/job-cards',                           [JobCardController::class, 'store']);
    Route::patch('/job-cards/{jobCard}/stage',          [JobCardController::class, 'updateStage']);
    Route::delete('/job-cards/{jobCard}',               [JobCardController::class, 'destroy']);

    // Inventory
    Route::get('/inventory/alerts',                     [InventoryController::class, 'alerts']);
    Route::get('/inventory',                            [InventoryController::class, 'index']);
    Route::post('/inventory',                           [InventoryController::class, 'store']);
    Route::get('/inventory/{inventoryItem}',            [InventoryController::class, 'show']);
    Route::put('/inventory/{inventoryItem}',            [InventoryController::class, 'update']);
    Route::delete('/inventory/{inventoryItem}',         [InventoryController::class, 'destroy']);
    Route::post('/inventory/{inventoryItem}/stock-in',  [InventoryController::class, 'stockIn']);
    Route::post('/inventory/{inventoryItem}/stock-out', [InventoryController::class, 'stockOut']);
    Route::get('/inventory/{inventoryItem}/movements',  [InventoryController::class, 'movements']);

    // Invoices / Payments / POS
    Route::get('/invoices',           [InvoiceController::class, 'index']);
    Route::get('/invoices/today',     [InvoiceController::class, 'todaySummary']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::post('/pos/checkout',      [InvoiceController::class, 'checkout']);
    Route::patch('/invoices/{invoice}/status',       [InvoiceController::class, 'updateStatus']);
    Route::patch('/invoices/{invoice}/link-booking', [InvoiceController::class, 'linkBooking']);

    // Reports
    Route::get('/reports', [ReportsController::class, 'index']);

    // Settings
    Route::get('/settings',  [SettingsController::class, 'index']);
    Route::post('/settings', [SettingsController::class, 'update']);

    // Checklists
   Route::prefix('checklists')->name('checklists.')->group(function () {
        Route::get('/',                          [ChecklistController::class, 'index']);
        Route::post('/',                         [ChecklistController::class, 'store']);
        Route::get('/{checklist}',               [ChecklistController::class, 'show']);
        Route::put('/{checklist}',               [ChecklistController::class, 'update']);
        Route::delete('/{checklist}',            [ChecklistController::class, 'destroy']);
        Route::post('/{checklist}/checkout',     [ChecklistController::class, 'checkout']);
        Route::get('/{checklist}/summary',       [ChecklistController::class, 'summary']);
    });
    Route::get('/users',          [UserController::class, 'index']);
    Route::post('/users',         [UserController::class, 'store']);
    Route::put('/users/{user}',   [UserController::class, 'update']);
    Route::delete('/users/{user}',[UserController::class, 'destroy']);
    
    // Services (add PUT + DELETE if not present)
   Route::get('/settings/contact',              [SettingsController::class, 'contact']);
    Route::put('/settings/contact',              [SettingsController::class, 'updateContact']);
    Route::get('/settings',                      [SettingsController::class, 'index']);
    Route::post('/settings',                     [SettingsController::class, 'store']);

    Route::get('/service-categories',            [ServiceCategoryController::class, 'index']);
    Route::post('/service-categories',           [ServiceCategoryController::class, 'store']);
    Route::put('/service-categories/{serviceCategory}', [ServiceCategoryController::class, 'update']);

    Route::get('/services',                      [ServiceController::class, 'index']);
    Route::post('/services',                     [ServiceController::class, 'store']);
    Route::put('/services/{service}',            [ServiceController::class, 'update']);
    Route::patch('/services/{service}',          [ServiceController::class, 'update']);
    Route::delete('/services/{service}',         [ServiceController::class, 'destroy']);

    Route::post('/mpesa/stk-push', [MpesaController::class, 'stkPush']);

    Route::put('/profile', [ProfileController::class, 'update']);
});
Route::post('/admin/mpesa/callback', [MpesaController::class, 'callback']);