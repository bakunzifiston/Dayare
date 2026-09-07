@php
    $workspaceType = $workspaceType ?? null;
    $tenantEnvironmentFilter = $tenantEnvironmentFilter ?? null;
@endphp

<form method="GET" action="{{ $action }}" class="hub-period-filter__toggles" aria-label="{{ __('Workspace type') }}">
    @if (filled($tenantEnvironmentFilter))
        <input type="hidden" name="tenant_environment" value="{{ $tenantEnvironmentFilter }}">
    @endif
    @foreach ([
        '' => __('All'),
        \App\Models\Business::TYPE_PROCESSOR => __('Processor'),
        \App\Models\Business::TYPE_BUTCHER => __('Butcher'),
        \App\Models\Business::TYPE_LOGISTICS => __('Logistics'),
    ] as $value => $label)
        <label class="hub-period-filter__toggle">
            <input type="radio"
                   name="{{ \App\Services\SuperAdmin\SuperAdminUserOverviewService::WORKSPACE_QUERY }}"
                   value="{{ $value }}"
                   @checked(($workspaceType ?? '') === $value)
                   onchange="this.form.submit()">
            <span>{{ $label }}</span>
        </label>
    @endforeach
</form>
