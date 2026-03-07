<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Species configuration') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">{{ __('Species list') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ __('Configure which animal species can be selected across modules.') }}
                        </p>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-6">
                    @if (session('status'))
                        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('species.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        @csrf
                        <div class="md:col-span-2">
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <x-input-label for="code" :value="__('Code')" />
                            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code')" required />
                            <p class="mt-1 text-xs text-gray-400">{{ __('Used internally (e.g. cattle, goat).') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('code')" />
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center">
                                <input id="is_active" name="is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" checked>
                                <label for="is_active" class="ml-2 text-sm text-gray-700">{{ __('Active') }}</label>
                            </div>
                            <x-primary-button class="ml-auto">
                                {{ __('Add species') }}
                            </x-primary-button>
                        </div>
                    </form>

                    <div class="border-t border-gray-100 pt-4">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">{{ __('Name') }}</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">{{ __('Code') }}</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">{{ __('Order') }}</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">{{ __('Status') }}</th>
                                    <th class="px-3 py-2 text-right font-semibold text-gray-700">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($species as $item)
                                    <tr>
                                        <td class="px-3 py-2">
                                            <form method="POST" action="{{ route('species.update', $item) }}" class="space-y-1 md:flex md:items-center md:space-y-0 md:space-x-2">
                                                @csrf
                                                @method('PUT')
                                                <x-text-input name="name" type="text" class="w-full md:w-40" :value="old('name_'.$item->id, $item->name)" />
                                        </td>
                                        <td class="px-3 py-2">
                                                <x-text-input name="code" type="text" class="w-full md:w-32" :value="old('code_'.$item->id, $item->code)" />
                                        </td>
                                        <td class="px-3 py-2">
                                                <x-text-input name="sort_order" type="number" min="0" class="w-20" :value="old('sort_order_'.$item->id, $item->sort_order)" />
                                        </td>
                                        <td class="px-3 py-2">
                                                <label class="inline-flex items-center text-xs text-gray-700">
                                                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked($item->is_active)>
                                                    <span class="ml-1">{{ __('Active') }}</span>
                                                </label>
                                            </form>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <form method="POST" action="{{ route('species.destroy', $item) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-600 hover:text-red-800" onclick="return confirm('{{ __('Delete this species?') }}')">
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500">
                                            {{ __('No species configured yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

