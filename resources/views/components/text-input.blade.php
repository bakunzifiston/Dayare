@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary rounded-bucha shadow-sm']) }}>
