<?php

use function Pest\Laravel\get;

it('admin association members page is removed', function () {
    get('/admin/mitglieder')->assertNotFound();
});
