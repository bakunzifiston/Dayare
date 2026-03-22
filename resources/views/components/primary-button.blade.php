<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-bucha font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy focus:bg-bucha-burgundy active:bg-bucha-burgundy focus:outline-none focus:ring-2 focus:ring-bucha-primary focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
