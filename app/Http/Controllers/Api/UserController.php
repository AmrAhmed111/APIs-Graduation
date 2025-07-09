<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use AuthorizesRequests;

    /**
     * @OA\Get(
     *     path="/api/users-group/users",
     *     summary="Get all users",
     *     tags={"Users"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved all users",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="users", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *                     @OA\Property(property="role", type="string", example="owner", enum={"owner", "doctors", "moderator"})
     *                 )
     *             )
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to perform this action"),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     )
     * )
     */

    // Display a listing of the resource.
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $users = User::all();

        return response()->json(['users' => $users], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/users-group/users",
     *     summary="Create a new user",
     *     tags={"Users"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "role"},
     *
     *             @OA\Property(property="name", type="string", example="John Doe", description="User's name"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email address"),
     *             @OA\Property(property="password", type="string", example="Password123!", description="User's password (min 8 characters)"),
     *             @OA\Property(property="role", type="string", example="owner", enum={"owner", "doctors", "moderator"}, description="User's role")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *                 @OA\Property(property="role", type="string", example="owner")
     *             )
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to perform this action"),
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
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:owner,doctors,moderator',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json(['user' => $user], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/users-group/users/{user}",
     *     summary="Get a specific user",
     *     tags={"Users"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID of the user",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved user",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *                 @OA\Property(property="role", type="string", example="owner")
     *             )
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to perform this action"),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="User not found"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Display the specified resource.
    public function show(string $user)
    {
        $this->authorize('view', $user);

        return response()->json(['user' => $user], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/users-group/users/{user}",
     *     summary="Update a specific user",
     *     tags={"Users"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID of the user",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string", example="John Doe", description="User's name"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email address"),
     *             @OA\Property(property="password", type="string", example="Password123!", description="User's new password (min 8 characters)"),
     *             @OA\Property(property="role", type="string", example="owner", enum={"owner", "doctors", "moderator"}, description="User's role")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *                 @OA\Property(property="role", type="string", example="owner")
     *             )
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to perform this action"),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="User not found"),
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
    public function update(Request $request, string $user)
    {
        $this->authorize('update', $user);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$user,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:owner,doctors,moderator',
        ]);

        if ($request->has('password')) {
            $request->merge(['password' => Hash::make($request->password)]);
        }

        $user = User::findOrFail($user);
        $user->update($request->all());

        return response()->json(['user' => $user], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/users-group/users/{user}",
     *     summary="Delete a specific user",
     *     tags={"Users"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID of the user",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="User deleted successfully"),
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to perform this action"),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="User not found"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Remove the specified resource from storage.
    public function destroy(string $user)
    {
        $this->authorize('delete', $user);

        $user = User::findOrFail($user);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/users-group/user/register",
     *     summary="Register a new user",
     *     tags={"User Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "role"},
     *
     *             @OA\Property(property="name", type="string", example="John Doe", description="User's name (3-50 characters, letters and spaces only)"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email address"),
     *             @OA\Property(property="password", type="string", example="Password123!", description="Password (min 8 characters, must include uppercase, lowercase, number, and special character)"),
     *             @OA\Property(property="role", type="string", example="owner", enum={"owner", "doctors", "moderator"}, description="User's role")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *                 @OA\Property(property="role", type="string", example="owner")
     *             ),
     *             @OA\Property(property="message", type="string", example="user registered successfully"),
     *             @OA\Property(property="status", type="integer", example=201)
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

    // Register a new user
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3|max:50|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email|max:100|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
            ],
            'role' => 'required|in:owner,doctors,moderator',
        ], [
            'password.regex' => 'The password must contain an uppercase letter, a lowercase letter, a number, and a special character.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // return response()->json(['user' => $user], 201);
        return response()->json([
            'user' => $user,
            'message' => 'user registered successfully',
            'status' => 201,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/users-group/user/login",
     *     summary="Log in a user",
     *     tags={"User Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email address"),
     *             @OA\Property(property="password", type="string", example="Password123!", description="User's password")
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
     *     )
     * )
     */

    // Login a user
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['The email is incorrect. Please verify that this email is correct.'],
            ])->status(401);
        }

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The password is incorrect. Please verify that this password is correct.'],
            ])->status(401);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'status' => 200,
            'token' => $token,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/users-group/user/logout",
     *     summary="Log out a user",
     *     tags={"User Authentication"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="User logged out successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Owner logged out successfully"),
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

    // Logout a user
    public function logout(Request $request)
    {
        // Verify that the user is the owner
        if ($request->user() instanceof User) {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Owner logged out successfully',
                'status' => 200,
            ], 200);
        }

        // If the user is not an owner
        return response()->json([
            'message' => 'You are not authorized to logout here.',
            'status' => 403,
        ], 403);
    }
}
