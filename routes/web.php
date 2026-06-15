<?php

use App\Http\Controllers\AdministrativeDivisionController;
use App\Http\Controllers\AnimalIntakeController;
use App\Http\Controllers\AnteMortemInspectionController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ColdRoomController;
use App\Http\Controllers\ColdRoomStandardController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CrmDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryConfirmationController;
use App\Http\Controllers\MeatExportDocumentController;
use App\Http\Controllers\DemandController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\Farmer\AnimalCertificateController;
use App\Http\Controllers\Farmer\AnimalCertificateLogController;
use App\Http\Controllers\Farmer\AnimalCertificateTemplateController;
use App\Http\Controllers\Farmer\AnimalController;
use App\Http\Controllers\Farmer\AnimalHealthRecordController;
use App\Http\Controllers\Farmer\AnimalOwnershipTransferController;
use App\Http\Controllers\Farmer\BuyerController;
use App\Http\Controllers\Farmer\DiseaseRecordController;
use App\Http\Controllers\Farmer\FarmController;
use App\Http\Controllers\Farmer\FarmerAnimalCertificateHubController;
use App\Http\Controllers\Farmer\FarmerAnimalHubController;
use App\Http\Controllers\Farmer\FarmerFeedingHubController;
use App\Http\Controllers\Farmer\FarmerHealthCertificateController;
use App\Http\Controllers\Farmer\FarmerHealthHubController;
use App\Http\Controllers\Farmer\FarmerLivestockHubController;
use App\Http\Controllers\Farmer\FarmerMovementHubController;
use App\Http\Controllers\Farmer\FarmerSalesHubController;
use App\Http\Controllers\Farmer\FeedingRecordController;
use App\Http\Controllers\Farmer\FeedingScheduleController;
use App\Http\Controllers\Farmer\FeedInventoryController;
use App\Http\Controllers\Farmer\FeedSupplierController;
use App\Http\Controllers\Farmer\FeedTypeController;
use App\Http\Controllers\Farmer\HealthTimelineController;
use App\Http\Controllers\Farmer\LivestockController;
use App\Http\Controllers\Farmer\LivestockMovementController;
use App\Http\Controllers\Farmer\MortalityRecordController;
use App\Http\Controllers\Farmer\MovementAnimalController;
use App\Http\Controllers\Farmer\MovementLogController;
use App\Http\Controllers\Farmer\MovementPermitController;
use App\Http\Controllers\Farmer\SaleAnimalController;
use App\Http\Controllers\Farmer\SaleController;
use App\Http\Controllers\Farmer\SaleDocumentController;
use App\Http\Controllers\Farmer\SaleLogController;
use App\Http\Controllers\Farmer\SalePaymentController;
use App\Http\Controllers\Farmer\TreatmentController;
use App\Http\Controllers\Farmer\VaccinationController;
use App\Http\Controllers\Farmer\VeterinaryVisitController;
use App\Http\Controllers\FarmerDashboardController;
use App\Http\Controllers\Finance\FinanceCasualWorkerController;
use App\Http\Controllers\Finance\FinanceCostAllocationController;
use App\Http\Controllers\Finance\FinanceInvoiceController;
use App\Http\Controllers\Finance\FinancePayableController;
use App\Http\Controllers\FinanceDashboardController;
use App\Http\Controllers\InspectorController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LogisticsDashboardController;
use App\Http\Controllers\LogisticsModuleController;
use App\Http\Controllers\PostMortemInspectionController;
use App\Http\Controllers\ProcessorBusinessContextController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SlaughterExecutionController;
use App\Http\Controllers\SlaughterPlanController;
use App\Http\Controllers\SpeciesController;
use App\Http\Controllers\SuperAdminConfigurationController;
use App\Http\Controllers\SuperAdminComplianceController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdminUserController;
use App\Http\Controllers\SuperAdmin\RicaController;
use App\Http\Controllers\SuperAdminVibeProgrammeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TenantUserController;
use App\Http\Controllers\TransportTripController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\WarehouseStorageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->defaultDashboardRouteName());
    }

    return view('welcome');
})->name('home');

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/trace/{slug}', [\App\Http\Controllers\TraceabilityController::class, 'show'])->name('traceability.show');
Route::get('/trace/{slug}/pdf', [\App\Http\Controllers\TraceabilityController::class, 'exportPdf'])->name('traceability.pdf');
Route::get('/verify/permit', [\App\Http\Controllers\PublicPermitVerificationController::class, 'lookup'])->name('verify.permit.lookup');
Route::get('/verify/permit/{identifier}', [\App\Http\Controllers\PublicPermitVerificationController::class, 'show'])->name('verify.permit.show');
Route::get('/verify/{token}', \App\Http\Controllers\PublicAnimalVerificationController::class)->name('animal.verify');
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/animal-passport', [\App\Http\Controllers\PublicAnimalPassportController::class, 'create'])->name('animal.passport.lookup');
    Route::match(['get', 'post'], '/animal-passport/pdf', [\App\Http\Controllers\PublicAnimalPassportController::class, 'pdf'])->name('animal.passport.pdf');
});
Route::get('/movement/{token}', \App\Http\Controllers\PublicMovementVerificationController::class)->name('movement.verify');
Route::get('/permit/{token}', fn (string $token) => redirect()->route('movement.verify', ['token' => $token]))->name('permit.verify');
Route::view('/contact-us', 'contact')->name('contact-us');
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/product/{productId}', [ShopController::class, 'show'])->name('shop.product');
Route::post('/shop/cart/add', [ShopController::class, 'addToCart'])->name('shop.cart.add');
Route::get('/shop/cart', [ShopController::class, 'cart'])->name('shop.cart');
Route::post('/shop/cart/update', [ShopController::class, 'updateCart'])->name('shop.cart.update');
Route::post('/shop/cart/remove', [ShopController::class, 'removeFromCart'])->name('shop.cart.remove');
Route::get('/shop/checkout', [ShopController::class, 'checkout'])->name('shop.checkout');
Route::post('/shop/checkout', [ShopController::class, 'placeOrder'])->name('shop.place-order');
Route::get('/shop/success', [ShopController::class, 'success'])->name('shop.success');
Route::get('/ecosystem/{audience}', function (string $audience) {
    $pages = [
        'farmers' => [
            'title' => __('🐄 For Farmers — Turn Your Livestock Into Verified Value'),
            'subtitle' => __('Stop selling blind. Start selling with proof.'),
            'intro' => __('BuchaPro helps you prove where your livestock comes from, how it was raised, and its health status—so buyers trust you and pay you better.'),
            'hero_image' => asset('images/ecosystem/farmers/farmers-pasture.png'),
            'why_image' => asset('images/ecosystem/farmers/farmers-goats.png'),
            'works_image' => asset('images/ecosystem/farmers/farmers-angus.png'),
            'trust_image' => asset('images/ecosystem/farmers/farmers-pasture.png'),
            'mobile_image' => asset('images/ecosystem/farmers/farmers-goats.png'),
            'why' => [
                ['title' => __('Ensure authenticity of origin'), 'body' => ''],
                ['title' => __('Maintain baseline quality standards'), 'body' => ''],
                ['title' => __('Enable full traceability from source to consumer'), 'body' => ''],
                ['title' => __('Support a transparent and accountable supply chain'), 'body' => ''],
            ],
            'steps' => [
                ['title' => __('Register Your Livestock'), 'body' => __('Create a digital profile for each animal with basic details.')],
                ['title' => __('Record Health & Growth'), 'body' => __('Track vaccinations, treatments, and key events.')],
                ['title' => __('Get Verified'), 'body' => __('Inspectors and vets validate your data for higher trust.')],
                ['title' => __('Sell With Confidence'), 'body' => __('Buyers see verified information and are ready to pay for quality.')],
            ],
            'trust_title' => __('Built for Trust'),
            'trust_body' => __('Every record is time-stamped and cannot be altered without trace. Verification levels show whether data is farmer-declared or officially confirmed.'),
            'mobile_title' => __('📱 All in Your Pocket'),
            'mobile_points' => [
                __('Register animals'),
                __('Update records'),
                __('Track movements'),
                __('Connect with buyers'),
            ],
            'mobile_footer' => __('Anytime, anywhere.'),
            'cta_title' => __('Building Trust from the Ground Up'),
            'cta_subtitle_paragraphs' => [
                __('A strong food system starts with strong farmers.'),
                __('By integrating farmers into a digital and verified ecosystem, BuchaPro ensures that trust is not assumed—but proven.'),
            ],
            'cta_show_rocket' => false,
            'breadcrumb' => __('Farmers'),
            'why_heading' => __('BuchaPro relies on farmers to:'),
            'how_heading' => __('🔗 How It Works'),
            'how_subheading' => __('From registration to confident sales'),
        ],
        'processors' => [
            'title' => __('🏭 For Processors — Automate Compliance. Prove Quality at Scale.'),
            'subtitle' => __('Stop managing risk manually. Start operating with verified systems.'),
            'intro' => __('BuchaPro gives you the tools to track every batch, enforce hygiene standards, and prove compliance without paperwork chaos.'),
            'hero_image' => 'https://images.unsplash.com/photo-1588168333986-5078d3ae3976?auto=format&fit=crop&w=1400&q=80',
            'why_image' => 'https://images.unsplash.com/photo-1603048297172-c92544798d5a?auto=format&fit=crop&w=1000&q=80',
            'works_image' => 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?auto=format&fit=crop&w=1000&q=80',
            'trust_image' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1000&q=80',
            'why' => [
                ['title' => __('📋 Automated Compliance'), 'body' => __('Digitally manage hygiene checks, inspections, and processing standards in one system.')],
                ['title' => __('🔗 Full Batch Traceability'), 'body' => __('Track every animal from intake to final product—no gaps, no uncertainty.')],
                ['title' => __('📉 Reduce Loss & Risk'), 'body' => __('Identify issues early with structured tracking and alerts.')],
                ['title' => __('📊 Operational Visibility'), 'body' => __('Know exactly what is processed, when, and under what conditions.')],
            ],
            'steps' => [
                ['title' => __('Receive Verified Livestock'), 'body' => __('Animals arrive with full origin and health records.')],
                ['title' => __('Process Under Standardized Conditions'), 'body' => __('Log processing stages, inspections, and handling procedures.')],
                ['title' => __('Generate Certified Batches'), 'body' => __('Each batch is tagged, tracked, and graded.')],
                ['title' => __('Supply with Confidence'), 'body' => __('Retailers and buyers trust your output because it’s verifiable.')],
            ],
            'trust_title' => __('Built for Accountability'),
            'trust_body' => __('Every action is recorded, time-stamped, and traceable. No missing data. No unverified processes.'),
            'cta_title' => __('Upgrade Your Processing Standards'),
            'cta_subtitle' => __('Move from manual operations to a system built for scale, compliance, and trust.'),
            'breadcrumb' => __('Processors'),
            'why_heading' => __('⚙️ Why Processors Use BuchaPro'),
            'how_heading' => __('🔄 How It Works'),
            'how_subheading' => __('From verified intake to confident supply'),
        ],
        'logistics' => [
            'title' => __('🚛 For Logistics — Turn Every Delivery Into a Verified Operation'),
            'subtitle' => __('Don’t just transport—prove integrity in transit.'),
            'intro' => __('BuchaPro enables you to monitor, track, and validate every shipment with real-time data.'),
            'hero_image' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1400&q=80',
            'why_image' => asset('images/ecosystem/logistics/logistics-livestock-transport.png'),
            'works_image' => asset('images/ecosystem/logistics/logistics-buchapro-truck.png'),
            'trust_image' => asset('images/ecosystem/logistics/logistics-livestock-transport.png'),
            'why' => [
                ['title' => __('🌡️ Cold-Chain Monitoring'), 'body' => __('Track temperature conditions throughout the journey.')],
                ['title' => __('📍 Real-Time Visibility'), 'body' => __('Know where every shipment is at any moment.')],
                ['title' => __('📊 Performance Tracking'), 'body' => __('Build a reputation based on reliability and compliance.')],
                ['title' => __('🔗 Integrated Demand'), 'body' => __('Access a consistent flow of delivery jobs within the network.')],
            ],
            'steps' => [
                ['title' => __('Receive Transport Requests'), 'body' => __('Get assigned verified shipments from processors or farms.')],
                ['title' => __('Track Conditions in Transit'), 'body' => __('Monitor temperature and location in real time.')],
                ['title' => __('Log Delivery Events'), 'body' => __('Capture handovers, delays, and conditions.')],
                ['title' => __('Build Trust Through Data'), 'body' => __('Your delivery history becomes proof of reliability.')],
            ],
            'trust_title' => __('No More Blind Deliveries'),
            'trust_body' => __('Every shipment is recorded, tracked, and verified—reducing disputes and losses.'),
            'cta_title' => __('Drive With Purpose'),
            'cta_subtitle' => __('Join a network where your performance is visible, valued, and rewarded.'),
            'breadcrumb' => __('Logistics'),
            'why_heading' => __('📦 Why Logistics Providers Choose BuchaPro'),
            'how_heading' => __('🔄 How It Works'),
            'how_subheading' => __('From verified requests to trusted delivery history'),
        ],
        'retailers' => [
            'title' => __('🏪 For Retailers — Sell With Confidence. Eliminate the Trust Gap.'),
            'subtitle' => __('Your customers are asking questions. Now you have answers.'),
            'intro' => __('BuchaPro gives you access to verified, traceable meat that builds customer trust instantly.'),
            'hero_image' => 'https://images.unsplash.com/photo-1578916171728-46686eac8d58?auto=format&fit=crop&w=1400&q=80',
            'why_image' => 'https://images.unsplash.com/photo-1603048297172-c92544798d5a?auto=format&fit=crop&w=1000&q=80',
            'works_image' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=1000&q=80',
            'trust_image' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=1000&q=80',
            'why' => [
                ['title' => __('✅ Verified Supply'), 'body' => __('Source only certified, traceable products.')],
                ['title' => __('📈 Increase Customer Trust'), 'body' => __('Show exactly where your products come from.')],
                ['title' => __('💰 Sell at Premium Prices'), 'body' => __('Trusted products justify higher pricing.')],
                ['title' => __('📦 Simplified Sourcing'), 'body' => __('Order directly from verified processors and suppliers.')],
            ],
            'steps' => [
                ['title' => __('Browse Certified Products'), 'body' => __('Access a marketplace of graded meat.')],
                ['title' => __('Order With Full Transparency'), 'body' => __('See origin, quality, and handling history.')],
                ['title' => __('Receive Verified Deliveries'), 'body' => __('Track shipments and conditions in real time.')],
                ['title' => __('Sell With Proof'), 'body' => __('Let customers verify what they’re buying.')],
            ],
            'trust_title' => __('Protect Your Brand'),
            'trust_body' => __('No more uncertainty. Every product you sell is backed by data.'),
            'cta_title' => __('Upgrade Your Butchery or Store'),
            'cta_subtitle' => __('Stand out by offering what others can’t—proof.'),
            'cta_primary' => __('Start Sourcing Verified Meat'),
            'breadcrumb' => __('Retailers'),
            'why_heading' => __('🛒 Why Retailers Use BuchaPro'),
            'how_heading' => __('🔄 How It Works'),
            'how_subheading' => __('From certified sourcing to proof at the counter'),
        ],
        'consumers' => [
            'title' => __('📱 For Consumers — Know Your Meat. Don’t Guess.'),
            'subtitle' => __('What you eat should never be a mystery.'),
            'intro' => __('BuchaPro gives you full visibility into where your meat comes from and how it was handled.'),
            'hero_image' => 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?auto=format&fit=crop&w=1400&q=80',
            'why_image' => 'https://images.unsplash.com/photo-1603048297172-c92544798d5a?auto=format&fit=crop&w=1000&q=80',
            'works_image' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=1000&q=80',
            'trust_image' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=1000&q=80',
            'why' => [
                ['title' => __('🔍 Scan & Verify'), 'body' => __('Check origin, quality, and handling instantly.')],
                ['title' => __('🛡️ Food Safety You Can Trust'), 'body' => __('See verified data, not assumptions.')],
                ['title' => __('📦 Order Directly'), 'body' => __('Buy from certified sources without middlemen.')],
                ['title' => __('📍 Track Your Order'), 'body' => __('Follow your delivery in real time.')],
            ],
            'steps' => [
                ['title' => __('Scan or Browse Products'), 'body' => __('Access detailed product information instantly.')],
                ['title' => __('Verify Origin & Quality'), 'body' => __('See farm, processing, and transport history.')],
                ['title' => __('Order With Confidence'), 'body' => __('Choose verified products only.')],
                ['title' => __('Receive Fresh & Safe Meat'), 'body' => __('Track delivery conditions until it reaches you.')],
            ],
            'trust_title' => __('Transparency in Every Bite'),
            'trust_body' => __('If it’s not verified, you’ll know.'),
            'cta_title' => __('Take Control of What You Eat'),
            'cta_subtitle' => __('Stop guessing. Start knowing.'),
            'cta_primary' => __('Download the App'),
            'cta_primary_href' => route('home').'#mobile-platform',
            'breadcrumb' => __('Consumers'),
            'why_heading' => __('🥩 Why Consumers Choose BuchaPro'),
            'how_heading' => __('🔄 How It Works'),
            'how_subheading' => __('From scan or browse to fresh, tracked delivery'),
        ],
    ];

    abort_unless(array_key_exists($audience, $pages), 404);

    return view('ecosystem.show', [
        'audience' => $audience,
        'page' => $pages[$audience],
    ]);
})->name('ecosystem.show');

// Temporary: debug why /businesses/{id}/facilities 404 on cPanel. Remove after fixing.
Route::get('/debug-facilities-route', function () {
    if (! config('app.debug')) {
        abort(404);
    }
    $businessId = (int) (request('business_id', 5));
    $business = \App\Models\Business::find($businessId);
    $user = auth()->user();
    $facilitiesRouteExists = collect(\Illuminate\Support\Facades\Route::getRoutes())->contains(fn ($r) => $r->getName() === 'businesses.facilities.index');

    return response()->json([
        'message' => 'Debug: facilities route check',
        'business_id_checked' => $businessId,
        'business_exists' => (bool) $business,
        'business_user_id' => $business?->user_id,
        'logged_in_user_id' => $user?->id,
        'logged_in_email' => $user?->email,
        'ownership_match' => $business && $user ? ($business->user_id === $user->id) : false,
        'facilities_route_registered' => $facilitiesRouteExists,
        'suggestion' => ! $facilitiesRouteExists
            ? 'Run: php artisan route:clear && php artisan config:clear'
            : ($business && $user && $business->user_id !== $user->id
                ? 'Business belongs to another user. Update businesses.user_id to '.$user->id.' for this business.'
                : 'Route and ownership OK. If still 404, check document root points to /public and .htaccess is in use.'),
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
})->middleware('auth');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'tenant', 'workspace:processor'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'tenant', 'workspace:farmer', 'tenant.permission'])->prefix('farmer')->name('farmer.')->group(function () {
    Route::get('dashboard', FarmerDashboardController::class)->name('dashboard');
    Route::get('livestock', [FarmerLivestockHubController::class, 'index'])->name('livestock.index');
    Route::get('animals', [FarmerAnimalHubController::class, 'index'])->name('animals.index');
    Route::prefix('health')->name('health.')->group(function () {
        Route::get('/', [FarmerHealthHubController::class, 'index'])->name('hub');
        Route::get('timeline', [HealthTimelineController::class, 'index'])->name('timeline.index');
        Route::resource('vaccinations', VaccinationController::class);
        Route::resource('treatments', TreatmentController::class);
        Route::resource('diseases', DiseaseRecordController::class)->parameters(['diseases' => 'disease']);
        Route::resource('vet-visits', VeterinaryVisitController::class)->parameters(['vet-visits' => 'vet_visit']);
        Route::resource('mortalities', MortalityRecordController::class)->parameters(['mortalities' => 'mortality']);
    });
    Route::prefix('feeding')->name('feeding.')->group(function () {
        Route::get('/', [FarmerFeedingHubController::class, 'index'])->name('hub');
        Route::resource('feed-types', FeedTypeController::class)->parameters(['feed-types' => 'feed_type']);
        Route::resource('inventory', FeedInventoryController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('records', FeedingRecordController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('suppliers', FeedSupplierController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('schedules', FeedingScheduleController::class)->only(['index', 'create', 'store']);
    });
    Route::prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/', [FarmerAnimalCertificateHubController::class, 'index'])->name('hub');
        Route::resource('animal-certificates', AnimalCertificateController::class)->parameters(['animal-certificates' => 'certificate']);
        Route::post('animal-certificates/{certificate}/revoke', [AnimalCertificateController::class, 'revoke'])->name('animal-certificates.revoke');
        Route::get('animal-certificates/{certificate}/download', [AnimalCertificateController::class, 'download'])->name('animal-certificates.download');
        Route::get('animal-certificates/{certificate}/qr', [AnimalCertificateController::class, 'qr'])->name('animal-certificates.qr');
        Route::resource('templates', AnimalCertificateTemplateController::class)->only(['index', 'create', 'store']);
        Route::resource('ownership-transfers', AnimalOwnershipTransferController::class)->only(['index', 'create', 'store']);
        Route::get('logs', [AnimalCertificateLogController::class, 'index'])->name('logs.index');
    });
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [FarmerSalesHubController::class, 'index'])->name('hub');
        Route::resource('buyers', BuyerController::class);
        Route::resource('records', SaleController::class)->parameters(['records' => 'sale']);
        Route::post('records/{sale}/confirm', [SaleController::class, 'confirm'])->name('records.confirm');
        Route::post('records/{sale}/approve', [SaleController::class, 'approve'])->name('records.approve');
        Route::post('records/{sale}/complete', [SaleController::class, 'complete'])->name('records.complete');
        Route::post('records/{sale}/cancel', [SaleController::class, 'cancel'])->name('records.cancel');
        Route::get('animals', [SaleAnimalController::class, 'index'])->name('animals.index');
        Route::get('payments', [SalePaymentController::class, 'index'])->name('payments.index');
        Route::get('records/{sale}/payments/create', [SalePaymentController::class, 'create'])->name('payments.create');
        Route::post('records/{sale}/payments', [SalePaymentController::class, 'store'])->name('payments.store');
        Route::get('documents', [SaleDocumentController::class, 'index'])->name('documents.index');
        Route::post('records/{sale}/documents', [SaleDocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}/download', [SaleDocumentController::class, 'download'])->name('documents.download');
        Route::get('logs', [SaleLogController::class, 'index'])->name('logs.index');
    });
    Route::get('health-certificates', [FarmerHealthCertificateController::class, 'index'])->name('health-certificates.index');
    Route::get('health-certificates/create', [FarmerHealthCertificateController::class, 'create'])->name('health-certificates.create');
    Route::post('health-certificates', [FarmerHealthCertificateController::class, 'store'])->name('health-certificates.store');
    Route::get('health-certificates/{health_certificate}', [FarmerHealthCertificateController::class, 'show'])->name('health-certificates.show');
    Route::get('health-certificates/{health_certificate}/download', [FarmerHealthCertificateController::class, 'download'])->name('health-certificates.download');
    Route::prefix('movement')->name('movement.')->group(function () {
        Route::get('/', [FarmerMovementHubController::class, 'index'])->name('hub');
        Route::resource('requests', \App\Http\Controllers\Farmer\PermitRequestController::class)->parameters(['requests' => 'permit_request']);
        Route::post('requests/{permit_request}/submit', [\App\Http\Controllers\Farmer\PermitRequestController::class, 'submit'])->name('requests.submit');
        Route::post('requests/{permit_request}/approve', [\App\Http\Controllers\Farmer\PermitRequestController::class, 'approve'])->name('requests.approve');
        Route::post('requests/{permit_request}/reject', [\App\Http\Controllers\Farmer\PermitRequestController::class, 'reject'])->name('requests.reject');
        Route::get('requests/{permit_request}/issue-permit', [\App\Http\Controllers\Farmer\PermitRequestController::class, 'issuePermit'])->name('requests.issue-permit');
        Route::get('history', [\App\Http\Controllers\Farmer\MovementHistoryController::class, 'index'])->name('history.index');
        Route::get('history/{movementHistory}', [\App\Http\Controllers\Farmer\MovementHistoryController::class, 'show'])->name('history.show');
        Route::get('verification', fn () => redirect()->route('verify.permit.lookup'))->name('verification');
        Route::get('permits/{movement_permit}/download', [MovementPermitController::class, 'download'])->name('permits.download');
        Route::post('permits/import-pdf', [MovementPermitController::class, 'importFromPdf'])->name('permits.import-pdf');
        Route::resource('permits', MovementPermitController::class)->parameters(['permits' => 'movement_permit']);
        Route::post('permits/{movement_permit}/submit', [MovementPermitController::class, 'submit'])->name('permits.submit');
        Route::post('permits/{movement_permit}/approve', [MovementPermitController::class, 'approve'])->name('permits.approve');
        Route::post('permits/{movement_permit}/reject', [MovementPermitController::class, 'reject'])->name('permits.reject');
        Route::post('permits/{movement_permit}/start-transit', [MovementPermitController::class, 'startTransit'])->name('permits.start-transit');
        Route::post('permits/{movement_permit}/confirm-arrival', [MovementPermitController::class, 'confirmArrival'])->name('permits.confirm-arrival');
        Route::post('permits/{movement_permit}/cancel', [MovementPermitController::class, 'cancel'])->name('permits.cancel');
        Route::get('animals', [MovementAnimalController::class, 'index'])->name('animals.index');
        Route::get('logs', [MovementLogController::class, 'index'])->name('logs.index');
    });
    Route::redirect('movement-permits', '/farmer/movement/permits')->name('movement-permits.index');
    Route::redirect('movement-permits/create', '/farmer/movement/permits/create')->name('movement-permits.create');
    Route::post('movement-permits', [MovementPermitController::class, 'store'])->name('movement-permits.store');
    Route::post('movement-permits/import-pdf', [MovementPermitController::class, 'importFromPdf'])->name('movement-permits.import-pdf');
    Route::get('movement-permits/{movement_permit}', fn (\App\Models\MovementPermit $movementPermit) => redirect()->route('farmer.movement.permits.show', $movementPermit))->name('movement-permits.show');
    Route::get('movement-permits/{movement_permit}/download', [MovementPermitController::class, 'download'])->name('movement-permits.download');
    Route::resource('farms', FarmController::class);
    Route::resource('farms.livestock', LivestockController::class);
    // Explicit animal routes — nested Route::resource can 404 on some hosts (same pattern as businesses.facilities).
    Route::get('farms/{farm}/livestock/{livestock}/animals', [AnimalController::class, 'index'])->name('farms.livestock.animals.index');
    Route::get('farms/{farm}/livestock/{livestock}/animals/create', [AnimalController::class, 'create'])->name('farms.livestock.animals.create');
    Route::post('farms/{farm}/livestock/{livestock}/animals', [AnimalController::class, 'store'])->name('farms.livestock.animals.store');
    Route::get('farms/{farm}/livestock/{livestock}/animals/{animal}', [AnimalController::class, 'show'])->name('farms.livestock.animals.show');
    Route::get('farms/{farm}/livestock/{livestock}/animals/{animal}/edit', [AnimalController::class, 'edit'])->name('farms.livestock.animals.edit');
    Route::match(['put', 'patch'], 'farms/{farm}/livestock/{livestock}/animals/{animal}', [AnimalController::class, 'update'])->name('farms.livestock.animals.update');
    Route::delete('farms/{farm}/livestock/{livestock}/animals/{animal}', [AnimalController::class, 'destroy'])->name('farms.livestock.animals.destroy');
    Route::post('farms/{farm}/livestock/move', [LivestockMovementController::class, 'store'])->name('farms.livestock.move');
    Route::get('farms/{farm}/livestock/{livestock}', [LivestockController::class, 'show'])->name('farms.livestock.show');
    Route::patch('farms/{farm}/livestock/{livestock}/details', [LivestockController::class, 'updateDetails'])->name('farms.livestock.details.update');
    Route::patch('farms/{farm}/livestock-health-splits', [LivestockController::class, 'updateHealthSplits'])->name('farms.livestock-health-splits.update');
    Route::get('farms/{farm}/health-records', [AnimalHealthRecordController::class, 'index'])->name('farms.health-records.index');
    Route::post('farms/{farm}/health-records', [AnimalHealthRecordController::class, 'store'])->name('farms.health-records.store');
    Route::delete('farms/{farm}/health-records/{health_record}', [AnimalHealthRecordController::class, 'destroy'])->name('farms.health-records.destroy');
    Route::redirect('supply-requests', '/farmer/dashboard')->name('supply-requests.index');
    Route::redirect('supply-history', '/farmer/dashboard')->name('supply-history');
});

Route::middleware(['auth', 'verified', 'tenant', 'workspace:logistics'])->group(function () {
    Route::prefix('logistics')->name('logistics.')->group(function () {
        Route::get('dashboard', LogisticsDashboardController::class)->name('dashboard.index');
        Route::get('company', [LogisticsModuleController::class, 'company'])->name('company.index');
        Route::post('company', [LogisticsModuleController::class, 'storeCompany'])->name('company.store');
        Route::get('company/{logistics_company}', [LogisticsModuleController::class, 'showCompany'])->name('company.show');
        Route::get('company/{logistics_company}/edit', [LogisticsModuleController::class, 'editCompany'])->name('company.edit');
        Route::put('company/{logistics_company}', [LogisticsModuleController::class, 'updateCompany'])->name('company.update');
        Route::delete('company/{logistics_company}', [LogisticsModuleController::class, 'destroyCompany'])->name('company.destroy');
        Route::get('assets', [LogisticsModuleController::class, 'assets'])->name('assets.index');
        Route::get('orders', [LogisticsModuleController::class, 'orders'])->name('orders.index');
        Route::post('orders', [LogisticsModuleController::class, 'storeOrder'])->name('orders.store');
        Route::get('planning', [LogisticsModuleController::class, 'planning'])->name('planning.index');
        Route::post('planning/trips', [LogisticsModuleController::class, 'planTrip'])->name('planning.store');
        Route::get('trips', [LogisticsModuleController::class, 'trips'])->name('trips.index');
        Route::post('trips/{trip}/start', [LogisticsModuleController::class, 'startTrip'])->name('trips.start');
        Route::post('trips/{trip}/complete', [LogisticsModuleController::class, 'completeTrip'])->name('trips.complete');
        Route::get('tracking', [LogisticsModuleController::class, 'tracking'])->name('tracking.index');
        Route::post('tracking/{trip}', [LogisticsModuleController::class, 'storeTracking'])->name('tracking.store');
        Route::get('compliance', [LogisticsModuleController::class, 'compliance'])->name('compliance.index');
        Route::post('compliance/{trip}', [LogisticsModuleController::class, 'storeCompliance'])->name('compliance.store');
        Route::get('billing', [LogisticsModuleController::class, 'billing'])->name('billing.index');
        Route::get('vehicles', [LogisticsModuleController::class, 'vehicles'])->name('vehicles.index');
        Route::post('vehicles', [LogisticsModuleController::class, 'storeVehicle'])->name('vehicles.store');
        Route::get('drivers', [LogisticsModuleController::class, 'drivers'])->name('drivers.index');
        Route::post('drivers', [LogisticsModuleController::class, 'storeDriver'])->name('drivers.store');
        Route::get('invoices', [LogisticsModuleController::class, 'invoices'])->name('invoices.index');
        Route::post('billing/{trip}/invoice', [LogisticsModuleController::class, 'storeInvoice'])->name('billing.store');
        Route::get('reporting', [LogisticsModuleController::class, 'reporting'])->name('reporting');
    });
});

// Administrative divisions (cascade dropdowns) — shared by farmer, processor, logistics, etc.
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('divisions', [AdministrativeDivisionController::class, 'index'])->name('divisions.index');
});

Route::middleware(['auth', 'tenant', 'workspace:processor', 'tenant.permission'])->group(function () {
    Route::get('businesses/overview', [BusinessController::class, 'hub'])->name('businesses.hub');
    Route::get('businesses/{business}/documents/{type}/{filename}', [BusinessController::class, 'downloadDocument'])
        ->name('businesses.document.download');
    Route::resource('businesses', BusinessController::class);
    // Explicit facilities routes (nested under business) – avoids 404 on some cPanel setups
    Route::get('businesses/{business}/facilities', [FacilityController::class, 'index'])->name('businesses.facilities.index');
    Route::get('businesses/{business}/facilities/create', [FacilityController::class, 'create'])->name('businesses.facilities.create');
    Route::post('businesses/{business}/facilities', [FacilityController::class, 'store'])->name('businesses.facilities.store');
    Route::get('businesses/{business}/facilities/{facility}', [FacilityController::class, 'show'])->name('businesses.facilities.show');
    Route::get('businesses/{business}/facilities/{facility}/edit', [FacilityController::class, 'edit'])->name('businesses.facilities.edit');
    Route::put('businesses/{business}/facilities/{facility}', [FacilityController::class, 'update'])->name('businesses.facilities.update');
    Route::delete('businesses/{business}/facilities/{facility}', [FacilityController::class, 'destroy'])->name('businesses.facilities.destroy');

    Route::get('inspectors/overview', [InspectorController::class, 'hub'])->name('inspectors.hub');
    Route::resource('inspectors', InspectorController::class);
    Route::get('animal-intakes/overview', [AnimalIntakeController::class, 'hub'])->name('animal-intakes.hub');
    Route::post('animal-intakes/{animal_intake}/submit', [AnimalIntakeController::class, 'submitDraft'])->name('animal-intakes.submit');
    Route::resource('animal-intakes', AnimalIntakeController::class);
    Route::get('slaughter-plans/overview', [SlaughterPlanController::class, 'hub'])->name('slaughter-plans.hub');
    Route::resource('slaughter-plans', SlaughterPlanController::class);
    Route::get('slaughter-executions/overview', [SlaughterExecutionController::class, 'hub'])->name('slaughter-executions.hub');
    Route::resource('slaughter-executions', SlaughterExecutionController::class);
    Route::get('batches/overview', [BatchController::class, 'hub'])->name('batches.hub');
    Route::resource('batches', BatchController::class);
    Route::get('post-mortem-inspections/overview', [PostMortemInspectionController::class, 'hub'])
        ->name('post-mortem-inspections.hub');
    Route::resource('post-mortem-inspections', PostMortemInspectionController::class);
    Route::get('certificates/overview', [CertificateController::class, 'hub'])->name('certificates.hub');
    Route::resource('certificates', CertificateController::class);
    Route::get('certificates/{certificate}/qr', [CertificateController::class, 'qr'])->name('certificates.qr');
    Route::get('certificates-export', [CertificateController::class, 'export'])->name('certificates.export');
    Route::get('certificates/{certificate}/export-pdf', [CertificateController::class, 'exportSingle'])->name('certificates.export-single');
    Route::resource('warehouse-storages', WarehouseStorageController::class);
    Route::resource('cold-room-standards', ColdRoomStandardController::class)->except(['show']);
    Route::prefix('cold-rooms')->name('cold-rooms.')->group(function () {
        Route::get('/', [ColdRoomController::class, 'hub'])->name('hub');
        Route::get('manage', [ColdRoomController::class, 'index'])->name('manage.index');
        Route::get('manage/create', [ColdRoomController::class, 'create'])->name('manage.create');
        Route::post('manage', [ColdRoomController::class, 'store'])->name('manage.store');
        Route::get('manage/{cold_room}/edit', [ColdRoomController::class, 'edit'])->name('manage.edit');
        Route::put('manage/{cold_room}', [ColdRoomController::class, 'update'])->name('manage.update');
        Route::delete('manage/{cold_room}', [ColdRoomController::class, 'destroy'])->name('manage.destroy');
    });
    Route::post('warehouse-storages/{warehouse_storage}/temperature-logs', [WarehouseStorageController::class, 'storeTemperatureLog'])->name('warehouse-storages.temperature-logs.store');
    Route::delete('warehouse-storages/{warehouse_storage}/temperature-logs/{temperature_log}', [WarehouseStorageController::class, 'destroyTemperatureLog'])->name('warehouse-storages.temperature-logs.destroy');
    Route::get('transport-trips/overview', [TransportTripController::class, 'hub'])->name('transport-trips.hub');
    Route::get('transport-trips/export/traceability', [TransportTripController::class, 'exportTraceability'])
        ->name('transport-trips.export.traceability');
    Route::get('transport-trips/export', [TransportTripController::class, 'export'])->name('transport-trips.export');
    Route::resource('transport-trips', TransportTripController::class);
    Route::get('delivery-confirmations/contracts', [DeliveryConfirmationController::class, 'contracts'])
        ->name('delivery-confirmations.contracts');
    Route::get('delivery-confirmations/export', [DeliveryConfirmationController::class, 'export'])
        ->name('delivery-confirmations.export');
    Route::prefix('delivery-confirmations/{delivery_confirmation}/export-documents')
        ->name('export-documents.')
        ->group(function () {
            Route::get('/', [MeatExportDocumentController::class, 'index'])->name('index');
            Route::get('/create', [MeatExportDocumentController::class, 'create'])->name('create');
            Route::post('/', [MeatExportDocumentController::class, 'store'])->name('store');
            Route::get('/{meat_export_document}', [MeatExportDocumentController::class, 'show'])->name('show');
            Route::get('/{meat_export_document}/edit', [MeatExportDocumentController::class, 'edit'])->name('edit');
            Route::put('/{meat_export_document}', [MeatExportDocumentController::class, 'update'])->name('update');
            Route::delete('/{meat_export_document}', [MeatExportDocumentController::class, 'destroy'])->name('destroy');
            Route::get('/{meat_export_document}/download', [MeatExportDocumentController::class, 'download'])->name('download');
        });
    Route::resource('delivery-confirmations', DeliveryConfirmationController::class);
    Route::get('compliance', [ComplianceController::class, 'index'])->name('compliance.index');
    Route::resource('ante-mortem-inspections', AnteMortemInspectionController::class);

    // CRM / HR modules (full CRUD)
    Route::resource('employees', EmployeeController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::get('contracts/{contract}/file/{type}/{filename}', [ContractController::class, 'downloadFile'])->name('contracts.file.download');
    Route::resource('contracts', ContractController::class);
    Route::get('crm', [CrmDashboardController::class, 'index'])->name('crm.dashboard');
    Route::resource('clients', ClientController::class);
    Route::post('clients/{client}/activities', [ClientController::class, 'storeActivity'])->name('clients.activities.store');
    Route::resource('demands', DemandController::class);
    Route::get('recipients', [RecipientController::class, 'index'])->name('recipients.index');
    Route::delete('client-activities/{client_activity}', [App\Http\Controllers\ClientActivityController::class, 'destroy'])->name('client-activities.destroy');

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', FinanceDashboardController::class)->name('dashboard');
        Route::resource('invoices', FinanceInvoiceController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::post('invoices/{invoice}/mark-paid', [FinanceInvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::post('invoices/from-delivery/{delivery}', [FinanceInvoiceController::class, 'createFromDelivery'])->name('invoices.from-delivery');
        Route::resource('payables', FinancePayableController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::post('payables/{payable}/mark-paid', [FinancePayableController::class, 'markPaid'])->name('payables.mark-paid');
        Route::resource('casual-workers', FinanceCasualWorkerController::class)->except(['show']);
        Route::resource('cost-allocations', FinanceCostAllocationController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::post('cost-allocations/template', [FinanceCostAllocationController::class, 'storeTemplate'])->name('cost-allocations.store-template');
    });

    Route::resource('tenant-users', TenantUserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])->names('tenant-users');

    Route::prefix('processor')->name('processor.')->group(function () {
        Route::redirect('supply-requests', '/dashboard')->name('supply-requests.index');
        Route::redirect('supply-requests/create', '/dashboard')->name('supply-requests.create');
        Route::post('business-context', [ProcessorBusinessContextController::class, 'update'])->name('business-context.update');
    });

});

Route::middleware(['auth', 'tenant', 'super_admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/', [SuperAdminDashboardController::class, 'index'])
        ->middleware('super_admin.module:dashboard')
        ->name('dashboard');
    Route::get('compliance', [SuperAdminComplianceController::class, 'index'])
        ->middleware('super_admin.module:dashboard')
        ->name('compliance.index');
    Route::get('configuration', [SuperAdminConfigurationController::class, 'index'])
        ->middleware('super_admin.module:configuration')
        ->name('configurations.index');
    Route::get('vibe-programme', [SuperAdminVibeProgrammeController::class, 'index'])
        ->middleware('super_admin.module:vibe_programme')
        ->name('vibe-programme.index');
    Route::get('vibe-programme/export', [SuperAdminVibeProgrammeController::class, 'exportProgrammeCsv'])
        ->middleware('super_admin.module:vibe_programme')
        ->name('vibe-programme.export');
    Route::get('vibe-programme/{business}', [SuperAdminVibeProgrammeController::class, 'show'])
        ->middleware('super_admin.module:vibe_programme')
        ->name('vibe-programme.show');
    Route::get('vibe-programme/{business}/export', [SuperAdminVibeProgrammeController::class, 'exportBusinessCsv'])
        ->middleware('super_admin.module:vibe_programme')
        ->name('vibe-programme.export-business');
    Route::resource('species', SpeciesController::class)
        ->middleware('super_admin.module:configuration')
        ->except(['show', 'create', 'edit']);
    Route::resource('units', UnitController::class)
        ->middleware('super_admin.module:configuration')
        ->except(['show', 'create', 'edit']);
    Route::resource('users', SuperAdminUserController::class)
        ->middleware('super_admin.module:user_management')
        ->except(['show']);
    Route::delete('tenants/bulk-delete', [SuperAdminUserController::class, 'destroyTenantsBulk'])
        ->middleware('super_admin.module:user_management')
        ->name('tenants.bulk-destroy');
    Route::delete('tenants/{tenant}', [SuperAdminUserController::class, 'destroyTenant'])
        ->middleware('super_admin.module:user_management')
        ->name('tenants.destroy');
    Route::patch('tenants/{tenant}/tenant-environment', [SuperAdminUserController::class, 'updateTenantEnvironment'])
        ->middleware('super_admin.module:user_management')
        ->name('tenants.environment');
});

Route::middleware(['auth', 'tenant', 'super_admin', 'super_admin.module:rica'])
    ->prefix('super-admin/rica')
    ->name('rica.')
    ->group(function () {
        Route::get('/', [RicaController::class, 'hub'])->name('hub');
        Route::get('slaughterhouses', [RicaController::class, 'index'])->name('slaughterhouses.index');
        Route::get('slaughterhouses/{facility}', [RicaController::class, 'show'])->name('slaughterhouses.show');
        Route::get('reports', [RicaController::class, 'reports'])->name('reports');
        Route::get('reports/export', [RicaController::class, 'export'])->name('reports.export');
    });

Route::middleware(['auth', 'tenant', 'tenant.permission'])->group(function () {
    Route::get('settings', [SettingsController::class, 'edit'])
        ->middleware('super_admin.module:system_settings')
        ->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])
        ->middleware('super_admin.module:system_settings')
        ->name('settings.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
