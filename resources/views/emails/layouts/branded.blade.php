@php
    $branding = $branding ?? [];
    $primary = $branding['primary_color'] ?? '#0f172a';
    $secondary = $branding['secondary_color'] ?? '#1e293b';
    $accent = $branding['accent_color'] ?? '#3b82f6';
    $headerHtml = $branding['email_header_html'] ?? null;
    $footerHtml = $branding['email_footer_html'] ?? null;
    $logoPath = $branding['logo_path'] ?? null;
    $appName = config('app.name', 'Platform');
    $direction = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $direction }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? $appName }}</title>
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Tahoma, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            direction: {{ $direction }};
        }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 24px; }
        .card { background: #ffffff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .brand-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid {{ $primary }}; }
        .brand-header img { max-height: 40px; }
        .brand-name { font-size: 18px; font-weight: 600; color: {{ $primary }}; }
        .content { font-size: 15px; line-height: 1.6; }
        .btn { display: inline-block; padding: 12px 24px; background: {{ $accent }}; color: #ffffff !important; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .footer { margin-top: 24px; text-align: center; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="brand-header">
                @if($logoPath)
                    <img src="{{ asset($logoPath) }}" alt="{{ $appName }}">
                @endif
                <span class="brand-name">{{ $appName }}</span>
            </div>

            @if($headerHtml)
                {!! $headerHtml !!}
            @endif

            <div class="content">
                {{ $slot }}
            </div>

            @if($footerHtml)
                <div class="footer">{!! $footerHtml !!}</div>
            @else
                <div class="footer">© {{ now()->year }} {{ $appName }}</div>
            @endif
        </div>
    </div>
</body>
</html>
