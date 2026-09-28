<form action="{{ route('locale.update') }}" method="POST" class="d-inline-flex align-items-center gap-2">
    @csrf
    <button type="submit" name="locale" value="ar"
            class="btn btn-link btn-sm text-decoration-none p-0 {{ app()->getLocale() === 'ar' ? 'fw-bold' : 'text-muted' }}">العربية</button>
    <span class="text-muted">|</span>
    <button type="submit" name="locale" value="en"
            class="btn btn-link btn-sm text-decoration-none p-0 {{ app()->getLocale() === 'en' ? 'fw-bold' : 'text-muted' }}">English</button>
</form>