@php
    $attributes = $this->memberAttributes;
@endphp

<flux:tooltip content="{{ __('Insert Placeholders') }}" class="contents">
    <flux:dropdown class="">
        <flux:button icon-trailing="variable" variant="subtle" size="sm"></flux:button>
        <flux:menu>
            @foreach($attributes as $attribute)
                <flux:menu.item @click="insertPlaceholder('{{ $attribute }}')">[!{{ $attribute }}]</flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>
</flux:tooltip>

<script>
    function insertPlaceholder(name) {
        const editorEl = document.querySelector("ui-editor");
        if (!editorEl || !editorEl.editor) return;
        editorEl.editor.chain().focus().insertContent(`[!${name}]`).run();
    }
</script>

