<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; }
        h1 { font-size: 16pt; margin: 0 0 4pt; }
        h2 { font-size: 11pt; margin: 18pt 0 4pt; color: #374151; }
        .meta { color: #6b7280; margin-bottom: 12pt; font-size: 9pt; }
        .meta-row { margin: 2pt 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 4pt; }
        th, td { padding: 5pt 6pt; border-bottom: 0.4pt solid #e5e7eb; text-align: left; font-size: 9pt; }
        th { background: #f3f4f6; font-weight: 700; }
        td.code { font-family: DejaVu Sans Mono, monospace; }
        .signature {
            margin-top: 30pt;
            display: table;
            width: 100%;
        }
        .signature .col { display: table-cell; width: 50%; padding-right: 12pt; }
        .signature .label { font-size: 8pt; color: #6b7280; }
        .signature .line { border-bottom: 0.4pt solid #6b7280; height: 30pt; margin: 4pt 0 2pt; }
        .footer { color: #9ca3af; font-size: 7pt; margin-top: 20pt; text-align: center; }
    </style>
</head>
<body>
    <h1><?php echo e(config('app.name')); ?> — Employee Asset Summary</h1>
    <div class="meta">
        <div class="meta-row">Employee: <strong><?php echo e($employee->first_name); ?> <?php echo e($employee->last_name); ?></strong> (<?php echo e($employee->code); ?>)</div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employee->position): ?>
            <div class="meta-row">Position: <?php echo e($employee->position); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employee->department): ?>
            <div class="meta-row">Department: <?php echo e($employee->department->name); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employee->branch): ?>
            <div class="meta-row">Branch: <?php echo e($employee->branch->name); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <div class="meta-row">Generated: <?php echo e($generatedAt->format('Y-m-d H:i')); ?></div>
    </div>

    <h2>Assigned assets (<?php echo e($assets->count()); ?>)</h2>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($assets->isEmpty()): ?>
        <p style="color:#6b7280">No assets are currently assigned to this employee.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Category</th>
                    <th>Model</th>
                    <th>S/N</th>
                    <th>Branch</th>
                    <th>Supplier</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $assets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="code"><?php echo e($asset->code); ?></td>
                        <td><?php echo e($asset->category?->name ?? '—'); ?></td>
                        <td><?php echo e(trim(($asset->assetModel?->manufacturer ?? '').' '.($asset->assetModel?->model_name ?? $asset->name ?? '')) ?: '—'); ?></td>
                        <td class="code"><?php echo e($asset->serial_number ?? '—'); ?></td>
                        <td><?php echo e($asset->branch?->name ?? '—'); ?></td>
                        <td><?php echo e($asset->supplier?->name ?? '—'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="signature">
        <div class="col">
            <div class="label">Employee acknowledgement</div>
            <div class="line"></div>
            <div class="label">Signature &amp; date</div>
        </div>
        <div class="col">
            <div class="label">IT / HR signature</div>
            <div class="line"></div>
            <div class="label">Signature &amp; date</div>
        </div>
    </div>

    <div class="footer">Generated by <?php echo e(config('app.name')); ?> on <?php echo e($generatedAt->toIso8601String()); ?></div>
</body>
</html>
<?php /**PATH C:\Users\MohamedZahran\Downloads\DEITAM\resources\views/pdf/employee-asset-summary.blade.php ENDPATH**/ ?>