<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiStockGeneratorService implements AiClientInterface
{
    public function generate(string $prompt, int $count = 5): array
    {
        $apiKey = config('openai.api_key');
        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY belum dikonfigurasi.');
        }

        $count = max(1, min(20, $count));

        $system = 'Kamu adalah asisten inventaris. Berdasarkan permintaan pengguna, buat daftar item stok barang. '
            .'Balas HANYA JSON dengan bentuk {"items":[{"name":string,"sku":string,"description":string (Markdown singkat),'
            .'"quantity":integer,"unit":string,"price":number,"min_stock":integer}]}. '
            ."Buat tepat {$count} item. Gunakan Bahasa Indonesia dan harga dalam Rupiah.";

        $response = Http::baseUrl((string) config('openai.base_url'))
            ->withToken($apiKey)
            ->timeout((int) config('openai.timeout'))
            ->post('/chat/completions', [
                'model' => config('openai.model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Permintaan ke OpenAI gagal (HTTP '.$response->status().').');
        }

        $decoded = json_decode((string) $response->json('choices.0.message.content'), true);
        $items = is_array($decoded) ? ($decoded['items'] ?? []) : [];

        $result = [];
        foreach ((array) $items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $dto = GeneratedStockItem::fromArray($item);
            if ($dto->name !== '') {
                $result[] = $dto;
            }
        }

        if ($result === []) {
            throw new RuntimeException('AI tidak mengembalikan item yang valid.');
        }

        return array_slice($result, 0, $count);
    }
}
