@props([
    'reviews' => [],
    'summary' => [],
    'theme' => 'light',
])

<div class="larareviews-list-container larareviews-theme-{{ $theme }}" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <!-- Optional Embedded Summary -->
    @if(!empty($summary))
        <x-larareviews-summary :summary="$summary" :theme="$theme" />
    @endif

    <!-- Reviews Stream -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
        @forelse($reviews as $review)
            @php
                $platform = $review->platform ?? 'custom';
                $platformName = config("larareviews.ui.platform_names.{$platform}", ucfirst($platform));
                $platformColor = config("larareviews.ui.platform_colors.{$platform}", '#6C757D');
            @endphp

            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); transition: transform 0.2s, box-shadow 0.2s;">
                
                <!-- Header: Reviewer & Platform Tag -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <!-- Avatar -->
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: #f3f4f6; overflow: hidden; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #4b5563; font-size: 16px;">
                            @if(!empty($review->reviewer_avatar))
                                <img src="{{ $review->reviewer_avatar }}" alt="{{ $review->reviewer_name }}" style="width: 100%; height: 100%; object-fit: cover;" />
                            @else
                                {{ strtoupper(substr($review->reviewer_name ?? 'U', 0, 1)) }}
                            @endif
                        </div>

                        <div>
                            <div style="font-weight: 700; color: #111827; font-size: 15px; display: flex; align-items: center; gap: 6px;">
                                {{ $review->reviewer_name ?? 'Verified Traveler' }}

                                @if($review->verified)
                                    <span title="Verified Review" style="color: #10b981; font-size: 14px;">✓</span>
                                @endif
                            </div>

                            <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">
                                @if(!empty($review->reviewer_location))
                                    <span>📍 {{ $review->reviewer_location }}</span> • 
                                @endif
                                <span>{{ $review->review_date?->format('M d, Y') ?? 'Recent' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Platform Tag Badge -->
                    <a href="{{ $review->original_url ?? '#' }}" target="_blank" rel="noopener noreferrer" style="text-decoration: none;">
                        <span style="display: inline-flex; align-items: center; gap: 6px; background: {{ $platformColor }}15; border: 1px solid {{ $platformColor }}30; color: {{ $platformColor }}; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: {{ $platformColor }};"></span>
                            {{ $platformName }}
                        </span>
                    </a>

                </div>

                <!-- Rating & Title -->
                <div style="margin-bottom: 8px;">
                    <span style="color: #fbbf24; font-size: 16px; letter-spacing: 1px;">
                        @for($i = 1; $i <= 5; $i++)
                            {{ $review->rating >= $i ? '★' : '☆' }}
                        @endfor
                    </span>

                    @if(!empty($review->title))
                        <h4 style="display: inline-block; margin: 0 0 0 8px; font-size: 15px; font-weight: 700; color: #1f2937;">
                            {{ $review->title }}
                        </h4>
                    @endif
                </div>

                <!-- Review Content Body -->
                @if(!empty($review->content))
                    <p style="font-size: 14px; line-height: 1.6; color: #4b5563; margin: 0 0 12px 0;">
                        {{ $review->content }}
                    </p>
                @endif

                <!-- Review Photos (if any) -->
                @if(!empty($review->photos) && is_array($review->photos))
                    <div style="display: flex; gap: 8px; overflow-x: auto; margin-bottom: 12px; padding-bottom: 4px;">
                        @foreach($review->photos as $photo)
                            <img src="{{ $photo }}" style="width: 72px; height: 72px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb;" />
                        @endforeach
                    </div>
                @endif

                <!-- Owner / Management Response -->
                @if(!empty($review->response))
                    <div style="background: #f9fafb; border-left: 3px solid #3b82f6; padding: 12px 14px; border-radius: 0 8px 8px 0; margin-top: 12px; font-size: 13px;">
                        <div style="font-weight: 700; color: #1d4ed8; margin-bottom: 4px;">
                            💬 Management Response:
                        </div>
                        <div style="color: #374151; line-height: 1.5;">
                            {{ $review->response }}
                        </div>
                    </div>
                @endif

            </div>
        @empty
            <div style="text-align: center; padding: 40px; background: #f9fafb; border-radius: 12px; border: 1px dashed #d1d5db; color: #6b7280;">
                <p style="font-size: 15px; margin: 0; font-weight: 600;">No reviews available yet for this platform.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination Links -->
    @if(is_object($reviews) && method_exists($reviews, 'links'))
        <div style="margin-top: 24px;">
            {{ $reviews->links() }}
        </div>
    @endif

</div>
