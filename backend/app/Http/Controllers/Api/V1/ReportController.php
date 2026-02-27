<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreReportRequest;
use App\Models\Report;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $mediaPaths = [];
        if ($request->hasFile('media_attachments')) {
            foreach ($request->file('media_attachments') as $file) {
                $path = $file->store('reports/media', 'public');
                $mediaPaths[] = $path;
            }
        }

        $report = Report::create([
            'user_id' => $request->user()->id,
            'report_type_id' => $validated['report_type_id'],
            'description' => $validated['description'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'is_synchronized' => $validated['is_synchronized'] ?? true,
            'status' => 'new',
            'media_attachments' => $mediaPaths,
        ]);

        return response()->json([
            'message' => 'Report submitted successfully',
            'report' => $report,
        ], 201);
    }
}
