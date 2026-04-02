@php
    $isSuperAdmin = Auth::user()?->isSuperAdmin();
    $user = Auth::user();

    $tenantNav = [
        ['label' => __('Dashboard'), 'route' => 'dashboard', 'icon' => 'dashboard', 'permission' => null],
        [
            'group' => __('Operations'),
            'icon' => 'box',
            'children' => [
                ['label' => __('Businesses'), 'route' => 'businesses.index', 'icon' => 'building', 'permission' => 'manage businesses'],
                ['label' => __('Inspectors'), 'route' => 'inspectors.index', 'icon' => 'user', 'permission' => 'manage inspectors'],
                ['label' => __('Animal intake'), 'route' => 'animal-intakes.index', 'icon' => 'intake', 'permission' => 'manage animal intakes'],
                ['label' => __('Slaughter planning'), 'route' => 'slaughter-plans.index', 'icon' => 'calendar', 'permission' => 'manage slaughter plans'],
                ['label' => __('Ante-mortem'), 'route' => 'ante-mortem-inspections.index', 'icon' => 'clipboard-list', 'permission' => 'manage ante-mortem'],
                ['label' => __('Slaughter execution'), 'route' => 'slaughter-executions.index', 'icon' => 'play', 'permission' => 'manage slaughter executions'],
                ['label' => __('Batches'), 'route' => 'batches.index', 'icon' => 'box', 'permission' => 'manage batches'],
                ['label' => __('Post-mortem'), 'route' => 'post-mortem-inspections.index', 'icon' => 'clipboard', 'permission' => 'manage post-mortem'],
                ['label' => __('Certificates'), 'route' => 'certificates.index', 'icon' => 'certificate', 'permission' => 'manage certificates'],
                ['label' => __('Cold Room'), 'route' => 'warehouse-storages.index', 'icon' => 'box', 'permission' => 'manage warehouse'],
                ['label' => __('Transport'), 'route' => 'transport-trips.index', 'icon' => 'truck', 'permission' => 'manage transport'],
                ['label' => __('Delivery confirmation'), 'route' => 'delivery-confirmations.index', 'icon' => 'check', 'permission' => 'manage delivery confirmations'],
                ['label' => __('Compliance'), 'route' => 'compliance.index', 'icon' => 'shield', 'permission' => 'view compliance'],
            ],
        ],
        [
            'group' => __('CRM & HR'),
            'icon' => 'user',
            'children' => [
                ['label' => __('CRM'), 'route' => 'crm.dashboard', 'icon' => 'dashboard', 'permission' => 'view crm'],
                ['label' => __('Employees'), 'route' => 'employees.index', 'icon' => 'user', 'permission' => 'manage employees'],
                ['label' => __('Suppliers'), 'route' => 'suppliers.index', 'icon' => 'building', 'permission' => 'manage suppliers'],
                ['label' => __('Contracts'), 'route' => 'contracts.index', 'icon' => 'clipboard', 'permission' => 'manage contracts'],
                ['label' => __('Clients'), 'route' => 'clients.index', 'icon' => 'user', 'permission' => 'manage clients'],
                ['label' => __('Demand'), 'route' => 'demands.index', 'icon' => 'clipboard-list', 'permission' => 'manage demands'],
            ],
        ],
    ];
    $tenantNav[] = ['label' => __('Users'), 'route' => 'tenant-users.index', 'icon' => 'users', 'permission' => 'manage tenant users'];
    $tenantNav[] = ['label' => __('Settings'), 'route' => 'settings.edit', 'icon' => 'settings', 'permission' => 'manage settings'];

    if (! $isSuperAdmin && $user && ! $user->canManageTenantUsers()) {
        $filtered = [];
        foreach ($tenantNav as $item) {
            if (isset($item['group'])) {
                $children = array_values(array_filter($item['children'] ?? [], fn ($c) => empty($c['permission']) || $user->can($c['permission'])));
                if (count($children) > 0) {
                    $item['children'] = $children;
                    $filtered[] = $item;
                }
            } else {
                if (empty($item['permission']) || $user->can($item['permission'])) {
                    $filtered[] = $item;
                }
            }
        }
        $tenantNav = $filtered;
    }

    $navGroups = $isSuperAdmin
        ? [
            ['label' => __('Platform dashboard'), 'route' => 'super-admin.dashboard', 'icon' => 'shield'],
            ['label' => __('Settings'), 'route' => 'settings.edit', 'icon' => 'settings'],
        ]
        : $tenantNav;
@endphp
<aside
    id="sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-5 pb-4 flex flex-col text-white border-r border-black/20 transition-transform duration-200 ease-out -translate-x-full lg:translate-x-0 shadow-bucha-md"
    style="background: linear-gradient(180deg, #4a1016 0%, #2d0a0e 100%);"
    :class="{ 'translate-x-0': sidebarOpen }"
    aria-label="Sidebar"
>
    <div class="flex items-center justify-between px-5 mb-6 gap-2">
        <x-sidebar-brand
            :href="$isSuperAdmin ? route('super-admin.dashboard') : route('dashboard')"
            :show-admin-badge="$isSuperAdmin"
        />
        <button @click="sidebarOpen = false" type="button" class="lg:hidden p-2 rounded-bucha text-white/80 hover:text-white hover:bg-white/10">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 space-y-0.5">
        @foreach ($navGroups as $item)
            @if (isset($item['group']))
                @php
                    $childRoutes = array_column($item['children'], 'route');
                    $groupActive = false;
                    foreach ($childRoutes as $r) {
                        $pattern = str_replace('.index', '.*', $r);
                        if (request()->routeIs($r) || request()->routeIs($pattern)) { $groupActive = true; break; }
                    }
                @endphp
                <div class="mb-1" x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-bucha text-sm font-medium text-white/90 hover:bg-white/10 hover:text-white transition-colors">
                        @include('layouts.partials.sidebar-icon', ['icon' => $item['icon'] ?? ''])
                        <span class="flex-1 text-left">{{ $item['group'] }}</span>
                        <svg class="w-4 h-4 shrink-0 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-transition class="ml-2 mt-0.5 space-y-0.5 border-l border-white/20 pl-2">
                        @foreach ($item['children'] as $child)
                            @php $routePattern = str_replace('.index', '.*', $child['route']); $active = request()->routeIs($child['route']) || request()->routeIs($routePattern); @endphp
                            <a href="{{ route($child['route']) }}" class="flex items-center gap-2.5 px-2.5 py-2 rounded-bucha text-sm font-medium transition-colors {{ $active ? 'bg-bucha-primary/35 text-white shadow-inner ring-1 ring-white/10' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                                @include('layouts.partials.sidebar-icon', ['icon' => $child['icon'] ?? ''])
                                <span>{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                @php $routePattern = str_replace('.index', '.*', $item['route']); $active = request()->routeIs($item['route']) || request()->routeIs($routePattern); @endphp
                <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-bucha text-sm font-medium transition-colors {{ $active ? 'bg-bucha-primary/35 text-white border-l-2 border-red-200/90 shadow-inner' : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                    @include('layouts.partials.sidebar-icon', ['icon' => $item['icon'] ?? ''])
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>
    <div class="mt-auto px-3 pt-3 border-t border-white/10">
        <div class="flex items-center gap-3 px-3 py-2 rounded-lg text-white">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/15 text-sm font-semibold text-white ring-2 ring-red-200/25">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-white/70 truncate">{{ Auth::user()->email }}</p>
            </div>
        </div>
        <div class="mt-2 flex gap-1">
            <a href="{{ route('profile.edit') }}" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-bucha text-xs font-medium text-white/80 hover:bg-white/10 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ __('Profile') }}
            </a>
            <form method="POST" action="{{ route('logout') }}" class="flex-1">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-bucha text-xs font-medium text-white/80 hover:bg-white/10 hover:text-red-300 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</aside>
