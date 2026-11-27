<?php

namespace App\Http\Requests\Admin\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        foreach (['livemode', 'reconciled'] as $field) {

            if ($this->has($field)) {

                $value = filter_var(
                    $this->input($field),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );

                if ($value !== null) {
                    $values[$field] = $value;
                }
            }
        }

        if (is_string($this->input('currency'))) {

            $values['currency'] = strtolower(
                $this->input('currency')
            );
        }

        $this->merge($values);
    }

    public function rules(): array
    {
        return [

            'currency' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[a-z]{3}$/',
            ],

            'livemode' => [
                'sometimes',
                'required',
                'boolean',
            ],

            'from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:from',
            ],

            'months' => [
                'sometimes',
                'integer',
                'min:1',
                'max:24',
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],

            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'status' => [
                'nullable',
                'string',
                'max:50',
            ],

            'type' => [
                'nullable',
                'string',
                'max:50',
            ],

            'direction' => [
                'nullable',
                Rule::in(['credit', 'debit']),
            ],

            'store_id' => [
                'nullable',
                'integer',
                'exists:stores,id',
            ],

            'store_subscription_id' => [
                'nullable',
                'integer',
                'exists:store_subscriptions,id',
            ],

            'platform_invoice_id' => [
                'nullable',
                'integer',
                'exists:platform_invoices,id',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:platform_expense_categories,id',
            ],

            'provider' => [
                'nullable',
                'string',
                'max:100',
            ],

            'reconciled' => [
                'sometimes',
                'boolean',
            ],

            'q' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}