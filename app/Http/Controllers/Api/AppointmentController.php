<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DoctorInformation;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/appointments/book",
     *     summary="Book an appointment for a patient with a doctor",
     *     tags={"Appointments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="doc_id", type="integer", example=1, description="ID of the doctor"),
     *             @OA\Property(property="appoint_time", type="string", example="14:00", description="Appointment time in 24-hour format (H:i)"),
     *             @OA\Property(property="appoint_date", type="string", format="date", example="2025-07-10", description="Appointment date in Y-m-d format")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Appointment booked successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="appointment", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="pat_id", type="integer", example=1),
     *                 @OA\Property(property="doc_id", type="integer", example=1),
     *                 @OA\Property(property="appoint_time", type="string", example="14:00"),
     *                 @OA\Property(property="appoint_date", type="string", example="2025-07-10")
     *             ),
     *             @OA\Property(property="message", type="string", example="Appointment booked successfully"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input or time not available",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Selected time is not available in the doctor's schedule."),
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
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only patients can perform this action."),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Doctor or schedule not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Doctor information not found."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=409,
     *         description="Appointment slot already booked",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="This appointment slot is already booked."),
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
     *             @OA\Property(property="status", type="integer", example=422),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    // Book an appointment for a patient with a doctor.
    public function bookAppointment(Request $request)
    {
        $patient = $this->ensurePatient();

        $data = $this->validateRequest($request, [
            'doc_id' => 'required|exists:doctors,id',
            'appoint_time' => 'required|date_format:G:i',
            'appoint_date' => 'required|date',
        ]);

        // If validation fails, return the JsonResponse
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $doctorInfo = DoctorInformation::where('doctor_id', $data['doc_id'])
            ->select('doctor_id', 'schedule')
            ->first();

        if (! $doctorInfo) {
            return $this->errorResponse('Doctor information not found.', 404);
        }

        $schedule = $this->parseSchedule($doctorInfo->schedule);
        if (empty($schedule)) {
            return $this->errorResponse('Doctor schedule not found.', 404);
        }

        // Normalize the appoint_time to H:i format (EX: "2:00" -> "02:00")
        $appointTime = Carbon::createFromFormat('G:i', $data['appoint_time'])->format('H:i');
        $appointmentDateTime = Carbon::createFromFormat('Y-m-d H:i', "{$data['appoint_date']} {$appointTime}");

        if ($appointmentDateTime->isPast()) {
            return $this->errorResponse('Cannot book an appointment in the past.', 400);
        }

        // Get the day of the week for the appointment date
        $dayOfWeek = Carbon::parse($data['appoint_date'])->format('l'); // EX: "Wednesday"

        // Check if the day has a schedule
        if (! isset($schedule[$dayOfWeek]) || empty($schedule[$dayOfWeek])) {
            return $this->errorResponse("No available schedule for $dayOfWeek.", 400);
        }

        // Convert schedule times to 24-hour format for comparison
        $availableTimes = array_map(function ($time) {
            $time = trim($time); // Clean extra spaces
            try {
                // Parse as 12-hour format (EX: "1:00 PM")
                return Carbon::createFromFormat('h:i A', $time)->format('H:i');
            } catch (\Exception $e) {
                // Log the error and skip invalid times
                Log::warning('Invalid time format in schedule: '.$time.' - Error: '.$e->getMessage());

                return null;
            }
        }, $schedule[$dayOfWeek]);

        // Remove any null values from parsing errors
        $availableTimes = array_filter($availableTimes, fn ($time) => ! is_null($time));

        // Check if there are any valid times
        if (empty($availableTimes)) {
            return $this->errorResponse('No valid times found in the doctor\'s schedule.', 400);
        }

        // Check if the requested time is in the schedule
        if (! in_array($appointTime, $availableTimes)) {
            return $this->errorResponse('Selected time is not available in the doctor\'s schedule.', 400);
        }

        // Check for existing appointment
        if (Appointment::where('doc_id', $data['doc_id'])
            ->where('appoint_date', $data['appoint_date'])
            ->where('appoint_time', $appointTime)
            ->exists()
        ) {
            return $this->errorResponse('This appointment slot is already booked.', 409);
        }

        $appointment = Appointment::create([
            'pat_id' => $patient->id,
            'doc_id' => $data['doc_id'],
            'appoint_time' => $appointTime,
            'appoint_date' => $data['appoint_date'],
        ]);

        return $this->successResponse([
            'appointment' => $appointment,
            'message' => 'Appointment booked successfully',
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/appointments/cancel/{appointmentId}",
     *     summary="Cancel an appointment",
     *     tags={"Appointments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="appointmentId",
     *         in="path",
     *         required=true,
     *         description="ID of the appointment to cancel",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Appointment cancelled successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Appointment cancelled successfully"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Cannot cancel a past appointment",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Cannot cancel a past appointment."),
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
     *     )
     * )
     */

    // Cancel an appointment.
    public function cancelAppointment($appointmentId)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if there's an authenticated user
        if (! $user) {
            return $this->errorResponse('Unauthenticated. Please log in.', 401);
        }

        // Find the appointment
        $appointment = Appointment::where('id', $appointmentId)->first();

        // If appointment doesn't exist
        if (! $appointment) {
            return $this->errorResponse('Appointment not found.', 404);
        }

        // Check if the user is a Patient and owns the appointment
        if ($user instanceof Patient && $appointment->pat_id === $user->id) {
            $appointmentDateTime = Carbon::parse("{$appointment->appoint_date} {$appointment->appoint_time}");
            if ($appointmentDateTime->isPast()) {
                return $this->errorResponse('Cannot cancel a past appointment.', 400);
            }

            // Handle the canceled appointment (transfer to canceled_appointments and delete)
            $this->handleCanceledAppointment($appointment);

            return $this->successResponse([
                'message' => 'Appointment cancelled successfully',
            ], 200);
        }

        // If user is not a Patient or doesn't own the appointment
        return $this->errorResponse('You do not have permission to cancel this appointment.', 403);
    }

    /**
     * @OA\Get(
     *     path="/api/appointments/doctors/available/{docId}",
     *     summary="Get available appointments for a doctor on a specific date",
     *     tags={"Appointments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="docId",
     *         in="path",
     *         required=true,
     *         description="ID of the doctor",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         description="Date to check available appointments (Y-m-d format, defaults to today)",
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
     *             @OA\Property(property="date", type="string", example="2025-07-10"),
     *             @OA\Property(property="message", type="string", example="Available appointments retrieved successfully"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid date or no schedule available",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No available schedule for Wednesday."),
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
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only patients can perform this action."),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Doctor or schedule not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Doctor not found."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Get available appointments for a doctor on a specific date.
    public function getAvailableAppointments(Request $request, $docId)
    {
        $patient = $this->ensurePatient();

        // Check if doctor exists
        $doctorInfo = DoctorInformation::where('doctor_id', $docId)
            ->select('doctor_id', 'schedule')
            ->first();

        if (! $doctorInfo) {
            return $this->errorResponse('Doctor not found.', 404);
        }

        $schedule = $this->parseSchedule($doctorInfo->schedule);
        if (empty($schedule)) {
            return $this->errorResponse('Doctor schedule not found.', 404);
        }

        $appointDate = $request->query('date', Carbon::today()->toDateString());
        $parsedDate = Carbon::parse($appointDate);

        if ($parsedDate->isPast() && ! $parsedDate->isToday()) {
            return $this->errorResponse('Cannot retrieve appointments for past dates.', 400);
        }

        // Get the day of the week for the appointment date
        $dayOfWeek = Carbon::parse($appointDate)->format('l'); // EX: "Wednesday"

        // Check if the day has a schedule
        if (! isset($schedule[$dayOfWeek]) || empty($schedule[$dayOfWeek])) {
            return $this->errorResponse("No available schedule for $dayOfWeek.", 400);
        }

        // Convert schedule times to 24-hour format
        $availableTimes = array_map(function ($time) {
            $time = trim($time); // Clean extra spaces
            try {
                // Parse as 12-hour format (EX: "1:00 PM")
                return Carbon::createFromFormat('h:i A', $time)->format('H:i');
            } catch (\Exception $e) {
                // Log the error and skip invalid times
                Log::warning('Invalid time format in schedule: '.$time.' - Error: '.$e->getMessage());

                return null;
            }
        }, $schedule[$dayOfWeek]);

        // Remove any null values from parsing errors
        $availableTimes = array_filter($availableTimes, fn ($time) => ! is_null($time));

        // Check if there are any valid times
        if (empty($availableTimes)) {
            return $this->errorResponse('No valid times found in the doctor\'s schedule.', 400);
        }

        // Get booked appointments
        $bookedAppointments = Appointment::where('doc_id', $docId)
            ->where('appoint_date', $appointDate)
            ->pluck('appoint_time')
            ->toArray();

        // Filter available times
        $availableAppointments = array_filter($availableTimes, function ($time) use ($bookedAppointments, $appointDate) {
            $appointmentDateTime = Carbon::createFromFormat('Y-m-d H:i', "$appointDate $time");

            return ! in_array($time, $bookedAppointments) && ! $appointmentDateTime->isPast();
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

    /**
     * Parse the doctor's schedule from JSON or array.
     */
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

    // Handle the transfer of a canceled appointment to canceled_appointments table.
    private function handleCanceledAppointment($appointment)
    {
        DB::table('canceled_appointments')->insert([
            'pat_id' => $appointment->pat_id,
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
     *     path="/api/appointments/upcoming",
     *     summary="Retrieve upcoming appointments for the authenticated patient",
     *     tags={"Appointments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Upcoming appointments retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="appointment_details", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="appointment_id", type="integer", example=1),
     *                     @OA\Property(property="doctor_image", type="string", nullable=true, example="http://your-app-url/storage/doctor_images/doctor.jpg"),
     *                     @OA\Property(property="doctor_name", type="string", example="John Doe"),
     *                     @OA\Property(property="specialization", type="string", nullable=true, example="Cardiology"),
     *                     @OA\Property(property="appointment_date", type="string", example="2025-07-10"),
     *                     @OA\Property(property="appointment_time", type="string", example="14:00")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Upcoming appointments retrieved successfully"),
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
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only patients can perform this action."),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No appointments found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No upcoming appointments found for this patient."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Retrieve upcoming appointments for the authenticated patient (Doctor's image, name, specialization, and appointment time).
    public function getPatientAppointments()
    {
        $patient = $this->ensurePatient();

        // Fetch appointments for the patient from appointments table with related doctor data
        $appointments = Appointment::where('pat_id', $patient->id)
            ->with([
                'doctor' => fn ($query) => $query->select('id', 'firstName', 'lastName', 'spec_id')
                    ->with([
                        'image' => fn ($query) => $query->select('id', 'doctor_id', 'image_name'),
                        'specialization' => fn ($query) => $query->select('id', 'name'),
                    ]),
            ])
            ->get();

        // If no appointments found
        if ($appointments->isEmpty()) {
            return $this->errorResponse('No upcoming appointments found for this patient.', 404);
        }

        // Prepare the appointment details array
        $appointmentDetails = $appointments->map(function ($appointment) {
            $doctorImage = $appointment->doctor->image
                ? env('APP_URL').'/storage/'.$appointment->doctor->image->image_name
                : null;

            return [
                'appointment_id' => $appointment->id, // For cancel action
                'doctor_image' => $doctorImage,
                'doctor_name' => $appointment->doctor->firstName.' '.$appointment->doctor->lastName,
                'specialization' => $appointment->doctor->specialization ? $appointment->doctor->specialization->name : null,
                'appointment_date' => $appointment->appoint_date,
                'appointment_time' => $appointment->appoint_time,
            ];
        })->all();

        return $this->successResponse([
            'appointment_details' => $appointmentDetails,
            'message' => 'Upcoming appointments retrieved successfully',
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/appointments/canceled",
     *     summary="Retrieve canceled appointments for the authenticated patient",
     *     tags={"Appointments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Canceled appointments retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="appointment_details", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="appointment_id", type="integer", example=1),
     *                     @OA\Property(property="doctor_image", type="string", nullable=true, example="http://your-app-url/storage/doctor_images/doctor.jpg"),
     *                     @OA\Property(property="doctor_name", type="string", example="John Doe"),
     *                     @OA\Property(property="specialization", type="string", nullable=true, example="Cardiology"),
     *                     @OA\Property(property="appointment_date", type="string", example="2025-07-10"),
     *                     @OA\Property(property="appointment_time", type="string", example="14:00")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Canceled appointments retrieved successfully"),
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
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only patients can perform this action."),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No canceled appointments found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No canceled appointments found for this patient."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Retrieve canceled appointments for the authenticated patient (Doctor's image, name, specialization, and appointment time).
    public function getCanceledAppointments()
    {
        $patient = $this->ensurePatient();

        // Fetch canceled appointments for the patient with related doctor data
        $appointments = DB::table('canceled_appointments')
            ->where('pat_id', $patient->id)
            ->join('doctors', 'canceled_appointments.doc_id', '=', 'doctors.id')
            ->leftJoin('doctor_images', 'doctors.id', '=', 'doctor_images.doctor_id')
            ->leftJoin('specializations', 'doctors.spec_id', '=', 'specializations.id')
            ->select(
                'canceled_appointments.*',
                'doctors.firstName',
                'doctors.lastName',
                'doctor_images.image_name',
                'specializations.name as specialization_name'
            )
            ->get();

        // If no canceled appointments found
        if ($appointments->isEmpty()) {
            return $this->errorResponse('No canceled appointments found for this patient.', 404);
        }

        // Prepare the appointment details array
        $appointmentDetails = $appointments->map(function ($appointment) {
            $doctorImage = $appointment->image_name
                ? env('APP_URL').'/storage/'.$appointment->image_name
                : null;

            return [
                'appointment_id' => $appointment->id,
                'doctor_image' => $doctorImage,
                'doctor_name' => $appointment->firstName.' '.$appointment->lastName,
                'specialization' => $appointment->specialization_name,
                'appointment_date' => $appointment->appoint_date,
                'appointment_time' => $appointment->appoint_time,
            ];
        })->all();

        return $this->successResponse([
            'appointment_details' => $appointmentDetails,
            'message' => 'Canceled appointments retrieved successfully',
        ], 200);
    }
}
