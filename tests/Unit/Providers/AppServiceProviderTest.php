<?php

namespace Tests\Unit\Providers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use App\Features\Stock\Repositories\StockRepository;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_bindings(): void
    {
        $this->assertInstanceOf(StockRepository::class, app(StockRepositoryInterface::class));
        $this->assertInstanceOf(OpenAiStockGeneratorService::class, app(AiClientInterface::class));
    }

    public function test_policy_is_registered(): void
    {
        $this->assertInstanceOf(StockPolicy::class, Gate::getPolicyFor(Stock::class));
    }

    public function test_rate_limiter_is_defined_for_guest_and_user(): void
    {
        $callback = RateLimiter::limiter('1000-per-second');
        $this->assertNotNull($callback);

        $guest = $callback(Request::create('/'));
        $this->assertInstanceOf(Limit::class, $guest);
        $this->assertSame(1000, $guest->maxAttempts);

        $request = Request::create('/');
        $user = new User;
        $user->id = 9;
        $request->setUserResolver(fn () => $user);
        $this->assertSame(9, $callback($request)->key);
    }

    public function test_force_https(): void
    {
        config(['app.force_https' => true]);
        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', url('/x'));
    }
}
