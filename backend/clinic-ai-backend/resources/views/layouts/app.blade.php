<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Clinic AI</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            background: #f5f5f5;
            margin: 0;
        }
        header {
            background: #2c3e50;
            color: white;
            padding: 12px 20px;
        }
        nav a {
            color: white;
            margin-right: 12px;
            text-decoration: none;
            font-weight: bold;
        }
        main {
            padding: 24px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
        }
        button {
            padding: 10px 16px;
            font-size: 16px;
        }
    </style>
</head>
<body>
<header>
    <nav>
        <a href="{{ route('home') }}">🏠 ホーム</a>
        <a href="{{ route('reception') }}">受付</a>
        <a href="{{ route('waiting') }}">待合</a>
        <a href="{{ route('exam') }}">診察</a>
        <a href="{{ route('admin') }}">管理</a>
    </nav>
</header>

<main>
    @yield('content')
</main>
</body>
</html>
