<?php

namespace Ingenius\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ingenius\Orders\Services\OrderStatusManager;

class ChangeOrderStatusRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $orderStatusManager = app(OrderStatusManager::class);

        $statuses = $orderStatusManager->getStatuses();

        return [
            'status' => 'required|string|in:' . implode(',', array_keys($statuses)),
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
