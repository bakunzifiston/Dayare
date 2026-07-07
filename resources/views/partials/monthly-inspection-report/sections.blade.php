{{-- Sections 1–6 — layout aligned with PDF --}}

<section class="rica-section">
    <h2 class="rica-section-title">
        <span class="rica-section-num">1.</span> {{ __('PRIVATE MEAT INSPECTOR DETAILS') }}
        <span class="rica-section-title-rw">/ {{ __('AMAKURU Y\'UMUGENZUZI W\'INYAMA WIGENGA') }}</span>
    </h2>
    @if ($inspector)
        <table class="rica-field-table">
            <tr>
                <td class="field-label">{{ __('Names') }} / {{ __('Amazina') }}</td>
                <td class="field-value" colspan="3">{{ $inspector['name'] }}</td>
            </tr>
            <tr>
                <td class="field-label">{{ __('Email') }} / {{ __('Imeli') }}</td>
                <td class="field-value">{{ $inspector['email'] ?: '—' }}</td>
                <td class="field-label">{{ __('Phone') }} / {{ __('Telefoni') }}</td>
                <td class="field-value">{{ $inspector['phone'] ?: '—' }}</td>
            </tr>
            <tr>
                <td class="field-label">{{ __('Authorization No.') }}</td>
                <td class="field-value">{{ $inspector['authorization_number'] ?: '—' }}</td>
                <td class="field-label">{{ __('Issue date') }} / {{ __('Igihe yatangiwe') }}</td>
                <td class="field-value">{{ $fmtDate($inspector['authorization_issue_date']) }}</td>
            </tr>
        </table>
        @if (count($report['inspectors']) > 1)
            <p class="rica-note">{{ __('Also active: :names', ['names' => collect($report['inspectors'])->skip(1)->pluck('name')->implode(', ')]) }}</p>
        @endif
    @else
        <p class="rica-empty">{{ __('No inspector activity recorded for this period.') }}</p>
    @endif
</section>

<section class="rica-section">
    <h2 class="rica-section-title">
        <span class="rica-section-num">2.</span> {{ __('SLAUGHTERHOUSE DETAILS') }}
        <span class="rica-section-title-rw">/ {{ __('AMAKURU Y\'IBAGIRO') }}</span>
    </h2>
    <table class="rica-field-table">
        <tr>
            <td class="field-label">{{ __('Names') }} / {{ __('Amazina') }}</td>
            <td class="field-value" colspan="3">{{ $sh['name'] }}</td>
        </tr>
        <tr>
            <td class="field-label">{{ __('Operator') }}</td>
            <td class="field-value" colspan="3">{{ $sh['operator_name'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="field-label">{{ __('Registration No.') }}</td>
            <td class="field-value">{{ $sh['registration_number'] ?: '—' }}</td>
            <td class="field-label">{{ __('License No.') }}</td>
            <td class="field-value">{{ $sh['license_number'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="field-label">{{ __('License issue date') }}</td>
            <td class="field-value">{{ $fmtDate($sh['license_issue_date']) }}</td>
            <td class="field-label">{{ __('Phone') }} / {{ __('Telefoni') }}</td>
            <td class="field-value">{{ $sh['phone'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="field-label">{{ __('Email') }} / {{ __('Imeli') }}</td>
            <td class="field-value" colspan="3">{{ $sh['email'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="field-label">{{ __('District') }} / {{ __('Akarere') }}</td>
            <td class="field-value">{{ $sh['district'] ?: '—' }}</td>
            <td class="field-label">{{ __('Sector') }} / {{ __('Umurenge') }}</td>
            <td class="field-value">{{ $sh['sector'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="field-label">{{ __('Cell') }} / {{ __('Akagari') }}</td>
            <td class="field-value">{{ $sh['cell'] ?: '—' }}</td>
            <td class="field-label">{{ __('Village') }} / {{ __('Umudugudu') }}</td>
            <td class="field-value">{{ $sh['village'] ?: '—' }}</td>
        </tr>
    </table>
</section>

<section class="rica-section">
    <h2 class="rica-section-title">
        <span class="rica-section-num">3.</span> {{ __('RECEIVED ANIMALS DETAILS') }}
        <span class="rica-section-title-rw">/ {{ __('AMAKURU Y\'AMATUNGO YAKIRIWE') }}</span>
    </h2>
    <div class="rica-table-wrap">
        <table class="rica-table">
            <thead>
                <tr>
                    <th>{{ __('Origin') }} / {{ __('Inkomoko') }}</th>
                    <th>{{ __('Species') }} / {{ __('Ubwoko') }}</th>
                    <th class="text-center">{{ __('Male') }} / {{ __('Gabo') }}</th>
                    <th class="text-center">{{ __('Female') }} / {{ __('Gore') }}</th>
                    <th>{{ __('Comment') }} / {{ __('Icyongerwaho') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['received_animals']['rows'] as $row)
                    <tr>
                        <td>{{ $row['origin'] }}</td>
                        <td>{{ $row['species'] }}</td>
                        <td class="text-center tabular-nums">{{ $row['male'] }}</td>
                        <td class="text-center tabular-nums">{{ $row['female'] }}</td>
                        <td>{{ $row['comment'] ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="rica-empty">{{ __('No animals received in this period.') }}</td></tr>
                @endforelse
            </tbody>
            @if (count($report['received_animals']['totals_by_species']) > 0)
                <tfoot>
                    <tr>
                        <td colspan="5"><strong>{{ __('Total received animal per sex') }} / {{ __('Igiteranyo kuri buri gitsina') }}</strong></td>
                    </tr>
                    @foreach ($report['received_animals']['totals_by_species'] as $total)
                        <tr>
                            <td></td>
                            <td>{{ $total['species'] }}</td>
                            <td class="text-center tabular-nums">{{ $total['male'] }}</td>
                            <td class="text-center tabular-nums">{{ $total['female'] }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                </tfoot>
            @endif
        </table>
    </div>
</section>

<section class="rica-section">
    <h2 class="rica-section-title">
        <span class="rica-section-num">4.</span> {{ __('ANTE-MORTEM INSPECTION DETAILS') }}
        <span class="rica-section-title-rw">/ {{ __('AMAKURU Y\'IGENZURA RYA MBERE YO KUBAGA') }}</span>
    </h2>
    <div class="rica-table-wrap">
        <table class="rica-table">
            <thead>
                <tr>
                    <th>{{ __('Species') }} / {{ __('Ubwoko') }}</th>
                    <th class="text-center">{{ __('No. of healthy animals') }}</th>
                    <th class="text-center">{{ __('Number') }} / {{ __('Umubare') }}</th>
                    <th>{{ __('Conditions') }} / {{ __('Imiterere') }}</th>
                    <th>{{ __('Action taken') }} / {{ __('Igikorwa cyakozwe') }}</th>
                    <th>{{ __('Final action') }} / {{ __('Icyemezo') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['ante_mortem']['rows'] as $row)
                    @if ($row['unhealthy_count'] === 0)
                        <tr>
                            <td>{{ $row['species'] }}</td>
                            <td class="text-center tabular-nums">{{ $row['healthy'] }}</td>
                            <td class="text-center tabular-nums">0</td>
                            <td class="rica-muted">{{ __('None recorded') }}</td>
                            <td class="rica-muted">{{ __('None recorded') }}</td>
                            <td class="rica-muted">{{ __('None recorded') }}</td>
                        </tr>
                    @else
                        @foreach ($row['unhealthy'] as $unhealthy)
                            <tr>
                                @if ($loop->first)
                                    <td rowspan="{{ $row['unhealthy_count'] }}">{{ $row['species'] }}</td>
                                    <td rowspan="{{ $row['unhealthy_count'] }}" class="text-center tabular-nums align-top">{{ $row['healthy'] }}</td>
                                @endif
                                <td class="text-center tabular-nums">{{ $unhealthy['number'] }}</td>
                                <td>{{ $unhealthy['conditions'] }}</td>
                                <td>{{ $unhealthy['action_taken'] }}</td>
                                <td>{{ $unhealthy['final_action'] }}</td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr><td colspan="6" class="rica-empty">{{ __('No ante-mortem inspections in this period.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="rica-section">
    <h2 class="rica-section-title">
        <span class="rica-section-num">5.</span> {{ __('POST-MORTEM INSPECTION DETAILS') }}
        <span class="rica-section-title-rw">/ {{ __('AMAKURU Y\'IGENZURA RYA NYUMA YO KUBAGA') }}</span>
    </h2>
    <div class="rica-table-wrap">
        <table class="rica-table">
            <thead>
                <tr>
                    <th>{{ __('Species') }} / {{ __('Ubwoko') }}</th>
                    <th class="text-center">{{ __('No. of approved carcasses') }}</th>
                    <th class="text-center">{{ __('Number') }} / {{ __('Umubare') }}</th>
                    <th>{{ __('Seized part / organ') }} / {{ __('Igice cyafashwe') }}</th>
                    <th>{{ __('Reason') }} / {{ __('Impamvu') }}</th>
                    <th class="text-right">{{ __('Qty (kg)') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['post_mortem']['rows'] as $row)
                    @if ($row['condemned_count'] === 0)
                        <tr>
                            <td>{{ $row['species'] }}</td>
                            <td class="text-center tabular-nums">{{ $row['approved'] }}</td>
                            <td class="text-center tabular-nums">0</td>
                            <td class="rica-muted">{{ __('None recorded') }}</td>
                            <td class="rica-muted">{{ __('None recorded') }}</td>
                            <td class="text-right tabular-nums rica-muted">0.00</td>
                        </tr>
                    @else
                        @foreach ($row['condemned'] as $condemned)
                            <tr>
                                @if ($loop->first)
                                    <td rowspan="{{ $row['condemned_count'] }}">{{ $row['species'] }}</td>
                                    <td rowspan="{{ $row['condemned_count'] }}" class="text-center tabular-nums align-top">{{ $row['approved'] }}</td>
                                @endif
                                <td class="text-center tabular-nums">{{ $condemned['number'] }}</td>
                                <td>{{ $condemned['seized_part'] }}</td>
                                <td>{{ $condemned['reason'] }}</td>
                                <td class="text-right tabular-nums">{{ number_format($condemned['qty_kg'], 2) }}</td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr><td colspan="6" class="rica-empty">{{ __('No post-mortem inspections in this period.') }}</td></tr>
                @endforelse
            </tbody>
            @if (count($report['post_mortem']['totals_by_species']) > 0)
                <tfoot>
                    <tr>
                        <td colspan="6"><strong>{{ __('Total of rejected meat per species') }}</strong></td>
                    </tr>
                    @foreach ($report['post_mortem']['totals_by_species'] as $total)
                        <tr>
                            <td>{{ $total['species'] }}</td>
                            <td colspan="4"></td>
                            <td class="text-right tabular-nums">{{ number_format($total['qty_kg'], 2) }}</td>
                        </tr>
                    @endforeach
                </tfoot>
            @endif
        </table>
    </div>
</section>

<section class="rica-section">
    <h2 class="rica-section-title">
        <span class="rica-section-num">6.</span> {{ __('MEAT SUPPLY DETAILS') }}
        <span class="rica-section-title-rw">/ {{ __('AMAKURU AJYANYE NO KUGEMURA INYAMA') }}</span>
    </h2>
    @if ($report['meat_supply']['certificate_serial_range']['start'])
        <p class="rica-cert-note">
            <strong>{{ __('Certificates') }}:</strong>
            {{ $report['meat_supply']['certificate_serial_range']['start'] }}
            @if ($report['meat_supply']['certificate_serial_range']['end'] && $report['meat_supply']['certificate_serial_range']['end'] !== $report['meat_supply']['certificate_serial_range']['start'])
                – {{ $report['meat_supply']['certificate_serial_range']['end'] }}
            @endif
        </p>
    @endif
    <div class="rica-table-wrap">
        <table class="rica-table">
            <thead>
                <tr>
                    <th>{{ __('Species') }}</th>
                    <th class="text-right">{{ __('Qty (kg)') }}</th>
                    <th>{{ __('Certificate No.') }}</th>
                    <th>{{ __('District') }}</th>
                    <th>{{ __('Sector') }}</th>
                    <th>{{ __('Other') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['meat_supply']['rows'] as $row)
                    <tr>
                        <td>{{ $row['species'] }}</td>
                        <td class="text-right tabular-nums">{{ number_format($row['qty_kg'], 2) }}</td>
                        <td>{{ $row['certificate_number'] }}</td>
                        <td>{{ $row['destination_district'] ?: '—' }}</td>
                        <td>{{ $row['destination_sector'] ?: '—' }}</td>
                        <td>{{ $row['destination_other'] ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="rica-empty">{{ __('No certificates issued in this period.') }}</td></tr>
                @endforelse
            </tbody>
            @if (count($report['meat_supply']['totals_by_species']) > 0)
                <tfoot>
                    <tr>
                        <td colspan="6"><strong>{{ __('Total delivered meat per species') }}</strong></td>
                    </tr>
                    @foreach ($report['meat_supply']['totals_by_species'] as $total)
                        <tr>
                            <td>{{ $total['species'] }}</td>
                            <td class="text-right tabular-nums">{{ number_format($total['qty_kg'], 2) }}</td>
                            <td colspan="4"></td>
                        </tr>
                    @endforeach
                </tfoot>
            @endif
        </table>
    </div>
</section>
