<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthPatientRequest;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PatientController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/patient-group/patients",
     *     summary="Get all patients",
     *     tags={"Patients"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved all patients",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="patients", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="fullName", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="patient@example.com"),
     *                     @OA\Property(property="gender", type="string", nullable=true, example="male"),
     *                     @OA\Property(property="phoneNumber", type="string", nullable=true, example="+1234567890"),
     *                     @OA\Property(property="address", type="string", nullable=true, example="123 Main St"),
     *                     @OA\Property(property="image", type="object", nullable=true,
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="image_name", type="string", example="http://example.com/storage/images/patient_img/patient_1.jpg")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Successfully retrieved all patients"),
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
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to view patients list"),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     )
     * )
     */

    // Display a listing of the resource.
    public function index()
    {
        if (! Gate::allows('viewAny', Patient::class)) {
            return response()->json([
                'message' => 'You are not authorized to view patients list',
                'status' => 403,
            ], 403);
        }

        // $patients = Patient::all();

        $patients = Patient::with('image')->get();

        $patients->each(function ($patient) {
            if ($patient->image) {
                $patient->image->image_name = env('APP_URL').'/storage/'.$patient->image->image_name;
            }
        });

        return response()->json([
            'patients' => $patients,
            'message' => 'Successfully retrieved all patients',
            'status' => 200,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/patient-group/patient/register",
     *     summary="Register a new patient",
     *     tags={"Patients"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"fullName", "email", "password", "DateofBirth"},
     *
     *                 @OA\Property(property="fullName", type="string", example="John Doe", description="Full name of the patient"),
     *                 @OA\Property(property="email", type="string", format="email", example="patient@example.com", description="Patient's email address"),
     *                 @OA\Property(property="password", type="string", example="Password123!", description="Password (min 8 characters, must include uppercase, lowercase, number, and special character)"),
     *                 @OA\Property(property="DateofBirth", type="string", format="date", example="1990-01-01", description="Patient's date of birth"),
     *                 @OA\Property(property="gender", type="string", nullable=true, example="male", description="Patient's gender (male, female, other)"),
     *                 @OA\Property(property="phoneNumber", type="string", nullable=true, example="+1234567890", description="Patient's phone number (7 to 11 digits)"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="123 Main St", description="Patient's address"),
     *                 @OA\Property(property="image", type="file", format="binary", description="Profile image (png, jpg, jpeg, gif, max 2MB)")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Patient registered successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="patient", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="fullName", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="patient@example.com"),
     *                 @OA\Property(property="gender", type="string", nullable=true, example="male"),
     *                 @OA\Property(property="phoneNumber", type="string", nullable=true, example="+1234567890"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="123 Main St"),
     *                 @OA\Property(property="image", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="image_name", type="string", example="http://example.com/storage/images/patient_img/patient_1.jpg")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="You have been registered successfully"),
     *             @OA\Property(property="status", type="integer", example=201)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized action",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only unauthenticated users can register."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="status", type="integer", example=422)
     *         )
     *     )
     * )
     */

    // Store a newly created resource in storage.
    public function store(AuthPatientRequest $request)
    {
        // Check that the user is not authenticated in patient guard or any other guard
        if (auth('patient')->check() || auth('sanctum')->check()) {
            return response()->json([
                'message' => 'Unauthorized. Only unauthenticated users can register.',
                'status' => 403,
            ], 403);
        }

        if (! Gate::allows('create-patient')) {
            return response()->json([
                'message' => 'Unauthorized. Only unauthenticated users can register.',
                'status' => 403,
            ], 403);
        }

        $validate = $request->validated();

        // Hash the password before creating the patient
        $validate['password'] = Hash::make($validate['password']);

        // Create the patient using only the validated fields
        $patient = Patient::create($validate);

        // Store the image in the public disk (optional, with default if none)
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images/patient_img', 'public');
            $patient->image()->create(['image_name' => $imagePath]);
        } else {
            $patient->image()->create(['image_name' => 'images/patient_img/default.png']);
        }

        // Load the patient with the image data
        $patient = Patient::with('image')->find($patient->id);

        // Update the image path if exists
        if ($patient->image) {
            $patient->image->image_name = env('APP_URL').'/storage/'.$patient->image->image_name;
        }

        return response()->json([
            'patient' => $patient,
            'message' => 'You have been registered successfully',
            'status' => 201,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/patient-group/patients/{id}",
     *     summary="Get a specific patient",
     *     tags={"Patients"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the patient",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved patient",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="patient", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="fullName", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="patient@example.com"),
     *                 @OA\Property(property="gender", type="string", nullable=true, example="male"),
     *                 @OA\Property(property="phoneNumber", type="string", nullable=true, example="+1234567890"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="123 Main St"),
     *                 @OA\Property(property="image", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="image_name", type="string", example="http://example.com/storage/images/patient_img/patient_1.jpg")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Successfully retrieved patient"),
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
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to view this patient."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Patient not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Patient not found"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Display the specified resource.
    public function show(string $id)
    {
        $patient = Patient::with('image')->find($id);

        if (! $patient) {
            return response()->json([
                'message' => 'Patient not found',
                'status' => 404,
            ], 404);
        }

        if (! Gate::allows('view', $patient)) {
            return response()->json([
                'message' => 'You are not authorized to view this patient.',
                'status' => 403,
            ], 403);
        }

        if ($patient->image) {
            $patient->image->image_name = env('APP_URL').'/storage/'.$patient->image->image_name;
        }

        return response()->json([
            'patient' => $patient,
            'message' => 'Successfully retrieved patient',
            'status' => 200,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/patient-group/patient/update/{id}",
     *     summary="Update a specific patient",
     *     tags={"Patients"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the patient",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="fullName", type="string", example="John Doe", description="Full name of the patient"),
     *                 @OA\Property(property="password", type="string", example="Password123!", description="Password (min 8 characters, must include uppercase, lowercase, number, and special character)"),
     *                 @OA\Property(property="gender", type="string", nullable=true, example="male", description="Patient's gender (male, female, other)"),
     *                 @OA\Property(property="phoneNumber", type="string", nullable=true, example="+1234567890", description="Patient's phone number (7 to 11 digits)"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="123 Main St", description="Patient's address"),
     *                 @OA\Property(property="image", type="file", format="binary", description="Profile image (png, jpg, jpeg, gif, max 2MB)")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Patient updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="patient", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="fullName", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="patient@example.com"),
     *                 @OA\Property(property="gender", type="string", nullable=true, example="male"),
     *                 @OA\Property(property="phoneNumber", type="string", nullable=true, example="+1234567890"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="123 Main St"),
     *                 @OA\Property(property="image", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="image_name", type="string", example="http://example.com/storage/images/patient_img/patient_1.jpg")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Patient updated successfully"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid image file",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Invalid image file uploaded. Please check file type or size."),
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
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized action or restricted fields",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="You are not authorized to update email."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Patient not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Patient not found"),
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
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="status", type="integer", example=422)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Failed to store the image. Check folder permissions or disk space."),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
     *     )
     * )
     */

    // Update the specified resource in storage.
    public function update(Request $request, string $id)
    {
        $patient = Patient::find($id);

        if (! $patient) {
            return response()->json([
                'message' => 'Patient not found',
                'status' => 404,
            ], 404);
        }

        $patient = Patient::findOrFail($id);
        if (! Gate::allows('update', $patient)) {
            return response()->json([
                'message' => 'You are not authorized to update this patient.',
                'status' => 403,
            ], 403);
        }

        // Convert allowed values ​​to lowercase for verification
        $allowedGenders = array_map('strtolower', Patient::$genders);

        $request->validate([
            'fullName' => 'sometimes|string|min:3|max:50|regex:/^[a-zA-Z\s]+$/',
            'password' => [
                'sometimes',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
            ],
            'gender' => 'nullable|in:'.implode(',', $allowedGenders),
            'phoneNumber' => 'nullable|string||regex:/^\+?[0-9]{7,11}$/',
            'address' => 'nullable|string',
            'image' => [
                'nullable',
                'file',
                'mimes:png,jpg,jpeg,gif',
                'max:2048', // Maximum 2MB (2048KB)
            ],
        ],
            [
                'password.regex' => 'The password must contain an uppercase letter, a lowercase letter, a number, and a special character.',
                'phoneNumber.regex' => 'Invalid phone number. Please enter a valid phone number (7 to 11 digits).',
                'image.file' => 'The profile image must be a file.',
                'image.mimes' => 'The profile image must be a file of type: png, jpg, jpeg, gif.',
                'image.max' => 'The profile image must not exceed 2MB.',
            ]
        );

        // Check if email or DateofBirth is provided in the request and collect error messages
        $errors = [];
        if ($request->has('email')) {
            $errors[] = 'You are not authorized to update email.';
        }
        if ($request->has('DateofBirth')) {
            $errors[] = 'You are not authorized to update DateofBirth.';
        }

        // If there are any errors, return them
        if (! empty($errors)) {
            return response()->json([
                'message' => implode(' ', $errors), // Join messages with a space
                'status' => 403,
            ], 403);
        }

        $patient->update($request->only([
            'fullName',
            'password',
            'gender',
            'phoneNumber',
            'address',
        ]));

        // Process the image if it exists
        if ($request->hasFile('image')) {
            if ($request->file('image')->isValid()) {
                $oldImage = $patient->image;
                $newImagePath = $request->file('image')->store('images/patient_img', 'public');
                if ($newImagePath) { // Check if store was successful
                    if ($oldImage) {
                        if (Storage::disk('public')->exists($oldImage->image_name)) {
                            Storage::disk('public')->delete($oldImage->image_name);
                        }
                        $oldImage->update(['image_name' => $newImagePath]);
                    } else {
                        $patient->image()->create(['image_name' => $newImagePath]);
                    }
                } else {
                    return response()->json([
                        'message' => 'Failed to store the image. Check folder permissions or disk space.',
                        'status' => 500,
                    ], 500);
                }
            } else {
                return response()->json([
                    'message' => 'Invalid image file uploaded. Please check file type or size.',
                    'status' => 400,
                ], 400);
            }
        }

        // Bring the patient with the image data after the update
        $patient = Patient::with('image')->find($id);

        // Update the image path if exists
        if ($patient->image) {
            $patient->image->image_name = env('APP_URL').'/storage/'.$patient->image->image_name;
        }

        return response()->json([
            'patient' => $patient,
            'message' => 'Patient updated successfully',
            'status' => 200,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/patient-group/patient/destroy/{id}",
     *     summary="Attempt to permanently delete a patient",
     *     tags={"Patients"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the patient",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Permanent deletion not allowed",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Permanent deletion of patient accounts is not allowed."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     )
     * )
     */

    // Remove the specified resource from storage.
    public function destroy(string $id)
    {
        return response()->json([
            'message' => 'Permanent deletion of patient accounts is not allowed.',
            'status' => 403,
        ], 403);
    }

    /**
     * @OA\Get(
     *     path="/api/patient-group/patient/me",
     *     summary="Get current authenticated patient's data",
     *     tags={"Patients"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved current patient data",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="patient", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="fullName", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="patient@example.com"),
     *                 @OA\Property(property="gender", type="string", nullable=true, example="male"),
     *                 @OA\Property(property="phoneNumber", type="string", nullable=true, example="+1234567890"),
     *                 @OA\Property(property="address", type="string", nullable=true, example="123 Main St"),
     *                 @OA\Property(property="image", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="image_name", type="string", example="http://example.com/storage/images/patient_img/patient_1.jpg")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Successfully retrieved current patient data"),
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
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
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
     *             @OA\Property(property="message", type="string", example="Unauthorized. You are not a patient."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     )
     * )
     */

    // View current patient data (logged in)
    public function showCurrentPatient(Request $request)
    {
        // Get current patient (logged in)
        $patient = Auth::user();

        // Verify that the logged in user is a patient.
        if (! $patient instanceof Patient) {
            return response()->json([
                'message' => 'Unauthorized. You are not a patient.',
            ], 403);
        }

        // Load the image relationship
        $patient->load('image');

        // Modify image path if it exists
        if ($patient->image) {
            $patient->image->image_name = env('APP_URL').'/storage/'.$patient->image->image_name;
        }

        // Return patient data
        return response()->json([
            'patient' => $patient,
            'message' => 'Successfully retrieved current patient data',
            'status' => 200,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/patient-group/favorite/toggle",
     *     summary="Toggle a doctor as favorite for the authenticated patient",
     *     tags={"Patients"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"doctor_id"},
     *
     *             @OA\Property(property="doctor_id", type="integer", example=1, description="ID of the doctor to add or remove from favorites")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Doctor added or removed from favorites",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Doctor added to favorites"),
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
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
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
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only patients can manage favorite doctors."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="status", type="integer", example=422)
     *         )
     *     )
     * )
     */

    // Toggle a doctor as favorite for the authenticated patient
    public function toggleFavorite(Request $request)
    {
        $patient = Auth::user(); // The patient is logged in (assuming he is Patient)

        // Verify that the user is only a patient
        if (! $patient instanceof Patient) {
            return response()->json([
                'message' => 'Unauthorized. Only patients can manage favorite doctors.',
                'status' => 403,
            ], 403);
        }

        // Input verification
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        $doctorId = $request->input('doctor_id');

        // Check if the doctor is in your favorites
        if ($patient->favoriteDoctors()->where('doctor_id', $doctorId)->exists()) {
            // If it exists, delete it.
            $patient->favoriteDoctors()->detach($doctorId);

            return response()->json([
                'message' => 'Doctor removed from favorites',
                'status' => 200,
            ], 200);
        } else {
            // If it is not available, add it.
            $patient->favoriteDoctors()->attach($doctorId);

            return response()->json([
                'message' => 'Doctor added to favorites',
                'status' => 200,
            ], 200);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/patient-group/favorite/doctors",
     *     summary="Get the list of favorite doctors for the authenticated patient",
     *     tags={"Patients"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Favorite doctors retrieved successfully or no favorite doctors found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="favorite_doctors", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="firstName", type="string", example="Jane"),
     *                     @OA\Property(property="lastName", type="string", example="Smith"),
     *                     @OA\Property(property="image", type="object", nullable=true,
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="image_name", type="string", example="http://example.com/storage/images/doctor_img/doctor_1.jpg")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Favorite doctors retrieved successfully"),
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
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
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
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only patients can view favorite doctors."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     )
     * )
     */

    // Get the list of favorite doctors for the authenticated patient
    public function getFavoriteDoctors()
    {
        $patient = Auth::user();

        // Verify that the user is only a patient
        if (! $patient instanceof Patient) {
            return response()->json([
                'message' => 'Unauthorized. Only patients can view favorite doctors.',
                'status' => 403,
            ], 403);
        }

        // Retrieve favorite doctors with image data
        $favorites = $patient->favoriteDoctors()
            ->with(['image' => function ($query) {
                $query->select('id', 'doctor_id', 'image_name');
            }])
            ->get();

        // Edit image path for each doctor
        $favorites->each(function ($doctor) {
            if ($doctor->image) {
                $doctor->image->image_name = env('APP_URL').'/storage/'.$doctor->image->image_name;
            }
        });

        // Check if the list is empty
        if ($favorites->isEmpty()) {
            return response()->json([
                'message' => 'No favorite doctors found',
                'status' => 200,
            ], 200);
        }

        return response()->json([
            'favorite_doctors' => $favorites,
            'message' => 'Favorite doctors retrieved successfully',
            'status' => 200,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/patient-group/emergency/call",
     *     summary="Retrieve emergency contact number for patients",
     *     tags={"Patients"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Emergency number retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="emergency_number", type="string", example="111"),
     *             @OA\Property(property="message", type="string", example="Emergency number retrieved successfully. Please call 111."),
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
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
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
     *             @OA\Property(property="message", type="string", example="Unauthorized. You are not a patient."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     )
     * )
     */

    // Emergency call endpoint for patients
    public function emergencyCall(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if user is authenticated
        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'status' => 401,
            ], 401);
        }

        // Verify that the user is a patient
        if (! ($user instanceof Patient)) {
            return response()->json([
                'message' => 'Unauthorized. You are not a patient.',
                'status' => 403,
            ], 403);
        }

        $emergencyNumber = '111';

        // Return the emergency number
        return response()->json([
            'emergency_number' => $emergencyNumber,
            'message' => "Emergency number retrieved successfully. Please call $emergencyNumber.",
            'status' => 200,
        ], 200);
    }
}
