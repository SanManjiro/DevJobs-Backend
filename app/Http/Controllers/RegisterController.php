<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\DeveloperProfile;
use App\Models\CompanyProfile;

class RegisterController extends Controller
{
     public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:company, developper'
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role']
        ]);

        if ($user->isDeveloper()) {
            DeveloperProfile::create(['user_id' => '$user->id']);
        } else {
            CompanyProfile::create(['user_id' => '$user->id']);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

}
