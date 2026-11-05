<?php

namespace Tests\Unit\Middleware;

use App\Features\Stock\Models\Stock;
use App\Http\Middleware\EnsureStockOwnership;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureStockOwnershipMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function run(?User $user, mixed $stockParam)
    {
        $request = Request::create('/stocks/1');
        $request->setUserResolver(fn () => $user);
        $route = new Route('GET', '/stocks/{stock}', fn () => null);
        $route->bind($request);
        $route->setParameter('stock', $stockParam);
        $request->setRouteResolver(fn () => $route);

        return (new EnsureStockOwnership)->handle($request, fn () => new Response('ok'));
    }

    public function test_owner_passes_with_model_or_id(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id]);

        $this->assertSame('ok', $this->run($user, $stock)->getContent());
        $this->assertSame('ok', $this->run($user, (string) $stock->id)->getContent());
    }

    public function test_non_owner_is_forbidden(): void
    {
        $stock = Stock::factory()->create();

        $this->expectException(HttpException::class);
        $this->run(User::factory()->create(), $stock);
    }

    public function test_guest_is_forbidden(): void
    {
        $this->expectException(HttpException::class);
        $this->run(null, Stock::factory()->create());
    }

    public function test_unknown_stock_is_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->run(User::factory()->create(), '999999');
    }
}
