<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSpeakerRequest;
use App\Http\Requests\UpdateSpeakerRequest;
use App\Http\Resources\SpeakerResource;
use App\Models\Speaker;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ApiSpeakerController extends Controller
{
    public function index(): ResourceCollection
    {
        return SpeakerResource::collection(Cache::remember('speakers', Carbon::now()->endOfDay()->diffInSeconds(), function () {
            return Speaker::orderBy('id', 'asc')->get();
        }));
    }

    public function show(Speaker $speaker): SpeakerResource
    {
        return new SpeakerResource($speaker->loadMissing(['events']));
    }

    public function store(StoreSpeakerRequest $request): Response
    {
        try {
            $speaker = Speaker::create($request->validated());
            $speakerResource = new SpeakerResource($speaker);

            return response()->json($speakerResource, Response::HTTP_OK);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Failed to create speaker'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateSpeakerRequest $request, Speaker $speaker): Response
    {
        try {
            $speaker->update($request->validated());
            $speaker = $speaker->load(['events']);
            $speakerResource = new SpeakerResource($speaker);

            return response()->json($speakerResource, Response::HTTP_OK);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Failed to update speaker'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Speaker $speaker): Response
    {
        DB::beginTransaction();

        try {
            $speaker->delete();

            DB::commit();

            return response()->json([
                'message' => 'Speaker deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete speaker'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
