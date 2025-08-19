<aside class="sidebar">
    <a href="/" class="sidebar-brand">
        <i class="ti ti-brand-bootstrap"></i>
    </a>
    <nav class="sidebar-nav">
        <a href="{{ route('mypage.index') }}" id="mypage-link" class="sidebar-link">
            <i class="ti ti-users"></i>
        </a>
        <a href="#" id="open-settings-modal-btn" class="sidebar-link">
            <i class="ti ti-settings"></i>
        </a>
    </nav>
    <div class="sidebar-footer">
        <button type="button" id="dark-mode-toggle" class="sidebar-link">
            <i class="ti ti-sun"></i> {{-- 아이콘은 JS가 제어 --}}
        </button>
        <a href="#" class="sidebar-link" onclick="event.preventDefault(); document.getElementById('logout-form-sidebar').submit();">
            <i class="ti ti-logout"></i>
        </a>
        <form id="logout-form-sidebar" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</aside>