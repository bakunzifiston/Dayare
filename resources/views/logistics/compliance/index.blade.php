@component('layouts.logistics', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'selectedCompanyId' => $selectedCompanyId,
])
    <div class="space-y-4">
        <form method="GET" action="{{ route('logistics.compliance.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Company') }}</label>
            <div class="flex gap-2">
                <select name="company_id" class="w-full rounded-md border-slate-300 text-sm">
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) $selectedCompanyId === (int) $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-[#334155] px-3 py-2 text-xs font-semibold text-white hover:bg-[#1e293b]">{{ __('Load') }}</button>
            </div>
        </form>

        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Add compliance document') }}</h2>
            @if ($trips->isEmpty())
                <p class="text-sm text-slate-500">{{ __('No trips available for compliance yet. Plan a trip first.') }}</p>
            @else
                <form method="POST" action="{{ route('logistics.compliance.store', (int) $trips->first()->id) }}" class="grid gap-2 md:grid-cols-4">
                    @csrf
                    <select name="trip_id" onchange="this.form.action='{{ url('/logistics/compliance') }}/'+this.value" class="rounded-md border-slate-300 text-sm" required>
                        <option value="">{{ __('Trip') }}</option>
                        @foreach ($trips as $trip)
                            <option value="{{ $trip->id }}">#{{ $trip->id }}</option>
                        @endforeach
                    </select>
                    <select name="type" class="rounded-md border-slate-300 text-sm" required>
                        <option value="health_certificate">health_certificate</option>
                        <option value="movement_permit">movement_permit</option>
                    </select>
                    <select name="status" class="rounded-md border-slate-300 text-sm" required>
                        <option value="valid">valid</option>
                        <option value="pending">pending</option>
                        <option value="expired">expired</option>
                    </select>
                    <input type="number" min="0" name="reference_id" class="rounded-md border-slate-300 text-sm" placeholder="{{ __('Reference ID') }}">
                    <div class="md:col-span-4">
                        <button class="rounded-md bg-[#7A1C22] px-4 py-2 text-sm font-semibold text-white hover:bg-[#64161c]">{{ __('Save document') }}</button>
                    </div>
                </form>
            @endif
        </section>

        <x-logistics.table
            :columns="[__('Trip'), __('Type'), __('Reference'), __('Status')]"
            :has-rows="$complianceDocuments->isNotEmpty()"
            :empty-message="__('No compliance documents found')"
        >
            @foreach ($complianceDocuments as $document)
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-900">#{{ $document->trip_id }}</td>
                    <td class="px-4 py-3">{{ $document->type }}</td>
                    <td class="px-4 py-3">{{ $document->reference_id ?: '-' }}</td>
                    <td class="px-4 py-3"><x-logistics.status-badge :status="$document->status" /></td>
                </tr>
            @endforeach
        </x-logistics.table>
    </div>
@endcomponent
