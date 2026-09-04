<div id="sidebar">
  <div class="sidebar-wrapper active">
    <div class="sidebar-header position-relative">
      <div class="d-flex justify-content-center align-items-center">
        <div class="logo">
          <a href="{{route('dashboard')}}">
            <img src="{{ $appLogo }}" alt="Logo" style="width: 100%; height: 100px;">
          </a>
        </div>
        <div class="sidebar-toggler x">
          <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
        </div>
      </div>
    </div>
    <div class="sidebar-menu">
      <ul class="menu">
        <li class="sidebar-title">Menu</li>

        <!-- Tableau de bord -->
        <li class="sidebar-item @if(request()->routeIs('dashboard')) active @endif">
          <a href="{{route('dashboard')}}" class="sidebar-link">
            <i class="bi bi-grid-fill"></i>
            <span>Tableau de bord</span>
          </a>
        </li>

        <!-- Gestion des Employés -->
        @canany(['menu.employees', 'menu.departments', 'menu.areas'])
        <li class="sidebar-item has-sub @if(request()->routeIs('employees.*') || request()->routeIs('departments.*') || request()->routeIs('areas.*')) active @endif">
          <a href="#" class="sidebar-link">
            <i class="bi bi-people-fill"></i>
            <span>Gestion des <br>Employés</span>
          </a>
          <ul class="submenu @if(request()->routeIs('employees.*') || request()->routeIs('departments.*') || request()->routeIs('areas.*')) active @endif">
            @can('menu.employees')
            <li class="submenu-item @if(request()->routeIs('employees.*')) active @endif">
              <a href="{{route('employees.index')}}" class="submenu-link">
                <i class="bi bi-list-ul"></i>
                <span>Liste des employés</span>
              </a>
            </li>
            @endcan
            @can('menu.departments')
            <li class="submenu-item @if(request()->routeIs('departments.*')) active @endif">
              <a href="{{route('departments.index')}}" class="submenu-link">
                <i class="bi bi-building"></i>
                <span>Départements</span>
              </a>
            </li>
            @endcan
            @can('menu.areas')
            <li class="submenu-item @if(request()->routeIs('areas.*')) active @endif">
              <a href="{{route('areas.index')}}" class="submenu-link">
                <i class="bi bi-geo-alt"></i>
                <span>Zones</span>
              </a>
            </li>
            @endcan
          </ul>
        </li>
        @endcanany

        <!-- Gestion des Plannings -->
@canany(['menu.work-hours', 'menu.employee-schedules', 'menu.schedules'])
<li class="sidebar-item has-sub @if(request()->routeIs('schedules.*') || request()->routeIs('work-hours.*') || request()->routeIs('employee-schedules.*')) active @endif">
    <a href="#" class="sidebar-link">
        <i class="bi bi-calendar-week-fill"></i>
        <span>Gestion des <br>Plannings</span>
    </a>
    <ul class="submenu @if(request()->routeIs('schedules.*') || request()->routeIs('work-hours.*') || request()->routeIs('schedules.*') || request()->routeIs('rotations.*')) active @endif">
        <!-- Types d'horaires -->
        @can('menu.work-hours')
        <li class="submenu-item @if(request()->routeIs('work-hours.*')) active @endif">
            <a href="{{ route('work-hours.index') }}" class="submenu-link">
                <i class="bi bi-clock-history"></i>
                <span>Types d'horaires</span>
            </a>
        </li>
        @endcan

        <!-- Horaires rotatifs -->
        <li class="d-none submenu-item @if(request()->routeIs('rotations.*')) active @endif">
            <a href="{{ route('rotations.index') }}" class="submenu-link">
                <i class="bi bi-arrow-repeat"></i>
                <span>Horaires rotatifs</span>
            </a>
        </li>

        @can('menu.employee-schedules')
               <li class="submenu-item @if(request()->routeIs('employee-schedules.*')) active @endif">
    <a href="{{ route('employee-schedules.index') }}" class="sidebar-link">
        <i class="bi bi-calendar-check"></i>
        <span>Plannings Employés</span>
    </a>
</li>
        @endcan
        <!-- Calendrier d'assignation -->
        @can('menu.schedules')
        <li class="submenu-item @if(request()->routeIs('schedules.calendar')) active @endif">
            <a href="{{ route('schedules.calendar') }}" class="submenu-link">
                <i class="bi bi-calendar3"></i>
                <span>Calendrier</span>
            </a>
        </li>
        @endcan
    </ul>
</li>
@endcanany

        <!-- Gestion des autorisations -->
        @canany(['menu.employee-permissions', 'menu.missions', 'menu.leaves'])
        <li class="sidebar-item has-sub @if(request()->routeIs('authorizations.*') || request()->routeIs('absences.*') || request()->routeIs('delays.*') || request()->routeIs('leaves.*')) active @endif">
          <a href="#" class="sidebar-link">
            <i class="bi bi-clipboard-check-fill"></i>
            <span>Gestion des<br> autorisations</span>
          </a>
          <ul class="submenu @if(request()->routeIs('authorizations.*') || request()->routeIs('absences.*') || request()->routeIs('delays.*') || request()->routeIs('leaves.*')) active @endif">
            <!-- Absences -->
            <!-- <li class="submenu-item @if(request()->routeIs('absences.*')) active @endif">
              <a href="{{route('authorizations.absences.index')}}">
                <i class="bi bi-person-x-fill"></i>
                <span>Absences</span>
              </a>
            </li> -->
            @can('menu.employee-permissions')
            <li class="submenu-item @if(request()->routeIs('permissions.*')) active @endif">
              <a href="{{route('authorizations.employee-permissions.index')}}" class="submenu-link">
                <i class="bi bi-check-circle-fill"></i>
                <span>Permissions</span>
              </a>
            </li>
            @endcan
            @can('menu.missions')
            <li class="submenu-item {{ request()->routeIs('missions.*') ? 'active' : '' }}">
    <a href="{{ route('missions.index') }}" class="submenu-link">
        <i class="bi bi-briefcase me-2"></i>
        <span>Missions</span>
    </a>
</li>
            @endcan

            <!-- Retards -->
            <!-- <li class="submenu-item @if(request()->routeIs('delays.*')) active @endif">
              <a href="{{route('authorizations.delays.index')}}">
                <i class="bi bi-clock"></i>
                <span>Retards</span>
              </a>
            </li> -->

            <!-- Congés -->
            @can('menu.leaves')
            <li class="submenu-item @if(request()->routeIs('leaves.*')) active @endif">
              <a href="{{route('leaves.index')}}" class="submenu-link">
                <i class="bi bi-calendar-check"></i>
                <span>Congés</span>
              </a>
            </li>
            @endcan
            <!-- Congés -->
            <!-- <li class="submenu-item @if(request()->routeIs('leaves.*')) active @endif">
              <a href="{{route('leaves.index')}}">
                <i class="bi bi-calendar-check"></i>
                <span>Vacances</span>
              </a>
            </li> -->
          </ul>
        </li>
        @endcanany

       <!-- Gestion des Présences avec sous-menu -->
@canany(['menu.daily-attendance', 'menu.attendance-history', 'menu.attendance-presence', 'menu.attendance-absence', 'menu.attendance-retards'])
<li class="sidebar-item has-sub @if(request()->routeIs('admin.daily-attendance.*')) active @endif">
    <a href="#" class="sidebar-link">
        <i class="bi bi-clock-history"></i>
        <span>Historique des pointages</span>
    </a>
    <ul class="submenu @if(request()->routeIs('admin.daily-attendance.*')) active @endif">
        <!-- Historique complet -->
        @canany(['menu.daily-attendance', 'menu.attendance-history'])
        <li class="submenu-item @if(request()->routeIs('admin.daily-attendance.index')) active @endif">
            <a href="{{ route('admin.daily-attendance.index') }}" class="submenu-link">
                <i class="bi bi-table"></i>
                <span>Historique complet</span>
            </a>
        </li>
        @endcanany

        <!-- Liste des présences -->
        @canany(['menu.daily-attendance', 'menu.attendance-presence'])
        <li class="submenu-item @if(request()->routeIs('admin.daily-attendance.presence')) active @endif">
            <a href="{{ route('admin.daily-attendance.presence') }}" class="submenu-link">
                <i class="bi bi-person-check-fill text-success"></i>
                <span>Liste des présences</span>
            </a>
        </li>
        @endcanany

        <!-- Liste des absences -->
        @canany(['menu.daily-attendance', 'menu.attendance-absence'])
        <li class="submenu-item @if(request()->routeIs('admin.daily-attendance.absence')) active @endif">
            <a href="{{ route('admin.daily-attendance.absence') }}" class="submenu-link">
                <i class="bi bi-person-x-fill text-danger"></i>
                <span>Liste des absences</span>
            </a>
        </li>
        @endcanany

        <!-- Liste des retards -->
        @canany(['menu.daily-attendance', 'menu.attendance-retards'])
        <li class="submenu-item @if(request()->routeIs('admin.daily-attendance.retards')) active @endif">
            <a href="{{ route('admin.daily-attendance.retards') }}" class="submenu-link">
                <i class="bi bi-person-x-fill text-warning"></i>
                <span>Liste des retards</span>
            </a>
        </li>
        @endcanany
    </ul>
</li>
@endcanany

<!-- Rapports des Présences -->
@canany(['menu.reports', 'menu.reports-absences-delays', 'menu.reports-custom-presence'])
<li class="sidebar-item has-sub @if(request()->routeIs('reports.*')) active @endif">
    <a href="#" class="sidebar-link">
        <i class="bi bi-bar-chart-fill"></i>
        <span>Rapports des Présences</span>
    </a>
    <ul class="submenu @if(request()->routeIs('reports.*')) active @endif">
        @canany(['menu.reports', 'menu.reports-absences-delays'])
        <li class="submenu-item @if(request()->routeIs('reports.absences-delays')) active @endif">
            <a href="{{ route('reports.absences-delays') }}" class="submenu-link">
                <i class="bi bi-exclamation-triangle"></i>
                <span>État de pointage <br>(arrivées – départs)</span>
            </a>
        </li>
        @endcanany
        @canany(['menu.reports', 'menu.reports-custom-presence'])
        <li class="submenu-item @if(request()->routeIs('reports.custom.presence')) active @endif">
            <a href="{{ route('reports.custom.presence') }}" class="submenu-link">
                <i class="bi bi-funnel"></i>
                <span>Rapport d'assiduité et de ponctualité</span>
            </a>
        </li>
        @endcanany
    </ul>
</li>
@endcanany

        <!-- Appareils (existant) -->
        @can('menu.devices')
        <li class="sidebar-item @if(request()->routeIs('devices.*')) active @endif">
          <a href="{{route('devices.index')}}" class="sidebar-link">
            <i class="bi bi-hdd"></i>
            <span>Appareils</span>
          </a>
        </li>
        @endcan

        @canany(['menu.settings', 'menu.report-templates', 'menu.backup-data'])
        <li class="sidebar-item has-sub @if(request()->routeIs('settings.*') || request()->routeIs('backup-data.*')) active @endif">
          <a href="#" class="sidebar-link">
            <i class="bi bi-gear"></i>
            <span>Paramètres</span>
          </a>
          <ul class="submenu @if(request()->routeIs('settings.*') || request()->routeIs('backup-data.*')) active @endif">
            @can('menu.settings')
            <li class="submenu-item @if(request()->routeIs('settings.index') || request()->routeIs('settings.signataires.*')) active @endif">
              <a href="{{route('settings.index')}}" class="submenu-link">
                <i class="bi bi-sliders"></i>
                <span>Paramètres généraux</span>
              </a>
            </li>
            @endcan
            @canany(['menu.settings', 'menu.report-templates'])
            <li class="submenu-item @if(request()->routeIs('settings.report-templates.*')) active @endif">
              <a href="{{route('settings.report-templates.index')}}" class="submenu-link">
                <i class="bi bi-file-earmark-pdf"></i>
                <span>Modèles d'export</span>
              </a>
            </li>
            @endcanany
            @canany(['menu.settings', 'menu.backup-data'])
            <li class="submenu-item @if(request()->routeIs('backup-data.*')) active @endif">
              <a href="{{route('backup-data.index')}}" class="submenu-link">
                <i class="bi bi-database-down"></i>
                <span>Sauvegarde des données</span>
              </a>
            </li>
            @endcanany
          </ul>
        </li>
        @endcanany

        <!-- Utilisateurs & permissions (admin uniquement) -->
        @role('admin')
        <li class="sidebar-item @if(request()->routeIs('users.*')) active @endif">
          <a href="{{route('users.index')}}" class="sidebar-link">
            <i class="bi bi-person-gear"></i>
            <span>Utilisateurs</span>
          </a>
        </li>
        @endrole

        {{-- Journal des activités — super-admin / admin uniquement --}}
        @hasanyrole('admin|super-admin')
        <li class="sidebar-item @if(request()->routeIs('journalisation.*')) active @endif">
          <a href="{{route('journalisation.index')}}" class="sidebar-link">
            <i class="bi bi-journal-text"></i>
            <span>Journal des activités</span>
          </a>
        </li>
        @endhasanyrole

      </ul>
    </div>
  </div>
</div>

<!-- Ajouter ce CSS pour les badges en direct et les sous-menus -->
<style>
  .live-badge {
    animation: pulse 2s infinite;
    font-size: 0.6rem;
    padding: 0.2rem 0.4rem;
  }
  
  @keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
  }
  
  .submenu-header {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #6c757d;
    font-weight: 600;
    margin-top: 0.5rem;
    border-top: 1px solid #e9ecef;
  }
  
  .submenu-header:first-child {
    border-top: none;
    margin-top: 0;
  }
</style>