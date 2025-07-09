<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetCode;
use App\Models\Patient;
use App\Notifications\ResetPasswordCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/reset-password/send-code",
     *     summary="Request a password reset code",
     *     tags={"Password Reset"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="patient@example.com", description="Patient's email address")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Password reset code sent successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="A password reset code has been sent to your email."),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Email is invalid or not registered."),
     *             @OA\Property(property="status", type="integer", example=422),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    // Request to send password reset code
    public function sendResetCode(Request $request)
    {
        // التحقق من الإيميل
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:patients,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Email is invalid or not registered.',
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = $request->email;

        // Generate a new code (6 digits)
        $code = substr(str_shuffle('0123456789'), 0, 6);

        // Delete any previous codes for the same email.
        PasswordResetCode::where('email', $email)->delete();

        // Create a new code
        PasswordResetCode::create([
            'email' => $email,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(2), // Set expiration time to 2 minutes
        ]);

        // Send the code via email
        $patient = Patient::where('email', $email)->first();
        $patient->notify(new ResetPasswordCode($code));

        return response()->json([
            'message' => 'A password reset code has been sent to your email.',
            'status' => 200,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/reset-password/resend-code",
     *     summary="Resend a password reset code",
     *     tags={"Password Reset"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="patient@example.com", description="Patient's email address")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Password reset code resent successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="A password reset code has been sent to your email."),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Email is invalid or not registered."),
     *             @OA\Property(property="status", type="integer", example=422),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    // Resend the code
    public function resendCode(Request $request)
    {
        return $this->sendResetCode($request);
    }

    /**
     * @OA\Post(
     *     path="/api/reset-password/reset",
     *     summary="Reset patient password using code",
     *     tags={"Password Reset"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "code", "password", "password_confirmation"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="patient@example.com", description="Patient's email address"),
     *             @OA\Property(property="code", type="string", example="123456", description="6-digit reset code"),
     *             @OA\Property(property="password", type="string", example="Password123!", description="New password (min 8 characters, must include uppercase, lowercase, number, and special character)"),
     *             @OA\Property(property="password_confirmation", type="string", example="Password123!", description="Confirmation of the new password")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Password reset successfully."),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or invalid/expired code",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The code is incorrect."),
     *             @OA\Property(property="status", type="integer", example=422),
     *             @OA\Property(property="errors", type="object", nullable=true)
     *         )
     *     )
     * )
     */

    // Reset password
    public function resetPassword(Request $request)
    {
        // Verify inputs
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:patients,email',
            'code' => 'required|string|size:6',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
                'confirmed', // Make sure that password and password_confirmation match.
            ],
        ], [
            'password.regex' => 'Password must contain an uppercase letter, a lowercase letter, a number, and a special character.',
            'password.confirmed' => 'Confirm password does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Verification error.',
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = $request->email;
        $code = $request->code;
        $password = $request->password;

        // Code verification
        $resetCode = PasswordResetCode::where('email', $email)
            ->where('code', $code)
            ->first();

        if (! $resetCode) {
            return response()->json([
                'message' => 'The code is incorrect.',
                'status' => 422,
            ], 422);
        }

        if ($resetCode->expires_at->isPast()) {
            $resetCode->delete();

            return response()->json([
                'message' => 'The code has expired. Please request a new code.',
                'status' => 422,
            ], 422);
        }

        // Update password
        $patient = Patient::where('email', $email)->first();
        $patient->update([
            'password' => Hash::make($password),
        ]);

        // Delete the code after use
        $resetCode->delete();

        // Delete all previous tokens
        $patient->tokens()->delete();

        return response()->json([
            'message' => 'Password reset successfully.',
            'status' => 200,
        ], 200);
    }
}
