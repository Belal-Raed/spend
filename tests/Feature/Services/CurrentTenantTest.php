<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class CurrentTenantTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): CurrentTenant
    {
        return app(CurrentTenant::class);
    }

    public function test_the_signed_in_user_is_the_tenant(): void
    {
        $user = $this->signIn();

        $this->assertTrue($user->is($this->tenant()->getOrFail()));
    }

    public function test_there_is_no_tenant_without_a_signed_in_user(): void
    {
        $this->assertNull($this->tenant()->get());

        $this->expectException(LogicException::class);

        $this->tenant()->getOrFail();
    }

    public function test_run_as_makes_the_user_the_tenant_only_inside_the_callback(): void
    {
        $signedIn = $this->signIn();
        $other = User::factory()->create();

        $insideCallback = $this->tenant()->runAs($other, fn (): ?User => $this->tenant()->get());

        $this->assertTrue($other->is($insideCallback));
        $this->assertTrue($signedIn->is($this->tenant()->get()));
    }

    public function test_run_as_restores_the_previous_tenant_when_the_callback_throws(): void
    {
        $user = User::factory()->create();

        try {
            $this->tenant()->runAs($user, fn () => throw new RuntimeException);
        } catch (RuntimeException) {
        }

        $this->assertNull($this->tenant()->get());
    }
}
