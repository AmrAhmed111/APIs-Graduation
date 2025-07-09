<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicalTestController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/medical-test/medical-tests",
     *     summary="Get all medical tests",
     *     tags={"Medical Tests"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Medical tests retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="test_name", type="string", example="Blood Test"),
     *                     @OA\Property(property="description", type="string", nullable=true, example="A test to analyze blood components"),
     *                     @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule", nullable=true),
     *                     @OA\Property(property="cost", type="number", format="float", example=50.00)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Medical tests retrieved successfully."),
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
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only doctors or patients can perform this action."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     )
     * )
     */

    // Display a listing of the resource.
    public function index()
    {
        $tests = MedicalTest::all();

        return response()->json([
            'data' => $tests,
            'message' => 'Medical tests retrieved successfully.',
            'status' => 200,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/medical-test/create/medical-test",
     *     summary="Create a new medical test",
     *     tags={"Medical Tests"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"test_name", "cost"},
     *
     *             @OA\Property(property="test_name", type="string", example="Blood Test", description="Name of the medical test"),
     *             @OA\Property(property="description", type="string", nullable=true, example="A test to analyze blood components", description="Description of the medical test"),
     *             @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule", nullable=true, description="Schedule in JSON format"),
     *             @OA\Property(property="cost", type="number", format="float", example=50.00, description="Cost of the medical test")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Medical test created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="test_name", type="string", example="Blood Test"),
     *                 @OA\Property(property="description", type="string", nullable=true, example="A test to analyze blood components"),
     *                 @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule", nullable=true),
     *                 @OA\Property(property="cost", type="number", format="float", example=50.00)
     *             ),
     *             @OA\Property(property="message", type="string", example="Medical test created successfully."),
     *             @OA\Property(property="status", type="integer", example=201)
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
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Validation error."),
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="status", type="integer", example=422)
     *         )
     *     )
     * )
     */

    // Store a newly created resource in storage.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'schedule' => 'nullable|json',
            'cost' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $test = MedicalTest::create($request->only(['test_name', 'description', 'schedule', 'cost']));

        return response()->json([
            'data' => $test,
            'message' => 'Medical test created successfully.',
            'status' => 201,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/medical-test/medical-test/view/{id}",
     *     summary="Get a specific medical test",
     *     tags={"Medical Tests"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the medical test",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Medical test retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="test_name", type="string", example="Blood Test"),
     *                 @OA\Property(property="description", type="string", nullable=true, example="A test to analyze blood components"),
     *                 @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule", nullable=true),
     *                 @OA\Property(property="cost", type="number", format="float", example=50.00)
     *             ),
     *             @OA\Property(property="message", type="string", example="Medical test retrieved successfully."),
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
     *             @OA\Property(property="message", type="string", example="Unauthorized. Only patients can perform this action."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Medical test not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Medical test not found."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Display the specified resource.
    public function show(string $id)
    {
        $test = MedicalTest::find($id);

        if (! $test) {
            return response()->json([
                'message' => 'Medical test not found.',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'data' => $test,
            'message' => 'Medical test retrieved successfully.',
            'status' => 200,
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/medical-test/medical-test/update/{id}",
     *     summary="Update a specific medical test",
     *     tags={"Medical Tests"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the medical test",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="test_name", type="string", example="Blood Test", description="Name of the medical test"),
     *             @OA\Property(property="description", type="string", nullable=true, example="A test to analyze blood components", description="Description of the medical test"),
     *             @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule", nullable=true, description="Schedule in JSON format"),
     *             @OA\Property(property="cost", type="number", format="float", example=50.00, description="Cost of the medical test")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Medical test updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="test_name", type="string", example="Blood Test"),
     *                 @OA\Property(property="description", type="string", nullable=true, example="A test to analyze blood components"),
     *                 @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule", nullable=true),
     *                 @OA\Property(property="cost", type="number", format="float", example=50.00)
     *             ),
     *             @OA\Property(property="message", type="string", example="Medical test updated successfully."),
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
     *         response=404,
     *         description="Medical test not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Medical test not found."),
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
     *             @OA\Property(property="message", type="string", example="Validation error."),
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="status", type="integer", example=422)
     *         )
     *     )
     * )
     */

    // Update the specified resource in storage.
    public function update(Request $request, string $id)
    {
        $test = MedicalTest::find($id);

        if (! $test) {
            return response()->json([
                'message' => 'Medical test not found.',
                'status' => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'test_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'schedule' => 'sometimes|nullable|json',
            'cost' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $test->update($request->only(['test_name', 'description', 'schedule', 'cost']));

        return response()->json([
            'data' => $test,
            'message' => 'Medical test updated successfully.',
            'status' => 200,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/medical-test/medical-test/delete/{id}",
     *     summary="Delete a specific medical test",
     *     tags={"Medical Tests"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the medical test",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Medical test deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Medical test deleted successfully."),
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
     *         response=404,
     *         description="Medical test not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Medical test not found."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    // Remove the specified resource from storage.
    public function destroy(string $id)
    {
        $test = MedicalTest::find($id);

        if (! $test) {
            return response()->json([
                'message' => 'Medical test not found.',
                'status' => 404,
            ], 404);
        }

        $test->delete();

        return response()->json([
            'message' => 'Medical test deleted successfully.',
            'status' => 200,
        ], 200);
    }
}
