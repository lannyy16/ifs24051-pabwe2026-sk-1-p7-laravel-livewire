<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureRoleMiddlewareTest extends TestCase
{
    private function run(?User $user, string ...$roles)
    {
        $request = Request::create('/x');
        $request->setUserResolver(fn () => $user);

        return (new EnsureRole)->handle($request, fn () => new Response('ok'), ...$roles);
    }

    public function test_allows_matching_role(): void
    {
        $response = $this->run(new User(['role' => 'admin']), 'admin');

        $this->assertSame('ok', $response->getContent());
    }

    public function test_rejects_other_role(): void
    {
        $this->expectException(HttpException::class);
        $this->run(new User(['role' => 'member']), 'admin');
    }

    public function test_rejects_guest(): void
    {
        $this->expectException(HttpException::class);
        $this->run(null, 'admin');
    }
}
