<?php

namespace App\Http\Controllers;

use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PatientController extends Controller
{
    use ResponseTrait;

    # Register a patient with Gpitg Hospital.
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'Sponsor_ID' => ['required', 'integer'],
            'Patient_Name' => ['required', 'string', 'max:255'],
            'Date_Of_Birth' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'Gender' => ['required', 'string'],
            'Visit_Type_ID' => ['required', 'integer'],
            'Type_Of_Check_In' => ['required', 'integer'],
            'branchId' => ['required', 'integer'],
            'Employee_ID' => ['required', 'integer'],
            'pf3' => ['nullable'],
            'Diceased' => ['required', 'string'],
            'Referral_Status' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Please check the patient details.',
                422,
                $validator->errors()
            );
        }

        try {
            $patientData = [
                ...$validator->validated(),
                'pf3' => $request->input('pf3'),
                'Referral_Status' => $request->input('Referral_Status'),
            ];

            $response = Http::acceptJson()
                ->timeout(20)
                ->post('http://41.188.172.204:3033/patient-registration', $patientData);

            if (! $response->successful()) {
                Log::warning('Gpitg patient registration was rejected.', [
                    'status' => $response->status(),
                ]);

                return $this->errorResponse(
                    'The hospital service could not register the patient.',
                    502
                );
            }

            $checkInDateTime = $response->json('Check_In_Date_And_Time');

            if (! $checkInDateTime) {
                Log::warning('Gpitg registration response did not include check-in time.');

                return $this->errorResponse(
                    'The patient was registered, but no check-in time was returned.',
                    502
                );
            }

            return $this->successResponse(
                'Patient registered successfully.',
                [
                    'Check_In_Date_And_Time' => $checkInDateTime,
                ],
                201
            );
        } catch (Throwable $exception) {
            Log::error('Gpitg patient registration failed.', ['exception' => $exception]);

            return $this->errorResponse(
                'Unable to reach the hospital service right now.',
                502
            );
        }
    }
}
