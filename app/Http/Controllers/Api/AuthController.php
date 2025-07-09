<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Info(
 *     title="APIs-Graduation",
 *     version="1.0.0",
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/doctors-group/register",
     *     summary="Register a new doctor",
     *     tags={"Doctor Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="firstName", type="string", example="John", description="First name of the doctor"),
     *             @OA\Property(property="lastName", type="string", example="Doe", description="Last name of the doctor"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Doctor's email"),
     *             @OA\Property(property="password", type="string", example="password123", description="Doctor's password"),
     *             @OA\Property(property="spec_id", type="integer", example=1, description="Specialization ID of the doctor")
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
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="spec_id", type="integer", example=1)
     *             ),
     *             @OA\Property(property="message", type="string", example="Doctor registered successfully"),
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to register doctors."),
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
    // Register a new doctor
    public function register(AuthRequest $request)
    {
        if (Gate::allows('add-doctors')) {
            $validate = $request->validated();

            // Hash the password before creating the doctor
            $validate['password'] = Hash::make($validate['password']);

            // Create the doctor using only the validated fields
            $doctor = Doctor::create($validate);

            return response()->json([
                'doctor' => $doctor,
                'message' => 'Doctor registered successfully',
                'status' => 201,
            ], 201);
        } else {
            return response()->json([
                'message' => 'You are not authorized to register doctors.',
                'status' => 403,
            ], 403);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/doctors-group/login",
     *     summary="Login a doctor",
     *     tags={"Doctor Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Doctor's email"),
     *             @OA\Property(property="password", type="string", example="password123", description="Doctor's password")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="token", type="string", example="1|randomtokenstring", description="Sanctum authentication token")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The email is incorrect. Please verify that this email is correct."),
     *             @OA\Property(property="errors", type="object")
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
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    // Login a doctor
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $doctor = Doctor::where('email', $request->email)->first();

        if (! $doctor) {
            throw ValidationException::withMessages([
                'email' => ['The email is incorrect. Please verify that this email is correct.'],
                // 'status' => 401
            ])->status(401);
        }

        if (! Hash::check($request->password, $doctor->password)) {
            throw ValidationException::withMessages([
                'password' => ['The password is incorrect. Please verify that this password is correct.'],
            ])->status(401);
        }

        $doctor->tokens()->delete();

        $token = $doctor->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'status' => 200,
            'token' => $token,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/doctors-group/doctor/logout",
     *     summary="Logout a doctor",
     *     tags={"Doctor Authentication"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Doctor logged out successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Doctor logged out successfully"),
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to logout here.."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     )
     * )
     */
    // Logout a doctor
    public function logout(Request $request)
    {
        // Verify that the user is a doctor
        if ($request->user() instanceof Doctor) {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Doctor logged out successfully',
                'status' => 200,
            ], 200);
        }

        // If the user is not a doctor
        return response()->json([
            'message' => 'You are not authorized to logout here..',
            'status' => 403,
        ], 403);
    }
}
