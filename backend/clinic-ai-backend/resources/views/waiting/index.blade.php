<h2>待合室</h2>

<p>待機人数：{{ $vm->waitingCount }} 人
予想待ち時間：{{ $vm->estimatedWaitMinutes }} 分  </p>

<ul>
@if($vm->callingVisitCode)
  呼出中：{{ $vm->callingVisitCode }}
@endif
</ul>

<form method="POST" action="{{ route('waiting.callNext') }}">
    @csrf
    <button type="submit">次を呼ぶ</button>
</form>

<form method="POST" action="{{ route('ui.exit') }}">
    @csrf
    <input type="hidden" name="ui_name" value="exam">
    <button type="submit">画面を終了</button>
</form>
