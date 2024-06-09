<li>
    @if($route === 'logout')
        <form method="POST" action="{{ route($route) }}"
            @class([
              "group flex gap-x-3 rounded-md text-sm font-semibold leading-6",
              "text-slate-300 hover:text-slate-50" => !$current,
              "bg-slate-600 text-hfm-white hover:text-slate-50" => $current,
            ])>
            @csrf
            <button type="submit" class="flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6">
                <span class="h6 w-6 shrink-0">
                    {!! html_entity_decode($svg) !!}
                </span>
                {{$label}}
            </button>
        </form>

    @else
        <a href="{{ route($route) }}" wire:navigate.hover
            @class([
              "group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6",
              "text-slate-300 hover:text-slate-50" => !$current,
              "bg-slate-600 text-hfm-white hover:text-slate-50" => $current,
            ])>
        <span class="h6 w-6 shrink-0">
         {!! html_entity_decode($svg) !!}
        </span>
            {{$label}}
        </a>
    @endif
</li>
