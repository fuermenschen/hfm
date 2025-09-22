<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <flux:card>
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm">Sportler:innen</div>
                <div class="mt-1 text-2xl font-semibold">{{ number_format($totals['athletes'], 0, '.', "'") }}</div>
            </div>
            <flux:icon name="users" class="size-8" />
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm">Spender:innen</div>
                <div class="mt-1 text-2xl font-semibold">{{ number_format($totals['donors'], 0, '.', "'") }}</div>
            </div>
            <flux:icon name="heart" class="size-8" />
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm">Absolvierte Runden</div>
                <div class="mt-1 text-2xl font-semibold">{{ number_format($totals['rounds'], 0, '.', "'") }}</div>
            </div>
            <flux:icon name="flag" class="size-8" />
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm">Höhenmeter überwunden</div>
                <div class="mt-1 text-2xl font-semibold">{{ number_format($totals['elevation_m'], 0, '.', "'") }} m</div>
            </div>
            <flux:icon name="fire" class="size-8" />
        </div>
    </flux:card>

    <div class="sm:col-span-2">
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm">Total Spenden</div>
                    <div class="mt-1 text-4xl font-semibold tracking-tight">Fr. {{ number_format($totals['donations_total'], 2, '.', "'") }}</div>
                </div>
                <flux:icon name="banknotes" class="size-10" />
            </div>

            <div class="mt-6">
                <div class="mb-3 text-sm font-medium">Spenden pro Benefizpartner:in</div>
                <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($totals['per_partner'] as $partnerName => $amount)
                        <li class="flex items-center justify-between py-2">
                            <span>{{ $partnerName }}</span>
                            <span class="font-medium">Fr. {{ number_format($amount, 2, '.', "'") }}</span>
                        </li>
                    @empty
                        <li class="py-4 text-center text-sm">Noch keine Spenden erfasst.</li>
                    @endforelse
                </ul>
            </div>
        </flux:card>
    </div>
</div>
