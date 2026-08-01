@props([
    'summary' => [],
    'theme' => 'light',
])

@php
    $avg = $summary['average_rating'] ?? 0.0;
    $total = $summary['total_reviews'] ?? 0;
    $breakdown = $summary['breakdown']['percentages'] ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $counts = $summary['breakdown']['counts'] ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $platforms = $summary['platforms'] ?? [];
@endphp

<div class="larareviews-summary-box larareviews-theme-{{ $theme }}" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 24px;">
    
    <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: center; justify-content: space-between;">
        
        <!-- Overall Rating Score Column -->
        <div style="text-align: center; min-width: 160px; padding: 12px; background: #f9fafb; border-radius: 12px;">
            <div style="font-size: 48px; font-weight: 800; color: #111827; line-height: 1;">
                {{ number_format($avg, 1) }}
            </div>
            
            <div style="color: #fbbf24; font-size: 20px; margin: 8px 0; letter-spacing: 2px;">
                @for($i = 1; $i <= 5; $i++)
                    @if($avg >= $i)
                        ★
                    @elseif($avg >= ($i - 0.5))
                        ★
                    @else
                        ☆
                    @endif
                @endfor
            </div>
            
            <div style="font-size: 14px; font-weight: 600; color: #6b7280;">
                Based on {{ number_format($total) }} reviews
            </div>
        </div>

        <!-- Rating Distribution Bars Column -->
        <div style="flex: 1; min-width: 260px;">
            @foreach([5, 4, 3, 2, 1] as $star)
                @php $pct = $breakdown[$star] ?? 0; @endphp
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px; font-size: 13px; color: #374151;">
                    <span style="width: 48px; font-weight: 600;">{{ $star }} ★</span>
                    <div style="flex: 1; background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
                        <div style="width: {{ $pct }}%; background: #f59e0b; height: 100%; border-radius: 4px; transition: width 0.3s ease;"></div>
                    </div>
                    <span style="width: 40px; text-align: right; font-size: 12px; color: #6b7280;">{{ $pct }}%</span>
                </div>
            @endforeach
        </div>

        <!-- Platform Badges Breakdown -->
        @if(!empty($platforms))
            <div style="display: flex; flex-direction: column; gap: 8px; min-width: 200px; border-left: 1px solid #f3f4f6; padding-left: 20px;">
                <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 4px;">
                    Connected Platforms
                </div>

                @foreach($platforms as $p)
                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px; padding: 6px 12px; background: #f9fafb; border-radius: 8px;">
                        <span style="font-weight: 600; color: {{ $p['color'] ?? '#374151' }}; display: flex; align-items: center; gap: 6px;">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: {{ $p['color'] ?? '#374151' }};"></span>
                            {{ $p['name'] }}
                        </span>
                        <span style="font-weight: 700; color: #111827;">
                            ★ {{ number_format($p['average_rating'], 1) }} <span style="font-weight: 400; font-size: 11px; color: #6b7280;">({{ $p['total_reviews'] }})</span>
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
