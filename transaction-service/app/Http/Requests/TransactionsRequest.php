<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->input('type');

        return [
            'user_id' => 'required|integer',
            'receiver_id' => $type == 'transfer' ? 'required|integer' : 'nullable',
            'reference' => 'required|string',
            'type' => 'required|in:deposit,withdrawal,transfer',
            'amount' => 'required|numeric',
            'status' => 'required|in:pending,completed,failed'
        ];
    }
}
