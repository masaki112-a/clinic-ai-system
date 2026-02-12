<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Enums\VisitState;

class VisitListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validStates = collect(VisitState::cases())
            ->map(fn($case) => $case->value)
            ->implode(',');

        return [
            'state' => ['nullable', 'string'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => [
                'nullable', 
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) {
                    if ($this->has('date_from') && $this->input('date_from')) {
                        if (strtotime($value) < strtotime($this->input('date_from'))) {
                            $fail('終了日は開始日以降である必要があります');
                        }
                    }
                }
            ],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'in:id,visit_code,current_state,created_at,accepted_at'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'state.string' => '状態は文字列である必要があります',
            'date.date_format' => '日付はYYYY-MM-DD形式で入力してください',
            'date_from.date_format' => '開始日はYYYY-MM-DD形式で入力してください',
            'date_to.date_format' => '終了日はYYYY-MM-DD形式で入力してください',
            'date_to.after_or_equal' => '終了日は開始日以降である必要があります',
            'page.integer' => 'ページ番号は整数である必要があります',
            'page.min' => 'ページ番号は1以上である必要があります',
            'per_page.integer' => '件数は整数である必要があります',
            'per_page.min' => '件数は1以上である必要があります',
            'per_page.max' => '件数は100以下である必要があります',
            'sort.in' => 'ソート項目が無効です',
            'order.in' => 'ソート順が無効です',
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

    /**
     * Validate state parameter values
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('state')) {
                $states = explode(',', $this->input('state'));
                $validStates = collect(VisitState::cases())
                    ->map(fn($case) => $case->value)
                    ->toArray();

                foreach ($states as $state) {
                    if (!in_array(trim($state), $validStates)) {
                        $validator->errors()->add('state', "無効な状態です: {$state}");
                    }
                }
            }
        });
    }
}
