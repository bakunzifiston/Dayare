<?php

namespace App\Providers;

use App\Events\Logistics\TripCompleted;
use App\Events\Logistics\TripPlanned;
use App\Events\Logistics\TripStarted;
use App\Listeners\Logistics\LogTripCompleted;
use App\Listeners\Logistics\LogTripPlanned;
use App\Listeners\Logistics\LogTripStarted;
use App\Models\Animal;
use App\Models\AnimalCertificate;
use App\Models\AnimalCertificateTemplate;
use App\Models\Buyer;
use App\Models\ColdRoomTemperatureLog;
use App\Models\DiseaseRecord;
use App\Models\FeedingRecord;
use App\Models\FeedingSchedule;
use App\Models\FeedInventory;
use App\Models\FeedSupplier;
use App\Models\FeedType;
use App\Models\Livestock;
use App\Models\LogisticsCompany;
use App\Models\LogisticsOrder;
use App\Models\LogisticsTrip;
use App\Models\MortalityRecord;
use App\Models\MovementPermit;
use App\Models\PermitRequest;
use App\Models\Sale;
use App\Models\Treatment;
use App\Models\Vaccination;
use App\Models\VeterinaryVisit;
use App\Observers\ColdRoomTemperatureLogObserver;
use App\Policies\AnimalCertificatePolicy;
use App\Policies\AnimalCertificateTemplatePolicy;
use App\Policies\AnimalPolicy;
use App\Policies\BuyerPolicy;
use App\Policies\DiseaseRecordPolicy;
use App\Policies\FeedingRecordPolicy;
use App\Policies\FeedingSchedulePolicy;
use App\Policies\FeedInventoryPolicy;
use App\Policies\FeedSupplierPolicy;
use App\Policies\FeedTypePolicy;
use App\Policies\LivestockPolicy;
use App\Policies\LogisticsCompanyPolicy;
use App\Policies\LogisticsOrderPolicy;
use App\Policies\LogisticsTripPolicy;
use App\Policies\MortalityRecordPolicy;
use App\Policies\MovementPermitPolicy;
use App\Policies\PermitRequestPolicy;
use App\Policies\SalePolicy;
use App\Policies\TreatmentPolicy;
use App\Policies\VaccinationPolicy;
use App\Policies\VeterinaryVisitPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ensures dompdf.wrapper is bound even when package discovery is off or
        // bootstrap/cache/services.php is stale (common on cPanel after adding providers).
        if (class_exists(\Barryvdh\DomPDF\ServiceProvider::class)) {
            $this->app->register(\Barryvdh\DomPDF\ServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ColdRoomTemperatureLog::observe(ColdRoomTemperatureLogObserver::class);
        Gate::policy(Livestock::class, LivestockPolicy::class);
        Gate::policy(Animal::class, AnimalPolicy::class);
        Gate::policy(MovementPermit::class, MovementPermitPolicy::class);
        Gate::policy(PermitRequest::class, PermitRequestPolicy::class);
        Gate::policy(Buyer::class, BuyerPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(AnimalCertificate::class, AnimalCertificatePolicy::class);
        Gate::policy(AnimalCertificateTemplate::class, AnimalCertificateTemplatePolicy::class);
        Gate::policy(Vaccination::class, VaccinationPolicy::class);
        Gate::policy(Treatment::class, TreatmentPolicy::class);
        Gate::policy(DiseaseRecord::class, DiseaseRecordPolicy::class);
        Gate::policy(VeterinaryVisit::class, VeterinaryVisitPolicy::class);
        Gate::policy(MortalityRecord::class, MortalityRecordPolicy::class);
        Gate::policy(FeedType::class, FeedTypePolicy::class);
        Gate::policy(FeedSupplier::class, FeedSupplierPolicy::class);
        Gate::policy(FeedInventory::class, FeedInventoryPolicy::class);
        Gate::policy(FeedingRecord::class, FeedingRecordPolicy::class);
        Gate::policy(FeedingSchedule::class, FeedingSchedulePolicy::class);
        Gate::policy(LogisticsCompany::class, LogisticsCompanyPolicy::class);
        Gate::policy(LogisticsOrder::class, LogisticsOrderPolicy::class);
        Gate::policy(LogisticsTrip::class, LogisticsTripPolicy::class);

        Event::listen(TripPlanned::class, LogTripPlanned::class);
        Event::listen(TripStarted::class, LogTripStarted::class);
        Event::listen(TripCompleted::class, LogTripCompleted::class);

        // Super Admin bypasses all permission checks (roles/permissions still apply to tenants).
        Gate::before(function ($user, $ability) {
            if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }
        });

        // Session cookie: must match your site (HTTP vs HTTPS) so the browser sends it.
        $appUrl = config('app.url');
        if ($appUrl) {
            if (str_starts_with($appUrl, 'https://')) {
                config(['session.secure' => true]);  // HTTPS: cookie must be Secure so browser sends it
            } else {
                config(['session.secure' => false]); // HTTP: do not use Secure or browser won't send cookie
            }
        }

        // Use correct domain for links (View, Facilities, Edit) so they work on cPanel/production.
        $appUrl = config('app.url');
        $appHost = $appUrl ? parse_url($appUrl, PHP_URL_HOST) : null;
        $appUrlIsProduction = $appHost && ! in_array($appHost, ['localhost', '127.0.0.1'], true)
            && ! str_ends_with((string) $appHost, '.local') && ! str_ends_with((string) $appHost, '.test');

        if ($appUrlIsProduction && $appUrl) {
            // On server: APP_URL is set to real domain (e.g. https://dayare.sandbox.rw) – use it for all links.
            URL::forceRootUrl(rtrim($appUrl, '/'));
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }

            return;
        }

        if ($this->app->runningInConsole()) {
            if ($appUrl) {
                URL::forceRootUrl(rtrim($appUrl, '/'));
            }

            return;
        }

        $request = Request::capture();
        $host = $request->getHost();
        $isLocal = in_array($host, ['localhost', '127.0.0.1'], true)
            || str_ends_with($host, '.local') || str_ends_with($host, '.test');

        if (! $isLocal) {
            $scheme = $request->getScheme();
            $port = $request->getPort();
            $url = $scheme.'://'.$host.(in_array($port, [80, 443, null], true) ? '' : ':'.$port);
            URL::forceRootUrl(rtrim($url, '/'));
            if ($scheme === 'https') {
                URL::forceScheme('https');
            }
        } elseif ($appUrl) {
            URL::forceRootUrl(rtrim($appUrl, '/'));
        }
    }
}
