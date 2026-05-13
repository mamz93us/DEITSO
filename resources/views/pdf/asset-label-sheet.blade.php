<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 8mm; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; }
        table.sheet { border-collapse: collapse; width: 100%; table-layout: fixed; }
        table.sheet td {
            width: {{ (int) (100 / $columns) }}%;
            height: 33mm;
            padding: 0;
            vertical-align: top;
            border: 0.2mm dotted #ccc;
        }
        .label {
            box-sizing: border-box;
            width: 100%;
            height: 100%;
            padding: 2mm;
            display: table;
        }
        .row { display: table-row; }
        .cell { display: table-cell; vertical-align: middle; }
        .qr { width: 22mm; }
        .qr svg { width: 22mm; height: 22mm; }
        .meta { padding-left: 2mm; font-size: 7pt; line-height: 1.1; }
        .meta .code { font-weight: bold; font-size: 8pt; }
        .meta .brand { color: #555; font-size: 6pt; }
    </style>
</head>
<body>
@php
    $perPage = $columns * $rows;
    $pages = array_chunk($labels, $perPage);
@endphp
@foreach($pages as $pageIdx => $page)
    @if($pageIdx > 0)
        <div style="page-break-before: always;"></div>
    @endif
    <table class="sheet">
        @php
            $chunks = array_chunk($page, $columns);
        @endphp
        @foreach($chunks as $row)
            <tr>
                @for($c = 0; $c < $columns; $c++)
                    <td>
                        @if(isset($row[$c]))
                            @php $item = $row[$c]; $asset = $item['asset']; @endphp
                            <div class="label">
                                <div class="row">
                                    <div class="cell qr">{!! $item['qrSvg'] !!}</div>
                                    <div class="cell meta">
                                        <div class="code">{{ $asset->code }}</div>
                                        <div>{{ \Illuminate\Support\Str::limit($asset->name ?? $asset->assetModel?->model_name ?? '', 28) }}</div>
                                        @if($asset->serial_number)
                                            <div>SN: {{ $asset->serial_number }}</div>
                                        @endif
                                        <div class="brand">{{ config('app.name') }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </td>
                @endfor
            </tr>
        @endforeach
    </table>
@endforeach
</body>
</html>
