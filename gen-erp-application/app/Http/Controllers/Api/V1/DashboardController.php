<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Dashboard statistics"
 * )
 * REST API v1 controller for Dashboard operations.
 */
class DashboardController extends BaseApiController
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * @OA\Get(
     *     path="/dashboard",
     *     summary="Get dashboard statistics",
     *     tags={"Dashboard"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", type="object", @OA\Property(property="total_sales", type="integer"), @OA\Property(property="total_purchases", type="integer"), @OA\Property(property="total_expenses", type="integer"), @OA\Property(property="pending_approvals", type="integer"), @OA\Property(property="low_stock_products", type="array"), @OA\Property(property="recent_invoices", type="array"), @OA\Property(property="sales_chart", type="array"), @OA\Property(property="expense_chart", type="array")), @OA\Property(property="message", type="string")))
     */
    public function index(): JsonResponse
    {
        $data = [
            'total_sales' => $this->dashboardService->getTotalSales(),
            'total_purchases' => $this->dashboardService->getTotalPurchases(),
            'total_expenses' => $this->dashboardService->getTotalExpenses(),
            'pending_approvals' => $this->dashboardService->getPendingApprovals(),
            'low_stock_products' => $this->dashboardService->getLowStockProducts(),
            'recent_invoices' => $this->dashboardService->getRecentInvoices(),
            'sales_chart' => $this->dashboardService->getSalesChart(),
            'expense_chart' => $this->dashboardService->getExpenseChart(),
        ];

        return $this->success($data);
    }
}
