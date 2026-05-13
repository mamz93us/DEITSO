<?php

declare(strict_types=1);

namespace App\Services\Labels;

use App\Models\Asset;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Collection;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Renders printable QR-code labels for one or more assets.
 *
 * Two output formats:
 *   - single(): one 50×30 mm sticker per page (good for handheld printers)
 *   - sheet():  A4 sheet of N labels (good for office printers)
 *
 * The QR code encodes the platform's `/scan/{ulid}` URL — a phone camera
 * can scan it to be redirected to the asset detail page (auth-gated).
 */
class GenerateAssetLabelPdf
{
    /**
     * Default per-row × per-column layout for the A4 sheet. Matches Avery
     * L7651 (3×8 = 24 labels per page, ~63.5×38 mm each).
     */
    public const SHEET_COLUMNS = 3;

    public const SHEET_ROWS = 8;

    public function single(Asset $asset): DomPdf
    {
        return Pdf::loadView('pdf.asset-label', [
            'asset' => $asset,
            'qrSvg' => $this->qrCodeFor($asset),
        ])->setPaper([0, 0, 141.7, 85.0], 'portrait'); // 50×30 mm in pt (1 mm ≈ 2.834 pt)
    }

    /**
     * @param  Collection<int, Asset>  $assets
     */
    public function sheet(Collection $assets): DomPdf
    {
        $labels = $assets->map(fn (Asset $a) => [
            'asset' => $a,
            'qrSvg' => $this->qrCodeFor($a),
        ])->all();

        return Pdf::loadView('pdf.asset-label-sheet', [
            'labels' => $labels,
            'columns' => self::SHEET_COLUMNS,
            'rows' => self::SHEET_ROWS,
        ])->setPaper('a4', 'portrait');
    }

    /**
     * Returns the inline SVG markup for an Asset's QR code. SVG embeds cleanly
     * in DomPDF without needing the external `imagick` extension that PNG would.
     */
    protected function qrCodeFor(Asset $asset): string
    {
        return (string) QrCode::format('svg')
            ->size(180)
            ->margin(1)
            ->errorCorrection('M')
            ->generate(url('/scan/'.$asset->id));
    }
}
