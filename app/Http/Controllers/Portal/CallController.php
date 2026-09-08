<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Domains\Calls\CallStatus;
use App\Domains\Calls\Models\Call;
use Illuminate\Contracts\View\View;

final class CallController
{
    public function index(): View
    {
        return view('portal.convocatorias.index', [
            'open' => Call::query()
                ->where('status', CallStatus::Open)
                ->orderBy('closes_at')
                ->get(),
            'others' => Call::query()
                ->whereIn('status', [CallStatus::Closed, CallStatus::Resolved])
                ->orderByDesc('closes_at')
                ->limit(10)
                ->get(),
        ]);
    }

    public function show(Call $call): View
    {
        return view('portal.convocatorias.show', [
            'call' => $call,
            'propertyCount' => $call->properties()->count(),
        ]);
    }
}
