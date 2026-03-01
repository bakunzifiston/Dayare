<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb] focus:bg-[#2563eb] active:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-[#3B82F6] focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
