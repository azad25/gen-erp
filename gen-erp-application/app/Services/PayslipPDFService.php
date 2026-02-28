<?php

namespace App\Services;

use App\Models\PayrollEntry;
use App\Models\PayrollRun;

/**
 * Generates payslip PDFs for individual entries or bulk ZIP for an entire run.
 */
class PayslipPDFService
{
    /**
     * Generate a payslip PDF for a single entry and return its path.
     */
    public function generate(PayrollEntry $entry): string
    {
        $employee = $entry->employee;
        $company = $employee->company ?? null;

        $data = [
            'company_name' => $company?->name ?? '',
            'company_address' => $company?->address ?? '',
            'employee_name' => $employee->fullName(),
            'employee_code' => $employee->employee_code,
            'department' => $employee->department?->name ?? '',
            'designation' => $employee->designation?->name ?? '',
            'joining_date' => $employee->joining_date?->format('d M Y'),
            'period' => \Carbon\Carbon::create(null, $entry->period_month)->format('F').' '.$entry->period_year,
            'working_days' => $entry->working_days,
            'present_days' => $entry->present_days,
            'absent_days' => $entry->absent_days,
            'earnings' => $entry->earnings ?? [],
            'deductions' => $entry->deductions ?? [],
            'basic_salary' => $entry->basic_salary,
            'gross_salary' => $entry->gross_salary,
            'overtime_hours' => $entry->overtime_hours,
            'overtime_amount' => $entry->overtime_amount,
            'attendance_deduction' => $entry->attendance_deduction,
            'tax_deduction' => $entry->tax_deduction,
            'net_salary' => $entry->net_salary,
        ];

        // TODO: Phase 6 — render Blade view to PDF using barryvdh/laravel-dompdf
        $filename = "payslip_{$entry->employee_id}_{$entry->period_year}_{$entry->period_month}.pdf";
        $path = storage_path("app/private/{$entry->company_id}/payslips/{$filename}");

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        // Placeholder — actual PDF rendering deferred to when dompdf is available
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

        return $path;
    }

    /**
     * Generate bulk payslips as a ZIP for the entire run.
     */
    public function generateBulk(PayrollRun $run): string
    {
        $run->load('entries.employee');
        $paths = [];

        foreach ($run->entries as $entry) {
            $paths[] = $this->generate($entry);
        }

        // TODO: Phase 6 — create ZIP of all PDFs
        $zipPath = storage_path("app/private/{$run->company_id}/payslips/payroll_run_{$run->id}.zip");

        return $zipPath;
    }
}
