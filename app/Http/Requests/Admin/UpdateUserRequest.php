<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UpdateUserRequest extends FormRequest
{
    /**
     * Authorization is handled by the route middleware (permission:access admin).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            // Ignore the current user's own email so saving without changing it passes (diff #1).
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            // Password is optional on update; only validated when provided (diff #2).
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::in(Role::pluck('name'))],
        ];
    }
}
