@php
    $record = $getRecord();
@endphp

<div class="space-y-6">
    <x-filament::section heading="Adjuntos del currículum" description="Archivos cargados por el postulante en su currículum. No se pueden renombrar ni eliminar.">
        @livewire(\App\Livewire\JobApplications\CvFilesTable::class, ['jobApplicationId' => $record->id], key('cv-files-'.$record->id))
    </x-filament::section>

    <x-filament::section heading="Archivos adicionales" description="Puedes subir archivos extra para esta postulación. Estos sí se pueden renombrar y eliminar.">
        @livewire(\App\Livewire\JobApplications\AdditionalFilesTable::class, ['jobApplicationId' => $record->id], key('app-files-'.$record->id))
    </x-filament::section>
</div>

