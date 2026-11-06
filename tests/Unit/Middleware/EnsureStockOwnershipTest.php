<?php

namespace Tests\Unit\Middleware;

use App\Features\Stock\Models\Stock;
use App\Http\Middleware\EnsureStockOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureStockOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function dispatch(?User $user, ?Stock $stock)
    {
        $request = Request::create('/stocks/1');
        $request->setUserResolver(fn () => $user);

        $route = new Route(['GET'], '/stocks/{stock}', fn () => null);
        $route->bind($request);
        if ($stock) {
            $route->setParameter('stock', $stock);
        }
        $request->setRouteResolver(fn () => $route);

        return (new EnsureStockOwnership)->handle($request, fn () => new Response('ok'));
    }

    public function test_owner_passes(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id]);

        $this->assertSame('ok', $this->dispatch($user, $stock)->getContent());
    }

    public function test_route_without_stock_parameter_passes(): void
    {
        $this->assertSame('ok', $this->dispatch(User::factory()->create(), null)->getContent());
    }

    public function test_non_owner_gets_403(): void
    {
        $this->expectException(HttpException::class);

        $this->dispatch(User::factory()->create(), Stock::factory()->create());
    }

    public function test_guest_gets_403(): void
    {
        $this->expectException(HttpException::class);

        $this->dispatch(null, Stock::factory()->create());
    }
}
