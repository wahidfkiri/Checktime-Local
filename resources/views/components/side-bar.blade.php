<div id="sidebar">
  <div class="sidebar-wrapper active">
    <div class="sidebar-header position-relative">
      <div class="d-flex justify-content-center align-items-center">
        <div class="logo">
          <a href="{{route('dashboard')}}">
            <img src="{{asset('logo.jpg')}}" alt="Logo" srcset="" style="width: 150px; height: 100px;">
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
        <li class="sidebar-item has-sub @if(request()->routeIs('employees.*') || request()->routeIs('departments.*') || request()->routeIs('areas.*')) active @endif">
          <a href="#" class="sidebar-link">
            <i class="bi bi-people-fill"></i>
            <span>Gestion des <br>Employés</span>
          </a>
          <ul class="submenu @if(request()->routeIs('employees.*') || request()->routeIs('departments.*') || request()->routeIs('areas.*')) active @endif">
            <li class="submenu-item @if(request()->routeIs('employees.*')) active @endif">
              <a href="{{route('employees.index')}}">
                <i class="bi bi-list-ul"></i>
                <span>Liste des employés</span>
              </a>
            </li>
            <li class="submenu-item @if(request()->routeIs('departments.*')) active @endif">
              <a href="{{route('departments.index')}}">
                <i class="bi bi-building"></i>
                <span>Départements</span>
              </a>
            </li>
            <li class="submenu-item @if(request()->routeIs('areas.*')) active @endif">
              <a href="{{route('areas.index')}}">
                <i class="bi bi-geo-alt"></i>
                <span>Zones</span>
              </a>
            </li>
          </ul>
        </li>

        <!-- Gestion des Plannings -->
<li class="sidebar-item has-sub @if(request()->routeIs('schedules.*') || request()->routeIs('work-hours.*') || request()->routeIs('employee-schedules.*')) active @endif">
    <a href="#" class="sidebar-link">
        <i class="bi bi-calendar-week-fill"></i>
        <span>Gestion des <br>Plannings</span>
    </a>
    <ul class="submenu @if(request()->routeIs('schedules.*') || request()->routeIs('work-hours.*') || request()->routeIs('schedules.*') || request()->routeIs('rotations.*')) active @endif">
        <!-- Types d'horaires -->
        <li class="submenu-item @if(request()->routeIs('work-hours.*')) active @endif">
            <a href="{{ route('work-hours.index') }}">
                <i class="bi bi-clock-history"></i>
                <span>Types d'horaires</span>
            </a>
        </li>
        
        <!-- Horaires rotatifs -->
        <li class="d-none submenu-item @if(request()->routeIs('rotations.*')) active @endif">
            <a href="{{ route('rotations.index') }}">
                <i class="bi bi-arrow-repeat"></i>
                <span>Horaires rotatifs</span>
            </a>
        </li>
        
        
               <li class="submenu-item @if(request()->routeIs('employee-schedules.*')) active @endif">
    <a href="{{ route('employee-schedules.index') }}" class="sidebar-link">
        <i class="bi bi-calendar-check"></i>
        <span>Plannings Employés</span>
    </a>
</li>
        <!-- Calendrier d'assignation -->
        <li class="submenu-item @if(request()->routeIs('schedules.calendar')) active @endif">
            <a href="{{ route('schedules.calendar') }}">
                <i class="bi bi-calendar3"></i>
                <span>Calendrier</span>
            </a>
        </li>
    </ul>
</li>

        <!-- Gestion des autorisations -->
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
            <li class="submenu-item @if(request()->routeIs('permissions.*')) active @endif">
              <a href="{{route('authorizations.employee-permissions.index')}}">
                <i class="bi bi-check-circle-fill"></i>
                <span>Permissions</span>
              </a>
            </li>
            <li class="submenu-item {{ request()->routeIs('missions.*') ? 'active' : '' }}">
    <a href="{{ route('missions.index') }}">
        <i class="bi bi-briefcase me-2"></i>
        <span>Missions</span>
    </a>
</li>
            
            <!-- Retards -->
            <!-- <li class="submenu-item @if(request()->routeIs('delays.*')) active @endif">
              <a href="{{route('authorizations.delays.index')}}">
                <i class="bi bi-clock"></i>
                <span>Retards</span>
              </a>
            </li> -->
            
            <!-- Congés -->
            <li class="submenu-item @if(request()->routeIs('leaves.*')) active @endif">
              <a href="{{route('leaves.index')}}">
                <i class="bi bi-calendar-check"></i>
                <span>Congés</span>
              </a>
            </li>
            <!-- Congés -->
            <!-- <li class="submenu-item @if(request()->routeIs('leaves.*')) active @endif">
              <a href="{{route('leaves.index')}}">
                <i class="bi bi-calendar-check"></i>
                <span>Vacances</span>
              </a>
            </li> -->
          </ul>
        </li>

       <!-- Gestion des Présences avec sous-menu -->
<li class="sidebar-item has-sub @if(request()->routeIs('admin.daily-attendance.*')) active @endif">
    <a href="#" class="sidebar-link">
        <i class="bi bi-clock-history"></i>
        <span>Historique des pointages</span>
    </a>
    <ul class="submenu @if(request()->routeIs('admin.daily-attendance.*')) active @endif">
        <!-- Historique complet -->
        <li class="submenu-item @if(request()->routeIs('admin.daily-attendance.index')) active @endif">
            <a href="{{ route('admin.daily-attendance.index') }}">
                <i class="bi bi-table"></i>
                <span>Historique complet</span>
            </a>
        </li>
        
        <!-- Liste des présences -->
        <li class="submenu-item @if(request()->routeIs('admin.daily-attendance.presence')) active @endif">
            <a href="{{ route('admin.daily-attendance.presence') }}">
                <i class="bi bi-person-check-fill text-success"></i>
                <span>Liste des présences</span>
            </a>
        </li>
        
        <!-- Liste des absences -->
        <li class="submenu-item @if(request()->routeIs('admin.daily-attendance.absence')) active @endif">
            <a href="{{ route('admin.daily-attendance.absence') }}">
                <i class="bi bi-person-x-fill text-danger"></i>
                <span>Liste des absences</span>
            </a>
        </li>
        
        <!-- Liste des retards -->
        <li class="submenu-item @if(request()->routeIs('admin.daily-attendance.retards')) active @endif">
            <a href="{{ route('admin.daily-attendance.retards') }}">
                <i class="bi bi-person-x-fill text-warning"></i>
                <span>Liste des retards</span>
            </a>
        </li>
    </ul>
</li>

<!-- Rapports des Présences (inchangé) -->
<li class="sidebar-item has-sub @if(request()->routeIs('reports.*')) active @endif">
    <a href="#" class="sidebar-link">
        <i class="bi bi-bar-chart-fill"></i>
        <span>Rapports des Présences</span>
    </a>
    <ul class="submenu @if(request()->routeIs('reports.*')) active @endif">
        <li class="submenu-item @if(request()->routeIs('reports.absences-delays')) active @endif">
            <a href="{{ route('reports.absences-delays') }}">
                <i class="bi bi-exclamation-triangle"></i>
                <span>État de pointage <br>(arrivées – départs)</span>
            </a>
        </li>
        <li class="submenu-item @if(request()->routeIs('reports.custom.presence')) active @endif">
            <a href="{{ route('reports.custom.presence') }}">
                <i class="bi bi-funnel"></i>
                <span>Rapport d'assiduité et de ponctualité</span>
            </a>
        </li>
    </ul>
</li>

        <!-- Appareils (existant) -->
        <li class="sidebar-item @if(request()->routeIs('devices.*')) active @endif">
          <a href="{{route('devices.index')}}" class="sidebar-link">
            <i class="bi bi-hdd"></i>
            <span>Appareils</span>
          </a>
        </li>
        
        <li class="sidebar-item @if(request()->routeIs('settings.*')) active @endif">
          <a href="{{route('settings.index')}}" class="sidebar-link">
            <i class="bi bi-gear"></i>
            <span>Paramètres</span>
          </a>
        </li>

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