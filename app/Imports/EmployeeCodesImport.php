<?php

namespace App\Imports;

use App\Models\EmployeeMember;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class EmployeeCodesImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public array $results = [];

    public function collection(Collection $rows): void
    {
        // Get employee codes from current Excel chunk
        $codes = $rows->pluck('emp_code')
            ->filter(fn ($code) => $code !== null && trim((string) $code) !== '')
            ->map(fn ($code) => (string) $code)
            ->values();

        // Fetch matching employees in one database query
        $employees = EmployeeMember::whereIn(
            'employee_code',
            $codes->unique()->all()
        )
        ->get(['employee_code', 'endorsement_code'])
        ->keyBy(fn ($employee) => (string) $employee->employee_code);

        // Prepare output rows in original Excel order
        foreach ($codes as $code) {
            $employee = $employees->get($code);

            $this->results[] = [
                $code,
                $employee?->endorsement_code
            ];
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}