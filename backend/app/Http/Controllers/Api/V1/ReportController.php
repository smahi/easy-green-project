<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBulkReportRequest;
use App\Http\Requests\Api\V1\StoreReportRequest;
use App\Http\Resources\Api\V1\ReportResource;
use App\Models\Report;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReportController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $reports = $request->user()->reports()->with('reportType')->latest()->paginate(15);

        return ReportResource::collection($reports);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Report::class);

            $validated = $request->validated();
        } catch (\Exception $e) {
            \Log::error('Report Submission Error: '.$e->getMessage());
            throw $e;
        }

        $mediaPaths = [];
        if ($request->hasFile('media_attachments')) {
            foreach ($request->file('media_attachments') as $file) {
                $path = $file->store('reports/media', 'public');
                $mediaPaths[] = $path;
            }
        }

        // Handle translatable description
        $description = [];
        if (isset($validated['description'])) {
            $locale = app()->getLocale();
            $description[$locale] = $validated['description'];
        }

        $userId = $request->user()->id;
        $clientUuid = $validated['client_uuid'] ?? null;

        $data = [
            'report_type_id' => $validated['report_type_id'],
            'description' => $description,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'is_synchronized' => $validated['is_synchronized'] ?? true,
            'status' => 'new',
            'media_attachments' => $mediaPaths,
        ];

        // Idempotency check
        if ($clientUuid) {
            $report = Report::updateOrCreate(
                ['user_id' => $userId, 'client_uuid' => $clientUuid],
                $data
            );
        } else {
            $report = Report::create(array_merge(['user_id' => $userId], $data));
        }

        return response()->json([
            'message' => 'Report submitted successfully',
            'report' => new ReportResource($report->load('reportType')),
        ], 201);
    }

    /**
     * Store multiple newly created resources in storage (Offline sync).
     */
    public function storeBulk(StoreBulkReportRequest $request): JsonResponse
    {
        $this->authorize('create', Report::class);

        $validated = $request->validated();
        $userId = $request->user()->id;
        $locale = app()->getLocale();

        $insertedReports = [];

        foreach ($validated['reports'] as $reportData) {
            $description = [];
            if (isset($reportData['description'])) {
                $description[$locale] = $reportData['description'];
            }

            $clientUuid = $reportData['client_uuid'] ?? null;
            $data = [
                'report_type_id' => $reportData['report_type_id'],
                'description' => $description,
                'latitude' => $reportData['latitude'],
                'longitude' => $reportData['longitude'],
                'is_synchronized' => true,
                'status' => 'new',
                'created_at' => $reportData['created_at'] ?? now(),
            ];

            if ($clientUuid) {
                $report = Report::updateOrCreate(
                    ['user_id' => $userId, 'client_uuid' => $clientUuid],
                    $data
                );
            } else {
                $report = Report::create(array_merge(['user_id' => $userId], $data));
            }

            $insertedReports[] = $report;
        }

        return response()->json([
            'message' => 'Reports synchronized successfully',
            'count' => count($insertedReports),
        ], 201);
    }
}
