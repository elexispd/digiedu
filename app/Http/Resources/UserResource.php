<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'biometric_enabled' => (bool) $this->biometric_enabled,
            'push_notifications_enabled' => (bool) $this->push_notifications_enabled,
            'email_notifications_enabled' => (bool) $this->email_notifications_enabled,
            'two_factor_enabled' => (bool) $this->two_factor_enabled,
            'two_factor_email' => $this->two_factor_email,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
        ];
    }
}
