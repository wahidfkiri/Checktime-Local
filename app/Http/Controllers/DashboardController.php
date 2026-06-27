<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Zone;
use App\Models\Device;
use App\Models\DailyAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        
        // SYNC DES APPAREILS AVANT DE CALCULER LES STATS
        $this->syncDevicesIfNeeded();
        
        // Statistiques Principales
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $inactiveEmployees = Employee::where('status', 'inactive')->count();
        $suspendedEmployees = Employee::where('status', 'suspended')->count();
        
        // Statistiques de présence du jour
        $totalPresentToday = DailyAttendance::whereDate('attendance_date', $today)
            ->whereNotNull('check_in')
            ->count();
        
        $totalAbsentToday = $activeEmployees - $totalPresentToday;
        
        $totalRetardToday = DailyAttendance::whereDate('attendance_date', $today)
            ->where('is_late', true)
            ->count();
        
        $totalDepartments = Department::count();
        $totalZones = Zone::count();
        $totalDevices = Device::count();
        
        // Appareils récemment synchronisés
        $recentlySyncedDevices = Device::whereNotNull('last_sync')
            ->where('last_sync', '>=', Carbon::now()->subDay())
            ->count();
        
        // Données pour graphiques
        $employeeStatusData = [
            'active' => $activeEmployees,
            'inactive' => $inactiveEmployees,
            'suspended' => $suspendedEmployees
        ];
        
        // Statistiques de présence pour le graphique
        $attendanceTodayData = [
            'present' => $totalPresentToday,
            'absent' => $totalAbsentToday,
            'retard' => $totalRetardToday
        ];
        
        // Top départements par nombre d'employés
        $topDepartments = Employee::select('dept_name', DB::raw('COUNT(*) as count'))
            ->whereNotNull('dept_name')
            ->groupBy('dept_name')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get();
        
        $topDepartmentsLabels = $topDepartments->pluck('dept_name')->toArray();
        $topDepartmentsCountData = $topDepartments->pluck('count')->toArray();
        
        // Top zones par nombre d'employés
        $topZones = Employee::select('area_name', DB::raw('COUNT(*) as count'))
            ->whereNotNull('area_name')
            ->groupBy('area_name')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get();
        
        $topZonesLabels = $topZones->pluck('area_name')->toArray();
        $topZonesCountData = $topZones->pluck('count')->toArray();
        
        // Croissance mensuelle des employés
        $monthlyStats = $this->getMonthlyStats();
        $monthlyLabels = $monthlyStats['labels'];
        $monthlyNewEmployees = $monthlyStats['new_employees'];
        
        // Statistiques de présence hebdomadaires
        $weeklyAttendance = $this->getWeeklyAttendanceStats();
        
        // Derniers employés ajoutés
        $recentEmployees = Employee::orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        // Dernières présences enregistrées
        $recentAttendances = DailyAttendance::with('employee')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        // Statut des appareils (calculé APRÈS synchronisation)
        $fifteenDaysAgo = Carbon::now()->subDays(15);
        
        $activeDevices = Device::whereNotNull('last_sync')
            ->where('last_sync', '>=', $fifteenDaysAgo)
            ->count();
        
        $inactiveDevices = Device::where(function($query) use ($fifteenDaysAgo) {
                $query->whereNull('last_sync')
                      ->orWhere('last_sync', '<', $fifteenDaysAgo);
            })
            ->count();
        
        // Calcul des pourcentages
        $activeEmployeesPercentage = $totalEmployees > 0 ? round(($activeEmployees / $totalEmployees) * 100) : 0;
        $activeDevicesPercentage = $totalDevices > 0 ? round(($activeDevices / $totalDevices) * 100) : 0;
        $attendanceRate = $activeEmployees > 0 ? round(($totalPresentToday / $activeEmployees) * 100) : 0;
        
        // Synchronisation status
        $lastSyncTime = Cache::get('employees_last_sync');
        $lastSyncText = $lastSyncTime ? Carbon::createFromTimestamp($lastSyncTime)->diffForHumans() : 'Jamais';
        
        // Dernière synchro des appareils
        $lastDevicesSync = Cache::get('devices_last_sync');
        $lastDevicesSyncText = $lastDevicesSync ? Carbon::createFromTimestamp($lastDevicesSync)->diffForHumans() : 'Jamais';
        
        return view('dashboard', compact(
            'totalEmployees',
            'activeEmployees',
            'inactiveEmployees',
            'suspendedEmployees',
            'totalPresentToday',
            'totalAbsentToday',
            'totalRetardToday',
            'attendanceRate',
            'totalDepartments',
            'totalZones',
            'totalDevices',
            'recentlySyncedDevices',
            'employeeStatusData',
            'attendanceTodayData',
            'topDepartmentsLabels',
            'topDepartmentsCountData',
            'topZonesLabels',
            'topZonesCountData',
            'monthlyLabels',
            'monthlyNewEmployees',
            'weeklyAttendance',
            'recentEmployees',
            'recentAttendances',
            'activeDevices',
            'inactiveDevices',
            'activeEmployeesPercentage',
            'activeDevicesPercentage',
            'lastSyncText',
            'lastDevicesSyncText'
        ));
    }

    /**
     * Synchronise les appareils si nécessaire avant d'afficher les stats
     */
    private function syncDevicesIfNeeded(): void
    {
        if (Cache::get('devices_syncing', false)) {
            return;
        }
        
        $lastSync = Cache::get('devices_last_sync', 0);
        $syncInterval = 300; // 5 minutes
        
        if ($lastSync == 0 || (time() - $lastSync) > $syncInterval) {
            $this->syncDevices();
        }
    }

    /**
     * Synchronise les appareils
     */
    private function syncDevices(): void
    {
        try {
            Cache::put('devices_syncing', true, 300);
            
            Log::info("Dashboard - Synchronisation des devices");
            
            $accessConfig = DB::table('access_configs')->first();
            
            if (!$accessConfig || empty($accessConfig->general_token)) {
                Log::warning("Dashboard - Aucune configuration d'accès trouvée");
                Cache::forget('devices_syncing');
                return;
            }
            
            $token = $accessConfig->general_token;
            $allDevices = $this->fetchAllDevicesFromAPI($token);
            
            if (empty($allDevices)) {
                Cache::forget('devices_syncing');
                return;
            }
            
            $syncedCount = 0;
            foreach ($allDevices as $deviceData) {
                if ($this->syncSingleDevice($deviceData)) {
                    $syncedCount++;
                }
            }
            
            $this->deleteMissingDevices($allDevices);
            
            Cache::put('devices_last_sync', time(), now()->addHours(2));
            
            Log::info("Dashboard - Synchronisation terminée: {$syncedCount} devices");
            
        } catch (\Exception $e) {
            Log::error("Dashboard - Erreur syncDevices: " . $e->getMessage());
        } finally {
            Cache::forget('devices_syncing');
        }
    }

    /**
     * Récupère TOUTES les devices depuis l'API (avec pagination)
     */
    private function fetchAllDevicesFromAPI(string $token): array
    {
        $allDevices = [];
        $page = 1;
        $hasMore = true;
        
        try {
            while ($hasMore && $page <= 20) {
                $response = Http::withHeaders([
                    "Authorization" => "Token " . $token,
                    "Accept" => "application/json"
                ])
                ->timeout(30)
                ->get('http://54.37.15.111/iclock/api/terminals/', [
                    'page' => $page,
                    'limit' => 100
                ]);
                
                if (!$response->successful()) {
                    Log::warning("Dashboard - Échec de récupération des devices - Page {$page}");
                    break;
                }
                
                $data = $response->json();
                
                if (!isset($data['data']) || !is_array($data['data']) || empty($data['data'])) {
                    break;
                }
                
                $allDevices = array_merge($allDevices, $data['data']);
                $hasMore = isset($data['next']) && !empty($data['next']);
                $page++;
                
                if ($hasMore) {
                    usleep(200000);
                }
            }
            
            Log::info("Dashboard - Récupéré " . count($allDevices) . " devices depuis l'API");
            
        } catch (\Exception $e) {
            Log::error('Dashboard - Erreur fetchAllDevicesFromAPI: ' . $e->getMessage());
        }
        
        return $allDevices;
    }

    /**
     * Synchronise une seule device
     */
    private function syncSingleDevice(array $deviceData): bool
    {
        try {
            if (empty($deviceData['sn']) || empty($deviceData['alias'])) {
                return false;
            }
            
            $deviceCode = $deviceData['sn'];
            $existingDevice = Device::where('device_sn', $deviceCode)->first();
            
            $deviceAttributes = [
                'alias' => $deviceData['alias'],
                'ip' => $deviceData['ip_address'] ?? null,
                'terminal_name' => $deviceData['terminal_name'] ?? null,
                'area_name' => $deviceData['area_name'] ?? null,
                'last_sync' => $deviceData['last_activity'] ?? null,
                'metadata' => json_encode($deviceData),
                'updated_at' => now(),
            ];
            
            if ($existingDevice) {
                $existingDevice->update($deviceAttributes);
            } else {
                $deviceAttributes['device_sn'] = $deviceCode;
                $deviceAttributes['created_at'] = now();
                Device::create($deviceAttributes);
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Dashboard - Erreur syncSingleDevice {$deviceData['sn']}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime les devices qui n'existent plus dans l'API
     */
    private function deleteMissingDevices(array $apiDevices): void
    {
        try {
            $apiDeviceCodes = [];
            foreach ($apiDevices as $device) {
                if (!empty($device['sn'])) {
                    $apiDeviceCodes[] = $device['sn'];
                }
            }
            
            if (empty($apiDeviceCodes)) {
                return;
            }
            
            $devicesToDelete = Device::whereNotIn('device_sn', $apiDeviceCodes)->get();
            
            foreach ($devicesToDelete as $device) {
                $device->delete();
                Log::info("Dashboard - Device supprimée: {$device->device_sn} - n'existe plus dans l'API");
            }
            
        } catch (\Exception $e) {
            Log::error("Dashboard - Erreur deleteMissingDevices: " . $e->getMessage());
        }
    }

    private function getMonthlyStats($months = 6)
    {
        $labels = [];
        $newEmployees = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            
            $labels[] = $date->format('M Y');
            
            $newEmployees[] = Employee::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        }

        return [
            'labels' => $labels,
            'new_employees' => $newEmployees
        ];
    }

    private function getWeeklyAttendanceStats()
    {
        $stats = [];
        $startOfWeek = Carbon::now()->startOfWeek();
        
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            
            $present = DailyAttendance::whereDate('attendance_date', $date)
                ->whereNotNull('check_in')
                ->count();
            
            $retard = DailyAttendance::whereDate('attendance_date', $date)
                ->where('is_late', true)
                ->count();

            $absent = DailyAttendance::whereDate('attendance_date', $date)
                ->whereNull('check_in')
                ->count();
            
            $stats[] = [
                'day' => $date->format('D'),
                'date' => $date->format('Y-m-d'),
                'present' => $present,
                'absent' => $absent,
                'retard' => $retard
            ];
        }
        
        return $stats;
    }

    public function getStatsJson()
    {
        $today = Carbon::today();
        
        $this->syncDevicesIfNeeded();
        
        $activeEmployees = Employee::where('status', 'active')->count();
        $totalPresentToday = DailyAttendance::whereDate('attendance_date', $today)
            ->whereNotNull('check_in')
            ->count();
        
        $fifteenDaysAgo = Carbon::now()->subDays(15);
        
        $activeDevices = Device::whereNotNull('last_sync')
            ->where('last_sync', '>=', $fifteenDaysAgo)
            ->count();
        
        $inactiveDevices = Device::where(function($query) use ($fifteenDaysAgo) {
                $query->whereNull('last_sync')
                      ->orWhere('last_sync', '<', $fifteenDaysAgo);
            })
            ->count();
        
        return response()->json([
            'totalEmployees' => Employee::count(),
            'activeEmployees' => $activeEmployees,
            'totalPresentToday' => $totalPresentToday,
            'totalAbsentToday' => $activeEmployees - $totalPresentToday,
            'totalRetardToday' => DailyAttendance::whereDate('attendance_date', $today)
                ->where('is_late', true)
                ->count(),
            'totalDepartments' => Department::count(),
            'totalZones' => Zone::count(),
            'totalDevices' => Device::count(),
            'activeDevices' => $activeDevices,
            'inactiveDevices' => $inactiveDevices,
            'recentlySyncedDevices' => Device::whereNotNull('last_sync')
                ->where('last_sync', '>=', Carbon::now()->subDay())
                ->count(),
            'attendanceRate' => $activeEmployees > 0 ? round(($totalPresentToday / $activeEmployees) * 100, 2) : 0,
            'lastDevicesSync' => Cache::get('devices_last_sync') ? 
                date('d/m/Y H:i:s', Cache::get('devices_last_sync')) : 'Jamais'
        ]);
    }
}