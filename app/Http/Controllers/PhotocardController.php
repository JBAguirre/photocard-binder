<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePhotocardRequest;
use App\Http\Requests\UpdatePhotocardRequest;
use App\Http\Resources\PhotocardResource;
use App\Models\Photocard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use App\Http\Requests\IndexPhotocardRequest;

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

    public function store(StorePhotocardRequest $request): JsonResponse
    {
        $card = $request->user()->photocards()->create($request->validated());

        return (new PhotocardResource($card))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $id): PhotocardResource
    {
        return new PhotocardResource($this->findOwned($request, $id));
    }

    public function update(UpdatePhotocardRequest $request, string $id): PhotocardResource
    {
        $card = $this->findOwned($request, $id);
        $card->update($request->validated());

        return new PhotocardResource($card);
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->findOwned($request, $id)->delete();

        return response()->noContent();
    }

    private function findOwned(Request $request, string $id): Photocard
    {
        return $request->user()->photocards()->findOrFail($id);
    }
}