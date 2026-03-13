<div class="min-w-0 space-y-2">
    <div class="flex flex-wrap items-center gap-2">
        <div class="min-w-0 w-full sm:min-w-[22rem] sm:flex-1">
            {{ $topLeft ?? '' }}
        </div>

        <div class="w-full sm:ml-auto sm:w-auto">
            {{ $topRight ?? '' }}
        </div>
    </div>

    <div class="flex flex-wrap items-start gap-2">
        <div class="min-w-0 flex-1 basis-full lg:basis-auto">
            {{ $bottomLeft ?? '' }}
        </div>

        <div class="w-full xl:ml-auto xl:w-auto">
            {{ $bottomRight ?? '' }}
        </div>
    </div>
</div>
