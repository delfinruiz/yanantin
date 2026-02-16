<?php

namespace App\FormBuilder;

use Illuminate\Support\Str;

class FormDefinition
{
    public string $id;
    public string $name;
    public ?string $slug;
    public int $version;
    public array $layout;
    public array $elements;
    public ?string $themeId;
    public ?array $button;

    public function __construct(
        string $id,
        string $name,
        ?string $slug,
        int $version,
        array $layout,
        array $elements,
        ?string $themeId,
        ?array $button = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->slug = $slug;
        $this->version = $version;
        $this->layout = $layout;
        $this->elements = $elements;
        $this->themeId = $themeId;
        $this->button = $button;
    }

    public static function newId(): string
    {
        return (string) Str::ulid();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? self::newId(),
            $data['name'] ?? 'Formulario',
            $data['slug'] ?? null,
            (int) ($data['version'] ?? 1),
            $data['layout'] ?? ['rows' => []],
            $data['elements'] ?? [],
            $data['themeId'] ?? null,
            $data['button'] ?? [
                'label' => 'Enviar',
                'bg_color' => '#288cfa',
                'text_color' => '#ffffff',
            ]
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'version' => $this->version,
            'layout' => $this->layout,
            'elements' => $this->elements,
            'themeId' => $this->themeId,
            'button' => $this->button,
        ];
    }
}
