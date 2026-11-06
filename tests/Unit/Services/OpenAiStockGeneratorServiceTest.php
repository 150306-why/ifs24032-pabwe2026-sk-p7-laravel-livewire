<?php

namespace Tests\Unit\Services;

use App\Features\Ai\Services\OpenAiStockGeneratorService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiStockGeneratorServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'openai.api_key' => 'test-key',
            'openai.base_url' => 'https://api.openai.test/v1/',
            'openai.model' => 'test-model',
            'openai.timeout' => 5,
        ]);
    }

    private function fakeContent(string $content): void
    {
        Http::fake([
            'api.openai.test/*' => Http::response(['choices' => [['message' => ['content' => $content]]]]),
        ]);
    }

    public function test_generate_returns_only_valid_items(): void
    {
        $this->fakeContent(json_encode(['items' => [
            ['name' => 'Kabel USB', 'sku' => 'USB-1', 'quantity' => 10, 'unit' => 'pcs', 'price' => 15000, 'min_stock' => 5, 'status' => 'available'],
            ['name' => '', 'quantity' => 1],            // nama kosong -> dibuang
            'bukan array',                               // bukan array -> dibuang
            ['name' => 'Negatif', 'quantity' => -4],     // quantity negatif -> dibuang
            ['name' => 'Mouse', 'quantity' => 2],
        ]]));

        $items = (new OpenAiStockGeneratorService)->generate('perlengkapan komputer', 5);

        $this->assertCount(2, $items);
        $this->assertSame('Kabel USB', $items[0]->name);
        $this->assertSame('Mouse', $items[1]->name);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.openai.test/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['model'] === 'test-model'
                && $request['response_format']['type'] === 'json_object';
        });
    }

    public function test_generate_respects_count_limit_and_clamps(): void
    {
        $this->fakeContent(json_encode(['items' => [
            ['name' => 'A', 'quantity' => 1],
            ['name' => 'B', 'quantity' => 1],
            ['name' => 'C', 'quantity' => 1],
        ]]));

        $this->assertCount(1, (new OpenAiStockGeneratorService)->generate('x', 0));
        $this->assertCount(3, (new OpenAiStockGeneratorService)->generate('x', 99));
    }

    public function test_generate_accepts_plain_list_json(): void
    {
        $this->fakeContent(json_encode([['name' => 'A', 'quantity' => 1]]));

        $this->assertCount(1, (new OpenAiStockGeneratorService)->generate('x'));
    }

    public function test_missing_api_key_throws(): void
    {
        config(['openai.api_key' => '']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY');

        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_http_error_throws(): void
    {
        Http::fake(['api.openai.test/*' => Http::response(['error' => 'boom'], 500)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_connection_error_throws(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak dapat terhubung');

        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_invalid_or_empty_ai_output_throws(): void
    {
        foreach (['bukan json', 'null', '"teks"', json_encode(['items' => []]), json_encode(['items' => [['name' => '']]])] as $content) {
            $this->fakeContent($content);

            try {
                (new OpenAiStockGeneratorService)->generate('x');
                $this->fail("Seharusnya gagal untuk: {$content}");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('tidak menghasilkan data yang valid', $e->getMessage());
            }
        }
    }

    public function test_response_without_choices_throws(): void
    {
        Http::fake(['api.openai.test/*' => Http::response(['foo' => 'bar'])]);

        $this->expectException(RuntimeException::class);

        (new OpenAiStockGeneratorService)->generate('x');
    }
}
