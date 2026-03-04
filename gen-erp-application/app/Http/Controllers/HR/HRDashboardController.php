<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HRDashboardController extends Controller
{
    /**
     * Display the HR dashboard.
     */
    public function index(Request $request): Response
    {
        $company = CompanyContext::active();
        $period = $request->get('period', '30d');
        
        // Calculate date range based on period
        $endDate = Carbon::now();
        $startDate = match($period) {
            '7d' => $endDate->copy()->subDays(7),
            '30d' => $endDate->copy()->subDays(30),
            '90d' => $endDate->copy()->subDays(90),
            default => $endDate->copy()->subDays(30)
        };

        return Inertia::render('HR/Dashboard', [
            'metrics' => $this->getHRMetrics($company, $startDate, $endDate),
            'chartData' => $this->getChartData($company, $startDate, $endDate),
            'departments' => $this->getDepartmentOverview($company),
            'topPerformers' => $this->getTopPerformers($company),
            'leaveRequests' => $this->getRecentLeaveRequests($company),
            'payrollSummary' => $this->getPayrollSummary($company),
            'upcomingEvents' => $this->getUpcomingEvents($company),
        ]);
    }

    /**
     * Get HR metrics for the dashboard.
     */
    private function getHRMetrics($company, $startDate, $endDate): array
    {
        // Total employees
        $totalEmployees = DB::table('employees')
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->count();

        $previousEmployees = $totalEmployees - 3; // Mock previous count

        // Attendance rate (mock calculation)
        $attendanceRate = 94.5;
        $previousAttendanceRate = 92.1;

        // Pending leaves
        $pendingLeaves = DB::table('leave_requests')
            ->where('company_id', $company->id)
            ->where('status', 'pending')
            ->count();

        // Payroll cost (mock calculation)
        $payrollCost = 285000000; // 28.5 lakh in paisa
        $previousPayrollCost = 275000000; // 27.5 lakh in paisa

        return [
            'totalEmployees' => $totalEmployees,
            'employeesDelta' => $previousEmployees > 0 ? round((($totalEmployees - $previousEmployees) / $previousEmployees) * 100, 1) : 0,
            'employeesSparkline' => $this->generateSparklineData(7),
            'attendanceRate' => $attendanceRate,
            'attendanceDelta' => round($attendanceRate - $previousAttendanceRate, 1),
            'pendingLeaves' => $pendingLeaves,
            'payrollCost' => $payrollCost,
            'payrollDelta' => $previousPayrollCost > 0 ? round((($payrollCost - $previousPayrollCost) / $previousPayrollCost) * 100, 1) : 0,
        ];
    }

    /**
     * Get chart data for attendance trend.
     */
    private function getChartData($company, $startDate, $endDate): array
    {
        $days = $endDate->diffInDays($startDate);
        $labels = [];
        $present = [];
        $absent = [];
        $late = [];

        for ($i = 0; $i <= $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $labels[] = $date->format('M j');
            
            // Mock data for attendance
            $totalEmployees = 45; // Mock total
            $presentCount = rand(40, 45);
            $absentCount = rand(0, 3);
            $lateCount = rand(0, 5);

            $present[] = $presentCount;
            $absent[] = $absentCount;
            $late[] = $lateCount;
        }

        return [
            'labels' => $labels,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
        ];
    }

    /**
     * Get department overview.
     */
    private function getDepartmentOverview($company): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Sales & Marketing',
                'employeeCount' => 12,
                'attendanceRate' => 96,
                'presentToday' => 11,
                'onLeave' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Engineering',
                'employeeCount' => 18,
                'attendanceRate' => 94,
                'presentToday' => 17,
                'onLeave' => 1,
            ],
            [
                'id' => 3,
                'name' => 'Operations',
                'employeeCount' => 8,
                'attendanceRate' => 92,
                'presentToday' => 7,
                'onLeave' => 1,
            ],
            [
                'id' => 4,
                'name' => 'Finance & Admin',
                'employeeCount' => 7,
                'attendanceRate' => 98,
                'presentToday' => 7,
                'onLeave' => 0,
            ],
        ];
    }

    /**
     * Get top performers.
     */
    private function getTopPerformers($company): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Sarah Ahmed',
                'department' => 'Sales',
                'position' => 'Senior Sales Manager',
                'score' => 95,
            ],
            [
                'id' => 2,
                'name' => 'Mohammad Rahman',
                'department' => 'Engineering',
                'position' => 'Lead Developer',
                'score' => 92,
            ],
            [
                'id' => 3,
                'name' => 'Fatima Khan',
                'department' => 'Marketing',
                'position' => 'Marketing Specialist',
                'score' => 89,
            ],
            [
                'id' => 4,
                'name' => 'Ahmed Ali',
                'department' => 'Operations',
                'position' => 'Operations Manager',
                'score' => 87,
            ],
            [
                'id' => 5,
                'name' => 'Nadia Hassan',
                'department' => 'Finance',
                'position' => 'Financial Analyst',
                'score' => 85,
            ],
        ];
    }

    /**
     * Get recent leave requests.
     */
    private function getRecentLeaveRequests($company): array
    {
        return [
            [
                'id' => 1,
                'employeeName' => 'John Doe',
                'leaveType' => 'Annual Leave',
                'duration' => '3 days',
                'startDate' => Carbon::now()->addDays(5),
                'endDate' => Carbon::now()->addDays(7),
                'status' => 'Pending',
                'requestDate' => Carbon::now()->subDays(2),
            ],
            [
                'id' => 2,
                'employeeName' => 'Jane Smith',
                'leaveType' => 'Sick Leave',
                'duration' => '1 day',
                'startDate' => Carbon::now()->addDays(1),
                'endDate' => Carbon::now()->addDays(1),
                'status' => 'Approved',
                'requestDate' => Carbon::now()->subDays(1),
            ],
            [
                'id' => 3,
                'employeeName' => 'Mike Johnson',
                'leaveType' => 'Personal Leave',
                'duration' => '2 days',
                'startDate' => Carbon::now()->addDays(10),
                'endDate' => Carbon::now()->addDays(11),
                'status' => 'Pending',
                'requestDate' => Carbon::now()->subDays(3),
            ],
            [
                'id' => 4,
                'employeeName' => 'Lisa Brown',
                'leaveType' => 'Maternity Leave',
                'duration' => '90 days',
                'startDate' => Carbon::now()->addDays(30),
                'endDate' => Carbon::now()->addDays(120),
                'status' => 'Approved',
                'requestDate' => Carbon::now()->subDays(7),
            ],
        ];
    }

    /**
     * Get payroll summary.
     */
    private function getPayrollSummary($company): array
    {
        return [
            'grossPay' => 285000000, // 28.5 lakh in paisa
            'netPay' => 225000000, // 22.5 lakh in paisa
            'deductions' => 45000000, // 4.5 lakh in paisa
            'benefits' => 15000000, // 1.5 lakh in paisa
            'breakdown' => [
                ['category' => 'Basic Salary', 'amount' => 180000000, 'percentage' => 63, 'color' => '#0F766E'],
                ['category' => 'Allowances', 'amount' => 65000000, 'percentage' => 23, 'color' => '#14B8A6'],
                ['category' => 'Overtime', 'amount' => 25000000, 'percentage' => 9, 'color' => '#5EEAD4'],
                ['category' => 'Bonuses', 'amount' => 15000000, 'percentage' => 5, 'color' => '#99F6E4'],
            ],
        ];
    }

    /**
     * Get upcoming HR events.
     */
    private function getUpcomingEvents($company): array
    {
        return [
            'birthdays' => 3,
            'anniversaries' => 2,
            'reviews' => 5,
            'thisWeek' => [
                [
                    'id' => 1,
                    'title' => 'Birthday Celebration',
                    'employee' => 'Sarah Ahmed',
                    'type' => 'birthday',
                    'date' => Carbon::now()->addDays(2),
                ],
                [
                    'id' => 2,
                    'title' => '5 Year Anniversary',
                    'employee' => 'Mohammad Rahman',
                    'type' => 'anniversary',
                    'date' => Carbon::now()->addDays(4),
                ],
                [
                    'id' => 3,
                    'title' => 'Performance Review',
                    'employee' => 'Fatima Khan',
                    'type' => 'review',
                    'date' => Carbon::now()->addDays(6),
                ],
            ],
        ];
    }

    /**
     * Generate sparkline data for charts.
     */
    private function generateSparklineData(int $points): array
    {
        $data = [];
        for ($i = 0; $i < $points; $i++) {
            $data[] = rand(20, 100);
        }
        return $data;
    }
}