<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexPhotocardRequest;
use App\Http\Requests\StorePhotocardRequest;
use App\Http\Requests\UpdatePhotocardRequest;
use App\Http\Resources\PhotocardResource;
use App\Models\Photocard;
use App\Services\PhotocardImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Throwable;

class PhotocardController extends Controller
{
    public function index(IndexPhotocardRequest $request): AnonymousResourceCollection
    {
        $cards = $request->user()
            ->photocards()
            ->filter($request->validated())
            ->latest()
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return PhotocardResource::collection($cards);
    }

    public function store(StorePhotocardRequest $request, PhotocardImageService $images): JsonResponse
    {
        $data = Arr::except($request->validated(), 'image');

        $newPath = $request->hasFile('image')
            ? $images->store($request->file('image'), $request->user()->id)
            : null;

        try {
            $card = $request->user()->photocards()->create([
                ...$data,
                'image_path' => $newPath,
            ]);
        } catch (Throwable $e) {
            if ($newPath) {
                $images->delete($newPath);
            }

            throw $e;
        }

        return (new PhotocardResource($card))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $id): PhotocardResource
    {
        return new PhotocardResource($this->findOwned($request, $id));
    }

    public function update(UpdatePhotocardRequest $request, string $id, PhotocardImageService $images): PhotocardResource
    {
        $card = $this->findOwned($request, $id);
        $data = Arr::except($request->validated(), 'image');
        $oldPath = $card->image_path;
        $newPath = null;

        if ($request->hasFile('image')) {
            $newPath = $images->store($request->file('image'), $request->user()->id);
            $data['image_path'] = $newPath;
        }

        try {
            $card->update($data);
        } catch (Throwable $e) {
            if ($newPath) {
                $images->delete($newPath);
            }

            throw $e;
        }

        if ($newPath && $oldPath) {
            $images->delete($oldPath);
        }

        return new PhotocardResource($card);
    }

    public function destroy(Request $request, string $id, PhotocardImageService $images): Response
    {
        $card = $this->findOwned($request, $id);
        $path = $card->image_path;

        $card->delete();

        if ($path) {
            $images->delete($path);
        }

        return response()->noContent();
    }

    private function findOwned(Request $request, string $id): Photocard
    {
        return $request->user()->photocards()->findOrFail($id);
    }
}