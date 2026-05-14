<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; }
        h1 { font-size: 16pt; margin: 0 0 4pt; }
        h2 { font-size: 11pt; margin: 16pt 0 4pt; color: #374151; }
        .meta { color: #6b7280; font-size: 9pt; margin-bottom: 12pt; }
        .meta-row { margin: 2pt 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 4pt; }
        th, td { padding: 5pt 6pt; border-bottom: 0.4pt solid #e5e7eb; text-align: left; font-size: 9pt; }
        th { background: #f3f4f6; }
        td.code { font-family: DejaVu Sans Mono, monospace; }
        .badge { display: inline-block; padding: 1pt 6pt; border-radius: 8pt; font-size: 8pt; }
        .badge-good { background: #dcfce7; color: #166534; }
        .badge-fair { background: #fef9c3; color: #854d0e; }
        .badge-bad  { background: #fee2e2; color: #991b1b; }
        .signature { margin-top: 30pt; display: table; width: 100%; }
        .signature .col { display: table-cell; width: 50%; padding-right: 12pt; }
        .signature .label { font-size: 8pt; color: #6b7280; }
        .signature .line { border-bottom: 0.4pt solid #6b7280; height: 30pt; margin: 4pt 0 2pt; }
        .footer { color: #9ca3af; font-size: 7pt; margin-top: 18pt; text-align: center; }
    </style>
</head>
<body>
    <h1>Offboarding Handover — <?php echo e($process->code); ?></h1>
    <div class="meta">
        <div class="meta-row">Employee: <strong><?php echo e($employee->first_name); ?> <?php echo e($employee->last_name); ?></strong> (<?php echo e($employee->code); ?>)</div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employee->department): ?>
            <div class="meta-row">Department: <?php echo e($employee->department->name); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employee->branch): ?>
            <div class="meta-row">Branch: <?php echo e($employee->branch->name); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($process->target_date): ?>
            <div class="meta-row">Last working day: <?php echo e($process->target_date->format('Y-m-d')); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <div class="meta-row">Generated: <?php echo e($generatedAt->format('Y-m-d H:i')); ?></div>
    </div>

    <h2>Assets returned (<?php echo e($collected->count()); ?>)</h2>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($collected->isEmpty()): ?>
        <p style="color:#6b7280">No assets recorded as collected.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Asset code</th>
                    <th>Description</th>
                    <th>Condition</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $collected; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $result = is_array($task->result) ? $task->result : [];
                        $condition = $result['condition'] ?? 'good';
                        $badgeClass = match ($condition) {
                            'damaged', 'broken' => 'badge-bad',
                            'fair', 'used' => 'badge-fair',
                            default => 'badge-good',
                        };
                    ?>
                    <tr>
                        <td class="code"><?php echo e($result['asset_code'] ?? '—'); ?></td>
                        <td><?php echo e($task->title ?? ''); ?></td>
                        <td><span class="badge <?php echo e($badgeClass); ?>"><?php echo e($condition); ?></span></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="signature">
        <div class="col">
            <div class="label">Employee signature</div>
            <div class="line"></div>
            <div class="label">Date</div>
        </div>
        <div class="col">
            <div class="label">IT / HR signature</div>
            <div class="line"></div>
            <div class="label">Date</div>
        </div>
    </div>

    <div class="footer">Generated by <?php echo e(config('app.name')); ?> on <?php echo e($generatedAt->toIso8601String()); ?></div>
</body>
</html>
<?php /**PATH C:\Users\MohamedZahran\Downloads\DEITAM\resources\views/pdf/offboarding-handover.blade.php ENDPATH**/ ?>