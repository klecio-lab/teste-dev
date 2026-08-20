<?php

namespace App\Http\Requests;

use App\Models\Pokemon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MetricQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->query('fields'))) {
            $this->merge([
                'fields' => array_values(array_filter(array_map(
                    fn (string $field) => trim($field),
                    explode(',', (string) $this->query('fields')),
                ))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'metric' => ['sometimes', 'string', Rule::in(Pokemon::METRICS)],
            'order' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'fields' => ['sometimes', 'array', 'max:'.count(Pokemon::SELECTABLE_FIELDS)],
            'fields.*' => ['string', Rule::in(Pokemon::SELECTABLE_FIELDS)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function metric(): string
    {
        return $this->validated('metric', 'hp');
    }

    public function order(): string
    {
        return $this->validated('order', 'desc');
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', 10);
    }

    /**
     * Campos a retornar; a métrica escolhida é sempre incluída.
     *
     * @return list<string>
     */
    public function fields(): array
    {
        $fields = $this->validated('fields') ?? ['name', $this->metric()];

        return array_values(array_unique([...$fields, $this->metric()]));
    }
}
