@props([
    'reviews' => [],
    'cols' => 3,
    'theme' => 'light',
])

<div class="larareviews-grid-container larareviews-theme-{{ $theme }}" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
        @forelse($reviews as $review)
            @php
                $platform = $review->platform ?? 'custom';
                $platformName = config("larareviews.ui.platform_names.{$platform}", ucfirst($platform));
                $platformColor = config("larareviews.ui.platform_colors.{$platform}", '#6C757D');
            @endphp

            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between;">
                
                <div>
                    <!-- Card Top: Platform Tag & Star Rating -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="display: inline-flex; align-items: center; gap: 4px; background: {{ $platformColor }}15; color: {{ $platformColor }}; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;">
                            {{ $platformName }}
                        </span>
                        <span style="color: #fbbf24; font-size: 14px; letter-spacing: 1px;">
                            @for($i = 1; $i <= 5; $i++)
                                {{ $review->rating >= $i ? '★' : '☆' }}
                            @endfor
                        </span>
                    </div>

                    <!-- Title -->
                    @if(!empty($review->title))
                        <h4 style="margin: 0 0 8px 0; font-size: 15px; font-weight: 700; color: #111827; line-height: 1.3;">
                            "{{ $review->title }}"
                        </h4>
                    @endif

                    <!-- Body Content -->
                    <p style="font-size: 13px; line-height: 1.5; color: #4b5563; margin: 0 0 16px 0; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden;">
                        {{ $review->content }}
                    </p>
                </div>

                <!-- Footer: Reviewer Avatar & Name -->
                <div style="display: flex; align-items: center; gap: 10px; border-top: 1px solid #f3f4f6; padding-top: 12px;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #f3f4f6; overflow: hidden; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #4b5563; font-size: 13px;">
                        @if(!empty($review->reviewer_avatar))
                            <img src="{{ $review->reviewer_avatar }}" alt="{{ $review->reviewer_name }}" style="width: 100%; height: 100%; object-fit: cover;" />
                        @else
                            {{ strtoupper(substr($review->reviewer_name ?? 'U', 0, 1)) }}
                        @endif
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 13px; color: #111827;">
                            {{ $review->reviewer_name ?? 'Verified Traveler' }}
                        </div>
                        <div style="font-size: 11px; color: #9ca3af;">
                            {{ $review->review_date?->format('M Y') ?? 'Verified' }}
                        </div>
                    </div>
                </div>

            </div>
        @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 30px; background: #f9fafb; border-radius: 12px; color: #6b7280;">
                No reviews found.
            </div>
        @endforelse
    </div>
</div>
