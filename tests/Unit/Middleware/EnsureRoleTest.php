<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureRoleTest extends TestCase
{
    use RefreshDatabase;

    private function dispatch(?User $user, string ...$roles)
    {
        $request = Request::create('/x');
        $request->setUserResolver(fn () => $user);

        return (new EnsureRole)->handle($request, fn () => new Response('ok'), ...$roles);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->assertSame(route('login'), $this->dispatch(null, 'member')->headers->get('Location'));
    }

    public function test_allowed_role_passes(): void
    {
        $this->assertSame('ok', $this->dispatch(User::factory()->create(), 'member')->getContent());
        $this->assertSame('ok', $this->dispatch(User::factory()->admin()->create(), 'admin', 'member')->getContent());
    }

    public function test_wrong_role_gets_403(): void
    {
        $this->expectException(HttpException::class);

        $this->dispatch(User::factory()->create(), 'admin');
    }
}
