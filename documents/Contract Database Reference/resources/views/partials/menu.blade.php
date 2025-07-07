<aside class="main-sidebar sidebar-dark-primary elevation-4" style="min-height: 917px;">
    <!-- Brand Logo -->
    <a href="#" class="brand-link">
        <span class="brand-text font-weight-light">{{ trans('panel.site_title') }}</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user (optional) -->

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li>
                    <select class="searchable-field form-control">

                    </select>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs("admin.home") ? "active" : "" }}" href="{{ route("admin.home") }}">
                        <i class="fas fa-fw fa-tachometer-alt nav-icon">
                        </i>
                        <p>
                            {{ trans('global.dashboard') }}
                        </p>
                    </a>
                </li>
                @can('user_management_access')
                    <li class="nav-item has-treeview {{ request()->is("admin/permissions*") ? "menu-open" : "" }} {{ request()->is("admin/roles*") ? "menu-open" : "" }} {{ request()->is("admin/users*") ? "menu-open" : "" }}">
                        <a class="nav-link nav-dropdown-toggle {{ request()->is("admin/permissions*") ? "active" : "" }} {{ request()->is("admin/roles*") ? "active" : "" }} {{ request()->is("admin/users*") ? "active" : "" }}" href="#">
                            <i class="fa-fw nav-icon fas fa-users">

                            </i>
                            <p>
                                {{ trans('cruds.userManagement.title') }}
                                <i class="right fa fa-fw fa-angle-left nav-icon"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('permission_access')
                                <li class="nav-item">
                                    <a href="{{ route("admin.permissions.index") }}" class="nav-link {{ request()->is("admin/permissions") || request()->is("admin/permissions/*") ? "active" : "" }}">
                                        <i class="fa-fw nav-icon fas fa-unlock-alt">

                                        </i>
                                        <p>
                                            {{ trans('cruds.permission.title') }}
                                        </p>
                                    </a>
                                </li>
                            @endcan
                            @can('role_access')
                                <li class="nav-item">
                                    <a href="{{ route("admin.roles.index") }}" class="nav-link {{ request()->is("admin/roles") || request()->is("admin/roles/*") ? "active" : "" }}">
                                        <i class="fa-fw nav-icon fas fa-briefcase">

                                        </i>
                                        <p>
                                            {{ trans('cruds.role.title') }}
                                        </p>
                                    </a>
                                </li>
                               
                            @endcan
                            @can('user_access')
                                <li class="nav-item">
                                    <a href="{{ route("admin.users.index") }}" class="nav-link {{ request()->is("admin/users") || request()->is("admin/users/*") ? "active" : "" }}">
                                        <i class="fa-fw nav-icon fas fa-user">

                                        </i>
                                        <p>
                                            {{ trans('cruds.user.title') }}
                                        </p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan
                @can('contract_management_access')
                    <li class="nav-item has-treeview {{ request()->is("admin/contracts*") ? "menu-open" : "" }} {{ request()->is("admin/programs*") ? "menu-open" : "" }} {{ request()->is("admin/funders*") ? "menu-open" : "" }} {{ request()->is("admin/report-due-dates*") ? "menu-open" : "" }} {{ request()->is("admin/due-date-emails*") ? "menu-open" : "" }} {{ request()->is("admin/audit-logs*") ? "menu-open" : "" }} {{ request()->is("admin/reports*") ? "menu-open" : "" }}">
                        <a class="nav-link nav-dropdown-toggle {{ request()->is("admin/contracts*") ? "active" : "" }} {{ request()->is("admin/programs*") ? "active" : "" }} {{ request()->is("admin/funders*") ? "active" : "" }} {{ request()->is("admin/report-due-dates*") ? "active" : "" }} {{ request()->is("admin/due-date-emails*") ? "active" : "" }} {{ request()->is("admin/audit-logs*") ? "active" : "" }} {{ request()->is("admin/reports*") ? "active" : "" }}" href="#">
                            <i class="fa-fw nav-icon fas fa-cogs">

                            </i>
                            <p>
                                {{ trans('cruds.contractManagement.title') }}
                                <i class="right fa fa-fw fa-angle-left nav-icon"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('contract_access')
                                <li class="nav-item">
                                    <a href="{{ route("admin.contracts.index") }}" class="nav-link {{ request()->is("admin/contracts") || request()->is("admin/contracts/*") ? "active" : "" }}">
                                        <i class="fa-fw nav-icon fas fa-cogs">

                                        </i>
                                        <p>
                                            {{ trans('cruds.contract.title') }}
                                        </p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                 <a href="{{ route('admin.contracts.export') }}" class="nav-link">
                                    <i class="fas fa-file-download"></i>
                                   <span>Export Contracts</span>
                                  </a>
                                </li>
                            @endcan
                            @can('program_access')
                                <li class="nav-item">
                                    <a href="{{ route("admin.programs.index") }}" class="nav-link {{ request()->is("admin/programs") || request()->is("admin/programs/*") ? "active" : "" }}">
                                        <i class="fa-fw nav-icon fas fa-users">

                                        </i>
                                        <p>
                                            {{ trans('cruds.program.title') }}
                                        </p>
                                    </a>
                                </li>
                            @endcan
                            @can('funder_access')
                                <li class="nav-item">
                                    <a href="{{ route("admin.funders.index") }}" class="nav-link {{ request()->is("admin/funders") || request()->is("admin/funders/*") ? "active" : "" }}">
                                        <i class="fa-fw nav-icon fas fa-cogs">

                                        </i>
                                        <p>
                                            {{ trans('cruds.funder.title') }}
                                        </p>
                                    </a>
                                </li>
                            @endcan
                            <!-- @can('report_due_date_access')
                                <li class="nav-item">
                                    <a href="{{ route("admin.report-due-dates.index") }}" class="nav-link {{ request()->is("admin/report-due-dates") || request()->is("admin/report-due-dates/*") ? "active" : "" }}">
                                        <i class="fa-fw nav-icon fas fa-cogs">

                                        </i>
                                        <p>
                                            {{ trans('cruds.reportDueDate.title') }}
                                        </p>
                                    </a>
                                </li>
                            @endcan -->
                            @can('due_date_email_access')
                                <li class="nav-item">
                                    <a href="{{ route("admin.due-date-emails.index") }}" class="nav-link {{ request()->is("admin/due-date-emails") || request()->is("admin/due-date-emails/*") ? "active" : "" }}">
                                        <i class="fa-fw nav-icon fas fa-cogs">

                                        </i>
                                        <p>
                                            {{ trans('cruds.dueDateEmail.title') }}
                                        </p>
                                    </a>
                                </li>
                            @endcan
                            @can('audit_log_access')
                                <li class="nav-item">
                                    <a href="{{ route("admin.audit-logs.index") }}" class="nav-link {{ request()->is("admin/audit-logs") || request()->is("admin/audit-logs/*") ? "active" : "" }}">
                                        <i class="fa-fw nav-icon fas fa-file-alt">

                                        </i>
                                        <p>
                                            {{ trans('cruds.auditLog.title') }}
                                        </p>
                                    </a>
                                </li>
                            @endcan
                            @can('report_access')
                                <li class="nav-item">
                                    <a href="{{ route("admin.reports.index") }}" class="nav-link {{ request()->is("admin/reports") || request()->is("admin/reports/*") ? "active" : "" }}">
                                        <i class="fa-fw nav-icon fas fa-chart-pie">

                                        </i>
                                        <p>
                                            {{ trans('cruds.report.title') }}
                                        </p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan
                @if(file_exists(app_path('Http/Controllers/Auth/ChangePasswordController.php')))
                    @can('profile_password_edit')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('profile/password') || request()->is('profile/password/*') ? 'active' : '' }}" href="{{ route('profile.password.edit') }}">
                                <i class="fa-fw fas fa-key nav-icon">
                                </i>
                                <p>
                                    {{ trans('global.change_password') }}
                                </p>
                            </a>
                        </li>
                    @endcan
                @endif
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="event.preventDefault(); document.getElementById('logoutform').submit();">
                        <p>
                            <i class="fas fa-fw fa-sign-out-alt nav-icon">

                            </i>
                            <p>{{ trans('global.logout') }}</p>
                        </p>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>