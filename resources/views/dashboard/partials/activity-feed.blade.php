@forelse($activityFeed as $item)
    <a href="{{ $item['url'] }}" class="activity-tl-item text-decoration-none">
        <span class="activity-tl-dot dot-{{ $item['type'] }}"></span>
        <div class="activity-tl-body">
            <div class="d-flex justify-content-between align-items-start">
                <div class="activity-tl-title">{{ $item['title'] }}</div>
                <div class="activity-tl-time">{{ $item['created_at']->locale('ar')->diffForHumans() }}</div>
            </div>
            <div class="activity-tl-text">{{ $item['text'] }}</div>
            <div class="activity-tl-author">{{ $item['author'] }}</div>
        </div>
    </a>
@empty
    <p class="text-muted small mb-0">لا يوجد نشاط حديث.</p>
@endforelse