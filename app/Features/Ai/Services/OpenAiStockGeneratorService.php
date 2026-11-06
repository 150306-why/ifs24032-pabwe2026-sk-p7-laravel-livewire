<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class OpenAiStockGeneratorService implements AiClientInterface
{
    private const SYSTEM_PROMPT = <<<'TXT'
Kamu adalah asisten inventaris untuk aplikasi "Stock Things".
Berdasarkan permintaan pengguna, buat usulan data barang inventaris yang realistis.
Balas HANYA dengan JSON valid tanpa teks lain, dengan format:
{"items":[{"name":"string","sku":"string","description":"string (boleh Markdown)","quantity":0,"unit":"pcs","price":0,"min_stock":5,"status":"available|low_stock|out_of_stock"}]}
Aturan: quantity dan min_stock bilangan bulat >= 0, price angka >= 0 dalam Rupiah, sku unik dan singkat.
TXT;

    public function generate(string $prompt, int $count = 5): array
    {
        $apiKey = config('openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY belum dikonfigurasi di file .env.');
        }

        $count = max(1, min(10, $count));

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout((int) config('openai.timeout', 60))
                ->post(rtrim((string) config('openai.base_url'), '/').'/chat/completions', [
                    'model' => config('openai.model'),
                    'temperature' => 0.4,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                        ['role' => 'user', 'content' => "Buat {$count} barang inventaris. Permintaan: ".mb_substr($prompt, 0, 500)],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Tidak dapat terhubung ke layanan AI.', 0, $e);
        }

        if ($response->failed()) {
            throw new RuntimeException('Layanan AI mengembalikan error (HTTP '.$response->status().').');
        }

        return $this->parse((string) data_get($response->json(), 'choices.0.message.content', ''), $count);
    }

    /**
     * Mengubah respons teks AI menjadi daftar item yang sudah divalidasi.
     *
     * @return list<GeneratedStockItem>
     */
    public function parse(string $content, int $count): array
    {
        $decoded = json_decode($content, true);
        $list = [];

        if (is_array($decoded)) {
            $list = $decoded['items'] ?? (array_is_list($decoded) ? $decoded : []);
        }

        $items = [];

        foreach ((array) $list as $raw) {
            if (! is_array($raw) || Validator::make($raw, GeneratedStockItem::rules())->fails()) {
                continue;
            }

            $items[] = GeneratedStockItem::fromArray($raw);

            if (count($items) >= $count) {
                break;
            }
        }

        if ($items === []) {
            throw new RuntimeException('AI tidak menghasilkan data yang valid. Coba ubah prompt Anda.');
        }

        return $items;
    }
}
