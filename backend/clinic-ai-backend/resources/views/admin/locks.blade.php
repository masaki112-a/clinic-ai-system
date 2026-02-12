<h1>UIロック管理</h1>

<table border="1">
<tr>
    <th>UI</th>
    <th>使用者</th>
    <th>期限</th>
    <th>操作</th>
</tr>

@foreach ($locks as $lock)
<tr>
    <td>{{ $lock->ui_name }}</td>
    <td>{{ $lock->locked_by }}</td>
    <td>{{ $lock->expires_at }}</td>
    <td>
        <form method="POST" action="{{ route('admin.locks.forceUnlock') }}">
            @csrf
            <input type="hidden" name="ui_name" value="{{ $lock->ui_name }}">
            <input type="text" name="reason" placeholder="解除理由（必須）">
            <button type="submit">強制解除</button>
        </form>
    </td>
</tr>
@endforeach
</table>