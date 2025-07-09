<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalTestAppointment;
use App\Models\MedicalTestResult;
use App\Notifications\MedicalTestResultNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MedicalTestResultController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/medical-test-results/upload",
     *     summary="Upload a medical test result and notify patient and doctor",
     *     tags={"Medical Test Results"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"appointment_id", "result_file"},
     *
     *                 @OA\Property(property="appointment_id", type="integer", example=1, description="ID of the medical test appointment"),
     *                 @OA\Property(property="result_file", type="file", format="binary", description="PDF file of the test result (max 2MB)"),
     *                 @OA\Property(property="notes", type="string", nullable=true, example="Normal results", description="Optional notes about the test result (max 1000 characters)")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Test result uploaded and notifications sent successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="appointment_id", type="integer", example=1),
     *                 @OA\Property(property="pat_id", type="integer", example=1),
     *                 @OA\Property(property="doc_id", type="integer", example=1),
     *                 @OA\Property(property="result_file", type="string", example="medical_results/result_123.pdf"),
     *                 @OA\Property(property="notes", type="string", nullable=true, example="Normal results")
     *             ),
     *             @OA\Property(property="message", type="string", example="Test result uploaded and notifications sent successfully"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized action",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only doctors or moderators can upload results."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Medical test appointment not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Medical test appointment not found"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="status", type="integer", example=422)
     *         )
     *     )
     * )
     */

    // Upload a medical test result and notify patient and doctor.
    public function uploadResult(Request $request)
    {
        $doctor = Auth::guard('doctor')->user();
        $user = Auth::guard('sanctum')->user();

        if (! $doctor && ! $user) {
            return response()->json([
                'message' => 'Unauthorized. Only doctors or moderators can upload results.',
                'status' => 403,
            ], 403);
        }

        // Check user role if authenticated as user
        if ($user && $user->role !== 'moderator') {
            return response()->json([
                'message' => 'Unauthorized. Only moderators can upload results.',
                'status' => 403,
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|exists:medical_test_appointments,id',
            'result_file' => 'required|file|mimes:pdf|max:2048', // Max 2MB
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $appointment = MedicalTestAppointment::find($request->appointment_id);

        if (! $appointment) {
            return response()->json([
                'message' => 'Medical test appointment not found',
                'status' => 404,
            ], 404);
        }

        // Check doctor permissions
        if ($doctor) {
            // Regular doctors must be associated with the appointment
            if ($doctor->type === 'regular' && $appointment->doc_id !== $doctor->id) {
                return response()->json([
                    'message' => 'Unauthorized. Regular doctors can only upload results for their own appointments.',
                    'status' => 403,
                ], 403);
            }
        }

        // Moderators can upload for any appointment
        // Store the PDF file
        $file = $request->file('result_file');
        $path = $file->store('medical_results', 'public');

        // Create the result record
        $result = MedicalTestResult::create([
            'appointment_id' => $appointment->id,
            'pat_id' => $appointment->pat_id,
            'doc_id' => $appointment->doc_id,
            'result_file' => $path,
            'notes' => $request->notes,
        ]);

        // Notify patient and doctor
        if ($appointment->patient) {
            $appointment->patient->notify(new MedicalTestResultNotification($result));
        }
        $appointment->doctor->notify(new MedicalTestResultNotification($result));

        return response()->json([
            'result' => $result,
            'message' => 'Test result uploaded and notifications sent successfully',
            'status' => 200,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/medical-test-results/download/{resultId}",
     *     summary="Download or view a medical test result PDF",
     *     tags={"Medical Test Results"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="resultId",
     *         in="path",
     *         required=true,
     *         description="ID of the medical test result",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Medical test result file",
     *
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized action",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthorized. You can only download your own test results."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Medical test result or file not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Medical test result not found"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Download or view a test result PDF.
    public function downloadResult($resultId)
    {
        $result = MedicalTestResult::find($resultId);

        if (! $result) {
            return response()->json([
                'message' => 'Medical test result not found',
                'status' => 404,
            ], 404);
        }

        // Check user authentication and permissions
        $patient = Auth::guard('patient')->user();
        $doctor = Auth::guard('doctor')->user();
        $user = Auth::guard('sanctum')->user();

        // Check permissions
        if ($patient && $result->pat_id !== $patient->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only download your own test results.',
                'status' => 403,
            ], 403);
        }

        if ($doctor && $result->doc_id !== $doctor->id) {
            return response()->json([
                'message' => 'Unauthorized. Only the doctor who requested the test can download the result.',
                'status' => 403,
            ], 403);
        }

        if ($user && $user->role !== 'moderator') {
            return response()->json([
                'message' => 'Unauthorized. Only moderators can download results.',
                'status' => 403,
            ], 403);
        }

        $path = storage_path('app/public/'.$result->result_file);

        if (! file_exists($path)) {
            return response()->json([
                'message' => 'Result file not found',
                'status' => 404,
            ], 404);
        }

        return response()->file($path);
    }
}
