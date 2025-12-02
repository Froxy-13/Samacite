<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends BaseController
{
    /**
     * GET /api/user/profile
     * Récupérer le profil de l'utilisateur connecté
     */
    public function show(Request $request): JsonResponse
    {
        $user = auth()->user();

        return $this->success([
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'photo_url' => $user->photo_url ? Storage::url($user->photo_url) : null,
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ], 'Profil récupéré avec succès');
    }

    /**
     * PUT /api/user/profile
     * Modifier le profil (nom, prenom, email, telephone)
     */
    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'prenom' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|nullable|email|unique:users,email,' . $user->id,
            'telephone' => 'sometimes|required|string|unique:users,telephone,' . $user->id,
        ]);

        $user->update($validated);

        return $this->success([
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'photo_url' => $user->photo_url ? Storage::url($user->photo_url) : null,
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ], 'Profil mis à jour avec succès');
    }

    /**
     * POST /api/user/profile/photo
     * Upload/changer photo de profil
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        $user = auth()->user();

        // Supprimer ancienne photo si existe
        if ($user->photo_url && Storage::disk('public')->exists($user->photo_url)) {
            Storage::disk('public')->delete($user->photo_url);
        }

        // Sauvegarder nouvelle photo
        $path = $request->file('photo')->store('profile_photos', 'public');

        $user->update([
            'photo_url' => $path,
        ]);

        return $this->success([
            'photo_url' => Storage::url($path),
        ], 'Photo de profil mise à jour avec succès');
    }

    /**
     * PUT /api/user/password
     * Changer le mot de passe
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        // Vérifier que le mot de passe actuel est correct
        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return $this->success(null, 'Mot de passe modifié avec succès');
    }

    /**
     * Update the authenticated user's own profile (legacy)
     * PATCH /api/profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $request->validated();

        $user->update($data);

        return $this->success(
            new ProfileResource($user),
            'Profil mis à jour avec succès'
        );
    }
}
