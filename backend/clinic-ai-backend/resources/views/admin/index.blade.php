<h1>管理画面</h1>

<form method="POST" action="{{ route('ui.exit') }}">
    @csrf
    <input type="hidden" name="ui_name" value="exam">
    <button type="submit">画面を終了</button>
</form>
