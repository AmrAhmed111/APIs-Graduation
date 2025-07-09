<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalTest;
use Illuminate\Http\Request;

class MedicalTestSearchController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/search/medical-tests/search",
     *     summary="Search for medical tests by name",
     *     tags={"Search For Medical Tests"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Search query for medical test name",
     *
     *         @OA\Schema(type="string", example="Blood Test")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Medical tests retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="tests", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="test_name", type="string", example="Blood Test"),
     *                     @OA\Property(property="cost", type="number", format="float", nullable=true, example=50.00),
     *                     @OA\Property(property="description", type="string", nullable=true, example="A test to analyze blood components")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Medical tests retrieved successfully"),
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
     *         description="No medical tests found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No medical test found with this name"),
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
     *             @OA\Property(property="message", type="string", example="The query field is required."),
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="status", type="integer", example=422)
     *         )
     *     )
     * )
     */
    public function search(Request $request)
    {
        // Validate the search query
        $request->validate([
            'query' => 'required|string|min:1',
        ]);

        $query = trim($request->query('query'));

        // Determine if the query is short (for prefix search) or full (for exact match)
        $isShortQuery = strlen($query) < 3;

        // Search by medical test name
        $tests = MedicalTest::query();

        if ($isShortQuery) {
            // Prefix search for short queries (e.g., "B" or "Bl")
            $tests->where('test_name', 'LIKE', "{$query}%");
        } else {
            // Exact match for full name (e.g., "Blood Test")
            $tests->where('test_name', '=', $query);
        }

        $tests = $tests->get();

        // Check if no tests found
        if ($tests->isEmpty()) {
            return response()->json([
                'message' => 'No medical test found with this name',
                'status' => 404,
            ], 404);
        }

        // Format the response
        $results = $tests->map(function ($test) {
            return [
                'id' => $test->id,
                'test_name' => $test->test_name,
                'cost' => $test->cost ?? null,
                'description' => $test->description ?? null,
            ];
        });

        return response()->json([
            'tests' => $results,
            'message' => 'Medical tests retrieved successfully',
            'status' => 200,
        ], 200);
    }
}
