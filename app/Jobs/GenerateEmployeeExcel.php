<?php
namespace App\Jobs;

use App\Imports\EmployeeCodesImport;
use App\Exports\EmployeeEndorsementsExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

class GenerateEmployeeExcel implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $filePath,
        public string $jobId
    ) {}

    public function handle(): void
    {
        $statusPath = "employee-export-status/{$this->jobId}.json";

        // Mark job as processing
        Storage::disk('local')->put($statusPath, json_encode([
            'job_id' => $this->jobId,
            'status' => 'processing',
            'output_file' => null,
            'total_records' => 0,
            'error' => null,
        ]));

        // Check whether the uploaded file exists
        if (!Storage::disk('local')->exists($this->filePath)) {
            throw new RuntimeException('Uploaded Excel file not found.');
        }

        // Import employee codes in chunks
        $import = new EmployeeCodesImport();

        Excel::import($import, $this->filePath, 'local');

        // Generate output Excel file
        $outputPath = "employee-exports/{$this->jobId}.xlsx";

        $stored = Excel::store(
            new EmployeeEndorsementsExport($import->results),
            $outputPath,
            'local'
        );

        if (!$stored) {
            throw new RuntimeException(
                'Unable to generate output Excel file.'
            );
        }

        // Mark job as completed
        Storage::disk('local')->put($statusPath, json_encode([
            'job_id' => $this->jobId,
            'status' => 'completed',
            'output_file' => $outputPath,
            'total_records' => count($import->results),
            'error' => null,
        ]));

        // Delete the original uploaded file
        Storage::disk('local')->delete($this->filePath);
    }

    public function failed(Throwable $exception): void
    {
        Storage::disk('local')->put(
            "employee-export-status/{$this->jobId}.json",
            json_encode([
                'job_id' => $this->jobId,
                'status' => 'failed',
                'output_file' => null,
                'total_records' => 0,
                'error' => $exception->getMessage(),
            ])
        );
    }
}
