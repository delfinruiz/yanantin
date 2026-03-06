@extends('layouts.app')

@section('css')
  <meta name="description" content="Explora oportunidades laborales y únete a nuestro equipo. Ofertas activas y actualizadas.">
  <link rel="canonical" href="{{ url()->current() }}">
  <style>
    .careers-filter-card {
      border-radius: 1rem;
      padding: 2.5rem 3rem;
      background: #ffffff;
      box-shadow: 0 22px 45px rgba(15, 23, 42, 0.08);
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
      align-items: flex-start;
    }
    .b .careers-filter-card {
      background: radial-gradient(circle at 0% 0%, rgba(59, 130, 246, 0.18), rgba(15, 23, 42, 0.98));
      border: 1px solid rgba(148, 163, 184, 0.25);
      box-shadow: 0 28px 80px rgba(15, 23, 42, 0.75);
    }
    .careers-filter-heading {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }
    .careers-filter-heading h2 {
      font-size: clamp(1.8rem, 2.1vw, 2.2rem);
      color: #1e293b;
    }
    .careers-filter-heading p {
      font-size: 0.95rem;
      color: #64748b;
    }
    .careers-filter-card label {
      color: #334155;
      font-weight: 500;
    }
    .lk {
      color: #475569;
    }
    .b .lk {
      color: #94a3b8;
    }
    .b .careers-filter-card {
      color: #f9fafb;
    }
    .b .careers-filter-heading h2 {
      color: #ffffff;
    }
    .b .careers-filter-heading p {
      color: rgba(241, 245, 249, 0.92);
    }
    .b .careers-filter-card label {
      color: #e5e7eb;
    }
    .b .careers-filter-card input {
      color: #e5e7eb;
    }
    .b .careers-filter-card select {
      color: #e5e7eb;
      background-color: rgba(15, 23, 42, 0.65);
    }
    .b .careers-filter-card select option {
      color: #0f172a;
      background-color: #ffffff;
    }
    .careers-filter-form {
      width: 100%;
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 1.25rem 1.25rem;
      align-items: flex-end;
    }
    .careers-filter-field {
      width: 100%;
    }
    .careers-filter-cta {
      justify-self: center;
    }
    .careers-filter-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;     
      padding: 0.95rem 3.6rem;
      min-width: 15rem;
      border-radius: 999px;
      background-image: linear-gradient(135deg, #4138ecd5, #1d65dac4);
      color: #ffffff;
      font-size: 0.95rem;
      font-weight: 600;
      max-width: 100%;
      transform-origin: center;
      transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
      border: none;
    }
    .careers-filter-button:hover {
      filter: brightness(1.05);
      transform: translateY(-1px);
    }
    .careers-filter-button:active {
      transform: translateY(0);
      box-shadow: 0 12px 30px rgba(30, 64, 175, 0.6);
      filter: brightness(0.97);
    }
    .careers-filter-button span {
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .careers-modal-overlay {
      position: fixed;
      inset: 0;
      padding: 1.5rem;
      background: rgba(15, 23, 42, 0.75);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 2147483647;
      pointer-events: auto;
      backdrop-filter: blur(3px);
    }
    .careers-modal {
      width: 100%;
      max-width: 48rem;
      max-height: 90vh;
      overflow-y: auto;
      border-radius: 1rem;
      background: #ffffff;
      color: #0f172a;
      position: relative;
      padding: 2.25rem 2.5rem 2.1rem;
      box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
      border: 1px solid rgba(148, 163, 184, 0.25);
      scrollbar-width: none;
    }
    .careers-modal::-webkit-scrollbar {
      width: 0;
      height: 0;
    }
    .b .careers-modal {
      background: #151b2f;
      color: #e5e7eb;
      box-shadow: 0 30px 90px rgba(15, 23, 42, 0.8);
      border-color: rgba(148, 163, 184, 0.35);
    }
    .careers-modal-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1.5rem;
    }
    .careers-modal-subtitle {
      font-size: 0.9rem;
      color: #64748b;
      margin-bottom: 0.25rem;
    }
    .careers-modal-title {
      font-size: 1.35rem;
      font-weight: 600;
      letter-spacing: -0.01em;
    }
    .careers-modal-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem 1.1rem;
      font-size: 0.88rem;
      color: #6b7280;
      margin-bottom: 1.25rem;
    }
    .careers-modal-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.3rem 0.7rem;
      border-radius: 9999px;
      background: #e0edff;
      color: #1d4ed8;
      font-size: 0.8rem;
      font-weight: 500;
      margin-top: 2rem;
    }
    .careers-modal-section {
      margin-bottom: 1.4rem;
    }
    .careers-modal-section-title {
      font-size: 0.92rem;
      font-weight: 600;
      margin-bottom: 0.45rem;
    }
    .careers-modal-section-body {
      font-size: 0.9rem;
      line-height: 1.7;
      color: #111827;
    }
    .careers-modal-section-body ul {
      list-style-type: disc;
      padding-left: 1.25rem;
      margin: 0.35rem 0;
    }
    .careers-modal-section-body p {
      margin-bottom: 0.35rem;
    }
    body:not(.b) .careers-offers-count {
      font-size: 0.95rem;
      font-weight: 500;
      color: #4b5563;
    }
    .careers-modal-footer {
      display: flex;
      justify-content: flex-end;
      margin-top: 0.5rem;
    }
    .careers-modal-apply {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      padding: 0.7rem 1.5rem;
      border-radius: 9999px;
      background-image: linear-gradient(135deg, #4138ecd5, #1d65dac4);
      color: #ffffff;
      font-size: 0.9rem;
      font-weight: 600;
      border: none;
    }
    .careers-modal-apply svg {
      width: 15px;
      height: 15px;
    }
    .careers-modal-close {
      position: absolute;
      top: 0.9rem;
      right: 0.9rem;
      cursor: pointer;
      width: 32px;
      height: 32px;
      border-radius: 9999px;
      border: 1px solid rgba(148, 163, 184, 0.6);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(248, 250, 252, 0.96);
      color: #020617;
    }
    .careers-modal-close svg {
      width: 16px;
      height: 16px;
    }
    .careers-modal-close:hover {
      background: #e5e7eb;
    }
    .b .careers-modal-subtitle {
      color: rgba(148, 163, 184, 0.95);
    }
    .b .careers-modal-meta {
      color: rgba(148, 163, 184, 0.98);
    }
    .b .careers-modal-badge {
      background: rgba(37, 99, 235, 0.35);
      color: #e0f2fe;
    }
    .b .careers-modal-section-body {
      color: #e5e7eb;
    }
    .b .careers-modal-close {
      background: rgba(15, 23, 42, 0.9);
      color: #e5e7eb;
      border-color: rgba(148, 163, 184, 0.7);
    }
    .b .careers-modal-close:hover {
      background: rgba(15, 23, 42, 1);
    }
    @media (max-width: 640px) {
      .careers-modal {
        padding: 1.75rem 1.5rem 1.6rem;
        border-radius: 0.9rem;
      }
      .careers-modal-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
      }
      .careers-modal-title {
        font-size: 1.15rem;
      }
    }
    @media (max-width: 1024px) {
      .careers-filter-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }
    @media (max-width: 768px) {
      .careers-filter-card {
        padding: 1.75rem 1.5rem;
      }
      .careers-filter-form {
        grid-template-columns: minmax(0, 1fr);
      }
      .careers-filter-cta {
        justify-self: stretch;
      }
      .careers-filter-button {
        width: 100%;
      }
    }
  </style>
@endsection

@section('content')
<main>
  <section class="i pg ji gp uq">
    <img src="{{ asset('/asset/images/portada/shape-06.svg') }}" alt="Shape" class="h aa y" style="left: 0;" />
    <img src="{{ asset('/asset/images/portada/shape-07.svg') }}" alt="Shape" class="h w da ee" style="bottom: 0;" />
    <img src="{{ asset('/asset/images/portada/shape-12.svg') }}" alt="Shape" class="h p s" />
    <img src="{{ asset('/asset/images/portada/shape-13.svg') }}" alt="Shape" class="h r q" />

    <div class="bb ye ki xn vq z-10">
      <div class="animate_top careers-filter-card my-5">
        <div class="careers-filter-heading">
          <h2 class="fk vj pr kk wm">Trabaja con Nosotros</h2>
          <p>Filtra por título, ubicación y tipo de contrato para encontrar tu próxima oportunidad.</p>
        </div>

        <form method="GET" action="{{ route('careers.index') }}" class="careers-filter-form">
          <div class="vd to/2 careers-filter-field">
            <label class="rc" for="q">Buscar</label>
            <div class="rc wf">
              <input id="q" type="text" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por título o descripción"
                     class="vd ph sg zk xm _g ch pm hm dm dn em pl/50 xi mi" />
            </div>
          </div>

          <div class="vd to/4 careers-filter-field">
            <label class="rc" for="location">Ubicación</label>
            <select id="location" name="location" class="vd ph sg zk xm _g ch pm hm dm dn em pl/50 xi mi">
              <option value="">Todas las ubicaciones</option>
              @foreach($locations as $loc)
                <option value="{{ $loc }}" @selected($filters['location']===$loc)>{{ $loc }}</option>
              @endforeach
            </select>
          </div>

          <div class="vd to/4 careers-filter-field">
            <label class="rc" for="contract_type">Contrato</label>
            <select id="contract_type" name="contract_type" class="vd ph sg zk xm _g ch pm hm dm dn em pl/50 xi mi">
              <option value="">Todos los contratos</option>
              @foreach($contracts as $ct)
                <option value="{{ $ct }}" @selected($filters['contract_type']===$ct)>{{ $ct }}</option>
              @endforeach
            </select>
          </div>

          <div class="vd to/4 tc xf careers-filter-cta">
            <label class="rc">&nbsp;</label>
            <button type="submit" class="careers-filter-button">
              <span>Filtrar</span>
            </button>
          </div>
        </form>
        {{-- Chips de contrato removidos para un layout más limpio --}}
      </div>

      <div class="bb ye ki xn vq jb jo mt-6">
        <div class="bb ye ki xn vq z-10 animate_top" style="margin-bottom: 20px !important;">
          <p class="lk careers-offers-count">{{ $offers->total() }} ofertas</p>
        </div>

        @if ($offers->count())
          <div class="wc qf pn xo zf iq">
            @foreach ($offers as $offer)
              <article class="animate_top sg vk rm xm" x-data="{open:false}" x-on:keydown.escape.window="open=false">
                <div class="c rc i z-1 pg wg">
                  @if($offer->image)
                      <img class="w-full" src="{{ asset('storage/' . $offer->image) }}" alt="Oferta laboral" style="object-fit: cover; height: 300px;" />
                  @else
                      <img class="w-full" src="{{ asset('/asset/images/portada/blog-01.png') }}" alt="Oferta laboral" style="object-fit: cover; height: 300px;" />
                  @endif

                  <div class="im h r s df vd yc wg tc wf xf al hh/20 nl il z-10">
                    <button type="button"
                            class="vc ek rg gh sl ml il gi hi text-white"
                            style="color: #ffffff !important;"
                            @click.prevent="open = true">
                      Leer más
                    </button>
                  </div>
                </div>

                <div class="yh">
                  <div class="tc uf wf ag jq">
                    <div class="tc wf ag">
                      <img src="{{ asset('/asset/images/portada/icon-man.svg') }}" alt="Ubicación" />
                      <p>{{ $offer->location }}</p>
                    </div>
                    <div class="tc wf ag">
                      <img src="{{ asset('/asset/images/portada/icon-calender.svg') }}" alt="Publicado" />
                      <p>
                        {{ optional($offer->published_at)->format('d M, Y') }}
                      </p>
                    </div>
                  </div>
                  <h4 class="ek tj ml il kk wm xl eq lb">
                    <a href="javascript:void(0)" @click.prevent="open = true">
                      {{ $offer->title }}
                    </a>
                  </h4>
                  <p class="lk">{{ \Illuminate\Support\Str::limit(strip_tags($offer->description), 140) }}</p>
                </div>

                <template x-teleport="body">
                  <div x-show="open" x-cloak class="careers-modal-overlay">
                    <div class="careers-modal">
                      <button class="careers-modal-close" @click.stop="open=false">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                          <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                      </button>
                      <div class="careers-modal-header">
                        <div>
                          <div class="careers-modal-subtitle">Detalle de la oferta</div>
                          <div class="careers-modal-title">{{ $offer->title }}</div>
                        </div>
                        @if($offer->contract_type)
                          <span class="careers-modal-badge">
                            {{ $offer->contract_type }}
                          </span>
                        @endif
                      </div>
                      <div class="careers-modal-meta">
                        <span>{{ $offer->location }}</span>
                        @if($offer->deadline)
                          <span>Postula hasta {{ $offer->deadline->format('d M, Y') }}</span>
                        @endif
                        @if($offer->published_at)
                          <span>Publicado {{ $offer->published_at->format('d M, Y') }}</span>
                        @endif
                      </div>
                      <div class="careers-modal-section">
                        <div class="careers-modal-section-title">Descripción</div>
                        <div class="careers-modal-section-body">{!! $offer->description !!}</div>
                      </div>
                      <div class="careers-modal-section">
                        <div class="careers-modal-section-title">Requisitos</div>
                        <div class="careers-modal-section-body">
                          @if($offer->jobOfferRequirements->isNotEmpty())
                            <ul style="list-style-type: disc; padding-left: 1.25rem;">
                              @foreach($offer->jobOfferRequirements as $req)
                                <li>
                                  <strong>{{ $req->category }}</strong> ({{ $req->type }}): 
                                  {{ $req->evidence ?? $req->level }}
                                </li>
                              @endforeach
                            </ul>
                          @else
                            —
                          @endif
                        </div>
                      </div>
                      <div class="careers-modal-section">
                        <div class="careers-modal-section-title">Beneficios</div>
                        <div class="careers-modal-section-body">{!! $offer->benefits ?? '—' !!}</div>
                      </div>
                      <div class="careers-modal-footer">
                        <button
                          type="button"
                          class="careers-modal-apply"
                          @click.prevent="window.location.href='{{ auth()->check() ? \App\Filament\Resources\JobOffers\JobOfferResource::getUrl() : route('filament.admin.auth.login') }}'">
                          <span>Postular Ahora</span>
                          <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10h10m0 0L9 5m5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                </template>
              </article>
            @endforeach
          </div>

          <div class="mb lo bq i ua">
            @php
              $current = $offers->currentPage();
              $last = $offers->lastPage();
              $window = 1;
              $pages = collect([1, $last])
                ->merge(range(max(1, $current - $window), min($last, $current + $window)))
                ->unique()
                ->sort()
                ->values();
            @endphp
            <nav>
              <ul class="tc wf xf bg">
                {{-- Prev --}}
                <li>
                  @if ($offers->onFirstPage())
                    <span class="c tc wf xf wd in zc hn rg uj fo wk xm ml il hh rm tl zm yl an opacity-40 cursor-not-allowed">
                      <svg class="th lm ml il" width="8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.93884 6.99999L7.88884 11.95L6.47484 13.364L0.11084 6.99999L6.47484 0.635986L7.88884 2.04999L2.93884 6.99999Z" />
                      </svg>
                    </span>
                  @else
                    <a class="c tc wf xf wd in zc hn rg uj fo wk xm ml il hh rm tl zm yl an"
                       href="{{ $offers->previousPageUrl() }}">
                      <svg class="th lm ml il" width="8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.93884 6.99999L7.88884 11.95L6.47484 13.364L0.11084 6.99999L6.47484 0.635986L7.88884 2.04999L2.93884 6.99999Z" />
                      </svg>
                    </a>
                  @endif
                </li>

                {{-- Pages --}}
                @php $prev = null; @endphp
                @foreach ($pages as $page)
                  @if (! is_null($prev) && $page - $prev > 1)
                    <li>
                      <span class="c tc wf xf wd in zc hn rg uj fo wk xm ml il hh rm tl zm yl an">
                        ...
                      </span>
                    </li>
                  @endif
                  <li>
                    @if ($page === $current)
                      <span class="c tc wf xf wd in zc hn rg uj fo wk xm ml il lk gh sl tl zm yl an">
                        {{ $page }}
                      </span>
                    @else
                      <a class="c tc wf xf wd in zc hn rg uj fo wk xm ml il hh rm tl zm yl an"
                         href="{{ $offers->url($page) }}">
                        {{ $page }}
                      </a>
                    @endif
                  </li>
                  @php $prev = $page; @endphp
                @endforeach

                {{-- Next --}}
                <li>
                  @if ($offers->hasMorePages())
                    <a class="c tc wf xf wd in zc hn rg uj fo wk xm ml il hh rm tl zm yl an"
                       href="{{ $offers->nextPageUrl() }}">
                      <svg class="th lm ml il" width="8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5.06067 7.00001L0.110671 2.05001L1.52467 0.636014L7.88867 7.00001L1.52467 13.364L0.110672 11.95L5.06067 7.00001Z" fill="#fefdfo" />
                      </svg>
                    </a>
                  @else
                    <span class="c tc wf xf wd in zc hn rg uj fo wk xm ml il hh rm tl zm yl an opacity-40 cursor-not-allowed">
                      <svg class="th lm ml il" width="8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5.06067 7.00001L0.110671 2.05001L1.52467 0.636014L7.88867 7.00001L1.52467 13.364L0.110672 11.95L5.06067 7.00001Z" fill="#fefdfo" />
                      </svg>
                    </span>
                  @endif
                </li>
              </ul>
            </nav>
          </div>
        @else
          <div class="animate_top bb ze rj ki xn vq tc _o">
            <p class="lk">No hay ofertas disponibles en este momento.</p>
          </div>
        @endif
      </div>
    </div>
  </section>
</main>
@endsection
