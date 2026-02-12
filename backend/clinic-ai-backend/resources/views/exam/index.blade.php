@extends('layouts.app')

@php
    use App\Models\ExamSession;

    $lockedByOther =
        $lock
        && $state === ExamSession::STATE_IN_EXAM
        && $lock->locked_by !== request()->ip(); // or terminal_id
@endphp

@section('content')
<div class="container">
    <h1>診察室</h1>

    <p>
        現在の状態：
        <strong>{{ $state }}</strong>
    </p>

    {{-- 🔒 UIロック状態表示 --}}
    @if ($lockedByOther)
        <div style="margin-bottom: 16px; color: red; font-weight: bold;">
            🔒 この診察画面は現在
            <strong>{{ $lock->locked_by }}</strong>
            により使用中です。操作できません。
        </div>
    @endif

    {{-- idle --}}
    @if ($state === \App\Models\ExamSession::STATE_IDLE)
        <form method="POST" action="{{ route('exam.call') }}">
            @csrf
            <button type="submit" @if($lockedByOther) disabled @endif>
                呼出
            </button>
        </form>
    @endif

    {{-- calling --}}
    @if ($state === \App\Models\ExamSession::STATE_CALLING)
        <form method="POST" action="{{ route('exam.recall') }}">
            @csrf
            <button type="submit" @if($lockedByOther) disabled @endif>
                再呼出
            </button>
        </form>

        <form method="POST" action="{{ route('exam.start') }}">
            @csrf
            <button type="submit" @if($lockedByOther) disabled @endif>
                診察開始
            </button>
        </form>
    @endif

    {{-- in_exam --}}
    @if ($state === \App\Models\ExamSession::STATE_IN_EXAM)
        <form method="POST" action="{{ route('exam.end') }}">
            @csrf
            <button type="submit" @if($lockedByOther) disabled @endif>
                診察終了
            </button>
        </form>
    @endif

    {{-- finished --}}
    @if ($state === \App\Models\ExamSession::STATE_FINISHED)
        <p>診察は終了しました。</p>
    @endif

    @if(app()->environment('local'))
        <form method="POST" action="{{ route('exam.reset') }}">
            @csrf
            <button type="submit" style="margin-top:20px;color:red;">
                🔧 開発用：状態リセット
            </button>
        </form>
    @endif

    <form method="POST" action="{{ route('ui.exit') }}">
        @csrf
        <input type="hidden" name="ui_name" value="exam">
        <button type="submit">画面を終了</button>
    </form>



</div>
@endsection



