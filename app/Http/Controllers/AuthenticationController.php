<?php

namespace App\Http\Controllers;

use App\Http\Resources\GetDataResource;
use App\Http\Resources\RegisterResources;
use App\Models\User;
use Doctrine\Common\Lexer\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Schema(
 *     schema="User",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *     @OA\Property(property="firstname", type="string", example="John"),
 *     @OA\Property(property="lastname", type="string", example="Doe"),
 * )
 */
class AuthenticationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="User Login",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password","device_name"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="device_name", type="string", example="Device Name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="login successful", type="string", example="your_generated_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function loginRequest(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'device_name' => 'required',
            ]);
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                throw ValidationException::withMessages([
                    'login' => 'email doesnt exist'
                ]);
            }
            if (!Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'login' => ['wrong password']
                ]);
            }
            $token = $user->createToken($request->device_name)->plainTextToken;
            return response()->json([
                'login successful' => $token
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Authentication"},
     *     summary="User Registration",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "firstname", "lastname", "device_name"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="firstname", type="string", example="John"),
     *             @OA\Property(property="lastname", type="string", example="Doe"),
     *             @OA\Property(property="device_name", type="string", example="Device Name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="register successfull", type="string", example="your_generated_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="user already exist", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function registerRequest(Request $request)
    {
        try {
            $user = new User();
            $userValidate = $request->validate([
                'name' => 'required',
                'email' => 'required|unique:users,email',
                'password' => 'required',
                'firstname' => 'required',
                'lastname' => 'required',
                'device_name' => 'required'
            ], [
                'email.unique' => 'The email address is already registered. Please use a different email or log in.'
            ]);

            $hashedPassword = Hash::make($userValidate['password']);

            $user->name = $userValidate['name'];
            $user->email = $userValidate['email'];
            $user->password = $hashedPassword;
            $user->firstname = $userValidate['firstname'];
            $user->lastname = $userValidate['lastname'];
            $user->save();

            $token = $user->createToken($request->device_name)->plainTextToken;

            return response()->json([
                'register successfull' => $token
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'user already exist' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/logout",
     *     tags={"Authentication"},
     *     summary="Revoke User Token",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token successfully deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="string", example="token successfully deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function revokeToken(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'success' => 'token successfully deleted'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/user",
     *     tags={"Authentication"},
     *     summary="Get User Data",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User data retrieved",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function userData()
    {
        try {
            return new GetDataResource(Auth::user());
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
