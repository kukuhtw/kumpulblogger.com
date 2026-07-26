<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<style>
    :root { --admin-sidebar-width: 260px; --admin-dark: #1f2937; }
    html, body { min-height: 100%; }
    body { margin: 0; background: #f4f6f9; color: #25313c; overflow-x: hidden; }
    .admin-navbar { min-height: 62px; padding: .75rem 1.25rem .75rem 4.5rem; background: var(--admin-dark); color: #fff; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 1020; }
    .admin-navbar .brand { color: #fff; font-size: 1.1rem; font-weight: 600; text-decoration: none; }
    .admin-navbar a { color: rgba(255,255,255,.85); }
    .sidebar { position: fixed; z-index: 1030; inset: 0 auto 0 0; width: var(--admin-sidebar-width); height: 100vh; padding: 1.25rem 1rem; overflow-y: auto; background: var(--admin-dark); color: #fff; transition: transform .25s ease; box-shadow: 4px 0 16px rgba(0,0,0,.12); }
    .sidebar h4 { padding: .25rem .75rem 1rem; margin: 0 0 .75rem; border-bottom: 1px solid rgba(255,255,255,.12); font-size: .95rem; line-height: 1.5; overflow-wrap: anywhere; }
    .sidebar ul { list-style: none; padding: 0; margin: 0; }
    .sidebar ul li a { display: flex; align-items: center; gap: .65rem; padding: .65rem .75rem; border-radius: .45rem; color: rgba(255,255,255,.82); text-decoration: none; font-size: .91rem; }
    .sidebar ul li a:hover, .sidebar ul li a:focus { color: #fff; background: rgba(255,255,255,.1); }
    .sidebar ul ul { padding-left: .65rem; }
    .sidebar ul ul a { font-size: .84rem; }
    .toggle-btn { position: fixed; z-index: 1040; top: 11px; left: 12px; width: 40px; height: 40px; padding: 0; border: 1px solid rgba(255,255,255,.2); border-radius: .45rem; background: #374151; color: #fff; font-size: 1.35rem; line-height: 1; cursor: pointer; }
    .sidebar-backdrop { display: none; position: fixed; z-index: 1025; inset: 0; background: rgba(15,23,42,.55); }
    .admin-main { width: auto; max-width: none; min-height: calc(100vh - 120px); margin-left: var(--admin-sidebar-width); padding: 1.5rem; transition: margin-left .25s ease; }
    .admin-main.shifted { margin-left: 0; }
    /* Compatibility for existing admin pages that still use Bootstrap's container. */
    #mainContent.container { width: auto; max-width: none; margin-left: var(--admin-sidebar-width); margin-right: 0; padding: 1.5rem; transition: margin-left .25s ease; }
    #mainContent.container.shifted { margin-left: 0; }
    .page-title { margin: 0; font-size: 1.55rem; font-weight: 600; }
    .page-subtitle { margin: .3rem 0 0; color: #6c757d; }
    .card { border: 0; border-radius: .7rem; box-shadow: 0 3px 14px rgba(31,41,55,.08); }
    .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e9ecef; border-radius: .7rem .7rem 0 0 !important; background: #fff; color: #25313c; font-size: 1.05rem; font-weight: 600; }
    .table { margin-bottom: 0; }
    .table thead th { border-top: 0; border-bottom-width: 1px; background: #f8f9fa; color: #52606d; font-size: .78rem; text-transform: uppercase; letter-spacing: .025em; white-space: nowrap; }
    .table td { vertical-align: top; }
    .table-responsive { border-radius: .45rem; }
    .data-label { color: #6c757d; font-size: .78rem; }
    .metric-list { display: grid; gap: .3rem; margin-top: .7rem; font-size: .84rem; }
    .metric-list > div { display: flex; justify-content: space-between; gap: 1rem; padding-bottom: .25rem; border-bottom: 1px dashed #e5e7eb; }
    .pagination { flex-wrap: wrap; gap: .2rem; margin-bottom: 0; }
    .footer { margin-left: var(--admin-sidebar-width); padding: 1rem; background: #fff; border-top: 1px solid #e5e7eb; color: #6c757d; text-align: center; font-size: .85rem; transition: margin-left .25s ease; }
    .footer.shifted { margin-left: 0; }
    @media (min-width: 992px) { .sidebar.hidden { transform: translateX(-100%); } }
    @media (max-width: 991.98px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.open { transform: translateX(0); }
        .sidebar-backdrop.show { display: block; }
        .admin-main, .admin-main.shifted, #mainContent.container, #mainContent.container.shifted, .footer, .footer.shifted { margin-left: 0; }
        .admin-main { padding: 1rem; }
    }
    @media (max-width: 575.98px) {
        .admin-navbar { padding-right: .75rem; }
        .admin-navbar .brand { font-size: 1rem; }
        .admin-main { padding: .85rem; }
        .page-title { font-size: 1.3rem; }
        .card-body { padding: .9rem; }
        .mobile-stack-form { display: grid !important; grid-template-columns: 1fr; gap: .5rem; }
        .mobile-stack-form .form-control, .mobile-stack-form .btn { width: 100%; margin: 0 !important; }
    }
</style>
