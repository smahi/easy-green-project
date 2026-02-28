<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReportTypeResource;
use App\Models\ReportType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReportTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $reportTypes = ReportType::where('is_active', true)
            ->orderBy('severity_level', 'desc')
            ->get();

        $version = md5((string) $reportTypes->max('updated_at'));

        return ReportTypeResource::collection($reportTypes)->additional([
            'meta' => [
                'version' => $version,
            ],
        ]);
    }
}
