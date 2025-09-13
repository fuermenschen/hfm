<?php

use App\Components\AdminDonatorTable;
use Illuminate\Contracts\View\View;

it('renders actions from view for a row (feature)', function () {
    $component = app(AdminDonatorTable::class);

    $row = new stdClass;
    $row->id = 123;
    $row->login_token = 'test-token';

    $view = $component->actionsFromView($row);

    expect($view)->toBeInstanceOf(View::class);

    $html = $view->render();

    expect($html)->toContain('data-flux-dropdown');
    expect($html)->toContain('createDonorInvoice');
    expect($html)->toContain('confirmDeleteDonorInvoice');
});
