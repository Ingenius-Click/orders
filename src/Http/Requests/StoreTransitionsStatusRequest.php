<?php

namespace Ingenius\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ingenius\Orders\Services\OrderStatusManager;

class StoreTransitionsStatusRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $statusManager = app(OrderStatusManager::class);

        $registeredStatusIds = array_keys($statusManager->getStatuses());

        return [
            'transitions' => 'required|array',
            'transitions.*.from_status' => [
                'required',
                'string',
                Rule::in($registeredStatusIds),
            ],
            'transitions.*.to_status' => [
                'required',
                'string',
                Rule::in($registeredStatusIds),
            ],
            'transitions.*.is_enabled' => 'boolean',
            'transitions.*.sort_order' => 'integer',
            'transitions.*.module' => 'nullable|string',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
