<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AcceptQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visit_code' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'visit_code.required' => '来院コードは必須です',
            'visit_code.string' => '来院コードは文字列である必要があります',
            'visit_code.max' => '来院コードは255文字以内で入力してください',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => '入力内容に誤りがあります',
                    'details' => $validator->errors(),
                ]
            ], 422)
        );
    }
}
