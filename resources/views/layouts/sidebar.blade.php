<aside class="sidebar">
    <a href="#" class="sidebar-brand">
        <i class="ti ti-brand-bootstrap"></i>
    </a>
    <nav class="sidebar-nav">
        <a href="#" class="sidebar-link active">
            <i class="ti ti-layout-dashboard"></i>
        </a>
        <a href="#" class="sidebar-link">
            <i class="ti ti-home"></i>
        </a>
        <a href="#" class="sidebar-link">
            <i class="ti ti-users"></i>
        </a>
        <a href="#" id="open-settings-modal-btn" class="sidebar-link">
            <i class="ti ti-settings"></i>
        </a>
        <a href="#" class="sidebar-link">
            <i class="ti ti-history"></i>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="#" class="sidebar-link">
            <i class="ti ti-sun"></i>
        </a>
        <a href="#" class="sidebar-link" onclick="event.preventDefault(); document.getElementById('logout-form-sidebar').submit();">
            <i class="ti ti-logout"></i>
        </a>
        <form id="logout-form-sidebar" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</aside>