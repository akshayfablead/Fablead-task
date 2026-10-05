<?php

namespace App\Http\Requests;

use App\Models\Record;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('record');

        if ($record) {
            return $this->user()->can('update', $record);
        }

        return $this->user()->can('create', Record::class);
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'status' => [
                'required',
                Rule::in([
                    'draft',
                    'active',
                    'inactive',
                ]),
            ],

            'created_by' => [
                'prohibited',
            ],
        ];
    }
}