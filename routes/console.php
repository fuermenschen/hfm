<?php

use App\Jobs\DispatchDonorInvoiceRefresh;
use App\Jobs\DispatchStoryImageWarming;
use App\Jobs\ExpireEventGroupMembershipRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new ExpireEventGroupMembershipRequests)->weeklyOn(0, '01:00')->timezone('UTC')
    ->when(fn (): bool => Cache::add('schedule:group-request-cleanup:'.now('UTC')->format('YmdHi'), true, 120));
Schedule::job(new DispatchStoryImageWarming)->dailyAt('01:00')->timezone('UTC')
    ->when(fn (): bool => Cache::add('schedule:story-image-warming:'.now('UTC')->format('YmdHi'), true, 120));
Schedule::job(new DispatchDonorInvoiceRefresh)->dailyAt('02:00')->timezone('UTC')
    ->when(fn (): bool => Cache::add('schedule:donor-invoice-refresh:'.now('UTC')->format('YmdHi'), true, 120));
