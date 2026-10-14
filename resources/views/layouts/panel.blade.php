<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('page', 'Panel') - Like Hotel</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v=30">
<link rel="stylesheet" href="{{ asset('css/panel.css') }}?v=1">
@stack('head')
</head>
<body>
<div class="admin-layout">
  <div class="sidebar">
    <h2>{{ $area === 'admin' ? 'LIKE HOTEL' : 'OWNER' }}</h2>
    @foreach($menu as $route => $label)
      <a href="{{ route($area.'.'.$route) }}" class="{{ request()->routeIs($area.'.'.$route.'*') ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
    <form method="post" action="{{ route($area.'.logout') }}">@csrf<button class="logout-btn">Logout</button></form>
  </div>

  <div class="content">
    @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert danger">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>@endif

    @yield('content')
  </div>
</div>
@stack('scripts')
</body>
</html>
