<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LeaveIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_approval' => ['nullable', 'in:Pending,Approved,Rejected'],
            'per_page'        => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
