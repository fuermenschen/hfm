<a {{ $attributes->merge(['class' => 'text-hfm-red dark:text-hfm-lightred underline hover:text-hfm-light']) }}
    {{ $attributes->has('target') ? 'target=' . $attributes->get('target') : 'wire:navigate.hover' }}>
    {{ $slot }}
</a>
