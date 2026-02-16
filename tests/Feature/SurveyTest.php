<?php

use App\Models\Department;
use App\Models\Question;
use App\Models\Response;
use App\Models\Survey;
use App\Models\User;
use App\Services\SurveyStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('asigna encuesta a todos los usuarios', function () {
    $users = User::factory()->count(3)->create();
    $survey = Survey::factory()->create(['active' => true]);
    $survey->users()->syncWithoutDetaching($users->pluck('id')->mapWithKeys(fn ($id) => [$id => ['assigned_at' => now()]])->all());
    expect($survey->users()->count())->toBe(3);
});

it('asigna encuesta por departamentos', function () {
    $dept = Department::factory()->create();
    $users = User::factory()->count(2)->create();
    $dept->users()->sync($users->pluck('id'));
    $survey = Survey::factory()->create(['active' => true]);
    $survey->departments()->sync([$dept->id]);
    $assigned = User::whereHas('departments', fn ($q) => $q->where('departments.id', $dept->id))->pluck('id')->all();
    $survey->users()->sync($assigned);
    expect($survey->users()->pluck('users.id')->all())->toEqualCanonicalizing($users->pluck('id')->all());
});

it('valida respuestas requeridas', function () {
    $user = User::factory()->create();
    $survey = Survey::factory()->create();
    $q = Question::factory()->create([
        'survey_id' => $survey->id,
        'required' => true,
        'type' => 'likert',
        'options' => ['1' => 'Nunca', '2' => 'Siempre'],
    ]);
    Response::create(['question_id' => $q->id, 'user_id' => $user->id, 'value' => '2']);
    $count = Response::where('question_id', $q->id)->where('user_id', $user->id)->count();
    expect($count)->toBe(1);
});

it('calcula promedios por dimensión', function () {
    $user = User::factory()->create();
    $survey = Survey::factory()->create();
    $q1 = Question::factory()->create(['survey_id' => $survey->id, 'item' => 'Dim A', 'type' => 'scale_5']);
    $q2 = Question::factory()->create(['survey_id' => $survey->id, 'item' => 'Dim A', 'type' => 'scale_5']);
    Response::create(['question_id' => $q1->id, 'user_id' => $user->id, 'value' => '4']);
    Response::create(['question_id' => $q2->id, 'user_id' => $user->id, 'value' => '2']);
    $service = new SurveyStatsService();
    $stats = $service->dimensionStats($survey);
    expect($stats['Dim A']['avg'])->toBe(3.0);
});

