<?php

namespace Tests\Unit;

use App\FormBuilder\FormDefinition;
use App\FormBuilder\Theme;
use App\Services\FormBuilder\FormStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormStorageTest extends TestCase
{
    public function test_save_and_get_form()
    {
        Storage::fake('local');
        $storage = new FormStorage();
        $def = FormDefinition::fromArray([
            'id' => '01HX1JYV8QH2Y2XWZ4V8S7KJTB',
            'name' => 'Prueba',
            'elements' => [
                ['type' => 'text', 'label' => 'Nombre', 'name' => 'nombre'],
            ],
        ]);
        $storage->saveForm($def);
        $loaded = $storage->getForm($def->id);
        $this->assertNotNull($loaded);
        $this->assertSame('Prueba', $loaded->name);
        $this->assertCount(1, $loaded->elements);
    }

    public function test_append_and_read_submissions()
    {
        Storage::fake('local');
        $storage = new FormStorage();
        $formId = '01HX1JYV8QH2Y2XWZ4V8S7KJTB';
        $id1 = $storage->appendSubmission($formId, ['nombre' => 'Ana'], ['ip' => '127.0.0.1']);
        $id2 = $storage->appendSubmission($formId, ['nombre' => 'Luis'], ['ip' => '127.0.0.1']);
        $rows = $storage->readSubmissions($formId);
        $this->assertCount(2, $rows);
        $this->assertSame('Ana', $rows[0]['data']['nombre']);
        $this->assertSame('Luis', $rows[1]['data']['nombre']);
    }
}

