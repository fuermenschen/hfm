<?php

it('shows external membership form and portal links on association page', function () {
    $response = $this->get(route('association'));

    $response->assertSuccessful();
    $response->assertSee('https://vfme.webling.ch/forms/memberform/d33043f696f66f3fed32', escape: false);
    $response->assertSee('https://vfme.webling.ch/portal/', escape: false);
});
