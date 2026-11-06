<?php

namespace App\Features\Ai\Contracts;

use App\Features\Ai\DTO\GeneratedStockItem;

interface AiClientInterface
{
    /**
     * Menghasilkan usulan data inventaris berdasarkan prompt pengguna.
     *
     * @return list<GeneratedStockItem>
     *
     * @throws \RuntimeException jika layanan AI gagal atau hasilnya tidak valid
     */
    public function generate(string $prompt, int $count = 5): array;
}
