<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('processor.supply-requests.index') }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Supply requests') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ __('Discover livestock and request supply') }}</h2>
    </x-slot>

    <div class="max-w-6xl space-y-5">
        <div class="grid md:grid-cols-2 gap-4 bg-white rounded-bucha border border-slate-200/60 p-4">
            <div>
                <x-input-label for="processor_business_selector" :value="__('Your processor business')" />
                <select id="processor_business_selector" class="mt-1 block w-full rounded-lg border-gray-300" onchange="window.location='{{ route('processor.supply-requests.create') }}?processor_business_id='+this.value;">
                    @foreach ($processorBusinesses as $b)
                        <option value="{{ $b->id }}" @selected((string) request('processor_business_id', old('processor_business_id', $processorBusinesses->first()?->id)) === (string) $b->id)>{{ $b->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label :value="__('Livestock availability rule')" />
                <p class="mt-1 text-sm text-slate-600">{{ __('Only livestock with healthy_quantity - reserved_quantity greater than zero is shown.') }}</p>
            </div>
        </div>

        @if ($discoveries->isEmpty())
            <div class="rounded-bucha border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('No livestock is currently available for supply requests.') }}
            </div>
        @endif

        <div class="grid lg:grid-cols-2 gap-4">
            @foreach ($discoveries as $item)
                @php
                    /** @var \App\Models\Livestock $row */
                    $row = $item['livestock'];
                    $canRequest = $item['available_quantity'] > 0 && $item['has_valid_certification'];
                @endphp
                <article class="rounded-bucha border border-slate-200/70 bg-white p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ \App\Support\FarmerAnimalType::label($row->type) }}</h3>
                            <p class="text-sm text-slate-600">{{ $row->breed ?: __('No breed set') }}</p>
                            <p class="text-xs text-slate-500">{{ $row->farm?->business?->business_name }} · {{ $row->farm?->name }}</p>
                        </div>
                        <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $item['has_valid_certification'] ? 'bg-emerald-100 text-emerald-900' : 'bg-red-100 text-red-900' }}">
                            {{ $item['has_valid_certification'] ? __('Certified') : __('No valid certificate') }}
                        </span>
                    </div>

                    <dl class="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
                        <dt class="text-slate-500">{{ __('Available quantity') }}</dt>
                        <dd class="text-right font-medium text-slate-900">{{ $item['available_quantity'] }}</dd>
                        <dt class="text-slate-500">{{ __('Reserved quantity') }}</dt>
                        <dd class="text-right text-slate-700">{{ $item['reserved_quantity'] }}</dd>
                        <dt class="text-slate-500">{{ __('Average weight') }}</dt>
                        <dd class="text-right text-slate-700">{{ $item['average_weight'] !== null ? number_format((float) $item['average_weight'], 2).' kg' : '—' }}</dd>
                        <dt class="text-slate-500">{{ __('Price') }}</dt>
                        <dd class="text-right text-slate-700">{{ $row->base_price !== null ? number_format((float) $row->base_price, 2) : '—' }}</dd>
                        <dt class="text-slate-500">{{ __('Farm location') }}</dt>
                        <dd class="text-right text-slate-700">{{ $item['location'] !== '' ? $item['location'] : '—' }}</dd>
                        <dt class="text-slate-500">{{ __('Health status') }}</dt>
                        <dd class="text-right capitalize text-slate-700">{{ str_replace('_', ' ', $item['health_status']) }}</dd>
                    </dl>

                    <div class="pt-2 border-t border-slate-100">
                        @if (! $canRequest)
                            <button type="button" disabled class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-bucha bg-slate-200 text-slate-500 cursor-not-allowed">
                                {{ __('Request supply') }}
                            </button>
                            <p class="mt-2 text-xs text-red-700">{{ __('Request disabled: missing valid certification or no available stock.') }}</p>
                        @else
                            <a href="{{ route('processor.supply-requests.create', ['livestock_id' => $row->id, 'processor_business_id' => request('processor_business_id', old('processor_business_id', $processorBusinesses->first()?->id))]) }}#request-panel" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-bucha bg-bucha-primary text-white">
                                {{ __('Request supply') }}
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if ($selectedDiscovery)
            @php
                /** @var \App\Models\Livestock $selectedRow */
                $selectedRow = $selectedDiscovery['livestock'];
                $defaultProcessorId = old('processor_business_id', request('processor_business_id', $processorBusinesses->first()?->id));
            @endphp
            <section id="request-panel" class="bg-white rounded-bucha border border-slate-200/70 p-6 space-y-4">
                <h3 class="text-lg font-semibold text-slate-900">{{ __('Request supply from selected livestock') }}</h3>

                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div class="rounded border border-slate-200 p-3">
                        <p class="font-medium text-slate-800">{{ __('Livestock details') }}</p>
                        <p class="mt-2"><span class="text-slate-500">{{ __('Type') }}:</span> {{ \App\Support\FarmerAnimalType::label($selectedRow->type) }}</p>
                        <p><span class="text-slate-500">{{ __('Breed') }}:</span> {{ $selectedRow->breed ?: '—' }}</p>
                        <p><span class="text-slate-500">{{ __('Available') }}:</span> {{ $selectedDiscovery['available_quantity'] }}</p>
                        <p><span class="text-slate-500">{{ __('Average weight') }}:</span> {{ $selectedDiscovery['average_weight'] !== null ? number_format((float) $selectedDiscovery['average_weight'], 2).' kg' : '—' }}</p>
                        <p><span class="text-slate-500">{{ __('Health status') }}:</span> <span class="capitalize">{{ str_replace('_', ' ', $selectedDiscovery['health_status']) }}</span></p>
                        <p><span class="text-slate-500">{{ __('Base price') }}:</span> {{ $selectedRow->base_price !== null ? number_format((float) $selectedRow->base_price, 2) : '—' }}</p>
                    </div>
                    <div class="rounded border border-slate-200 p-3">
                        <p class="font-medium text-slate-800">{{ __('Farm and certification details') }}</p>
                        <p class="mt-2"><span class="text-slate-500">{{ __('Farmer') }}:</span> {{ $selectedRow->farm?->business?->business_name }}</p>
                        <p><span class="text-slate-500">{{ __('Farm') }}:</span> {{ $selectedRow->farm?->name }}</p>
                        <p><span class="text-slate-500">{{ __('Location') }}:</span> {{ $selectedDiscovery['location'] !== '' ? $selectedDiscovery['location'] : '—' }}</p>
                        <p class="mt-2 text-slate-500">{{ __('Valid certifications') }}:</p>
                        @if (collect($selectedDiscovery['certifications'])->isEmpty())
                            <p class="text-red-700">{{ __('No valid certification') }}</p>
                        @else
                            <ul class="list-disc list-inside text-slate-700">
                                @foreach ($selectedDiscovery['certifications'] as $cert)
                                    <li>{{ $cert->certificate_number }} — {{ ucfirst(str_replace('_', ' ', $cert->certificate_type)) }} @if ($cert->expiry_date) ({{ __('valid until') }} {{ $cert->expiry_date?->toDateString() }}) @endif</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                <x-input-error :messages="$errors->get('processor_business_id')" class="mt-0" />
                <x-input-error :messages="$errors->get('destination_facility_id')" class="mt-0" />
                <x-input-error :messages="$errors->get('requested_livestock_id')" class="mt-0" />
                <x-input-error :messages="$errors->get('quantity_requested')" class="mt-0" />
                <x-input-error :messages="$errors->get('required_certification_type')" class="mt-0" />

                <form method="post" action="{{ route('processor.supply-requests.store') }}" class="grid md:grid-cols-2 gap-4">
                    @csrf
                    <input type="hidden" name="requested_livestock_id" value="{{ $selectedRow->id }}">
                    <div>
                        <x-input-label for="processor_business_id" :value="__('Your processor business')" />
                        <select name="processor_business_id" id="processor_business_id" required class="mt-1 block w-full rounded-lg border-gray-300">
                            @foreach ($processorBusinesses as $b)
                                <option value="{{ $b->id }}" @selected((string) $defaultProcessorId === (string) $b->id)>{{ $b->business_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="destination_facility_id" :value="__('Receiving facility')" />
                        <select name="destination_facility_id" id="destination_facility_id" required class="mt-1 block w-full rounded-lg border-gray-300">
                            @foreach ($facilities as $fac)
                                <option value="{{ $fac->id }}" @selected((string) old('destination_facility_id') === (string) $fac->id)>{{ $fac->facility_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="quantity_requested" :value="__('Quantity')" />
                        <x-text-input id="quantity_requested" name="quantity_requested" type="number" min="1" :max="$selectedDiscovery['available_quantity']" class="mt-1 block w-full" :value="old('quantity_requested', 1)" required />
                    </div>
                    <div>
                        <x-input-label for="preferred_date" :value="__('Preferred date')" />
                        <x-text-input id="preferred_date" name="preferred_date" type="date" class="mt-1 block w-full" :value="old('preferred_date')" />
                    </div>
                    <div>
                        <x-input-label for="required_breed" :value="__('Breed requirement (optional)')" />
                        <x-text-input id="required_breed" name="required_breed" type="text" class="mt-1 block w-full" :value="old('required_breed', $selectedRow->breed)" />
                    </div>
                    <div>
                        <x-input-label for="required_weight" :value="__('Weight requirement (optional)')" />
                        <x-text-input id="required_weight" name="required_weight" type="text" class="mt-1 block w-full" :value="old('required_weight', $selectedRow->detail?->weight_range)" />
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 mt-1 text-sm text-slate-700">
                            <input type="checkbox" name="healthy_stock_required" value="1" @checked(old('healthy_stock_required', true)) class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary">
                            <span>{{ __('Require healthy stock') }}</span>
                        </label>
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 mt-1 text-sm text-slate-700">
                            <input type="checkbox" name="certification_required" value="1" @checked(old('certification_required', true)) class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary">
                            <span>{{ __('Require certification') }}</span>
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="required_certification_type" :value="__('Certification type (optional)')" />
                        <select name="required_certification_type" id="required_certification_type" class="mt-1 block w-full rounded-lg border-gray-300">
                            <option value="">{{ __('Any valid certificate') }}</option>
                            @foreach (\App\Models\FarmerHealthCertificate::TYPES as $type)
                                <option value="{{ $type }}" @selected(old('required_certification_type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">{{ __('Send request') }}</button>
                    </div>
                </form>
            </section>
        @endif
    </div>
</x-app-layout>
