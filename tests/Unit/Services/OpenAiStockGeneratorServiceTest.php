<?php

namespace Tests\Unit\Services;

use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiStockGeneratorServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['openai.api_key' => 'sk-test', 'openai.base_url' => 'https://api.openai.test/v1']);
    }

    private function fakeContent(string $content, int $status = 200): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $content]]]], $status)]);
    }

    public function test_requires_api_key(): void
    {
        config(['openai.api_key' => null]);

        $this->expectException(RuntimeException::class);
        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_generates_items(): void
    {
        $this->fakeContent(json_encode(['items' => [
            ['name' => 'Kopi', 'sku' => 'K1', 'quantity' => 10, 'unit' => 'kg', 'price' => 50000, 'min_stock' => 2],
            'bukan-array',
            ['name' => ''],
            ['name' => 'Teh'],
        ]]));

        $items = (new OpenAiStockGeneratorService)->generate('warung', 100);

        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(GeneratedStockItem::class, $items);
        $this->assertSame('Kopi', $items[0]->name);
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer sk-test')
            && str_contains($r->url(), '/chat/completions')
            && $r['response_format']['type'] === 'json_object');
    }

    public function test_limits_to_requested_count(): void
    {
        $this->fakeContent(json_encode(['items' => [['name' => 'A'], ['name' => 'B'], ['name' => 'C']]]));

        $this->assertCount(2, (new OpenAiStockGeneratorService)->generate('x', 2));
    }

    public function test_http_failure_throws(): void
    {
        Http::fake(['*' => Http::response('err', 500)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');
        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_invalid_json_throws(): void
    {
        $this->fakeContent('bukan json');

        $this->expectException(RuntimeException::class);
        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_missing_items_key_throws(): void
    {
        $this->fakeContent(json_encode(['foo' => 'bar']));

        $this->expectException(RuntimeException::class);
        (new OpenAiStockGeneratorService)->generate('x');
    }
}
