<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

/**
 * Base API Request with JSON validation response
 */
abstract class ApiRequest extends FormRequest
{
    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Sanitize input before validation
     */
    protected function prepareForValidation()
    {
        $this->merge(
            collect($this->all())->map(function ($value) {
                if (is_string($value)) {
                    return strip_tags(trim($value));
                }
                return $value;
            })->toArray()
        );
    }
}

/**
 * Audit Request Validator
 */
class AuditRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true; // Allow all authenticated users
    }

    public function rules(): array
    {
        return [
            'url' => 'required|url|max:2048',
            'depth' => 'nullable|integer|min:1|max:10',
            'follow_links' => 'nullable|boolean',
            'screenshot' => 'nullable|boolean',
        ];
    }
}

/**
 * Search Request Validator
 */
class SearchRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => 'nullable|string|max:100',
            'category' => 'nullable|string|in:SSC,Banking,Railway,UPSC,Teaching,Defense,Police,Engineering,Other',
            'department' => 'nullable|string|max:100',
            'last_date_from' => 'nullable|date',
            'last_date_to' => 'nullable|date|after:last_date_from',
        ];
    }
}

/**
 * Subscription Request Validator
 */
class SubscriptionRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()?->id === $this->input('user_id');
    }

    public function rules(): array
    {
        return [
            'plan_id' => 'required|string|exists:plans,id',
            'payment_method' => 'required|string|in:phonepe,stripe,razorpay',
            'payment_id' => 'required|string',
        ];
    }
}
