<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Specialization;
use Illuminate\Http\Request;

class DoctorSearchController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/search/doctors/search",
     *     summary="Search for doctors by name or specialization",
     *     tags={"Search For A Doctor"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Search query for doctor first name or specialization name",
     *
     *         @OA\Schema(type="string", example="Cardiology")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Doctors retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="doctors", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe"),
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="specialization", type="string", nullable=true, example="Cardiology"),
     *                     @OA\Property(property="experience", type="integer", nullable=true, example=10),
     *                     @OA\Property(property="rating", type="number", format="float", example=4.5),
     *                     @OA\Property(property="about", type="string", nullable=true, example="Experienced cardiologist"),
     *                     @OA\Property(property="salary", type="number", nullable=true, example=5000),
     *                     @OA\Property(property="schedule", ref="#/components/schemas/DoctorSchedule"),
     *                     @OA\Property(property="image_name", type="string", nullable=true, example="http://your-app-url/storage/images/doctor_img/doctor.jpg")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Doctors retrieved successfully"),
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
     *             @OA\Property(property="message", type="string", example="You are not authorized to search doctors."),
     *             @OA\Property(property="status", type="integer", example=403)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No doctors found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No doctor found with this name"),
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
    public function search(Request $request)
    {
        // Validate the search query
        $request->validate([
            'query' => 'required|string|min:1',
        ]);

        $query = trim($request->query('query'));

        // Determine if the query is short (for prefix search) or full (for exact match)
        $isShortQuery = strlen($query) < 3;

        // Search by doctor firstName
        $doctorsByName = Doctor::with(['information', 'specialization', 'image']);

        if ($isShortQuery) {
            // Prefix search for short queries (e.g., "a" or "al")
            $doctorsByName->where('firstName', 'LIKE', "{$query}%");
        } else {
            // Exact match for full firstName (e.g., "ali")
            $doctorsByName->where('firstName', '=', $query);
        }

        $doctorsByName = $doctorsByName->get();

        // Search by specialization name
        $doctorsBySpecialization = Doctor::with(['information', 'specialization', 'image'])
            ->whereHas('specialization', function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })
            ->get();

        // Merge and remove duplicates
        $doctors = $doctorsByName->merge($doctorsBySpecialization)->unique('id');

        // Check if no doctors found
        if ($doctors->isEmpty()) {
            // Check if the query matches any specialization
            $specializationExists = Specialization::where('name', 'LIKE', "%{$query}%")->exists();
            if ($specializationExists) {
                return response()->json([
                    'message' => "No doctors found for the specialization: {$query}",
                    'status' => 404,
                ], 404);
            } else {
                return response()->json([
                    'message' => 'No doctor found with this name',
                    'status' => 404,
                ], 404);
            }
        }

        // Format the response
        $results = $doctors->map(function ($doctor) {
            return [
                'id' => $doctor->id,
                'first_name' => $doctor->firstName,
                'last_name' => $doctor->lastName,
                'full_name' => $doctor->firstName.' '.$doctor->lastName,
                'specialization' => $doctor->specialization ? $doctor->specialization->name : null,
                'experience' => $doctor->information ? $doctor->information->experience : null,
                'rating' => $doctor->rating,
                'about' => $doctor->information ? $doctor->information->about : null,
                'salary' => $doctor->information ? $doctor->information->salary : null,
                'schedule' => $doctor->information ? $doctor->information->schedule : null,
                'image_name' => $doctor->image ? $doctor->image->image_name : null,
            ];
        });

        return response()->json([
            'doctors' => $results,
            'message' => 'Doctors retrieved successfully',
            'status' => 200,
        ], 200);
    }
}
