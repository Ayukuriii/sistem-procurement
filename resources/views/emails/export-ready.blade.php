<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Ready</title>
</head>
<body>
    <p>Halo {{ $userName }},</p>
    <p>Export modul <strong>{{ $module }}</strong> sudah siap diunduh.</p>
    <p>
        <a href="{{ $downloadUrl }}">Unduh file Excel</a>
    </p>
    <p>Jika tautan tidak berfungsi, salin URL berikut ke browser Anda:</p>
    <p>{{ $downloadUrl }}</p>
</body>
</html>
