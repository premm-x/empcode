<?php
namespace App\Http\Controllers;

use App\Jobs\GenerateEmployeeExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeExcelController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        // Store the uploaded Excel file
        $filePath = $request->file('file')
            ->store('employee-imports', 'local');

        // Generate a unique ID for this job
        $jobId = (string) Str::uuid();

        // Create initial status file
        $statusPath = "employee-export-status/{$jobId}.json";

        Storage::disk('local')->put($statusPath, json_encode([
            'job_id' => $jobId,
            'status' => 'pending',
            'output_file' => null,
            'total_records' => 0,
            'error' => null,
        ]));

        // Dispatch the job with BOTH required arguments
        GenerateEmployeeExcel::dispatch($filePath, $jobId);

        return response()->json([
            'job_id' => $jobId,
            'status' => 'processing',
            'message' => 'Your Excel file is being processed.',
        ], 202);
    }

    public function status(string $jobId)
    {
        if (!Str::isUuid($jobId)) {
            return response()->json([
                'message' => 'Invalid job ID.',
            ], 400);
        }

        $path = "employee-export-status/{$jobId}.json";

        if (!Storage::disk('local')->exists($path)) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $data = json_decode(
            Storage::disk('local')->get($path),
            true
        );

        if (($data['status'] ?? null) === 'completed') {
            $data['download_url'] = route(
                'employee-excel.download',
                $jobId
            );
        }

        return response()->json($data);
    }

    public function download(string $jobId)
    {
        if (!Str::isUuid($jobId)) {
            return response()->json([
                'message' => 'Invalid job ID.',
            ], 400);
        }

        $path = "employee-export-status/{$jobId}.json";

        if (!Storage::disk('local')->exists($path)) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $data = json_decode(
            Storage::disk('local')->get($path),
            true
        );

        if (($data['status'] ?? null) !== 'completed') {
            return response()->json([
                'message' => 'File is not ready yet.',
            ], 409);
        }

        $outputFile = $data['output_file'] ?? null;

        if (!$outputFile || !Storage::disk('local')->exists($outputFile)) {
            return response()->json([
                'message' => 'Generated file not found.',
            ], 404);
        }

        return Storage::disk('local')->download($outputFile);
    }
}
