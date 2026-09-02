<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(private DashboardService $dashboardService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // Auto-scope: non super-admin hanya melihat gudang sendiri
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            $gudangId = $request->filled('gudang_id') ? (int) $request->gudang_id : null;
        } else {
            $gudangId = $user->gudang_id;
        }

        $chartRange = $request->input('chart_range', '24h');

        if (!in_array($chartRange, ['24h', '7d', '30d'])) {
            $chartRange = '24h';
        }

        $data = $this->dashboardService->getDashboardData($gudangId, $chartRange);

        return $this->success($data, 'Dashboard data berhasil dimuat');
    }
}
