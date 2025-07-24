<?php

namespace Ingenius\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderStatusTransition extends Model
{
    use HasFactory;

    protected $table = 'order_status_transitions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'from_status',
        'to_status',
        'is_enabled',
        'sort_order',
        'module',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the identifier of the from status
     */
    public function getFromStatusIdentifier(): string
    {
        return $this->from_status;
    }

    /**
     * Get the identifier of the to status
     */
    public function getToStatusIdentifier(): string
    {
        return $this->to_status;
    }
}
