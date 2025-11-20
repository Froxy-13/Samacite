<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'telephone' => 'required|string|unique:users,telephone',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'email' => $validated['email'] ?? null,
            'telephone' => $validated['telephone'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assign default role - handle case where role doesn't exist
        try {
            $user->assignRole('Utilisateur');
        } catch (\Exception $e) {
            // Role doesn't exist yet, just continue
        }

        return $this->success([
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'email' => $user->email,
            'telephone' => $user->telephone,
        ], 'User registered successfully', 201);
    }

    /**
     * Login user with email or telephone
     */
    public function login(Request $request)
    {
        // Validate input - accept either email or phone
        $validated = $request->validate([
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'password' => 'required|string',
        ]);

        // Ensure at least one identifier is provided
        if (empty($validated['email']) && empty($validated['phone'])) {
            return $this->error('Email or phone is required', 422);
        }

        // Find user by email or phone
        $query = User::query();
        
        if (!empty($validated['email'])) {
            $query->where('email', $validated['email']);
        } else {
            $query->where('telephone', $validated['phone']);
        }
        
        $user = $query->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->error('Invalid credentials', 401);
        }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'telephone' => $user->telephone,
            ],
            'token' => $token,
        ], 'Logged in successfully');
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return $this->success([
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }
}
