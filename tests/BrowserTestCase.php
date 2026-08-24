<?php

namespace Tests;

use Illuminate\Support\Facades\Vite;

abstract class BrowserTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withVite();
        Vite::useHotFile(storage_path('framework/testing-vite.hot'));
    }
}
