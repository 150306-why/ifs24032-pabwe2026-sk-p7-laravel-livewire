<?php

namespace App\Http\Middleware;

use App\Features\Stock\Models\Stock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStockOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $stock = $request->route('stock');
        $user = $request->user();

        if ($stock instanceof Stock && ($user === null || $stock->user_id !== $user->id)) {
            abort(Response::HTTP_FORBIDDEN, 'Barang ini bukan milik Anda.');
        }

        return $next($request);
    }
}
