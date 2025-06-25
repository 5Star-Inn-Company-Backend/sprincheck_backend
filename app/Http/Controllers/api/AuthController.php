<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|unique:business,name',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(', ', $validator->errors()->all()), 'errors' => $validator->errors()], 422);
        }

        $business = Business::create([
            'name' => $request->business_name,
            'encryption_key' => 'enc'.Str::random(29),
            'api_key' => "scb".Str::random(42),
        ]);

        $user = User::create([
            'name' => explode("@",$request->email)[0],
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'business_id' => $business->id
        ]);

        return response()->json([
            'message' => 'Registration successful'
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(', ', $validator->errors()->all()), 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        $resetCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'reset_code' => Hash::make($resetCode),
            'reset_code_expires_at' => now()->addMinutes(15)
        ]);

        // Send email with reset code
        Mail::raw("Your password reset code is: {$resetCode}", function($message) use ($user) {
            $message->to($user->email)
                   ->subject('Password Reset Code');
        });

        return response()->json([
            'message' => 'Reset code has been sent to your email'
        ]);
    }

    public function verifyResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(', ', $validator->errors()->all()), 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user->reset_code || now()->gt($user->reset_code_expires_at)) {
            return response()->json([
                'message' => 'Reset code has expired'
            ], 400);
        }

        if (!Hash::check($request->code, $user->reset_code)) {
            return response()->json([
                'message' => 'Invalid reset code'
            ], 400);
        }

        return response()->json([
            'message' => 'Reset code verified'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(', ', $validator->errors()->all()), 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user->reset_code || now()->gt($user->reset_code_expires_at)) {
            return response()->json([
                'message' => 'Reset code has expired'
            ], 400);
        }

        if (!Hash::check($request->code, $user->reset_code)) {
            return response()->json([
                'message' => 'Invalid reset code'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'reset_code' => null,
            'reset_code_expires_at' => null
        ]);

        return response()->json([
            'message' => 'Password has been reset successfully'
        ]);
    }
}
