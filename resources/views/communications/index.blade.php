@extends('layouts.app')
@section('title', 'التواصل')
@section('content-class', 'p-4 flex-grow-1 bg-white')

@section('content')
<div class="communications-layout">

    {{-- Right column: project list --}}
    <aside class="comm-thread-list">
        <div class="comm-thread-list-header">
            <i class="fa-regular fa-comments me-1" style="color:#8A84AD;"></i>
            <span>المحادثات</span>
        </div>

        @forelse($projects as $p)
            <a href="{{ route('communications.index', ['project' => $p->project_id]) }}"
               class="comm-thread-item {{ $selectedProject && $selectedProject->project_id === $p->project_id ? 'active' : '' }}">
                <div class="comm-thread-title">{{ $p->project_name }}</div>
                <div class="comm-thread-sub">{{ $p->company_name }}</div>
            </a>
        @empty
            <div class="text-center text-muted py-4 small">لا توجد مشاريع.</div>
        @endforelse
    </aside>

    {{-- Left column: selected conversation --}}
    <section class="comm-thread-view">
        @if($selectedProject)
            <div class="comm-thread-view-header">
                <div>
                    <h5 class="comm-thread-view-title mb-0">{{ $selectedProject->project_name }}</h5>
                    <span class="text-muted small">{{ $selectedProject->company_name }}</span>
                </div>
                @php $pm = $selectedProject->managers->first(); @endphp
                @if($pm)
                    <span class="comm-thread-pm">
                        <i class="fa-regular fa-user me-1"></i>
                        مدير المشروع: {{ $pm->username }}
                    </span>
                @endif
            </div>

            <div class="comm-thread-messages">
                @forelse($thread as $item)
                    @if($item->type === 'ticket')
                        @php $t = $item->data; @endphp
                        <div class="comm-message comm-message-client">
                            <div class="comm-message-meta">
                                <span class="comm-message-author">{{ $t->client_name ?: 'العميل' }}</span>
                                <span class="comm-message-time">{{ $t->created_at->translatedFormat('d F Y - h:i A') }}</span>
                            </div>
                            <div class="comm-message-body">{{ $t->message }}</div>
                            <span class="comm-message-status {{ $t->status === \App\Enums\TicketStatus::Handled ? 'is-handled' : 'is-open' }}">
                                {{ $t->status->label() }}
                            </span>
                        </div>
                    @else
                        @php $c = $item->data; @endphp
                        <div class="comm-message comm-message-manager">
                            <div class="comm-message-meta">
                                <span class="comm-message-author">{{ $c->author_name ?: 'فريق المشروع' }}</span>
                                <span class="comm-message-time">{{ $c->created_at->translatedFormat('d F Y - h:i A') }}</span>
                            </div>
                            @if($c->comment_text)
                                <div class="comm-message-body">{{ $c->comment_text }}</div>
                            @endif
                            @if($c->attachment)
                                <a href="{{ Storage::url($c->attachment) }}" target="_blank" rel="noopener" class="comm-message-attachment">
                                    <i class="fa-regular fa-paperclip me-1"></i> مرفق
                                </a>
                            @endif
                        </div>
                    @endif
                @empty
                    <div class="text-center text-muted py-5">
                        <i class="fa-regular fa-comments mb-2" style="font-size: 32px; color:#D0CBE3;"></i>
                        <p class="small mb-0">لا توجد رسائل بعد. ابدأ المحادثة أدناه.</p>
                    </div>
                @endforelse
            </div>

                        <form action="{{ route('tickets.store', $selectedProject->project_id) }}" method="POST" class="comm-thread-reply">
                @csrf
                <textarea name="message" rows="3" class="form-control custom-input mb-2" placeholder="اكتب رسالتك أو استفسارك..." required>{{ old('message', $prefillMessage) }}</textarea>
                <div class="text-end">
                    <button type="submit" class="btn btn-save px-4">
                        <i class="fa-regular fa-paper-plane me-1"></i>
                        إرسال
                    </button>
                </div>
            </form>
        @else
            <div class="text-center text-muted py-5">
                <i class="fa-regular fa-folder-open mb-2" style="font-size: 32px; color:#D0CBE3;"></i>
                <p class="mb-0">لا توجد مشاريع لعرضها.</p>
            </div>
        @endif
    </section>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ta = document.querySelector('.comm-thread-reply textarea[name="message"]');
    if (!ta) return;

    var val = ta.value || '';
    // Only auto-focus + place cursor at end when we actually prefilled something
    // (i.e. the client arrived via the "التواصل بخصوص هذه المرحلة" button).
    if (val.trim() !== '') {
        ta.focus();
        ta.setSelectionRange(val.length, val.length);
    }
});
</script>
@endpush