<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\UiLockService;

class UiLockTest extends TestCase
{

    use RefreshDatabase;

    /** @test */
    public function 同じUIは二重ロックできない()
    {
        $service =  app(UiLockService::class);

        $service->lock('exam', 'terminal1');

        $this->expectException(\Exception::class);
        $service->lock('exam', 'terminal2');
    }

}
