@extends('layouts.app')


@section('content')
<h1>ホーム画面</h1>

<ul>
@foreach ($uiStatus as $ui)
    <li>
        {{ $ui['label'] }} :
        @if ($ui['is_locked'])
            🔒 使用中（{{ $ui['locked_by'] }}）
            @if (!is_null($ui['remaining']))
                / 残り {{ $ui['remaining'] }} 秒
            @endif
        @else
            🟢 空き
        @endif
    </li>

    @if (! $ui['is_locked'])
        <form method="POST" action="{{ route('home.launch') }}" style="display:inline;">
            @csrf
            <input type="hidden" name="ui_name" value="{{ $ui['name'] }}">
            <button type="submit">起動</button>
        </form>
    @endif

    

@endforeach

</ul>

@endsection