<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Domains\Calls\CallStatus;
use App\Domains\Calls\Models\Call;
use App\Domains\Housing\Models\Property;
use Illuminate\Contracts\View\View;

final class HomeController
{
    public function __invoke(): View
    {
        return view('portal.inicio', [
            'openCalls' => Call::query()
                ->where('status', CallStatus::Open)
                ->orderBy('closes_at')
                ->get(),
            'availableCount' => Property::query()->available()->count(),
            'featured' => Property::query()
                ->available()
                ->with('development')
                ->orderBy('monthly_rent')
                ->limit(3)
                ->get(),
        ]);
    }
}
