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
        <div class="cell qr"><?php echo $qrSvg; ?></div>
        <div class="cell meta">
            <div class="code"><?php echo e($asset->code); ?></div>
            <div><?php echo e(\Illuminate\Support\Str::limit($asset->name ?? $asset->assetModel?->model_name ?? '', 35)); ?></div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($asset->serial_number): ?>
                <div>SN: <?php echo e($asset->serial_number); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div class="brand"><?php echo e(config('app.name')); ?></div>
        </div>
    </div>
</div>
</body>
</html>
<?php /**PATH C:\Users\MohamedZahran\Downloads\DEITAM\resources\views/pdf/asset-label.blade.php ENDPATH**/ ?>