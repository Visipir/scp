<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Http\FormRequest;

class ResetUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        if (!$actor) {
            return false;
        }

        $target = $this->route('user');
        if (!$target instanceof User) {
            $target = User::find($target);
        }

        if (!$target) {
            return true;
        }

        return app(UserService::class)->canManageUser($actor, $target);
    }

    public function rules(): array
    {
        return [];
    }
}
