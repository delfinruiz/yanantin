@extends('layouts.app')

@section('content')
<section class="yc _j pg hh">
  <div class="rc sm:bd yf">
    <div class="qg">
      <h2 class="oe zk ap">Bienvenido, {{ $user->name }}</h2>
      <p class="ap zd">Este es tu panel como candidato. Aquí verás tus datos y las ofertas disponibles.</p>
    </div>

    <div class="qg">
      <h3 class="yg ap">Ofertas Recientes</h3>
      @if($offers->isEmpty())
        <p class="ap">No hay publicaciones recientes.</p>
      @else
        <div class="grid lg:ee qf _g">
          @foreach ($offers as $offer)
            <article class="ce fd gg c kb hc uh vh ii uo so">
              <h4 class="ap yg">{{ $offer->title }}</h4>
              <div class="ap gh"><span>{{ $offer->location }}</span> • <span>{{ $offer->contract_type }}</span></div>
              <p class="ap zj">{{ \Illuminate\Support\Str::limit(strip_tags($offer->description), 120) }}</p>
              <a class="yd bd re ef jj kk" href="{{ route('careers.index') }}">Ver detalles</a>
            </article>
          @endforeach
        </div>
      @endif
    </div>

    <div class="qg">
      <h3 class="yg ap">Historial de Postulaciones</h3>
      <p class="ap zj">Aún no has realizado postulaciones.</p>
    </div>
  </div>
</section>
@endsection

