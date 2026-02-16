<?php

namespace App\FormBuilder;

use Illuminate\Support\Str;

class Theme
{
    public string $id;
    public string $name;
    public array $tokens;

    public function __construct(string $id, string $name, array $tokens)
    {
        $this->id = $id;
        $this->name = $name;
        $this->tokens = $tokens;
    }

    public static function newId(): string
    {
        return (string) Str::ulid();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? self::newId(),
            $data['name'] ?? 'Tema',
            $data['tokens'] ?? [
                'colors' => [
                    'primary' => '#288cfa',
                    'secondary' => '#103766',
                    'danger' => '#ef4444',
                    'success' => '#2E865F',
                    'gray' => '#64748b',
                ],
                'fonts' => [
                    'base' => 'system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, sans-serif',
                ],
                'spacing' => [
                    'sm' => '0.5rem',
                    'md' => '1rem',
                    'lg' => '1.5rem',
                ],
                'radius' => [
                    'sm' => '0.25rem',
                    'md' => '0.5rem',
                    'lg' => '0.75rem',
                ],
            ]
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tokens' => $this->tokens,
        ];
    }
}

