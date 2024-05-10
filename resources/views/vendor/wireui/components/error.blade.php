@if(false)
    {{-- make sure the error text of the wireUI components is not rendered --}}
    @error($name)
    <p {{ $attributes->merge(['class' => 'mt-2 text-sm text-negative-600']) }}>
        {{ $message }}
    </p>
    @enderror
@endif
