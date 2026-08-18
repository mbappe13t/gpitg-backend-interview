<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\UserRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class RatingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $products = Product::query()
                ->withAvg('ratings', 'rating')
                ->with(['ratings' => function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                }])
                ->get()
                ->map(function (Product $product) {
                    $userRating = $product->ratings->first();
                    $minutesPassed = $userRating
                        ? (int) $userRating->rating_datetime->diffInMinutes(now())
                        : null;

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => $product->price,
                        'ratings' => round((float) ($product->ratings_avg_rating ?? 0), 2),
                        'user_rating' => $userRating?->rating,
                        'time_passed' => $minutesPassed,
                        'time_passed_human' => $userRating?->rating_datetime->diffForHumans(),
                        'active_time' => $minutesPassed !== null && $minutesPassed > 30
                            ? 'active'
                            : 'inactive',
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully.',
                'data' => $products,
            ]);
        } catch (Throwable $exception) {
            Log::error('Products could not be retrieved.', ['exception' => $exception]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve products right now.',
            ], 500);
        }
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        $validator = $this->ratingValidator($request);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            $product = Product::query()->find($productId);

            if (! $product) {
                return $this->productNotFound();
            }

            $rating = DB::transaction(function () use ($request, $product) {
                return UserRating::query()->updateOrCreate(
                    [
                        'user_id' => $request->user()->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'rating' => $request->integer('rating'),
                        'rating_datetime' => now(),
                    ]
                );
            });

            return response()->json([
                'success' => true,
                'message' => $rating->wasRecentlyCreated
                    ? 'Product rated successfully.'
                    : 'Your existing rating was updated.',
                'data' => $rating,
            ], $rating->wasRecentlyCreated ? 201 : 200);
        } catch (Throwable $exception) {
            return $this->serverError($exception);
        }
    }

    public function update(Request $request, int $productId): JsonResponse
    {
        $validator = $this->ratingValidator($request);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        try {
            if (! Product::query()->whereKey($productId)->exists()) {
                return $this->productNotFound();
            }

            $rating = UserRating::query()
                ->where('user_id', $request->user()->id)
                ->where('product_id', $productId)
                ->first();

            if (! $rating) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have not rated this product yet.',
                ], 404);
            }

            DB::transaction(function () use ($request, $rating) {
                $rating->update([
                    'rating' => $request->integer('rating'),
                    'rating_datetime' => now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Rating updated successfully.',
                'data' => $rating->fresh(),
            ]);
        } catch (Throwable $exception) {
            return $this->serverError($exception);
        }
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        try {
            if (! Product::query()->whereKey($productId)->exists()) {
                return $this->productNotFound();
            }

            $rating = UserRating::query()
                ->where('user_id', $request->user()->id)
                ->where('product_id', $productId)
                ->first();

            if (! $rating) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have not rated this product yet.',
                ], 404);
            }

            DB::transaction(fn () => $rating->delete());

            return response()->json([
                'success' => true,
                'message' => 'Rating removed successfully.',
            ]);
        } catch (Throwable $exception) {
            return $this->serverError($exception);
        }
    }

    private function ratingValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'rating' => ['required', 'integer', 'between:1,5'],
        ]);
    }

    private function validationError(array $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'The rating must be a whole number between 1 and 5.',
            'errors' => $errors,
        ], 422);
    }

    private function productNotFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Product not found.',
        ], 404);
    }

    private function serverError(Throwable $exception): JsonResponse
    {
        Log::error('Rating request failed.', ['exception' => $exception]);

        return response()->json([
            'success' => false,
            'message' => 'Unable to process the rating right now.',
        ], 500);
    }
}
