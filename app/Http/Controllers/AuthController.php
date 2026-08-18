<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AuthController extends Controller
{
    use ResponseTrait;

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Please check the submitted details.',
                422,
                $validator->errors()
            );
        }

        try {
            $user = User::query()->where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return $this->errorResponse('The email or password is incorrect.', 401);
            }

            $token = DB::transaction(
                fn () => $user->createToken('api-token')->plainTextToken
            );

            return $this->successResponse(
                'Login successful.',
                [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            );
        } catch (Throwable $exception) {
            Log::error('Login failed.', ['exception' => $exception]);

            return $this->errorResponse(
                'Unable to log in right now. Please try again.',
                500
            );
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $request->user()->currentAccessToken()?->delete();
            });

            return $this->successResponse('Logout successful.');
        } catch (Throwable $exception) {
            Log::error('Logout failed.', ['exception' => $exception]);

            return $this->errorResponse(
                'Unable to log out right now. Please try again.',
                500
            );
        }
    }
}
