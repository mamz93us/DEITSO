<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            width: 50mm;
            height: 30mm;
        }
        .label {
            box-sizing: border-box;
            width: 50mm;
            height: 30mm;
            padding: 1.5mm;
            border: 0.3mm solid #999;
            display: table;
        }
        .row { display: table-row; }
        .cell { display: table-cell; vertical-align: middle; }
        .qr { width: 22mm; }
        .qr svg { width: 22mm; height: 22mm; }
        .meta { padding-left: 2mm; font-size: 7pt; line-height: 1.1; }
        .meta .code { font-weight: bold; font-size: 8pt; letter-spacing: 0.3pt; }
        .meta .brand { color: #555; font-size: 6pt; }
    </style>
</head>
<body>
<div class="label">
    <div class="row">
        <div class="cell qr">{!! $qrSvg !!}</div>
        <div class="cell meta">
            <div class="code">{{ $asset->code }}</div>
            <div>{{ \Illuminate\Support\Str::limit($asset->name ?? $asset->assetModel?->model_name ?? '', 35) }}</div>
            @if($asset->serial_number)
                <div>SN: {{ $asset->serial_number }}</div>
            @endif
            <div class="brand">{{ config('app.name') }}</div>
        </div>
    </div>
</div>
</body>
</html>
