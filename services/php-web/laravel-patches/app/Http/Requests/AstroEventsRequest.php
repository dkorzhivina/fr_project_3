<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AstroEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'in:sun,moon'],
            'lat'  => ['nullable', 'numeric', 'between:-90,90'],
            'lon'  => ['nullable', 'numeric', 'between:-180,180'],
            'days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'from_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.in' => 'Body должен быть sun или moon',
            'lat.numeric' => 'Широта должна быть числом',
            'lat.between' => 'Широта должна быть в диапазоне от -90 до 90',
            'lon.numeric' => 'Долгота должна быть числом',
            'lon.between' => 'Долгота должна быть в диапазоне от -180 до 180',
            'days.integer' => 'Количество дней должно быть целым числом',
            'days.min' => 'Минимальное количество дней: 1',
            'days.max' => 'Максимальное количество дней: 30',
            'from_date.date' => 'Дата начала должна быть в формате YYYY-MM-DD',
            'from_date.date_format' => 'Дата начала должна быть в формате YYYY-MM-DD',
            'to_date.date' => 'Дата окончания должна быть в формате YYYY-MM-DD',
            'to_date.date_format' => 'Дата окончания должна быть в формате YYYY-MM-DD',
        ];
    }
}

