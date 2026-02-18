<div>
    @php
        $moods = [
            ['code' => 'sad', 'label' => 'Triste', 'class' => 'bg-[#ef4444]', 'emoji' => '😢'],
            ['code' => 'med_sad', 'label' => 'Medianamente Triste', 'class' => 'bg-[#f59e0b]', 'emoji' => '🙁'],
            ['code' => 'neutral', 'label' => 'Neutral', 'class' => 'bg-[#facc15]', 'emoji' => '😐'],
            ['code' => 'med_happy', 'label' => 'Medianamente Feliz', 'class' => 'bg-[#84cc16]', 'emoji' => '🙂'],
            ['code' => 'happy', 'label' => 'Feliz', 'class' => 'bg-[#22c55e]', 'emoji' => '😄'],
        ];
    @endphp

    @if($showPrompt)
        <div style="position:fixed; inset:0; background:rgba(2, 6, 23, 0.88); z-index:2147483647; display:flex; align-items:center; justify-content:center;">
            <div x-data="{ selected: null, processing: false }"
                 x-on:mood-saved.window="
                    processing = true;
                    setTimeout(() => {
                        window.dispatchEvent(new CustomEvent('daily-mood-updated'));
                        $wire.closePrompt();
                    }, 1200);
                 "
                 class="w-full max-w-md mx-4">
                <div class="relative bg-slate-800/95 text-white rounded-2xl shadow-2xl px-8 pt-7 pb-8 backdrop-blur border border-slate-700 rounded-xl">
                    <div class="text-center text-2xl font-semibold tracking-tight my-5" x-show="!processing">¿Cómo te sientes hoy?</div>

                    <!-- Grupo de caritas (se oculta mientras procesa) -->
                    <div class="flex items-center justify-center gap-4 mb-5" x-show="!processing">
                        @foreach($moods as $m)
                            <button type="button"
                                    x-on:click="selected='{{ $m['code'] }}'; processing = true"
                                    wire:click="setMood('{{ $m['code'] }}')"
                                    wire:loading.attr="disabled"
                                    class="mood-anim cursor-pointer {{ $m['class'] }}"
                                    title="{{ $m['label'] }}"
                                    aria-label="{{ $m['label'] }}"
                                    role="button"
                                    style="width:60px;height:60px;border-radius:9999px;display:flex;align-items:center;justify-content:center;box-shadow:0 10px 18px rgba(0,0,0,.22);">
                                <span style="font-size:2.5rem;line-height:0.85;">{{ $m['emoji'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <!-- Spinner central mientras procesa -->
                    <div class="flex flex-col items-center justify-center py-8 my-5" x-show="processing" style="display:none;">
                        <div style="
                            height:48px;
                            width:48px;
                            border-radius:9999px;
                            border:4px solid rgba(255,255,255,0.85);
                            border-top-color:transparent;
                            animation: mood-spin 0.75s linear infinite;
                        "></div>
                        <div class="mt-4 text-sm text-slate-200 text-center mt-5">Procesando tu respuesta…</div>
                    </div>

                    <div class="text-[12px] text-slate-300 text-center my-5" x-show="!processing">Se registra una vez al día</div>
                </div>
            </div>
        </div>
    @endif

    <style>
        @keyframes mood-spin {
            to {
                transform: rotate(360deg);
            }
        }
        .mood-anim {
            transition: transform .16s ease, box-shadow .16s ease;
            will-change: transform, box-shadow;
        }
        .mood-anim:hover {
            transform: translateY(-2px) scale(1.06);
            box-shadow: 0 14px 24px rgba(0,0,0,.28);
        }
        .mood-anim:active {
            transform: translateY(0) scale(0.98);
        }
        .mood-anim:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,255,255,.35), 0 14px 24px rgba(0,0,0,.28);
        }
    </style>

</div>
