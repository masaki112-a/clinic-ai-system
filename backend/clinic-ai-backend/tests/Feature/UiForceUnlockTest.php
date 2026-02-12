<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\UiLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UiForceUnlockTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ロック中のUIは強制解除できる()
    {
        $service = new UiLockService();

        $service->lock('exam', 'terminal1');

        $service->forceUnlock('exam', 'admin_terminal', '端末フリーズ');

        $this->assertDatabaseMissing('ui_locks', [
            'ui_name' => 'exam',
        ]);
    }

    /** @test */
    public function ロックされていないUIは強制解除できない()
    {
        $this->expectException(\Exception::class);

        $service = new UiLockService();
        $service->forceUnlock('exam', 'admin_terminal', '誤操作');
    }

    /** @test */
    public function 理由なしでは強制解除できない()
    {
        $this->expectException(\Exception::class);

        $service = new UiLockService();

        $service->lock('exam', 'terminal1');

        $service->forceUnlock('exam', 'admin_terminal', '');
    }

    /** @test */
    public function 強制解除時にログが作成される()
    {
        $service = new UiLockService();

        $service->lock('exam', 'terminal1');
        $service->forceUnlock('exam', 'admin_terminal', 'ブラウザ強制終了');

        $this->assertDatabaseHas('ui_lock_logs', [
            'ui_name' => 'exam',
            'action'  => 'force_unlock',
        ]);
    }
}
