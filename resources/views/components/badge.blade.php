@props([
    'averageRating' => 0.0,
    'totalReviews' => 0,
    'platformName' => null,
])

<div style="display: inline-flex; align-items: center; gap: 8px; background: #ffffff; border: 1px solid #e5e7eb; padding: 6px 14px; border-radius: 30px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <span style="color: #fbbf24; font-size: 15px; font-weight: 800; display: flex; align-items: center; gap: 3px;">
        ★ {{ number_format($averageRating, 1) }}
    </span>
    <span style="font-size: 13px; font-weight: 600; color: #374151;">
        ({{ number_format($totalReviews) }} {{ Str::plural('review', $totalReviews) }})
    </span>
    @if($platformName)
        <span style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; border-left: 1px solid #e5e7eb; padding-left: 8px;">
            {{ $platformName }}
        </span>
    @endif
</div>
