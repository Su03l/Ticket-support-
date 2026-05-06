<?php

namespace App\Services;

use App\Enums\CustomFieldAppliesTo;
use App\Enums\CustomFieldType;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CustomFieldService
{
    private const ALLOWED_RULES = ['required', 'nullable', 'string', 'integer', 'numeric', 'date', 'boolean', 'array', 'max:255', 'max:1000'];

    public function fieldsFor(User $user, CustomFieldAppliesTo $appliesTo): Collection
    {
        return CustomField::query()->where('company_id', $user->company_id)->where('applies_to', $appliesTo)->where('is_active', true)->orderBy('sort_order')->get();
    }

    public function create(User $user, array $attributes): CustomField
    {
        $rules = array_values(array_intersect(Arr::wrap($attributes['validation_rules'] ?? []), self::ALLOWED_RULES));

        return CustomField::query()->create([
            'company_id' => $user->company_id,
            'key' => Str::slug($attributes['key'] ?? $attributes['label'], '_'),
            ...$attributes,
            'validation_rules' => $rules,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    public function saveValues(Model $fieldable, array $values): void
    {
        foreach ($values as $fieldId => $value) {
            $field = CustomField::query()->where('company_id', $fieldable->company_id)->findOrFail($fieldId);

            CustomFieldValue::query()->updateOrCreate([
                'custom_field_id' => $field->id,
                'fieldable_type' => $fieldable::class,
                'fieldable_id' => $fieldable->id,
            ], [
                'company_id' => $fieldable->company_id,
                'value' => is_array($value) ? $value : ['value' => $value],
            ]);
        }
    }

    public function validationRules(CustomFieldAppliesTo $appliesTo, User $user): array
    {
        return $this->fieldsFor($user, $appliesTo)
            ->mapWithKeys(fn (CustomField $field): array => [
                "customFields.{$field->id}" => $field->is_required ? ['required'] : ['nullable'],
            ])
            ->all();
    }
}
