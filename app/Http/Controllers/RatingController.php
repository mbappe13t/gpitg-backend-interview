<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\UserRating;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class RatingController extends Controller
{
    use ResponseTrait;

    # List products together with their rating details.
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

            return $this->successResponse('Products retrieved successfully.', $products);
        } catch (Throwable $exception) {
            Log::error('Products could not be retrieved.', ['exception' => $exception]);

            return $this->errorResponse('Unable to retrieve products right now.', 500);
        }
    }

    # Create or replace the current user's product rating.
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

            return $this->successResponse(
                $rating->wasRecentlyCreated
                    ? 'Product rated successfully.'
                    : 'Your existing rating was updated.',
                $rating,
                $rating->wasRecentlyCreated ? 201 : 200
            );
        } catch (Throwable $exception) {
            return $this->serverError($exception);
        }
    }

    # Change the current user's existing product rating.
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
                return $this->errorResponse('You have not rated this product yet.', 404);
            }

            DB::transaction(function () use ($request, $rating) {
                $rating->update([
                    'rating' => $request->integer('rating'),
                    'rating_datetime' => now(),
                ]);
            });

            return $this->successResponse('Rating updated successfully.', $rating->fresh());
        } catch (Throwable $exception) {
            return $this->serverError($exception);
        }
    }

    # Remove the current user's product rating.
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
                return $this->errorResponse('You have not rated this product yet.', 404);
            }

            DB::transaction(fn () => $rating->delete());

            return $this->successResponse('Rating removed successfully.');
        } catch (Throwable $exception) {
            return $this->serverError($exception);
        }
    }

    # Check that a rating is a whole number from 1 to 5.
    private function ratingValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'rating' => ['required', 'integer', 'between:1,5'],
        ]);
    }

    # Handle invalid rating details.
    private function validationError(array $errors): JsonResponse
    {
        return $this->errorResponse(
            'The rating must be a whole number between 1 and 5.',
            422,
            $errors
        );
    }

    # Handle a product that does not exist.
    private function productNotFound(): JsonResponse
    {
        return $this->errorResponse('Product not found.', 404);
    }

    # Handle an unexpected rating error.
    private function serverError(Throwable $exception): JsonResponse
    {
        Log::error('Rating request failed.', ['exception' => $exception]);

        return $this->errorResponse('Unable to process the rating right now.', 500);
    }
}
