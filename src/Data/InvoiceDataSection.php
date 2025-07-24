<?php

namespace Ingenius\Orders\Data;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class InvoiceDataSection implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * @var string
     */
    protected string $title;

    /**
     * @var array<string, mixed>
     */
    protected array $properties;

    /**
     * @var int
     */
    protected int $order = 50;

    /**
     * Create a new invoice data section.
     *
     * @param string $title
     * @param array<string, mixed> $properties
     * @param int $order
     */
    public function __construct(string $title, array $properties = [], int $order = 50)
    {
        $this->title = $title;
        $this->properties = $properties;
        $this->order = $order;
    }

    /**
     * Get the section title.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the section properties.
     *
     * @return array<string, mixed>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Get the section order.
     *
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * Add a property to the section.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addProperty(string $key, $value): self
    {
        $this->properties[$key] = $value;
        return $this;
    }

    /**
     * Convert the section to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'properties' => $this->properties,
            'order' => $this->order,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
