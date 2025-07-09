<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Models\Doctor;
use App\Models\DoctorArchive;
use App\Models\DoctorImageArchive;
use App\Models\DoctorInformationArchive;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Schema(
 *     schema="DoctorSchedule",
 *     type="object",
 *
 *     @OA\Property(property="Monday", type="array", @OA\Items(type="string", example="10:00 AM, 1:00PM, 4:00PM")),
 *     @OA\Property(property="Tuesday", type="array", @OA\Items(type="string", example="9:00 AM, 1:00PM, 4:00PM"))
 * )
 */
class DoctorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/doctors-group/doctors",
     *     summary="List all doctors",
     *     tags={"Doctors"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved all doctors",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="doctors", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="firstName", type="string", example="John"),
     *                     @OA\Property(property="lastName", type="string", example="Doe"),
     *                     @OA\Property(property="phoneNumber", type="string", example="+1234567890"),
     *                     @OA\Property(property="rating", type="number", format="float", example=4.5),
     *                     @OA\Property(property="image", type="object", nullable=true,
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="doctor_id", type="integer", example=1),
     *                         @OA\Property(property="image_name", type="string", example="http://your-app-url/storage/images/doctor_img/doctor.jpg")
     *                     ),
     *                     @OA\Property(property="information", type="object", nullable=true,
     *                         @OA\Property(property="about", type="string", example="Experienced cardiologist"),
     *                         @OA\Property(property="experience", type="integer", example=10),
     *                         @OA\Property(property="number_of_patients", type="integer", example=500),
     *                         @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule"),
     *                         @OA\Property(property="salary", type="number", example=5000)
     *                     ),
     *                     @OA\Property(property="specialization_name", type="string", nullable=true, example="Cardiology"),
     *                     @OA\Property(property="city_name", type="string", nullable=true, example="New York"),
     *                     @OA\Property(property="user_role", type="string", nullable=true, example="doctor")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Successfully retrieved all doctors"),
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
     *             @OA\Property(property="message", type="string", example="Unauthenticated."),
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to view doctors list."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     )
     * )
     */

    // Display a listing of the resource.
    public function index()
    {
        // Only the owner can see all doctors
        if (Gate::allows('view-doctors')) {
            $doctors = Doctor::with(['image', 'information', 'user'])->get();

            $doctors->each(function ($doctor) {
                if ($doctor->image) {
                    $doctor->image->image_name = env('APP_URL').'/storage/'.$doctor->image->image_name;
                }
                $doctor->specialization_name = $doctor->specialization ? $doctor->specialization->name : null;
                $doctor->city_name = $doctor->city ? $doctor->city->name : null;
                $doctor->user_role = $doctor->user ? $doctor->user->role : null;
                unset($doctor->spec_id, $doctor->city_id, $doctor->user_id);
            });

            return response()->json([
                'doctors' => $doctors,
                'message' => 'Successfully retrieved all doctors',
                'status' => 200,
            ], 200);
        }

        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'status' => 401,
            ], 401);
        }

        // If the user is sick, he sees the doctors.
        if ($user instanceof Patient) {
            $doctors = Doctor::select('id', 'firstName', 'lastName', 'phoneNumber', 'rating', 'spec_id', 'city_id', 'user_id')
                ->with([
                    'image' => fn ($query) => $query->select('id', 'doctor_id', 'image_name'),
                    'information' => fn ($query) => $query->select('id', 'doctor_id', 'about', 'experience', 'number_of_patients', 'schedule', 'salary'),
                    'specialization' => fn ($query) => $query->select('id', 'name'),
                    'city' => fn ($query) => $query->select('id', 'name'),
                    'user' => fn ($query) => $query->select('id', 'role'),
                ])
                ->get();

            $doctors->each(function ($doctor) {
                if ($doctor->image) {
                    $doctor->image->image_name = env('APP_URL').'/storage/'.$doctor->image->image_name;
                }
                $doctor->specialization_name = $doctor->specialization ? $doctor->specialization->name : null;
                $doctor->city_name = $doctor->city ? $doctor->city->name : null;
                $doctor->user_role = $doctor->user ? $doctor->user->role : null;
                unset($doctor->spec_id, $doctor->city_id, $doctor->user_id);
            });

            return response()->json([
                'doctors' => $doctors,
                'message' => 'All doctors retrieved successfully for patient',
                'status' => 200,
            ], 200);
        }

        return response()->json([
            'message' => 'You are not authorized to view doctors list.',
            'status' => 403,
        ], 403);
    }

    /**
     * @OA\Post(
     *     path="/api/doctors-group/doctors",
     *     summary="Store a new doctor",
     *     tags={"Doctors"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="firstName", type="string", example="John", description="First name of the doctor"),
     *                 @OA\Property(property="lastName", type="string", example="Doe", description="Last name of the doctor"),
     *                 @OA\Property(property="username", type="string", example="johndoe", description="Unique username of the doctor"),
     *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Doctor's email"),
     *                 @OA\Property(property="password", type="string", example="Password123!", description="Doctor's password"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male", description="Doctor's gender"),
     *                 @OA\Property(property="phoneNumber", type="string", example="+1234567890", description="Doctor's phone number"),
     *                 @OA\Property(property="rating", type="number", example=4.5, description="Doctor's rating"),
     *                 @OA\Property(property="spec_id", type="integer", example=1, description="Specialization ID"),
     *                 @OA\Property(property="city_id", type="integer", example=1, description="City ID"),
     *                 @OA\Property(property="image", type="file", description="Doctor's profile image (PNG, JPG, JPEG, GIF, max 2MB)"),
     *                 @OA\Property(property="experience", type="integer", example=10, description="Years of experience"),
     *                 @OA\Property(property="number_of_patients", type="integer", example=500, description="Number of patients treated"),
     *                 @OA\Property(property="about", type="string", example="Experienced cardiologist", description="About the doctor"),
     *                 @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule", description="Doctor's schedule in JSON format"),
     *                 @OA\Property(property="salary", type="number", example=5000, description="Doctor's salary")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Doctor registered successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="doctor", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="firstName", type="string", example="John"),
     *                 @OA\Property(property="lastName", type="string", example="Doe"),
     *                 @OA\Property(property="image", type="object", nullable=true,
     *                     @OA\Property(property="image_name", type="string", example="http://your-app-url/storage/images/doctor_img/doctor.jpg")
     *                 ),
     *                 @OA\Property(property="information", type="object", nullable=true,
     *                     @OA\Property(property="about", type="string", example="Experienced cardiologist"),
     *                     @OA\Property(property="experience", type="integer", example=10),
     *                     @OA\Property(property="number_of_patients", type="integer", example=500),
     *                     @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule"),
     *                     @OA\Property(property="salary", type="number", example=5000)
     *                 ),
     *                 @OA\Property(property="specialization_name", type="string", nullable=true, example="Cardiology"),
     *                 @OA\Property(property="city_name", type="string", nullable=true, example="New York"),
     *                 @OA\Property(property="user_role", type="string", nullable=true, example="doctor")
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to add doctors."),
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
    public function store(AuthRequest $request)
    {
        if (Gate::allows('add-doctors', $request->user())) {
            $validate = $request->validated();

            // Hash the password before creating the doctor
            $validate['password'] = Hash::make($validate['password']);

            // Create the doctor using only the validated fields
            $doctor = Doctor::create($validate);

            // Store the image in the public disk
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('images/doctor_img', 'public');
                $doctor->image()->create([
                    'image_name' => $imagePath,
                ]);
            } else {
                $doctor->image()->create([
                    'image_name' => 'images/doctor_img/default.png',
                ]);
            }

            // Store the doctor information
            $doctor->information()->create(
                array_merge(
                    $request->only(['experience', 'number_of_patients', 'about', 'schedule', 'salary']),
                    ['number_of_patients' => $request->input('number_of_patients', 0)]
                )
            );

            $doctor = $doctor->load(['image', 'information']);
            $doctor->each(function ($doctor) {
                if ($doctor->image) {
                    $doctor->image->image_name = env('APP_URL').'/storage/'.$doctor->image->image_name;
                }
                $doctor->specialization_name = $doctor->specialization ? $doctor->specialization->name : null;
                $doctor->city_name = $doctor->city ? $doctor->city->name : null;
                $doctor->user_role = $doctor->user ? $doctor->user->role : null;
                unset($doctor->spec_id, $doctor->city_id, $doctor->user_id);
            });

            return response()->json([
                'doctor' => $doctor,
                'message' => 'You have been registered successfully',
                'status' => 201,
            ], 201);
        } else {
            return response()->json([
                'message' => 'You are not authorized to add doctors.',
                'status' => 403,
            ], 403);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/doctors-group/doctors/{id}",
     *     summary="Display a specific doctor (for doctors)",
     *     tags={"Doctors"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the doctor",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Doctor found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="doctor", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="firstName", type="string", example="John"),
     *                 @OA\Property(property="lastName", type="string", example="Doe"),
     *                 @OA\Property(property="image", type="object", nullable=true,
     *                     @OA\Property(property="image_name", type="string", example="http://your-app-url/storage/images/doctor_img/doctor.jpg")
     *                 ),
     *                 @OA\Property(property="information", type="object", nullable=true,
     *                     @OA\Property(property="about", type="string", example="Experienced cardiologist"),
     *                     @OA\Property(property="experience", type="integer", example=10),
     *                     @OA\Property(property="number_of_patients", type="integer", example=500),
     *                     @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule"),
     *                     @OA\Property(property="salary", type="number", example=5000)
     *                 ),
     *                 @OA\Property(property="specialization_name", type="string", nullable=true, example="Cardiology"),
     *                 @OA\Property(property="city_name", type="string", nullable=true, example="New York"),
     *                 @OA\Property(property="user_role", type="string", nullable=true, example="doctor")
     *             ),
     *             @OA\Property(property="message", type="string", example="Doctor found"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized action",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="You are not authorized to view this doctor."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Doctor not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Doctor not found"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Display the specified resource.
    public function show(string $id)
    {
        $doctor = Doctor::with(['image', 'information', 'user'])->find($id);

        if (! $doctor) {
            return response()->json([
                'message' => 'Doctor not found',
                'status' => 404,
            ], 404);
        }

        $user = request()->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'status' => 401,
            ], 401);
        }

        if (Gate::allows('view-doctor', $doctor)) {
            if ($doctor->image) {
                $doctor->image->image_name = env('APP_URL').'/storage/'.$doctor->image->image_name;
            }

            $doctor->specialization_name = $doctor->specialization ? $doctor->specialization->name : null;
            $doctor->city_name = $doctor->city ? $doctor->city->name : null;
            $doctor->user_role = $doctor->user ? $doctor->user->role : null;
            unset($doctor->spec_id, $doctor->city_id, $doctor->user_id);

            return response()->json([
                'doctor' => $doctor,
                'message' => 'Doctor found',
                'status' => 200,
            ], 200);
        }

        // The Patient sees specific data (showForPatient logic)
        if ($user instanceof Patient) {
            $doctor = Doctor::select('id', 'firstName', 'lastName', 'phoneNumber', 'rating', 'spec_id', 'city_id', 'user_id')
                ->with([
                    'image' => fn ($query) => $query->select('id', 'doctor_id', 'image_name'),
                    'information' => fn ($query) => $query->select('id', 'doctor_id', 'about', 'experience', 'number_of_patients', 'schedule', 'salary'),
                    'specialization' => fn ($query) => $query->select('id', 'name'),
                    'city' => fn ($query) => $query->select('id', 'name'),
                    'user' => fn ($query) => $query->select('id', 'role'),
                ])
                ->find($id);

            if (! $doctor) {
                return response()->json([
                    'message' => 'Doctor not found',
                    'status' => 404,
                ], 404);
            }

            if ($doctor->image) {
                $doctor->image->image_name = env('APP_URL').'/storage/'.$doctor->image->image_name;
            }

            $doctor->specialization_name = $doctor->specialization ? $doctor->specialization->name : null;
            $doctor->city_name = $doctor->city ? $doctor->city->name : null;
            $doctor->user_role = $doctor->user ? $doctor->user->role : null;
            unset($doctor->spec_id, $doctor->city_id, $doctor->user_id);

            return response()->json([
                'doctor' => $doctor,
                'message' => 'Doctor details retrieved successfully for patient',
                'status' => 200,
            ], 200);
        }

        return response()->json([
            'message' => 'You are not authorized to view this doctor.',
            'status' => 403,
        ], 403);
    }

    /**
     * @OA\Post(
     *     path="/api/doctors-group/doctors/update/{id}",
     *     summary="Update a doctor's information",
     *     tags={"Doctors"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the doctor to update",
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
     *                 @OA\Property(property="firstName", type="string", example="John", description="First name of the doctor"),
     *                 @OA\Property(property="lastName", type="string", example="Doe", description="Last name of the doctor"),
     *                 @OA\Property(property="username", type="string", example="johndoe", description="Unique username"),
     *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Email address"),
     *                 @OA\Property(property="password", type="string", example="Password123!", description="Password (min 8 chars, with upper, lower, number, special char)"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male", description="Gender"),
     *                 @OA\Property(property="phoneNumber", type="string", example="+1234567890", description="Phone number (7-11 digits)"),
     *                 @OA\Property(property="rating", type="number", example=4.5, description="Rating (0-5, owner only)"),
     *                 @OA\Property(property="spec_id", type="integer", example=1, description="Specialization ID"),
     *                 @OA\Property(property="city_id", type="integer", example=1, description="City ID"),
     *                 @OA\Property(property="image", type="file", description="Profile image (PNG, JPG, JPEG, GIF, max 2MB)"),
     *                 @OA\Property(property="experience", type="integer", example=10, description="Years of experience (owner only)"),
     *                 @OA\Property(property="number_of_patients", type="integer", example=500, description="Number of patients (owner only)"),
     *                 @OA\Property(property="about", type="string", example="Experienced cardiologist", description="About the doctor (owner only)"),
     *                 @OA\Property(property="salary", type="number", example=5000, description="Salary (owner only)"),
     *                 @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule", description="Schedule in JSON format (owner only)"),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Doctor updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="doctor", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="firstName", type="string", example="John"),
     *                 @OA\Property(property="lastName", type="string", example="Doe"),
     *                 @OA\Property(property="image", type="object", nullable=true,
     *                     @OA\Property(property="image_name", type="string", example="http://your-app-url/storage/images/doctor_img/doctor.jpg")
     *                 ),
     *                 @OA\Property(property="information", type="object", nullable=true,
     *                     @OA\Property(property="about", type="string", example="Experienced cardiologist"),
     *                     @OA\Property(property="experience", type="integer", example=10),
     *                     @OA\Property(property="number_of_patients", type="integer", example=500),
     *                     @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule"),
     *                     @OA\Property(property="salary", type="number", example=5000)
     *                 ),
     *                 @OA\Property(property="specialization_name", type="string", nullable=true, example="Cardiology"),
     *                 @OA\Property(property="city_name", type="string", nullable=true, example="New York"),
     *                 @OA\Property(property="user_role", type="string", nullable=true, example="doctor")
     *             ),
     *             @OA\Property(property="message", type="string", example="The doctor has been updated successfully"),
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
     *             @OA\Property(property="message", type="string", example="Invalid image file uploaded."),
     *             @OA\Property(property="status", type="integer", example=400)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized action",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="You are not authorized to update this doctor."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Doctor not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Doctor not found"),
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
     *     )
     * )
     */

    // Update the specified resource in storage.
    public function update(Request $request, $id)
    {
        $doctor = Doctor::find($id);
        if (! $doctor) {
            return response()->json([
                'message' => 'Doctor not found',
                'status' => 404,
            ], 404);
        }

        if (! Gate::allows('update-doctor', $doctor)) {
            return response()->json([
                'message' => 'You are not authorized to update this doctor.',
                'status' => 403,
            ], 403);
        }

        $validated = $request->validate([
            'firstName' => 'sometimes|string|min:3|max:50|regex:/^[a-zA-Z\s]+$/',
            'lastName' => 'sometimes|string|min:3|max:50|regex:/^[a-zA-Z\s]+$/',
            'username' => 'sometimes|string||min:5|max:30|unique:doctors|regex:/^[a-zA-Z0-9_]+$/|unique:doctors,username,'.$doctor->id,
            'email' => 'sometimes|email|max:100|unique:doctors,email,'.$doctor->id,
            'password' => [
                'sometimes',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
            ],
            'gender' => 'sometimes|in:male,female',
            'phoneNumber' => 'nullable|string|regex:/^\+?[0-9]{7,11}$/',
            'rating' => 'sometimes|numeric|min:0|max:5',
            'image' => [
                'nullable',
                'file',
                'mimes:png,jpg,jpeg,gif',
                'max:2048', // Maximum 2MB (2048KB)
            ],
            'spec_id' => 'sometimes|exists:specializations,id',
            'city_id' => 'sometimes|exists:cities,id',
            'experience' => 'sometimes|nullable|integer|min:0',
            'number_of_patients' => 'sometimes|nullable|integer|min:0',
            'about' => 'sometimes|nullable|string|max:500',
            'salary' => 'sometimes|nullable|numeric|min:0',
            'schedule' => 'sometimes|nullable|json',
        ],
            [
                'password.regex' => 'The password must contain an uppercase letter, a lowercase letter, a number, and a special character.',
                'phoneNumber.regex' => 'Invalid phone number. Please enter a valid phone number (7 to 11 digits).',
                'rating.numeric' => 'The rating must be a number.',
                'rating.min' => 'The rating must be at least 0.',
                'rating.max' => 'The rating cannot be more than 5.',
                'image.file' => 'The profile image must be a file.',
                'image.mimes' => 'The profile image must be a file of type: png, jpg, jpeg, gif.',
                'image.max' => 'The profile image must not exceed 2MB.',
                'schedule.json' => 'Schedule must be a valid JSON string.',
            ]
        );

        if ($request->has('password')) {
            $request->merge(['password' => Hash::make($request->password)]);
        }

        if ($request->has('rating') && ! (Auth::user() instanceof User && Auth::user()->role === 'owner')) {
            return response()->json([
                'message' => 'You are not authorized to update the rating',
                'status' => 403,
            ], 403);
        }

        // Update the doctor with the validated fields
        if (! empty($validated)) {
            // Update doctor fields
            $doctor->update($request->only(['firstName', 'lastName', 'username', 'email', 'password', 'gender', 'phoneNumber', 'rating', 'spec_id']));

            // Check and update doctor_information only if authorized
            if (! empty(array_filter($request->only(['experience', 'number_of_patients', 'about', 'salary', 'schedule'])))) {
                if (Auth::user()->role !== 'owner') {
                    return response()->json([
                        'message' => 'You are not authorized to update this data.',
                        'status' => 403,
                    ], 403);
                }
                $doctor->information()->updateOrCreate(
                    ['doctor_id' => $doctor->id],
                    array_merge(
                        [
                            'experience' => null,
                            'number_of_patients' => 0,
                            'about' => null,
                            'salary' => null,
                            'schedule' => null,
                        ],
                        array_filter($request->only(['experience', 'number_of_patients', 'about', 'salary', 'schedule'])) // Filter out null values
                    )
                );
            }
        }

        // Process the image if it exists
        if ($request->hasFile('image')) {
            if ($request->file('image')->isValid()) {
                $oldImage = $doctor->image;
                $newImagePath = $request->file('image')->store('images/doctor_img', 'public');
                if ($oldImage) {
                    if (Storage::disk('public')->exists($oldImage->image_name)) {
                        Storage::disk('public')->delete($oldImage->image_name);
                    }
                    $oldImage->update(['image_name' => $newImagePath]);
                } else {
                    $doctor->image()->create(['image_name' => $newImagePath]);
                }
            } else {
                return response()->json([
                    'message' => 'Invalid image file uploaded.',
                    'status' => 400,
                ], 400);
            }
        }

        // Bring the doctor with the image data after the update
        $doctor = Doctor::with(['image', 'information'])->find($id);

        if ($doctor->image) {
            $doctor->image->image_name = env('APP_URL').'/storage/'.$doctor->image->image_name;
        }

        $doctor->specialization_name = $doctor->specialization ? $doctor->specialization->name : null;
        $doctor->city_name = $doctor->city ? $doctor->city->name : null;
        $doctor->user_role = $doctor->user ? $doctor->user->role : null;
        unset($doctor->spec_id, $doctor->city_id, $doctor->user_id);

        return response()->json([
            'doctor' => $doctor,
            'message' => 'The doctor has been updated successfully',
            'status' => 200,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/doctors-group/doctors/{id}",
     *     summary="Delete a doctor",
     *     tags={"Doctors"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the doctor to delete",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Doctor archived successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Doctor deleted successfully"),
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to delete this doctor."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Doctor not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Doctor not found"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Remove the specified resource from storage.
    public function destroy(string $id)
    {
        try {
            // $doctor = Doctor::findOrFail($id);
            $doctor = Doctor::with(['image', 'information'])->findOrFail($id);

            if (! Gate::allows('delete-doctor', $doctor)) {
                return response()->json([
                    'message' => 'You are not authorized to delete this doctor.',
                    'status' => 403,
                ], 403);
            }

            // Transfer doctor data to doctor_archives
            $archivedData = $doctor->toArray();
            $archivedData['password'] = $doctor->password;
            $archivedData['deleted_at'] = now();
            $archivedDoctor = DoctorArchive::create($archivedData);

            // Move the image to doctor_image_archives and the archive folder
            if ($doctor->image) {
                $oldImagePath = $doctor->image->image_name; // Old path: images/doctor_img/...
                $imageFileName = basename($oldImagePath); // Extract file name only
                $newImagePath = 'images/doctor_img_archive/'.$imageFileName; // New Path

                // Move file from doctor_img to doctor_img_archive
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->move($oldImagePath, $newImagePath);
                }

                // Save image data to archive table
                DoctorImageArchive::create([
                    'image_name' => $newImagePath,
                    'doctor_id' => $archivedDoctor->id,
                    'deleted_at' => now(),
                ]);

                // Delete the image from the filesystem if it exists.
                $doctor->image()->delete();
            }

            // Transfer information to doctor_information_archives (if present)
            if ($doctor->information) {
                DoctorInformationArchive::create([
                    'experience' => $doctor->information->experience,
                    'number_of_patients' => $doctor->information->number_of_patients,
                    'about' => $doctor->information->about,
                    'schedule' => $doctor->information->schedule,
                    'salary' => $doctor->information->salary,
                    'doctor_id' => $archivedDoctor->id,
                    'deleted_at' => now(),
                ]);
                $doctor->information()->delete();
            }

            // Delete tokens associated with the doctor only
            $doctor->tokens()->delete();

            $doctor->delete();

            return response()->json([
                'message' => 'Doctor deleted successfully',
                'status' => 200,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Doctor not found',
                'status' => 404,
            ], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/doctors-group/doctors/restore/{id}",
     *     summary="Restore a doctor from archive",
     *     tags={"Doctors"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the archived doctor to restore",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Doctor restored successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="doctor", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="firstName", type="string", example="John"),
     *                 @OA\Property(property="lastName", type="string", example="Doe"),
     *                 @OA\Property(property="image", type="object", nullable=true,
     *                     @OA\Property(property="image_name", type="string", example="http://your-app-url/storage/images/doctor_img/doctor.jpg")
     *                 ),
     *                 @OA\Property(property="information", type="object", nullable=true,
     *                     @OA\Property(property="about", type="string", example="Experienced cardiologist"),
     *                     @OA\Property(property="experience", type="integer", example=10),
     *                     @OA\Property(property="number_of_patients", type="integer", example=500),
     *                     @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule"),
     *                     @OA\Property(property="salary", type="number", example=5000)
     *                 ),
     *                 @OA\Property(property="specialization_name", type="string", nullable=true, example="Cardiology"),
     *                 @OA\Property(property="city_name", type="string", nullable=true, example="New York"),
     *                 @OA\Property(property="user_role", type="string", nullable=true, example="doctor")
     *             ),
     *             @OA\Property(property="message", type="string", example="Doctor restored successfully"),
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to restore doctors."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Archived doctor not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Archived doctor not found"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Failed to restore doctor",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Failed to restore doctor: [error message]"),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
     *     )
     * )
     */

    // Restore the specified resource from storage.
    public function restore(string $id)
    {
        try {
            $archivedDoctor = DoctorArchive::findOrFail($id);

            if (! Gate::allows('add-doctors')) {
                return response()->json([
                    'message' => 'You are not authorized to restore doctors.',
                    'status' => 403,
                ], 403);
            }

            // Search for a doctor in the doctors table based on username
            $doctor = Doctor::where('username', $archivedDoctor->username)->first();

            if ($doctor) {
                // Update existing record
                $doctor->update(
                    Arr::except($archivedDoctor->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at'])
                );
            } else {
                // Create a new record if it does not exist
                $doctor = Doctor::create(
                    Arr::except($archivedDoctor->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at'])
                );
            }

            // Restore image from doctor_image_archives
            $archivedImage = DoctorImageArchive::where('doctor_id', $archivedDoctor->id)->first();
            if ($archivedImage && $archivedImage->image_name) { // Check that the record and image_name exist.
                $archivedImagePath = $archivedImage->image_name; // Path in archive
                $imageFileName = basename($archivedImagePath); // Extract file name
                $originalImagePath = 'images/doctor_img/'.$imageFileName; // Original path

                // Move file from doctor_img_archive to doctor_img
                if (Storage::disk('public')->exists($archivedImagePath)) {
                    Storage::disk('public')->move($archivedImagePath, $originalImagePath);
                }

                // Update or create the image
                if ($doctor->image) {
                    $doctor->image->update(['image_name' => $originalImagePath]);
                } else {
                    $doctor->image()->create(['image_name' => $originalImagePath]);
                }

                $archivedImage->delete(); // Delete record from archive
            }

            // Recover information from doctor_information_archives
            $archivedInfo = DoctorInformationArchive::where('doctor_id', $archivedDoctor->id)->first();
            if ($archivedInfo) {
                $doctor->information()->create([
                    'experience' => $archivedInfo->experience,
                    'number_of_patients' => $archivedInfo->number_of_patients,
                    'about' => $archivedInfo->about,
                    'schedule' => $archivedInfo->schedule,
                    'salary' => $archivedInfo->salary,
                ]);
                $archivedInfo->delete(); // Delete the record from the archive after restoration
            }

            // Delete log from doctor_archives after restore
            $archivedDoctor->delete();

            // Load relationships to return full data
            $doctor->load(['image', 'information']);
            if ($doctor->image) {
                $doctor->image->image_name = env('APP_URL').'/storage/'.$doctor->image->image_name;
            }

            $doctor->specialization_name = $doctor->specialization ? $doctor->specialization->name : null;
            $doctor->city_name = $doctor->city ? $doctor->city->name : null;
            $doctor->user_role = $doctor->user ? $doctor->user->role : null;
            unset($doctor->spec_id, $doctor->city_id, $doctor->user_id);

            return response()->json([
                'doctor' => $doctor,
                'message' => 'Doctor restored successfully',
                'status' => 200,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Archived doctor not found',
                'status' => 404,
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to restore doctor: '.$e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/doctors-group/doctors/delete/archive/{id}",
     *     summary="Delete archived doctor data",
     *     tags={"Doctors"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the archived doctor to delete",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Archived doctor data deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Archived data for doctor ID 1 deleted successfully"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Archived doctor not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Archived doctor not found"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Failed to delete archived data",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Failed to delete archived data: [error message]"),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
     *     )
     * )
     */

    // Delete the specified archived doctor.
    public function deleteArchive(string $id)
    {
        try {
            // Search for archived doctor based on id
            $archivedDoctor = DoctorArchive::find($id);

            if (! $archivedDoctor) {
                return response()->json([
                    'message' => 'Archived doctor not found',
                    'status' => 404,
                ], 404);
            }

            // Delete images associated with the doctor from the folder and the doctor_image_archives table
            $archivedImages = DoctorImageArchive::where('doctor_id', $id)->get();
            foreach ($archivedImages as $image) {
                $imagePath = $image->image_name; // Path: images/doctor_img_archive/...
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
                $image->delete();
            }

            // Delete information related to the doctor from doctor_information_archives
            DoctorInformationArchive::where('doctor_id', $id)->delete();

            // Delete doctor data from doctor_archives
            $archivedDoctor->delete();

            return response()->json([
                'message' => "Archived data for doctor ID {$id} deleted successfully",
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete archived data: '.$e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/doctors-group/top-doctors",
     *     summary="Get top 10 doctors based on rating",
     *     tags={"Doctors"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Top 10 doctors retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="doctors", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="firstName", type="string", example="John"),
     *                     @OA\Property(property="lastName", type="string", example="Doe"),
     *                     @OA\Property(property="rating", type="number", example=4.5),
     *                     @OA\Property(property="image", type="object", nullable=true,
     *                         @OA\Property(property="image_name", type="string", example="http://your-app-url/storage/images/doctor_img/doctor.jpg")
     *                     ),
     *                     @OA\Property(property="specialization_name", type="string", nullable=true, example="Cardiology"),
     *                     @OA\Property(property="city_name", type="string", nullable=true, example="New York"),
     *                     @OA\Property(property="user_role", type="string", nullable=true, example="doctor")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Top 10 doctors retrieved successfully"),
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
     *             @OA\Property(property="message", type="string", example="Unauthenticated."),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     )
     * )
     */

    // Display the top 10 doctors based on rating.
    public function topDoctors()
    {
        $topDoctors = Doctor::with('image')
            ->orderBy('rating', 'desc')
            ->take(10)
            ->get();

        $topDoctors->each(function ($doctor) {
            if ($doctor->image) {
                $doctor->image->image_name = env('APP_URL').'/storage/'.$doctor->image->image_name;
            }
            $doctor->specialization_name = $doctor->specialization ? $doctor->specialization->name : null;
            $doctor->city_name = $doctor->city ? $doctor->city->name : null;
            $doctor->user_role = $doctor->user ? $doctor->user->role : null;
            unset($doctor->spec_id, $doctor->city_id, $doctor->user_id);
        });

        return response()->json([
            'doctors' => $topDoctors,
            'message' => 'Top 10 doctors retrieved successfully',
            'status' => 200,
        ], 200);
    }
}
