<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 8mm; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; }
        table.sheet { border-collapse: collapse; width: 100%; table-layout: fixed; }
        table.sheet td {
            width: <?php echo e((int) (100 / $columns)); ?>%;
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
<?php
    $perPage = $columns * $rows;
    $pages = array_chunk($labels, $perPage);
?>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pageIdx => $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pageIdx > 0): ?>
        <div style="page-break-before: always;"></div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <table class="sheet">
        <?php
            $chunks = array_chunk($page, $columns);
        ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $chunks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($c = 0; $c < $columns; $c++): ?>
                    <td>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($row[$c])): ?>
                            <?php $item = $row[$c]; $asset = $item['asset']; ?>
                            <div class="label">
                                <div class="row">
                                    <div class="cell qr"><?php echo $item['qrSvg']; ?></div>
                                    <div class="cell meta">
                                        <div class="code"><?php echo e($asset->code); ?></div>
                                        <div><?php echo e(\Illuminate\Support\Str::limit($asset->name ?? $asset->assetModel?->model_name ?? '', 28)); ?></div>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($asset->serial_number): ?>
                                            <div>SN: <?php echo e($asset->serial_number); ?></div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <div class="brand"><?php echo e(config('app.name')); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </table>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</body>
</html>
<?php /**PATH C:\Users\MohamedZahran\Downloads\DEITAM\resources\views/pdf/asset-label-sheet.blade.php ENDPATH**/ ?>