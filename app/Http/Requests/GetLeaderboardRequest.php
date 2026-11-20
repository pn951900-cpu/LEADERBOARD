<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetLeaderboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'season_id' => ['nullable', 'integer', 'exists:seasons,id'],
            'page'      => ['nullable', 'integer', 'min:1'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'season_id.exists'  => 'Kỳ thi đấu (season_id) không tồn tại.',
            'page.min'          => 'Số trang (page) phải lớn hơn hoặc bằng 1.',
            'per_page.max'      => 'Số lượng hiển thị (per_page) không được vượt quá 100 bản ghi/trang.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu yêu cầu không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}

