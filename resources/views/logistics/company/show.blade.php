@php
    use App\Models\LogisticsCompany;

    $c = $logisticsCompany;
    $locationParts = array_filter([
        $c->country?->name,
        $c->province?->name,
        $c->district?->name,
        $c->sector?->name,
        $c->cell?->name,
        $c->village?->name,
    ]);
@endphp

@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    @slot('actions')
        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ route('logistics.company.index', ['company_id' => $c->id]) }}"
                class="rounded-md border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
                {{ __('Back to list') }}
            </a>
            <a
                href="{{ route('logistics.company.edit', $c) }}?company_id={{ $c->id }}"
                class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]"
            >
                {{ __('Edit') }}
            </a>
        </div>
    @endslot

    <div class="space-y-6">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Details') }}</h2>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Business workspace') }}</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $c->business?->business_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Company type') }}</dt>
                    <dd class="mt-0.5 text-slate-900">
                        @if ($c->company_type === LogisticsCompany::TYPE_SHARED_COMPANY)
                            {{ __('Shared Company') }}
                        @else
                            {{ __('Individual') }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Registration number') }}</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $c->registration_number }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Tax ID') }}</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $c->tax_id ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('License') }}</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $c->license_type }} · {{ optional($c->license_expiry_date)->toDateString() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Contact person') }}</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $c->contact_person ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Location') }}</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $locationParts !== [] ? implode(' · ', $locationParts) : '—' }}</dd>
                </div>
            </dl>
        </div>

        @if ($c->company_type === LogisticsCompany::TYPE_SHARED_COMPANY && $c->members->isNotEmpty())
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Members') }}</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-slate-500">{{ __('Name') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-slate-500">{{ __('Phone') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-slate-500">{{ __('Email') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($c->members as $member)
                                <tr>
                                    <td class="px-3 py-2 text-slate-900">{{ $member->first_name }} {{ $member->last_name }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $member->phone }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $member->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endcomponent
