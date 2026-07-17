<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The authenticated user (login / register / me). `email` is fine here — the
 * caller is the account owner. Admin listings reuse this and add `joined`.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'email'    => $this->email,
            'role'     => $this->role,
            'isActive' => (bool) $this->is_active,
            'joined'   => $this->created_at?->toIso8601String(),
        ];
    }
}
