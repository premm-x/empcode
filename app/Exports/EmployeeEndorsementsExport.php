<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeeEndorsementsExport implements FromCollection, WithHeadings
{
    public function __construct(
        private array $rows
    ) {}

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return [
            'Employee Code',
            'Endorsement Code'
        ];
    }
}