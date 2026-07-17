<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    /**
     * GET /v1/admin/users
     */
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(User::latest()->paginate(20));
    }

    /**
     * PATCH /v1/admin/users/{user}/toggle
     *
     * Un admin ne peut pas se désactiver lui-même : ce serait se verrouiller
     * dehors au milieu d'une session.
     */
    public function toggle(Request $request, User $user): UserResource
    {
        abort_if($user->id === $request->user()->id, 422, 'Vous ne pouvez pas désactiver votre propre compte.');

        $user->update(['is_active' => ! $user->is_active]);

        return new UserResource($user);
    }

    /**
     * DELETE /v1/admin/users/{user}
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'Vous ne pouvez pas supprimer votre propre compte.');

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé.']);
    }
}
