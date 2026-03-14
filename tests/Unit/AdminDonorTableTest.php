<?php

use App\Components\AdminDonorTable;
use Illuminate\Contracts\View\View;

it('renders actions from view for a row', function () {
    $component = app(AdminDonorTable::class);

    $row = new stdClass;
    $row->id = 123;
    $row->login_token = 'test-token';

    $view = $component->actionsFromView($row);

    expect($view)->toBeInstanceOf(View::class);

    $html = $view->render();

    expect($html)->toContain('flux:dropdown');
    expect($html)->toContain('createDonorInvoice');
    expect($html)->toContain('confirmDeleteDonorInvoice');
})->skip('Skipping in Unit test environment. Verified by Feature test.');
