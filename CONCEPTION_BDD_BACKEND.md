# Conception Base de Donnees et Backend - CheckTime

## 1. Contexte et stack technique
- Framework: Laravel 9 (PHP 8.x)
- Authentification: Laravel Auth + middleware `auth`
- RBAC: `spatie/laravel-permission` (middleware `role:client`)
- Reporting: `barryvdh/laravel-dompdf`
- DataTables serveur: `yajra/laravel-datatables-oracle`
- Architecture applicative: monolithe Laravel modulaire par packages locaux (`packages/vendor/*`)

## 2. Architecture backend

### 2.1 Organisation modulaire
Le backend est structure autour de 4 modules fonctionnels, charges comme packages Laravel:
- `Vendor\\Employee`: gestion referentielle RH (zones/areas, departements, employes)
- `Vendor\\Planning`: horaires, plannings employes, assignation calendrier
- `Vendor\\Attendance`: synchronisation et consultation des presences/retards/absences
- `Vendor\\Report`: rapports analytiques et exports PDF

Ces modules sont actives via `config/app.php` (providers) et chargent leurs propres routes + vues via leurs `*ServiceProvider`.

### 2.2 Routage et espaces fonctionnels
Deux couches de routage coexistent:
- Routes applicatives classiques: `routes/web.php` (prefixes type `/reports`, `/daily-attendance`, etc.)
- Routes packages: ex. `packages/vendor/report/src/routes/web.php` avec prefixe `/admin/reports`

Middleware principal sur les modules metiers:
- `web`
- `auth`
- `role:client`
- `client.active`

=> Le systeme est multi-client avec isolement logique par `client_id` sur la majorite des requetes.

### 2.3 Couches backend
- **Controllers**: orchestration HTTP, validation, pagination DataTables, exports
- **Models Eloquent**: mapping relationnel et casts
- **Services**:
  - `CheckTimeService`: appels API biometrie externe (`http://54.37.15.111`)
  - `AttendanceSyncService`: synchronisation robuste des transactions (retry, timeout, traitement batch)
  - `BiometricService`: verification biometrie / generation de payloads
- **Views Blade**: interfaces web et templates PDF

### 2.4 Flux metiers principaux

#### A) Synchronisation des presences
1. Recuperation token dans `access_configs.general_token`
2. Appels API transactions par employe/periode
3. Persist des transactions brutes (modele `AttendanceTransaction`)
4. Consolidation journaliere (arrivee/depart, statut, retard, etc.) en table de presence quotidienne

#### B) Planification
1. Definition types horaires (`work_hour_types`)
2. Affectation de plannings (`employee_schedules`)
3. Generation/calcul de plannings journaliers (`daily_plannings`)
4. Prise en compte cycles rotation + jours feries

#### C) Reporting
1. Filtrage par periode + employe
2. Aggregation statut/retard/absence
3. Enrichissement par planning, permissions, conges, missions
4. Export PDF et suivi d'exports (`pdf_exports`, `pdf_export_params`)

## 3. Conception base de donnees

## 3.1 Principe de modelisation
- Cle de partition metier: `client_id` present dans la plupart des tables
- FK explicites sur entites coeur (clients, employes, plannings, conges, permissions)
- Denormalisation partielle pour performance/API (ex: `area_name`, `dept_name`, `terminal_alias`)

## 3.2 Domaines de donnees

### A) Tenant / securite / configuration
- `users`: comptes applicatifs
- `clients`: entite tenant (societe)
- `client_users`: comptes secondaires rattaches client
- `access_configs`: identifiants + token API biometrie
- `settings`: configuration communication (email/sms)
- Tables Spatie permission (`roles`, `permissions`, `model_has_roles`, etc.)

### B) Referentiel RH
- `zones` (avec `area_id` ajoute)
- `departments` (hierarchie via `parent_id`, + `department_id` legacy)
- `employees` (code RH/API `emp_code`, infos perso, zone/departement textuels)
- `devices` (terminaux biometrie)

### C) Planning
- `work_hour_types`
- `employee_schedules` (fixe / rotation / planifie)
- `schedule_rotations`
- `daily_plannings`
- `holidays`

### D) Presence et autorisations
- `real_time_logs` (logs instantanes)
- table de presence quotidienne (voir section risques de nommage)
- `employee_permissions`
- `leave_types`
- `leaves`
- `missions`

### E) Reporting et communication
- `report_settings`
- `email_logs`
- `sms_logs`
- `pdf_exports`
- `pdf_export_params`

## 3.3 Relations principales (vue logique)
- `users (1) -> (N) clients`
- `clients (1) -> (N) employees`
- `clients (1) -> (N) devices`
- `clients (1) -> (N) employee_schedules`
- `employees (1) -> (N) employee_schedules`
- `employees (1) -> (N) daily_plannings`
- `employees (1) -> (N) leaves`
- `leave_types (1) -> (N) leaves`
- `employees (1) -> (N) employee_permissions`
- `employees (1) -> (N) missions`
- `clients (1) -> (N) report_settings / email_logs / sms_logs`

## 3.4 Contraintes et index importants
- Index de statut/date sur `employee_permissions`
- Unicite par employe + date sur `daily_plannings`
- Index de suivi export (`pdf_exports.status`, `created_at`)
- FK cascade sur la plupart des tables dependantes de `clients` et `employees`

## 4. Constats de review (techniques)

### 4.1 Points forts
- Bonne separation fonctionnelle par package (Employee/Planning/Attendance/Report)
- Presence d'une couche service pour l'integration API externe
- Prise en charge de la multi-tenance metier via `client_id`
- Couverture fonctionnelle riche (sync, planning, rapports, exports)

### 4.2 Points d'attention
- Coexistence de routes legacy (`routes/web.php`) et routes packages (`/admin/...`) pouvant creer des doublons fonctionnels.
- Incoherences historiques de nommage/schema sur la presence quotidienne:
  - migration `daily_attendance` (singulier, colonnes `date`, `arrival_time`, ...)
  - modeles/controllers utilises avec `DailyAttendance` et colonnes `attendance_date`, `check_in`, `check_out`, etc.
- Le modele `AttendanceTransaction` est reference metierement; verifier la presence effective de sa migration/table en environnement cible.
- Quelques artefacts legacy dans le nommage (`abscences` dans les vues report package, champs additionnels redondants type `department_id` / `parent_id`).

## 5. Recommandations d'architecture
- Normaliser une seule couche de routes par domaine (preferer packages, deprecier legacy progressivement).
- Stabiliser le schema final de presence quotidienne (`daily_attendances` + colonnes finales) et aligner tous les modeles/controllers.
- Documenter officiellement les contrats API externes (token, endpoints, erreurs, retry).
- Ajouter un diagramme ERD versionne + matrice de responsabilites par package.
- Renforcer les tests d'integration sur les flux critiques: sync -> consolidation journaliere -> reporting.

## 6. Fichier source de cette review
Document genere a partir de l'analyse du code source (models, migrations, routes, controllers, services) du projet courant.
