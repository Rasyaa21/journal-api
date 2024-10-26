<?php

namespace App\Http\Controllers;

use App\Http\Resources\DetailResources;
use App\Http\Resources\JournalResources;
use App\Models\Journal;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Components(
 *     @OA\Schema(
 *         schema="JournalResource",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="user_id", type="integer", example=2),
 *         @OA\Property(property="title", type="string", example="My Journal Title"),
 *         @OA\Property(property="content", type="string", example="Journal content goes here..."),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-26T12:34:56Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-26T12:34:56Z")
 *     )
 * )
 */


class JournalController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/index",
     *     summary="Retrieve all journals for authenticated user",
     *     tags={"Journal"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="no data available"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/JournalResource"))
     *         )
     *     ),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $journal = Journal::where('user_id', $user->id)->get();
            if ($journal->isEmpty()) {
                return response()->json([
                    'status' => 200,
                    'message' => 'no data available'
                ], 200);
            }
            return response()->json(['status' => 200, 'data' => JournalResources::collection($journal)]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/index/{post_id}",
     *     summary="Retrieve a specific journal entry",
     *     tags={"Journal"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="post_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/JournalResource")),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function findSpecificItem($post_id)
    {
        try {
            $user = Auth::user();
            $journal = Journal::where('user_id', $user->id)->with('writer')->findOrFail($post_id);
            return response()->json(['status' => 200, 'data' => new DetailResources($journal)]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 422,
                'message' => 'error',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/journal",
     *     summary="Create a new journal entry",
     *     tags={"Journal"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description", "mood_id", "user_id", "image"},
     *             @OA\Property(property="title", type="string", example="My Journal Title"),
     *             @OA\Property(property="description", type="string", example="Detailed content of the journal"),
     *             @OA\Property(property="mood_id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="image", type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validateData = $request->validate([
                'title' => 'required',
                'description' => 'required',
                'mood_id'  => 'required',
                'user_id' => 'required',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $journal = new Journal();
            $image = $request->file('image');
            $journal->title = $validateData['title'];
            $journal->description = $validateData['description'];
            $journal->mood_id = $validateData['mood_id'];
            $journal->user_id = $validateData['user_id'];
            $journal->image = $image->storeAs('public/posts', $image->hashName());
            $journal->save();

            return response()->json(['status' => 200, 'data' => new JournalResources($journal)]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'message' => 'validation error',
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/journal/{post_id}",
     *     summary="Update an existing journal entry",
     *     tags={"Journal"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="post_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Journal Title"),
     *             @OA\Property(property="description", type="string", example="Updated content"),
     *             @OA\Property(property="mood_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, $post_id)
    {
        try {
            $validateData = $request->validate([
                'title' => 'sometimes|required',
                'description' => 'sometimes|required',
                'mood_id' => 'sometimes|required|integer',
            ]);

            $user = Auth::user();
            $journal = Journal::where('user_id', $user->id)->findOrFail($post_id);
            $journal->update($validateData);

            return response()->json(['status' => 200, 'data' => new JournalResources($journal)]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 422,
                'message' => 'error',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/journal/{post_id}",
     *     summary="Delete a journal entry",
     *     tags={"Journal"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="post_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Deleted successfully"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function delete($post_id)
    {
        try {
            $user = Auth::user();
            $journal = Journal::where('user_id', $user->id)->findOrFail($post_id);
            $journal->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Data has been successfully deleted'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Data not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
