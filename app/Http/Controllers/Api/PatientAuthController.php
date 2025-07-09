<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PatientAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/patient-group/patient/login",
     *     summary="Log in a patient",
     *     tags={"Patient Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="patient@example.com", description="Patient's email address"),
     *             @OA\Property(property="password", type="string", example="password123", description="Patient's password")
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
     *             @OA\Property(property="token", type="string", example="1|abc123xyz", description="Sanctum authentication token")
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
     *             @OA\Property(property="errors", type="object"),
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
     *             @OA\Property(property="message", type="string", example="Unauthorized. Doctors cannot log in as patients."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        // Check that the user is not registered as owner or Doctor
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            if ($user instanceof User && $user->role === 'owner') {
                return response()->json([
                    'message' => 'Unauthorized. Owners cannot log in as patients.',
                    'status' => 403,
                ], 403);
            }
            if ($user instanceof Doctor) {
                return response()->json([
                    'message' => 'Unauthorized. Doctors cannot log in as patients.',
                    'status' => 403,
                ], 403);
            }
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $patient = Patient::withTrashed()->where('email', $request->email)->first();
        // $patient = Patient::where('email', $request->email)->first();

        if (! $patient) {
            throw ValidationException::withMessages([
                'email' => ['The email is incorrect. Please verify that this email is correct.'],
            ])->status(401);
        }

        if (! Hash::check($request->password, $patient->password)) {
            throw ValidationException::withMessages([
                'password' => ['The password is incorrect. Please verify that this password is correct.'],
            ])->status(401);
        }

        if ($patient->trashed()) {
            $patient->restore(); // Recover account if it is temporarily deleted
        }

        $patient->tokens()->delete();

        $token = $patient->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'status' => 200,
            'token' => $token,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/patient-group/patient/logout",
     *     summary="Log out a patient and deactivate their account",
     *     tags={"Patient Authentication"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Logged out and account deactivated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Logged out and account deactivated successfully"),
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to logout here."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        // Verify that the user is the patient
        if ($request->user() instanceof Patient) {
            $patient = $request->user();

            $patient->delete();
            $patient->tokens()->delete();

            return response()->json([
                'message' => 'Logged out and account deactivated successfully',
                'status' => 200,
            ], 200);
        }

        // If the user is not an patient
        return response()->json([
            'message' => 'You are not authorized to logout here.',
            'status' => 403,
        ], 403);
    }
}
