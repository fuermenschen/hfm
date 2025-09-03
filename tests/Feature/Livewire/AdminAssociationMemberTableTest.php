<?php

it('admin association members page is removed', function () {
    $this->get('/admin/mitglieder')->assertNotFound();
});
