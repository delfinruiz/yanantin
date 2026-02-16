<?php

namespace Database\Seeders;

use App\Models\Survey;
use App\Models\Question;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        $survey = Survey::create([
            'title' => 'Encuesta de Clima Laboral',
            'description' => 'Plantilla estándar de clima laboral',
            'active' => true,
        ]);

        $likert = [
            '1' => 'Nunca',
            '2' => 'Casi nunca',
            '3' => 'A veces',
            '4' => 'Casi siempre',
            '5' => 'Siempre',
        ];

        $questions = [
            'Condiciones de trabajo' => [
                'Dispongo de los materiales y recursos necesarios para realizar mi trabajo?',
                'Las condiciones de trabajo permiten desempeñar mi trabajo con normalidad?',
                'Los materiales necesarios se me entregan en el tiempo requerido?',
                'Mi trabajo es seguro y no afecta mi salud?',
            ],
            'Relaciones interpersonales' => [
                'Las personas con las que me relaciono actúan con respeto y de manera ética?',
                'Cuento con la colaboración de personas de otros departamentos?',
                'Mi jefe inmediato tiene actitud abierta respecto a mis opiniones?',
                'El área de trabajo existe un ambiente de confianza?',
            ],
            'Comunicación interna' => [
                'La comunicación interna en la entidad es permanente y planificada?',
                'Recibe retroalimentación sobre las labores que realiza?',
                'La comunicación sobre resultados y estado actual de la entidad es clara?',
                'Recibió inducción suficiente y clara sobre la entidad al momento de su ingreso?',
            ],
            'Reconocimiento profesional' => [
                'Siento reconocimiento por mi desempeño profesional?',
                'La entidad valora y reconoce los logros de su personal?',
            ],
            'Satisfacción general' => [
                'Estoy satisfecho con mi trabajo en general?',
            ],
        ];

        $order = 1;
        foreach ($questions as $item => $qs) {
            foreach ($qs as $content) {
                Question::create([
                    'survey_id' => $survey->id,
                    'content' => $content,
                    'type' => 'likert',
                    'item' => $item,
                    'required' => true,
                    'options' => $likert,
                    'order' => $order++,
                ]);
            }
        }
    }
}

