<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please check the submitted details.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::query()->where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The email or password is incorrect.',
                ], 401);
            }

            $token = DB::transaction(
                fn () => $user->createToken('api-token')->plainTextToken
            );

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('Login failed.', ['exception' => $exception]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to log in right now. Please try again.',
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $request->user()->currentAccessToken()?->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Logout successful.',
            ]);
        } catch (Throwable $exception) {
            Log::error('Logout failed.', ['exception' => $exception]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to log out right now. Please try again.',
            ], 500);
        }
    }
}
