<div class="w-full bg-white dark:bg-gray-900 shadow-sm rounded-xl overflow-hidden font-sans text-sm border border-gray-200 dark:border-gray-700">
    <!-- Header tipo Curriculum -->
    <div class="bg-gray-50 dark:bg-gray-800 p-8 border-b border-gray-200 dark:border-gray-700">
        <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
            <!-- Avatar / Iniciales -->
            <div class="flex-shrink-0">
                @if($user->avatar_url)
                    <img src="{{ Filament\Facades\Filament::getUserAvatarUrl($user) }}" alt="{{ $user->name }}" class="w-24 h-24 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow-md">
                @else
                    <div class="w-24 h-24 rounded-full bg-primary-600 flex items-center justify-center text-white text-3xl font-bold border-4 border-white dark:border-gray-700 shadow-md">
                        {{ strtoupper(substr($user->name, 0, 1) . (str_contains($user->name, ' ') ? substr(explode(' ', $user->name)[1], 0, 1) : '')) }}
                    </div>
                @endif
            </div>

            <!-- Info Principal -->
            <div class="text-center md:text-left flex-grow">
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-2">{{ $user->name }}</h1>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-6 text-gray-600 dark:text-gray-400 mb-4 text-sm">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-envelope class="w-4 h-4 text-primary-500 flex-shrink-0" /> 
                        <span class="truncate">{{ $user->email }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-phone class="w-4 h-4 text-primary-500 flex-shrink-0" /> 
                        <span>{{ $profile->phone }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-map-pin class="w-4 h-4 text-primary-500 flex-shrink-0" /> 
                        <span>{{ $profile->city }}, {{ $profile->country }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-identification class="w-4 h-4 text-primary-500 flex-shrink-0" /> 
                        <span>{{ $profile->rut }}</span>
                    </div>
                    @if($profile->birth_date)
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-calendar class="w-4 h-4 text-primary-500 flex-shrink-0" /> 
                        <span>{{ \Carbon\Carbon::parse($profile->birth_date)->age }} años</span>
                    </div>
                    @endif
                </div>

                <div class="flex flex-wrap justify-center md:justify-start gap-3">
                    @if($profile->linkedin_url)
                        <a href="{{ $profile->linkedin_url }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-medium transition-colors">
                            <x-heroicon-m-link class="w-3.5 h-3.5" /> LinkedIn
                        </a>
                    @endif
                    @if($profile->portfolio_url)
                        <a href="{{ $profile->portfolio_url }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-50 text-purple-700 hover:bg-purple-100 text-xs font-medium transition-colors">
                            <x-heroicon-m-globe-alt class="w-3.5 h-3.5" /> Portafolio
                        </a>
                    @endif
                </div>
            </div>

            <!-- Resumen Profesional (Salario/Disponibilidad) -->
            <div class="bg-white dark:bg-gray-900 p-4 rounded-lg border border-gray-100 dark:border-gray-700 shadow-sm min-w-[200px] text-center md:text-right">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Aspiración Salarial</p>
                <p class="font-bold text-gray-900 dark:text-white text-lg mb-3">{{ $profile->currency }} ${{ number_format((float)$profile->salary_expectation, 0, ',', '.') }}</p>
                
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Disponibilidad</p>
                <div class="mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium {{ $profile->immediate_availability ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ $profile->immediate_availability ? 'Inmediata' : 'Por acordar' }}
                    </span>
                </div>

                <div class="flex flex-col gap-2 mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between md:justify-end gap-2 items-center">
                        <span class="text-xs text-gray-400 font-medium">Modalidad:</span>
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $profile->modality_availability ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between md:justify-end gap-2 items-center">
                        <span class="text-xs text-gray-400 font-medium">Traslado:</span>
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $profile->relocation_availability ? 'Disponible' : 'No' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido en 2 columnas -->
    <div class="p-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Columna Principal (Educación y Experiencia) -->
        <div class="lg:col-span-8 space-y-8">
            
            <!-- Educación -->
            @if($profile->education)
            <section>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                    <div class="p-1.5 bg-primary-100 text-primary-600 rounded-md">
                        <x-heroicon-m-academic-cap class="w-5 h-5" />
                    </div>
                    Formación Académica
                </h3>
                <div class="grid gap-4">
                    @foreach($profile->education as $edu)
                        <div class="flex items-start gap-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50">
                            <div class="flex-grow">
                                <h4 class="font-bold text-gray-900 dark:text-white">{{ $edu['title'] ?? '' }}</h4>
                                <p class="text-primary-600 dark:text-primary-400">{{ $edu['institution'] ?? '' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ isset($edu['start_date']) ? \Carbon\Carbon::parse($edu['start_date'])->format('Y') : '' }} 
                                    - 
                                    {{ isset($edu['end_date']) ? \Carbon\Carbon::parse($edu['end_date'])->format('Y') : 'Actualidad' }}
                                </p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                {{ $edu['status'] ?? '' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif

            <!-- Experiencia Laboral -->
            @if($profile->work_experience)
            <section>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                    <div class="p-1.5 bg-primary-100 text-primary-600 rounded-md">
                        <x-heroicon-m-briefcase class="w-5 h-5" />
                    </div>
                    Experiencia Laboral
                </h3>
                <div class="relative pl-3 space-y-8 before:absolute before:left-0 before:top-2 before:bottom-2 before:w-0.5 before:bg-gray-200 dark:before:bg-gray-700">
                    @foreach($profile->work_experience as $work)
                        <div class="relative pl-6">
                            <!-- Dot -->
                            <div class="absolute left-[-5px] top-1.5 w-3 h-3 rounded-full bg-primary-500 ring-4 ring-white dark:ring-gray-900"></div>
                            
                            <div class="mb-1">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $work['position'] ?? '' }}</h4>
                                <div class="flex flex-wrap items-center gap-x-2 text-primary-600 dark:text-primary-400 font-medium">
                                    <span>{{ $work['company'] ?? '' }}</span>
                                    <span class="text-gray-300">•</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ isset($work['start_date']) ? \Carbon\Carbon::parse($work['start_date'])->format('M Y') : '' }} 
                                        - 
                                        {{ isset($work['end_date']) ? \Carbon\Carbon::parse($work['end_date'])->format('M Y') : 'Actualidad' }}
                                    </span>
                                </div>
                            </div>
                            
                            @if(isset($work['functions']))
                            <div class="text-gray-600 dark:text-gray-300 mt-2 text-sm leading-relaxed">
                                <p class="whitespace-pre-line">{{ $work['functions'] }}</p>
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
            @endif

             <!-- Referencias Laborales -->
             @if($profile->references)
             <section>
                 <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                     <div class="p-1.5 bg-primary-100 text-primary-600 rounded-md">
                         <x-heroicon-m-users class="w-5 h-5" />
                     </div>
                     Referencias Laborales
                 </h3>
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                     @foreach($profile->references as $ref)
                         <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50">
                             <h4 class="font-bold text-gray-900 dark:text-white">{{ $ref['name'] ?? '' }}</h4>
                             <p class="text-sm text-primary-600 dark:text-primary-400 font-medium">{{ $ref['company'] ?? '' }}</p>
                             <div class="mt-2 space-y-1 text-xs text-gray-500 dark:text-gray-400">
                                 <div class="flex items-center gap-1.5">
                                     <x-heroicon-m-phone class="w-3 h-3" />
                                     <span>{{ $ref['phone'] ?? '' }}</span>
                                 </div>
                                 <div class="flex items-center gap-1.5">
                                     <x-heroicon-m-envelope class="w-3 h-3" />
                                     <span>{{ $ref['email'] ?? '' }}</span>
                                 </div>
                             </div>
                         </div>
                     @endforeach
                 </div>
             </section>
             @endif

        </div>

        <!-- Columna Lateral (Skills) -->
        <div class="lg:col-span-4 space-y-8">
            
            <!-- Idiomas -->
            @if($profile->languages)
            <section>
                <h3 class="font-bold text-gray-900 dark:text-white mb-3 uppercase text-xs tracking-wider border-b border-gray-200 dark:border-gray-700 pb-1">
                    Idiomas
                </h3>
                <ul class="space-y-3">
                    @foreach($profile->languages as $lang)
                        <li class="flex justify-between items-center">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $lang['language'] ?? '' }}</span>
                            <span class="text-xs text-primary-600 dark:text-primary-400 font-semibold bg-primary-50 dark:bg-primary-900/20 px-2 py-0.5 rounded">
                                {{ $lang['level'] ?? '' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
            @endif

            <!-- Habilidades Técnicas -->
            @if($profile->technical_skills)
            <section>
                <h3 class="font-bold text-gray-900 dark:text-white mb-3 uppercase text-xs tracking-wider border-b border-gray-200 dark:border-gray-700 pb-1">
                    Habilidades Técnicas
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($profile->technical_skills as $skill)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                            {{ $skill['software'] ?? '' }}
                        </span>
                    @endforeach
                </div>
            </section>
            @endif

            <!-- Habilidades Blandas -->
            @if($profile->soft_skills)
            <section>
                <h3 class="font-bold text-gray-900 dark:text-white mb-3 uppercase text-xs tracking-wider border-b border-gray-200 dark:border-gray-700 pb-1">
                    Competencias
                </h3>
                <ul class="space-y-2">
                    @foreach($profile->soft_skills as $skill)
                        <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <x-heroicon-s-star class="w-4 h-4 text-yellow-400 flex-shrink-0 mt-0.5" />
                            <span>{{ $skill['skill'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
            @endif

        </div>
    </div>
</div>