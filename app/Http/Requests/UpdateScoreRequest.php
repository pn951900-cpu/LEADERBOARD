<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'   => ['nullable', 'integer', 'exists:users,id'],
            'season_id' => ['nullable', 'integer', 'exists:seasons,id'],
            'points'    => ['required', 'integer', 'min:1', 'max:10000'], // Số điểm cộng thêm
        ];
    }

    public function messages(): array
    {
        return [
            'points.required' => 'Vui lòng cung cấp số điểm cần cộng (points).',
            'points.integer'  => 'Số điểm phải là một số nguyên.',
            'points.min'      => 'Số điểm cộng tối thiểu là 1 điểm.',
            'points.max'      => 'Số điểm cộng một lần tối đa là 10,000 điểm.',
            'user_id.exists'  => 'Người dùng không tồn tại.',
            'season_id.exists'=> 'Kỳ thi đấu không tồn tại.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu cập nhật điểm không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
