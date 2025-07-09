<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalTest;
use App\Models\MedicalTestAppointment;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MedicalTestAppointmentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/medical-test-appointments/appoint",
     *     summary="Appoint a medical test for a patient",
     *     tags={"Medical Test Appointments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"test_id","doc_id","appoint_time","appoint_date"},
     *
     *             @OA\Property(property="test_id", type="integer", example=1, description="ID of the medical test"),
     *             @OA\Property(property="doc_id", type="integer", example=1, description="ID of the doctor"),
     *             @OA\Property(property="appoint_time", type="string", format="time", example="14:00", description="Appointment time in 24-hour format (H:i)"),
     *             @OA\Property(property="appoint_date", type="string", format="date", example="2025-07-10", description="Appointment date in Y-m-d format")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Medical test appointed successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="appointment", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="pat_id", type="integer", example=1),
     *                 @OA\Property(property="test_id", type="integer", example=1),
     *                 @OA\Property(property="doc_id", type="integer", example=1),
     *                 @OA\Property(property="appoint_time", type="string", example="14:00"),
     *                 @OA\Property(property="appoint_date", type="string", example="2025-07-10"),
     *                 @OA\Property(property="medicalTest", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="test_name", type="string", example="Blood Test")
     *                 ),
     *                 @OA\Property(property="doctor", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="firstName", type="string", example="John"),
     *                     @OA\Property(property="lastName", type="string", example="Doe")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Medical test appointed successfully"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request data",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Cannot appoint a medical test in the past."),
     *             @OA\Property(property="status", type="integer", example=400)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated. Please log in."),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized action",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only patients can perform this action."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Medical test or schedule not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Medical test schedule not found."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=409,
     *         description="Appointment slot already taken",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="This appointment slot is already taken."),
     *             @OA\Property(property="status", type="integer", example=409)
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

    // Appoint a medical test for a patient.
    public function appointTest(Request $request)
    {
        $patient = $this->ensurePatient();

        $data = $this->validateRequest($request, [
            'test_id' => 'required|exists:medical_tests,id',
            'doc_id' => 'required|exists:doctors,id',
            'appoint_time' => 'required|date_format:G:i',
            'appoint_date' => 'required|date',
        ]);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        $test = MedicalTest::where('id', $data['test_id'])
            ->select('id', 'test_name', 'schedule')
            ->firstOrFail();

        $schedule = $this->parseSchedule($test->schedule);
        if (empty($schedule)) {
            return $this->errorResponse('Medical test schedule not found.', 404);
        }

        // Normalize the appoint_time to H:i format (EX: "2:00" -> "02:00")
        $appointTime = Carbon::createFromFormat('G:i', $data['appoint_time'])->format('H:i');
        $appointDateTime = Carbon::createFromFormat('Y-m-d H:i', "{$data['appoint_date']} {$appointTime}");

        if ($appointDateTime->isPast()) {
            return $this->errorResponse('Cannot appoint a medical test in the past.', 400);
        }

        // Get the day of the week for the appoint date
        $dayOfWeek = Carbon::parse($data['appoint_date'])->format('l'); // EX: "Wednesday"

        // Check if the day has a schedule
        if (! isset($schedule[$dayOfWeek]) || empty($schedule[$dayOfWeek])) {
            return $this->errorResponse("No available schedule for $dayOfWeek.", 400);
        }

        // Convert schedule times to 24-hour format for comparison
        $availableTimes = array_map(function ($time) {
            $time = trim($time);
            try {
                return Carbon::createFromFormat('h:i A', $time)->format('H:i');
            } catch (\Exception $e) {
                // Log::warning('Invalid time format in schedule: '.$time.' - Error: '.$e->getMessage());

                return null;
            }
        }, $schedule[$dayOfWeek]);

        $availableTimes = array_filter($availableTimes, fn ($time) => ! is_null($time));

        if (empty($availableTimes)) {
            return $this->errorResponse('No valid times found in the medical test schedule.', 400);
        }

        if (! in_array($appointTime, $availableTimes)) {
            return $this->errorResponse('Selected time is not available in the medical test schedule.', 400);
        }

        // Check for existing appointment
        if (MedicalTestAppointment::where('test_id', $data['test_id'])
            ->where('appoint_date', $data['appoint_date'])
            ->where('appoint_time', $appointTime)
            ->exists()
        ) {
            return $this->errorResponse('This appointment slot is already taken.', 409);
        }

        $appointment = MedicalTestAppointment::create([
            'pat_id' => $patient->id,
            'test_id' => $data['test_id'],
            'doc_id' => $data['doc_id'],
            'appoint_time' => $appointTime,
            'appoint_date' => $data['appoint_date'],
        ]);

        return $this->successResponse([
            'appointment' => $appointment->load('medicalTest', 'doctor'),
            'message' => 'Medical test appointed successfully',
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/medical-test-appointments/cancel/{appointmentId}",
     *     summary="Cancel a medical test appointment",
     *     tags={"Medical Test Appointments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="appointmentId",
     *         in="path",
     *         required=true,
     *         description="ID of the medical test appointment to cancel",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Medical test appointment cancelled successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Medical test appointment cancelled successfully"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated. Please log in."),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized action",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="You do not have permission to cancel this appointment."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Appointment not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Appointment not found."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Cannot cancel past appointment",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Cannot cancel a past appointment."),
     *             @OA\Property(property="status", type="integer", example=400)
     *         )
     *     )
     * )
     */

    // Cancel a medical test appointment.
    public function cancelTest($appointmentId)
    {
        $user = Auth::user();

        if (! $user) {
            return $this->errorResponse('Unauthenticated. Please log in.', 401);
        }

        $appointment = MedicalTestAppointment::where('id', $appointmentId)->first();

        if (! $appointment) {
            return $this->errorResponse('Appointment not found.', 404);
        }

        if ($user instanceof Patient && $appointment->pat_id === $user->id) {
            $appointmentDateTime = Carbon::parse("{$appointment->appoint_date} {$appointment->appoint_time}");
            if ($appointmentDateTime->isPast()) {
                return $this->errorResponse('Cannot cancel a past appointment.', 400);
            }

            // Handle the canceled appointment
            $this->handleCanceledMedicalTestAppointment($appointment);

            return $this->successResponse([
                'message' => 'Medical test appointment cancelled successfully',
            ], 200);
        }

        return $this->errorResponse('You do not have permission to cancel this appointment.', 403);
    }

    /**
     * @OA\Get(
     *     path="/api/medical-test-appointments/tests/available/{testId}",
     *     summary="Get available appointments for a medical test on a specific date",
     *     tags={"Medical Test Appointments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="testId",
     *         in="path",
     *         required=true,
     *         description="ID of the medical test",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         description="Date to check available appointments (defaults to today)",
     *
     *         @OA\Schema(type="string", format="date", example="2025-07-10")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Available appointments retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="available_appointments", type="array",
     *
     *                 @OA\Items(type="string", example="14:00")
     *             ),
     *
     *             @OA\Property(property="date", type="string", format="date", example="2025-07-10"),
     *             @OA\Property(property="message", type="string", example="Available appointments retrieved successfully"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid date or no valid times",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Cannot retrieve appointments for past dates."),
     *             @OA\Property(property="status", type="integer", example=400)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated. Please log in."),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized action",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only patients can perform this action."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Medical test or schedule not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Medical test not found."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Get available appointments for a medical test on a specific date.
    public function getAvailableTests(Request $request, $testId)
    {
        $patient = $this->ensurePatient();

        $test = MedicalTest::where('id', $testId)
            ->select('id', 'test_name', 'schedule')
            ->first();

        if (! $test) {
            return $this->errorResponse('Medical test not found.', 404);
        }

        $schedule = $this->parseSchedule($test->schedule);
        if (empty($schedule)) {
            return $this->errorResponse('Medical test schedule not found.', 404);
        }

        $appointDate = $request->query('date', Carbon::today()->toDateString());
        $parsedDate = Carbon::parse($appointDate);

        if ($parsedDate->isPast() && ! $parsedDate->isToday()) {
            return $this->errorResponse('Cannot retrieve appointments for past dates.', 400);
        }

        $dayOfWeek = Carbon::parse($appointDate)->format('l');

        if (! isset($schedule[$dayOfWeek]) || empty($schedule[$dayOfWeek])) {
            return $this->errorResponse("No available schedule for $dayOfWeek.", 400);
        }

        $availableTimes = array_map(function ($time) {
            $time = trim($time);
            try {
                return Carbon::createFromFormat('h:i A', $time)->format('H:i');
            } catch (\Exception $e) {
                // Log::warning('Invalid time format in schedule: '.$time.' - Error: '.$e->getMessage());

                return null;
            }
        }, $schedule[$dayOfWeek]);

        $availableTimes = array_filter($availableTimes, fn ($time) => ! is_null($time));

        if (empty($availableTimes)) {
            return $this->errorResponse('No valid times found in the medical test schedule.', 400);
        }

        $appointedTests = MedicalTestAppointment::where('test_id', $testId)
            ->where('appoint_date', $appointDate)
            ->pluck('appoint_time')
            ->toArray();

        $availableAppointments = array_filter($availableTimes, function ($time) use ($appointedTests, $appointDate) {
            $appointmentDateTime = Carbon::createFromFormat('Y-m-d H:i', "$appointDate $time");

            return ! in_array($time, $appointedTests) && ! $appointmentDateTime->isPast();
        });

        return $this->successResponse([
            'available_appointments' => array_values($availableAppointments),
            'date' => $appointDate,
            'message' => empty($availableAppointments) ? 'No available appointments for this date.' : 'Available appointments retrieved successfully',
        ], 200);
    }

    // Ensure the authenticated user is a patient.
    protected function ensurePatient()
    {
        $patient = Auth::guard('patient')->user();
        if (! $patient || ! $patient instanceof Patient) {
            return response()->json([
                'message' => 'Unauthorized. Only patients can perform this action.',
                'status' => 403,
            ], 403)->throwResponse();
        }

        return $patient;
    }

    // Validate the request and return validated data.
    protected function validateRequest(Request $request, array $rules)
    {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        return $validator->validated();
    }

    // Parse the medical test schedule from JSON or array.
    protected function parseSchedule($schedule)
    {
        return is_string($schedule) ? json_decode($schedule, true) : $schedule;
    }

    // Standardized success response.
    protected function successResponse(array $data, int $status)
    {
        return response()->json(array_merge($data, ['status' => $status]), $status);
    }

    // Standardized error response.
    protected function errorResponse(string $message, int $status)
    {
        return response()->json([
            'message' => $message,
            'status' => $status,
        ], $status);
    }

    // Handle the transfer of a canceled medical test appointment to canceled_medical_test_appointments table.
    private function handleCanceledMedicalTestAppointment($appointment)
    {
        DB::table('canceled_medical_test_appointments')->insert([
            'pat_id' => $appointment->pat_id,
            'test_id' => $appointment->test_id,
            'doc_id' => $appointment->doc_id,
            'appoint_date' => $appointment->appoint_date,
            'appoint_time' => $appointment->appoint_time,
            'canceled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointment->delete();
    }

    /**
     * @OA\Get(
     *     path="/api/medical-test-appointments/upcoming",
     *     summary="Retrieve upcoming medical test appointments for the authenticated patient",
     *     tags={"Medical Test Appointments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Upcoming medical test appointments retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="appointment_details", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="appointment_id", type="integer", example=1),
     *                     @OA\Property(property="test_name", type="string", example="Blood Test"),
     *                     @OA\Property(property="appointment_date", type="string", format="date", example="2025-07-10"),
     *                     @OA\Property(property="appointment_time", type="string", example="14:00")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Upcoming medical test appointments retrieved successfully"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated. Please log in."),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized action",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only patients can perform this action."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No upcoming appointments found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No upcoming medical test appointments found for this patient."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Retrieve upcoming medical test appointments for the authenticated patient.
    public function getUpcomingMedicalTestAppointments()
    {
        $patient = $this->ensurePatient();

        // Fetch upcoming appointments for the patient from medical_test_appointments table
        $appointments = MedicalTestAppointment::where('pat_id', $patient->id)
            ->with([
                'medicalTest' => fn ($query) => $query->select('id', 'test_name'),
            ])
            ->get();

        // If no appointments found
        if ($appointments->isEmpty()) {
            return $this->errorResponse('No upcoming medical test appointments found for this patient.', 404);
        }

        // Prepare the appointment details array
        $appointmentDetails = $appointments->map(function ($appointment) {
            return [
                'appointment_id' => $appointment->id,
                'test_name' => $appointment->medicalTest->test_name,
                'appointment_date' => $appointment->appoint_date,
                'appointment_time' => $appointment->appoint_time,
            ];
        })->all();

        return $this->successResponse([
            'appointment_details' => $appointmentDetails,
            'message' => 'Upcoming medical test appointments retrieved successfully',
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/medical-test-appointments/canceled",
     *     summary="Retrieve canceled medical test appointments for the authenticated patient",
     *     tags={"Medical Test Appointments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Canceled medical test appointments retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="appointment_details", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="appointment_id", type="integer", example=1),
     *                     @OA\Property(property="test_name", type="string", example="Blood Test"),
     *                     @OA\Property(property="appointment_date", type="string", format="date", example="2025-07-10"),
     *                     @OA\Property(property="appointment_time", type="string", example="14:00")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Canceled medical test appointments retrieved successfully"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated. Please log in."),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized action",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only patients can perform this action."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No canceled appointments found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No canceled medical test appointments found for this patient."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Retrieve canceled medical test appointments for the authenticated patient.
    public function getCanceledMedicalTestAppointments()
    {
        $patient = $this->ensurePatient();

        // Fetch canceled appointments for the patient with related medical test data
        $appointments = DB::table('canceled_medical_test_appointments')
            ->where('pat_id', $patient->id)
            ->join('medical_tests', 'canceled_medical_test_appointments.test_id', '=', 'medical_tests.id')
            ->select(
                'canceled_medical_test_appointments.*',
                'medical_tests.test_name as test_name'
            )
            ->get();

        // If no canceled appointments found
        if ($appointments->isEmpty()) {
            return $this->errorResponse('No canceled medical test appointments found for this patient.', 404);
        }

        // Prepare the appointment details array
        $appointmentDetails = $appointments->map(function ($appointment) {
            return [
                'appointment_id' => $appointment->id,
                'test_name' => $appointment->test_name,
                'appointment_date' => $appointment->appoint_date,
                'appointment_time' => $appointment->appoint_time,
            ];
        })->all();

        return $this->successResponse([
            'appointment_details' => $appointmentDetails,
            'message' => 'Canceled medical test appointments retrieved successfully',
        ], 200);
    }
}
