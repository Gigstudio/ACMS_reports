<?php
namespace GIG\Domain\Entities;

defined('_RUNKEY') or die;

abstract class Entity
{
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public static function fromArray(array $data): static
    {
        return new static($data);
    }

    public static function fromApiArray(array $data): static{
        return static::fromArray($data);
    }

    public function toArray(array $only = []): array{
        $result = [];
        $props = get_object_vars($this);
        if ($only) {
            foreach ($only as $field) {
                if (array_key_exists($field, $props)) {
                    $result[$field] = $props[$field];
                }
            }
        } else {
            $result = $props;
        }
        return $result;
    }

    public function update(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function equals(self $other): bool
    {
        if (property_exists($this, 'id') && property_exists($other, 'id')) {
            return $this->id === $other->id;
        }
        return $this === $other;
    }

    public function copy(): static
    {
        return unserialize(serialize($this));
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __get($name)
    {
        return property_exists($this, $name) ? $this->$name : null;
    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }
}
