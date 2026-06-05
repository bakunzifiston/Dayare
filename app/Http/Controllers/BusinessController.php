<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessRequest;
use App\Http\Requests\UpdateBusinessRequest;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Facility;
use App\Models\User;
use App\Support\RemovesLegacyBusinessNameUniqueIndexes;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BusinessController extends Controller
{
    public function hub(Request $request): View
    {
        $user = $request->user();
        $totalBusinesses = $user->businesses()->count();
        $activeCount = $user->businesses()->where('status', Business::STATUS_ACTIVE)->count();
        $suspendedCount = $user->businesses()->where('status', Business::STATUS_SUSPENDED)->count();
        $totalFacilities = Facility::whereIn('business_id', $user->accessibleBusinessIds())->count();
        $businessesWithFacilitiesCount = $user->businesses()->has('facilities')->count();

        return view('businesses.hub', compact(
            'totalBusinesses',
            'activeCount',
            'suspendedCount',
            'totalFacilities',
            'businessesWithFacilitiesCount',
        ));
    }

    public function index(Request $request): View
    {
        $businesses = $request->user()
            ->businesses()
            ->withCount('facilities')
            ->latest()
            ->paginate(10);

        $kpis = [
            'total' => $request->user()->businesses()->count(),
            'facilities' => \App\Models\Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())->count(),
        ];

        return view('businesses.index', compact('businesses', 'kpis'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $processorBusinesses = $request->user()->businesses()
            ->where('type', Business::TYPE_PROCESSOR)
            ->orderByDesc('id')
            ->get();

        if ($processorBusinesses->count() === 1) {
            return redirect()
                ->route('businesses.edit', $processorBusinesses->first())
                ->with('status', __('You already started business registration. Continue your setup below instead of registering again.'));
        }

        return view('businesses.create');
    }

    public function store(StoreBusinessRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $members = $validated['members'] ?? [];
        unset($validated['members']);

        $validated['type'] = $validated['type'] ?? Business::TYPE_PROCESSOR;
        $validated['pathway_status'] = $validated['pathway_status'] ?? 'active';

        $user = $request->user();
        $existingOwned = $this->findOwnedBusinessByName($user, (string) ($validated['business_name'] ?? ''));
        $updatedExisting = $existingOwned !== null;

        try {
            $business = $this->persistOnboardingBusiness($user, $validated, $existingOwned);
        } catch (QueryException $exception) {
            if ($existingOwned === null && $user->businesses()->count() === 1) {
                $fallbackOwned = $user->businesses()->first();
                if ($fallbackOwned !== null) {
                    $business = $this->persistOnboardingBusiness($user, $validated, $fallbackOwned);
                    $updatedExisting = true;
                } else {
                    $this->throwBusinessValidationFromQueryException($exception);
                }
            } else {
                $this->throwBusinessValidationFromQueryException($exception);
            }
        }

        $this->storeBusinessDocumentUploads($request, $business);

        BusinessUser::query()->updateOrCreate(
            ['business_id' => $business->id, 'user_id' => $user->id],
            ['role' => BusinessUser::ROLE_ORG_ADMIN]
        );

        $this->syncOwnershipMembers($business, $members, $updatedExisting);

        return redirect()->route('businesses.hub')
            ->with('status', __('Business registered successfully.'));
    }

    public function show(Request $request, Business $business): View
    {
        $userId = (int) $request->user()->id;
        $businessUserId = (int) $business->user_id;
        if ($businessUserId !== $userId) {
            abort(404, 'This business does not belong to your account. Business user_id='.($business->user_id ?? 'null').', your user_id='.$userId.'. Fix in DB: UPDATE businesses SET user_id='.$userId.' WHERE id='.$business->id.';');
        }

        $business->load(['facilities', 'ownershipMembers', 'countryDivision', 'provinceDivision', 'districtDivision', 'sectorDivision', 'cellDivision', 'villageDivision']);

        return view('businesses.show', compact('business'));
    }

    public function edit(Request $request, Business $business): View
    {
        if ((int) $business->user_id !== (int) $request->user()->id) {
            abort(404, 'This business does not belong to your account.');
        }

        $business->load('ownershipMembers');

        return view('businesses.edit', compact('business'));
    }

    public function update(UpdateBusinessRequest $request, Business $business): RedirectResponse
    {
        if ((int) $business->user_id !== (int) $request->user()->id) {
            abort(404, 'This business does not belong to your account.');
        }

        $validated = $request->validated();
        $members = $validated['members'] ?? [];
        unset($validated['members']);

        try {
            $business->update($validated);
        } catch (QueryException $exception) {
            $this->throwBusinessValidationFromQueryException($exception);
        }

        $this->storeBusinessDocumentUploads($request, $business);

        $this->syncOwnershipMembers($business, $members, true);

        return redirect()->route('businesses.hub')
            ->with('status', __('Business updated successfully.'));
    }

    public function destroy(Request $request, Business $business): RedirectResponse
    {
        if ((int) $business->user_id !== (int) $request->user()->id) {
            abort(404, 'This business does not belong to your account.');
        }

        $business->delete();

        return redirect()->route('businesses.hub')
            ->with('status', __('Business removed.'));
    }

    public function downloadDocument(Request $request, Business $business, string $type, string $filename): StreamedResponse
    {
        if ((int) $business->user_id !== (int) $request->user()->id) {
            abort(404);
        }

        if (! in_array($type, Business::SUPPORTING_DOCUMENTS, true)) {
            abort(404);
        }

        $uploads = $business->supporting_document_files ?? [];
        $path = $uploads[$type] ?? null;
        if (! $path || basename($path) !== $filename || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path, $filename);
    }

    private function storeBusinessDocumentUploads(Request $request, Business $business): void
    {
        if (! $request->hasFile('document_uploads')) {
            return;
        }

        $uploads = $business->supporting_document_files ?? [];
        $baseDir = 'businesses/'.$business->id.'/documents';
        $changed = false;

        foreach ($request->file('document_uploads') as $type => $file) {
            if (! in_array($type, Business::SUPPORTING_DOCUMENTS, true)) {
                continue;
            }
            if (! $file || ! $file->isValid()) {
                continue;
            }
            if (! empty($uploads[$type])) {
                Storage::disk('local')->delete($uploads[$type]);
            }
            $uploads[$type] = $file->store($baseDir.'/'.$type, 'local');
            $changed = true;
        }

        if ($changed) {
            $business->update(['supporting_document_files' => $uploads]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistOnboardingBusiness(User $user, array $validated, ?Business $existingOwned): Business
    {
        if ($existingOwned !== null) {
            $existingOwned->update($validated);

            return $existingOwned->fresh();
        }

        try {
            return $user->businesses()->create($validated);
        } catch (QueryException $exception) {
            if (! $this->isDuplicateBusinessNameException($exception)) {
                throw $exception;
            }

            RemovesLegacyBusinessNameUniqueIndexes::remove();

            return $user->businesses()->create($validated);
        }
    }

    private function findOwnedBusinessByName(User $user, string $businessName): ?Business
    {
        $normalized = Business::normalizeDisplayName($businessName);
        if ($normalized === '') {
            return null;
        }

        foreach ($user->businesses()->get() as $business) {
            $storedNormalized = $business->business_name_normalized !== null
                ? (string) $business->business_name_normalized
                : Business::normalizeDisplayName((string) $business->business_name);

            if ($storedNormalized === $normalized) {
                return $business;
            }
        }

        return null;
    }

    private function isDuplicateBusinessNameException(QueryException $exception): bool
    {
        $errorMessage = Str::lower($exception->getMessage());

        return str_contains($errorMessage, 'businesses_business_name_unique')
            || str_contains($errorMessage, 'businesses_business_name_normalized_unique')
            || str_contains($errorMessage, 'businesses.business_name')
            || (str_contains($errorMessage, 'duplicate entry') && str_contains($errorMessage, 'business_name'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $members
     */
    private function syncOwnershipMembers(Business $business, array $members, bool $replaceExisting): void
    {
        if ($replaceExisting) {
            $business->ownershipMembers()->delete();
        }

        foreach (array_values($members) as $i => $m) {
            $firstName = trim((string) ($m['first_name'] ?? ''));
            $lastName = trim((string) ($m['last_name'] ?? ''));
            if ($firstName !== '' || $lastName !== '') {
                $business->ownershipMembers()->create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $m['date_of_birth'] ?? null,
                    'gender' => $m['gender'] ?? null,
                    'pwd_status' => $m['pwd_status'] ?? null,
                    'phone' => $m['phone'] ?? null,
                    'email' => $m['email'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }
    }

    /**
     * Convert duplicate-key DB exceptions into user-friendly validation messages.
     */
    private function throwBusinessValidationFromQueryException(QueryException $exception): never
    {
        $errorMessage = Str::lower($exception->getMessage());

        if ($this->isDuplicateBusinessNameException($exception)
            || str_contains($errorMessage, 'business_name_normalized')) {
            throw ValidationException::withMessages([
                'business_name' => [__('We could not save this business name due to a database constraint. Please try again, or contact support if the problem continues.')],
            ]);
        }

        if (str_contains($errorMessage, 'businesses_registration_number_unique')
            || str_contains($errorMessage, 'businesses.registration_number')) {
            throw ValidationException::withMessages([
                'registration_number' => [__('This registration number is already in use.')],
            ]);
        }

        throw $exception;
    }
}
