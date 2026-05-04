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
    ProfileController,
    StaffMemberController,
    ReviewController,
    FeedbackController as AdminFeedbackController,
    GalleryController,
    SocialMediaController,
};


// ── Public ────────────────────────────────────────────────────────────────────
Route::post('/admin/login', [AuthController::class, 'login']);
Route::post('/admin/2fa/verify', [AuthController::class, 'verifyTwoFactor']);
Route::post('/admin/2fa/recovery/request', [AuthController::class, 'requestTwoFactorRecovery']);
Route::post('/admin/2fa/recovery/verify', [AuthController::class, 'verifyTwoFactorRecovery']);
Route::post('/admin/password/email', [AuthController::class, 'sendPasswordResetLink']);
Route::post('/admin/password/reset', [AuthController::class, 'resetPassword']);
Route::get('/gallery', [GalleryController::class, 'publicIndex']);
// Facebook OAuth callback — no auth required.
// Register this exact URL in Meta Developer Portal → Facebook Login → Valid OAuth Redirect URIs:
//   https://admin.premaxautoservice.co.ke/api/social-media/oauth/facebook/callback
Route::get('/social-media/oauth/facebook/callback', [SocialMediaController::class, 'oauthCallback']);
// TikTok OAuth callback — no auth required, account ID in path.
// Register the per-account URL in TikTok Developer Portal → your app → Redirect URI:
//   https://admin.premaxautoservice.co.ke/api/social-media/oauth/tiktok/{id}/callback
Route::get('/social-media/oauth/tiktok/{socialAccount}/callback', [SocialMediaController::class, 'tikTokOAuthCallback']);
Route::match(['get', 'post'], '/social-media/webhooks/{platform}/{socialAccount}', [SocialMediaController::class, 'webhookCallback']);

// ── Protected (Sanctum) ───────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {

    // Auth
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout',[AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view');

    //bookings     
    Route::get('/bookings/statuses',    [BookingsController::class, 'statuses'])->middleware('permission:bookings.manage');
    Route::get('/bookings',             [BookingsController::class, 'index'])->middleware('permission:bookings.manage');
    Route::post('/bookings',            [BookingsController::class, 'store'])->middleware('permission:bookings.manage');
    Route::get('/bookings/{booking}',   [BookingsController::class, 'show'])->middleware('permission:bookings.manage');
    Route::patch('/bookings/{booking}', [BookingsController::class, 'update'])->middleware('permission:bookings.manage');
    Route::post('/bookings/{booking}/collect-deposit', [BookingsController::class, 'collectDeposit'])->middleware('permission:bookings.manage');
    Route::delete('/bookings/{booking}',[BookingsController::class, 'destroy'])->middleware('permission:bookings.manage');
    Route::get('/booking-sources',    [BookingsController::class, 'sources'])->middleware('permission:bookings.manage');
    

    //services
     Route::get('/services',    [BookingsController::class, 'services'])->middleware('permission:bookings.manage');

    // Customers
    Route::get('/customers',                          [CustomerController::class, 'index'])->middleware('permission:customers.manage');
    Route::post('/customers',                         [CustomerController::class, 'store'])->middleware('permission:customers.manage');
    Route::get('/customers/{customer}',               [CustomerController::class, 'show'])->middleware('permission:customers.manage');
    Route::put('/customers/{customer}',               [CustomerController::class, 'update'])->middleware('permission:customers.manage');
    Route::get('/customers/{customer}/service-history',[CustomerController::class,'serviceHistory'])->middleware('permission:customers.manage');
    Route::get('/customers/{customer}/invoices',      [CustomerController::class, 'invoices'])->middleware('permission:customers.manage');

    // Vehicles
    Route::get('/vehicles',                          [VehicleController::class, 'index'])->middleware('permission:vehicles.manage');
    Route::post('/vehicles',                         [VehicleController::class, 'store'])->middleware('permission:vehicles.manage');
    Route::get('/vehicles/{vehicle}',                [VehicleController::class, 'show'])->middleware('permission:vehicles.manage');
    Route::put('/vehicles/{vehicle}',                [VehicleController::class, 'update'])->middleware('permission:vehicles.manage');
    Route::post('/vehicles/{vehicle}/inspections',   [VehicleController::class, 'addInspection'])->middleware('permission:vehicles.manage');
    Route::get('/vehicles/{vehicle}/history',        [VehicleController::class, 'history'])->middleware('permission:vehicles.manage');

    // Job Cards (Kanban)
    Route::get('/job-cards',                            [JobCardController::class, 'index'])->middleware('permission:job_cards.manage');
    Route::post('/job-cards',                           [JobCardController::class, 'store'])->middleware('permission:job_cards.manage');
    Route::patch('/job-cards/{jobCard}/stage',          [JobCardController::class, 'updateStage'])->middleware('permission:job_cards.manage');
    Route::delete('/job-cards/{jobCard}',               [JobCardController::class, 'destroy'])->middleware('permission:job_cards.manage');

    // Inventory
    Route::get('/inventory/alerts',                     [InventoryController::class, 'alerts'])->middleware('permission:inventory.manage');
    Route::get('/inventory',                            [InventoryController::class, 'index'])->middleware('permission:inventory.manage');
    Route::post('/inventory',                           [InventoryController::class, 'store'])->middleware('permission:inventory.manage');
    Route::get('/inventory/{inventoryItem}',            [InventoryController::class, 'show'])->middleware('permission:inventory.manage');
    Route::put('/inventory/{inventoryItem}',            [InventoryController::class, 'update'])->middleware('permission:inventory.manage');
    Route::delete('/inventory/{inventoryItem}',         [InventoryController::class, 'destroy'])->middleware('permission:inventory.manage');
    Route::post('/inventory/{inventoryItem}/stock-in',  [InventoryController::class, 'stockIn'])->middleware('permission:inventory.manage');
    Route::post('/inventory/{inventoryItem}/stock-out', [InventoryController::class, 'stockOut'])->middleware('permission:inventory.manage');
    Route::get('/inventory/{inventoryItem}/movements',  [InventoryController::class, 'movements'])->middleware('permission:inventory.manage');

    // Invoices / Payments / POS
    Route::get('/invoices',           [InvoiceController::class, 'index'])->middleware('permission:payments.manage');
    Route::get('/invoices/today',     [InvoiceController::class, 'todaySummary'])->middleware('permission:payments.manage');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:payments.manage');
    Route::post('/pos/checkout',      [InvoiceController::class, 'checkout'])->middleware('permission:pos.manage');
    Route::patch('/invoices/{invoice}/status',       [InvoiceController::class, 'updateStatus'])->middleware('permission:payments.manage');
    Route::patch('/invoices/{invoice}/link-booking', [InvoiceController::class, 'linkBooking'])->middleware('permission:payments.manage');

    // Reports
    Route::get('/reports', [ReportsController::class, 'index'])->middleware('permission:reports.view');

    // Settings
    Route::get('/settings',  [SettingsController::class, 'index'])->middleware('permission:settings.manage');
    Route::post('/settings', [SettingsController::class, 'update'])->middleware('permission:settings.manage');

    // Checklists
   Route::prefix('checklists')->name('checklists.')->group(function () {
        Route::get('/',                          [ChecklistController::class, 'index'])->middleware('permission:checklists.manage');
        Route::post('/',                         [ChecklistController::class, 'store'])->middleware('permission:checklists.manage');
        Route::get('/{checklist}',               [ChecklistController::class, 'show'])->middleware('permission:checklists.manage');
        Route::put('/{checklist}',               [ChecklistController::class, 'update'])->middleware('permission:checklists.manage');
        Route::delete('/{checklist}',            [ChecklistController::class, 'destroy'])->middleware('permission:checklists.manage');
        Route::post('/{checklist}/checkout',     [ChecklistController::class, 'checkout'])->middleware('permission:checklists.manage');
        Route::get('/{checklist}/summary',       [ChecklistController::class, 'summary'])->middleware('permission:checklists.manage');
    });
    Route::get('/users/meta',     [UserController::class, 'meta'])->middleware('permission:users.manage');
    Route::get('/users',          [UserController::class, 'index'])->middleware('permission:users.manage');
    Route::post('/users',         [UserController::class, 'store'])->middleware('permission:users.manage');
    Route::put('/users/{user}',   [UserController::class, 'update'])->middleware('permission:users.manage');
    Route::patch('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.manage');
    Route::delete('/users/{user}',[UserController::class, 'destroy'])->middleware('permission:users.manage');
    Route::post('/users/{user}/reset-2fa', [UserController::class, 'resetTwoFactor'])->middleware('permission:users.manage');
    Route::post('/users/{user}/send-password-reset', [UserController::class, 'sendPasswordResetLink'])->middleware('permission:users.manage');
    
    // Services (add PUT + DELETE if not present)
   Route::get('/settings/contact',              [SettingsController::class, 'contact'])->middleware('permission:settings.manage');
    Route::put('/settings/contact',              [SettingsController::class, 'updateContact'])->middleware('permission:settings.manage');
    Route::get('/settings',                      [SettingsController::class, 'index'])->middleware('permission:settings.manage');
    Route::post('/settings',                     [SettingsController::class, 'store'])->middleware('permission:settings.manage');

    Route::get('/service-categories',            [ServiceCategoryController::class, 'index'])->middleware('permission:services.manage');
    Route::post('/service-categories',           [ServiceCategoryController::class, 'store'])->middleware('permission:services.manage');
    Route::put('/service-categories/{serviceCategory}', [ServiceCategoryController::class, 'update'])->middleware('permission:services.manage');

    Route::get('/services',                      [ServiceController::class, 'index'])->middleware('permission:services.manage,gallery.manage,bookings.manage,pos.manage,feedback.manage,job_cards.manage');
    Route::post('/services',                     [ServiceController::class, 'store'])->middleware('permission:services.manage');
    Route::put('/services/{service}',            [ServiceController::class, 'update'])->middleware('permission:services.manage');
    Route::patch('/services/{service}',          [ServiceController::class, 'update'])->middleware('permission:services.manage');
    Route::delete('/services/{service}',         [ServiceController::class, 'destroy'])->middleware('permission:services.manage');

    Route::post('/mpesa/stk-push', [MpesaController::class, 'stkPush'])->middleware('permission:payments.manage');
    Route::get('/mpesa/status',      [MpesaController::class, 'checkStatus'])->middleware('permission:payments.manage');

    Route::put('/profile', [ProfileController::class, 'update']);

    // Staff Members
    Route::get   ('/staff-members',          [StaffMemberController::class, 'index'])->middleware('permission:staff.manage');
    Route::post  ('/staff-members',          [StaffMemberController::class, 'store'])->middleware('permission:staff.manage');
    Route::get   ('/staff-members/{staffMember}', [StaffMemberController::class, 'show'])->middleware('permission:staff.manage');
    Route::put   ('/staff-members/{staffMember}', [StaffMemberController::class, 'update'])->middleware('permission:staff.manage');
    Route::patch ('/staff-members/{staffMember}', [StaffMemberController::class, 'update'])->middleware('permission:staff.manage'); // for quick toggles
    Route::delete('/staff-members/{staffMember}', [StaffMemberController::class, 'destroy'])->middleware('permission:staff.manage');

    // Reviews
    Route::get   ('/reviews',          [ReviewController::class, 'index'])->middleware('permission:reviews.manage');
    Route::post  ('/reviews',          [ReviewController::class, 'store'])->middleware('permission:reviews.manage');
    Route::get   ('/reviews/{review}', [ReviewController::class, 'show'])->middleware('permission:reviews.manage');
    Route::put   ('/reviews/{review}', [ReviewController::class, 'update'])->middleware('permission:reviews.manage');
    Route::patch ('/reviews/{review}', [ReviewController::class, 'update'])->middleware('permission:reviews.manage'); // for quick status/featured toggles
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->middleware('permission:reviews.manage');

    Route::get('/feedback/stats',           [AdminFeedbackController::class, 'stats'])->middleware('permission:feedback.manage');
    Route::post('/feedback/generate-token', [AdminFeedbackController::class, 'generateToken'])->middleware('permission:feedback.manage');
    Route::get('/feedback',                 [AdminFeedbackController::class, 'index'])->middleware('permission:feedback.manage');
    Route::post('/feedback',                [AdminFeedbackController::class, 'store'])->middleware('permission:feedback.manage');
    Route::delete('/feedback/{feedback}',   [AdminFeedbackController::class, 'destroy'])->middleware('permission:feedback.manage');

    Route::get('/gallery',                  [GalleryController::class, 'index'])->middleware('permission:gallery.manage');
    Route::post('/gallery',                 [GalleryController::class, 'store'])->middleware('permission:gallery.manage');
    Route::patch('/gallery/{galleryItem}',  [GalleryController::class, 'update'])->middleware('permission:gallery.manage');
    Route::delete('/gallery/{galleryItem}', [GalleryController::class, 'destroy'])->middleware('permission:gallery.manage');

    Route::get('/social-media', [SocialMediaController::class, 'index'])->middleware('permission:social_media.view,social_media.accounts.manage,social_media.inbox.manage,social_media.posts.manage,social_media.engagement.manage');
    Route::post('/social-media/accounts', [SocialMediaController::class, 'storeAccount'])->middleware('permission:social_media.accounts.manage');
    Route::patch('/social-media/accounts/{socialAccount}', [SocialMediaController::class, 'updateAccount'])->middleware('permission:social_media.accounts.manage');
    Route::post('/social-media/accounts/{socialAccount}/sync', [SocialMediaController::class, 'syncAccount'])->middleware('permission:social_media.accounts.manage');
    Route::post('/social-media/accounts/{socialAccount}/refresh-token', [SocialMediaController::class, 'refreshToken'])->middleware('permission:social_media.accounts.manage');
    Route::get('/social-media/accounts/{socialAccount}/oauth-url', [SocialMediaController::class, 'getOAuthRedirectUrl'])->middleware('permission:social_media.accounts.manage');
    Route::post('/social-media/accounts/{socialAccount}/regenerate-page-token', [SocialMediaController::class, 'regeneratePageToken'])->middleware('permission:social_media.accounts.manage');
    Route::post('/social-media/accounts/{socialAccount}/sync-posts', [SocialMediaController::class, 'syncAccountPosts'])->middleware('permission:social_media.posts.manage');
    Route::post('/social-media/accounts/{socialAccount}/sync-messages', [SocialMediaController::class, 'syncAccountMessages'])->middleware('permission:social_media.inbox.manage');
    Route::get('/social-media/accounts/{socialAccount}/tiktok-oauth-url', [SocialMediaController::class, 'getTikTokOAuthUrl'])->middleware('permission:social_media.accounts.manage');
    Route::post('/social-media/accounts/{socialAccount}/refresh-tiktok-token', [SocialMediaController::class, 'refreshTikTokToken'])->middleware('permission:social_media.accounts.manage');
    Route::post('/social-media/accounts/{socialAccount}/verify-system-token', [SocialMediaController::class, 'verifySystemUserToken'])->middleware('permission:social_media.accounts.manage');
    Route::post('/social-media/media', [SocialMediaController::class, 'uploadMedia'])->middleware('permission:social_media.posts.manage');
    Route::post('/social-media/posts', [SocialMediaController::class, 'storePost'])->middleware('permission:social_media.posts.manage');
    Route::get('/social-media/posts/{socialPost}/comments', [SocialMediaController::class, 'postComments'])->middleware('permission:social_media.engagement.manage');
    Route::patch('/social-media/posts/{socialPost}', [SocialMediaController::class, 'updatePost'])->middleware('permission:social_media.posts.manage');
    Route::delete('/social-media/posts/{socialPost}', [SocialMediaController::class, 'destroyPost'])->middleware('permission:social_media.posts.manage');
    Route::post('/social-media/posts/{socialPost}/publish', [SocialMediaController::class, 'publishPost'])->middleware('permission:social_media.posts.manage');
    Route::post('/social-media/posts/{socialPost}/sync-interactions', [SocialMediaController::class, 'syncPostInteractions'])->middleware('permission:social_media.engagement.manage');
    Route::patch('/social-media/comments/{socialComment}', [SocialMediaController::class, 'updateComment'])->middleware('permission:social_media.engagement.manage');
    Route::post('/social-media/comments/{socialComment}/reply', [SocialMediaController::class, 'replyToComment'])->middleware('permission:social_media.engagement.manage');
    Route::get('/social-media/conversations/{socialConversation}/messages', [SocialMediaController::class, 'conversationMessages'])->middleware('permission:social_media.inbox.manage');
    Route::post('/social-media/conversations/{socialConversation}/messages', [SocialMediaController::class, 'sendMessage'])->middleware('permission:social_media.inbox.manage');
});
Route::post('/admin/mpesa/callback', [MpesaController::class, 'callback']);
