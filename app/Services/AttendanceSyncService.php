<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\AttendanceTransaction;
use App\Models\DailyAttendance;
use App\Models\AccessConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class AttendanceSyncService
{
    private $apiBaseUrl = 'http://54.37.15.111/iclock/api/transactions/';
    
    // Configuration des timeouts et retry
    private $connectionTimeout = 10;
    private $requestTimeout = 30;
    private $maxRetries = 3;
    private $retryDelay = 1000;
    private $retryMultiplier = 2;
    
    /**
     * Synchroniser les pointages pour tous les employés
     */
    public function syncAll($daysBack = 1)
    {
        $accessConfig = AccessConfig::first();
        
        if (!$accessConfig || !$accessConfig->general_token) {
            Log::warning("Pas de token configuré dans access_configs");
            return false;
        }
        
        $employees = Employee::whereNotNull('emp_code')->where('is_active', true)->get();
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($employees as $employee) {
            try {
                $result = $this->syncEmployeeAttendances($employee, $accessConfig->general_token, $daysBack);
                if ($result) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } catch (\Exception $e) {
                Log::error("Erreur pour l'employé {$employee->id} ({$employee->emp_code}): " . $e->getMessage());
                $errorCount++;
                continue;
            }
            
            usleep(100000); // 100ms
        }
        
        Log::info("Synchronisation terminée: {$successCount} succès, {$errorCount} échecs");
        
        // Mettre à jour les résumés pour les derniers jours
        $this->updateDailySummariesForPeriod($daysBack);
        
        return true;
    }
    
    /**
     * Synchroniser les pointages d'un employé avec gestion des timeouts et retry
     */
    private function syncEmployeeAttendances(Employee $employee, $token, $daysBack = 1)
    {
        $startDate = Carbon::now()->subDays($daysBack)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $page = 1;
        $hasMoreData = true;
        $allTransactions = [];
        $retryCount = 0;
        
        while ($hasMoreData) {
            try {
                Log::info("Synchronisation employé {$employee->emp_code} - Page {$page}");
                
                $response = $this->makeApiRequestWithRetry(
                    $employee->emp_code,
                    $token,
                    $startDate,
                    $endDate,
                    $page
                );
                
                if ($response && isset($response['data']) && count($response['data']) > 0) {
                    $allTransactions = array_merge($allTransactions, $response['data']);
                    
                    if (isset($response['next']) && $response['next'] && count($response['data']) >= 100) {
                        $page++;
                        usleep(200000);
                    } else {
                        $hasMoreData = false;
                    }
                } else {
                    $hasMoreData = false;
                }
                
                $retryCount = 0;
                
            } catch (ConnectionException $e) {
                Log::warning("Timeout pour l'employé {$employee->emp_code} (tentative {$retryCount}): " . $e->getMessage());
                
                $retryCount++;
                if ($retryCount >= $this->maxRetries) {
                    Log::error("Échec définitif pour l'employé {$employee->emp_code} après {$this->maxRetries} tentatives");
                    $hasMoreData = false;
                } else {
                    $delay = $this->retryDelay * pow($this->retryMultiplier, $retryCount - 1);
                    Log::info("Nouvelle tentative dans " . ($delay / 1000) . " secondes...");
                    usleep($delay * 1000);
                }
                
            } catch (\Exception $e) {
                Log::error("Erreur inattendue pour l'employé {$employee->emp_code}: " . $e->getMessage());
                $hasMoreData = false;
            }
        }
        
        if (!empty($allTransactions)) {
            try {
                $this->processEmployeeTransactions($employee, $allTransactions);
                Log::info("Synchronisé {$employee->emp_code}: " . count($allTransactions) . " transactions");
                return true;
            } catch (\Exception $e) {
                Log::error("Erreur traitement transactions pour {$employee->emp_code}: " . $e->getMessage());
                return false;
            }
        }
        
        Log::info("Aucune transaction pour {$employee->emp_code} sur la période");
        return true;
    }
    
    /**
     * Effectuer une requête API avec gestion de timeout et retry automatique
     */
    private function makeApiRequestWithRetry($empCode, $token, $startDate, $endDate, $page = 1)
    {
        $attempt = 1;
        $lastException = null;
        
        while ($attempt <= $this->maxRetries) {
            try {
                Log::info("API Request - Employé: {$empCode}, Page: {$page}, Tentative: {$attempt}/{$this->maxRetries}");
                
                $response = Http::withHeaders([
                    'Authorization' => 'Token ' . $token,
                    'Accept' => 'application/json',
                    'User-Agent' => 'Attendance-Sync-Service/1.0'
                ])
                ->timeout($this->requestTimeout)
                ->connectTimeout($this->connectionTimeout)
                ->retry(0)
                ->get($this->apiBaseUrl, [
                    'emp_code' => $empCode,
                    'start_time' => $startDate->format('Y-m-d H:i:s'),
                    'end_time' => $endDate->format('Y-m-d H:i:s'),
                    'page' => $page,
                    'limit' => 100
                ]);
                
                if ($response->successful()) {
                    Log::info("API Success - Employé: {$empCode}, Page: {$page}, Status: " . $response->status());
                    return $response->json();
                }
                
                $statusCode = $response->status();
                $errorBody = $response->body();
                
                Log::warning("API HTTP Error - Employé: {$empCode}, Status: {$statusCode}, Response: " . substr($errorBody, 0, 200));
                
                if (in_array($statusCode, [400, 401, 403, 404])) {
                    Log::error("Erreur fatale API - Status {$statusCode} pour {$empCode}");
                    return null;
                }
                
                if ($statusCode >= 500) {
                    $lastException = new \Exception("HTTP {$statusCode}: " . substr($errorBody, 0, 100));
                } else {
                    return null;
                }
                
            } catch (ConnectionException $e) {
                $lastException = $e;
                Log::warning("Timeout - Employé: {$empCode}, Tentative {$attempt}: " . $e->getMessage());
            } catch (RequestException $e) {
                $lastException = $e;
                Log::warning("Request Error - Employé: {$empCode}, Tentative {$attempt}: " . $e->getMessage());
            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning("Exception - Employé: {$empCode}, Tentative {$attempt}: " . $e->getMessage());
            }
            
            $delay = $this->retryDelay * pow($this->retryMultiplier, $attempt - 1);
            
            if ($attempt < $this->maxRetries) {
                Log::info("Nouvelle tentative pour {$empCode} dans " . ($delay / 1000) . " secondes...");
                usleep($delay * 1000);
            }
            
            $attempt++;
        }
        
        Log::error("Échec API après {$this->maxRetries} tentatives pour {$empCode}");
        return null;
    }
    
    /**
     * Traiter les transactions d'un employé
     */
    private function processEmployeeTransactions(Employee $employee, array $transactions)
    {
        DB::beginTransaction();
        
        try {
            $processedCount = 0;
            
            foreach ($transactions as $transactionData) {
                if (!isset($transactionData['id']) || !isset($transactionData['punch_time'])) {
                    Log::warning("Transaction invalide ignorée", $transactionData);
                    continue;
                }
                
                $cleanData = $this->cleanTransactionData($transactionData);
                
                AttendanceTransaction::updateOrCreate(
                    [
                        'transaction_id' => $cleanData['id'],
                        'employee_id' => $employee->id,
                    ],
                    [
                        'emp_code' => $cleanData['emp_code'] ?? $employee->emp_code,
                        'punch_time' => $cleanData['punch_time'],
                        'punch_state' => $cleanData['punch_state'] ?? null,
                        'verify_type' => $cleanData['verify_type'] ?? null,
                        'work_code' => $cleanData['work_code'] ?? null,
                        'terminal_sn' => $cleanData['terminal_sn'] ?? null,
                        'terminal_alias' => $cleanData['terminal_alias'] ?? null,
                        'area_alias' => $cleanData['area_alias'] ?? null,
                        'longitude' => $cleanData['longitude'] ?? null,
                        'latitude' => $cleanData['latitude'] ?? null,
                        'gps_location' => $cleanData['gps_location'] ?? null,
                        'mobile' => $cleanData['mobile'] ?? null,
                        'source' => $cleanData['source'] ?? null,
                        'purpose' => $cleanData['purpose'] ?? null,
                        'crc' => $cleanData['crc'] ?? null,
                        'is_attendance' => $cleanData['is_attendance'] ?? true,
                        'reserved' => $cleanData['reserved'] ?? null,
                        'upload_time' => isset($cleanData['upload_time']) ? Carbon::parse($cleanData['upload_time']) : null,
                        'sync_status' => $cleanData['sync_status'] ?? null,
                        'sync_time' => isset($cleanData['sync_time']) ? Carbon::parse($cleanData['sync_time']) : null,
                        'temperature' => $cleanData['temperature'] ?? null,
                        'mask_flag' => $cleanData['mask_flag'] ?? null,
                        'company' => $cleanData['company'] ?? null,
                        'terminal' => $cleanData['terminal'] ?? null,
                        'processed' => false,
                        'updated_at' => Carbon::now()
                    ]
                );
                
                $processedCount++;
            }
            
            DB::commit();
            Log::info("Transactions traitées pour {$employee->emp_code}: {$processedCount}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur transaction DB pour {$employee->emp_code}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Nettoyer les données de transaction
     */
    private function cleanTransactionData($data)
    {
        $cleaned = [];
        
        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            
            if (is_string($value)) {
                $value = trim($value);
            }
            
            if (in_array($key, ['punch_time', 'upload_time', 'sync_time']) && is_string($value)) {
                try {
                    Carbon::parse($value);
                } catch (\Exception $e) {
                    Log::warning("Date invalide pour {$key}: {$value}");
                    continue;
                }
            }
            
            $cleaned[$key] = $value;
        }
        
        return $cleaned;
    }
    
    /**
     * Mettre à jour les résumés pour une période
     */
    public function updateDailySummariesForPeriod($daysBack = 7)
    {
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays($daysBack);
        
        $currentDate = $startDate->copy();
        $updatedCount = 0;
        
        while ($currentDate <= $endDate) {
            try {
                $result = $this->updateDailySummaryForDate($currentDate);
                if ($result) {
                    $updatedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Erreur résumé pour {$currentDate->format('Y-m-d')}: " . $e->getMessage());
            }
            
            $currentDate->addDay();
        }
        
        Log::info("Résumés mis à jour: {$updatedCount} jours traités du {$startDate->format('Y-m-d')} au {$endDate->format('Y-m-d')}");
    }
    
    /**
     * Mettre à jour le résumé pour une date spécifique
     */
    public function updateDailySummaryForDate(Carbon $date)
    {
        $employees = Employee::whereNotNull('emp_code')->where('is_active', true)->get();
        $updatedCount = 0;
        
        foreach ($employees as $employee) {
            try {
                $result = $this->updateEmployeeDailySummary($employee, $date);
                if ($result) {
                    $updatedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Erreur résumé pour {$employee->emp_code} le {$date->format('Y-m-d')}: " . $e->getMessage());
                continue;
            }
        }
        
        Log::info("Résumé du {$date->format('Y-m-d')}: {$updatedCount}/" . $employees->count() . " employés mis à jour");
        
        return $updatedCount;
    }
    
    /**
     * Mettre à jour le résumé quotidien d'un employé
     */
    private function updateEmployeeDailySummary(Employee $employee, Carbon $date)
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        
        $transactions = AttendanceTransaction::where('employee_id', $employee->id)
            ->whereBetween('punch_time', [$startOfDay, $endOfDay])
            ->orderBy('punch_time')
            ->get();
        
        if ($transactions->isEmpty()) {
            return $this->updateOrCreateAbsentRecord($employee, $date);
        }
        
        $dailyTransactions = $transactions->filter(function ($transaction) use ($date) {
            return Carbon::parse($transaction->punch_time)->isSameDay($date);
        });
        
        if ($dailyTransactions->isEmpty()) {
            return $this->updateOrCreateAbsentRecord($employee, $date);
        }
        
        $stats = $this->calculateDailyStats($dailyTransactions, $date);
        
        $rawData = $dailyTransactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'transaction_id' => $transaction->transaction_id,
                'punch_time' => $transaction->punch_time->format('Y-m-d H:i:s'),
                'punch_state' => $transaction->punch_state,
                'verify_type' => $transaction->verify_type,
                'terminal_alias' => $transaction->terminal_alias,
                'area_alias' => $transaction->area_alias,
                'upload_time' => $transaction->upload_time ? $transaction->upload_time->format('Y-m-d H:i:s') : null,
                'source' => $transaction->source,
                'purpose' => $transaction->purpose
            ];
        })->toArray();
        
        $dailyAttendance = DailyAttendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'attendance_date' => $date->format('Y-m-d')
            ],
            [
                'emp_code' => $employee->emp_code,
                'check_in' => $stats['check_in'],
                'check_out' => $stats['check_out'],
                'total_punches' => $stats['total_punches'],
                'punch_times' => json_encode($stats['punch_times']),
                'work_hours' => $stats['work_hours'],
                'break_hours' => $stats['break_hours'],
                'effective_hours' => $stats['effective_hours'],
                'overtime_hours' => $stats['overtime_hours'],
                'status' => $stats['status'],
                'is_late' => $stats['is_late'],
                'late_minutes' => $stats['late_minutes'],
                'is_early_leave' => $stats['is_early_leave'],
                'early_minutes' => $stats['early_minutes'],
                'is_overtime' => $stats['is_overtime'],
                'is_short_work' => $stats['is_short_work'],
                'short_hours' => $stats['short_hours'],
                'has_multiple_punches' => $stats['has_multiple_punches'],
                'multiple_punches_count' => $stats['multiple_punches_count'],
                'raw_data' => json_encode($rawData),
                'notes' => $stats['notes'],
                'updated_at' => Carbon::now(),
                'last_sync_at' => Carbon::now()
            ]
        );
        
        AttendanceTransaction::whereIn('id', $dailyTransactions->pluck('id'))
            ->update([
                'processed' => true,
                'daily_attendance_id' => $dailyAttendance->id,
                'processed_at' => Carbon::now()
            ]);
        
        return $dailyAttendance;
    }
    
    /**
     * Calculer les statistiques quotidiennes
     */
    private function calculateDailyStats($transactions, Carbon $date)
    {
        $punchTimes = $transactions->pluck('punch_time')->map(function ($time) {
            return Carbon::parse($time);
        })->sort();
        
        $totalPunches = $punchTimes->count();
        $punchTimesArray = $punchTimes->map(function ($time) {
            return $time->format('H:i:s');
        })->toArray();
        
        $checkIn = $punchTimes->first();
        $checkOut = $punchTimes->last();
        
        $workHours = $this->calculateSmartWorkHours($punchTimes, $date);
        
        $expectedWorkHours = 7;
        $expectedTotalHours = 8;
        
        $stats = [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_punches' => $totalPunches,
            'punch_times' => $punchTimesArray,
            'work_hours' => $workHours,
            'break_hours' => max(0, $expectedTotalHours - $expectedWorkHours),
            'effective_hours' => $workHours,
            'overtime_hours' => 0,
            'status' => 'PRESENT',
            'is_late' => false,
            'late_minutes' => 0,
            'is_early_leave' => false,
            'early_minutes' => 0,
            'is_overtime' => false,
            'is_short_work' => false,
            'short_hours' => 0,
            'has_multiple_punches' => $totalPunches > 2,
            'multiple_punches_count' => $totalPunches,
            'notes' => ''
        ];
        
        $expectedCheckIn = $date->copy()->setTime(8, 0, 0);
        if ($checkIn && $checkIn->gt($expectedCheckIn)) {
            $lateMinutes = $expectedCheckIn->diffInMinutes($checkIn);
            $stats['late_minutes'] = $lateMinutes;
            
            if ($lateMinutes > 15) {
                $stats['is_late'] = true;
                $stats['status'] = 'LATE';
                $stats['notes'] .= "Retard de {$lateMinutes} minutes. ";
            }
        }
        
        $expectedCheckOut = $date->copy()->setTime(17, 0, 0);
        if ($checkOut && $checkOut->lt($expectedCheckOut)) {
            $earlyMinutes = $checkOut->diffInMinutes($expectedCheckOut);
            $stats['early_minutes'] = $earlyMinutes;
            
            if ($earlyMinutes > 30 && $workHours < $expectedWorkHours) {
                $stats['is_early_leave'] = true;
                $stats['status'] = 'EARLY_LEAVE';
                $stats['notes'] .= "Départ anticipé de {$earlyMinutes} minutes. ";
            }
        }
        
        if ($workHours > $expectedWorkHours) {
            $overtime = $workHours - $expectedWorkHours;
            $stats['overtime_hours'] = round($overtime, 2);
            
            if ($overtime > 0.5) {
                $stats['is_overtime'] = true;
                $stats['status'] = 'OVERTIME';
                $stats['notes'] .= "Heures supplémentaires: {$stats['overtime_hours']}h. ";
            }
        }
        
        if ($workHours < ($expectedWorkHours - 1)) {
            $stats['is_short_work'] = true;
            $stats['short_hours'] = round($expectedWorkHours - $workHours, 2);
            $stats['status'] = 'SHORT_WORK';
            $stats['notes'] .= "Travail court: {$stats['short_hours']}h manquantes. ";
        }
        
        if ($totalPunches == 1) {
            $stats['status'] = 'HALF_DAY';
            $stats['notes'] .= "Un seul pointage. ";
        } elseif ($totalPunches % 2 != 0) {
            $stats['status'] = 'IRREGULAR';
            $stats['notes'] .= "Nombre impair de pointages ({$totalPunches}). ";
        }
        
        if ($totalPunches > 4) {
            $stats['notes'] .= "Multiple pointages ({$totalPunches}) détectés. ";
        }
        
        $stats['effective_hours'] = $this->calculateEffectiveHours($punchTimes, $date);
        
        return $stats;
    }
    
    /**
     * Calculer les heures de travail intelligentes
     */
    private function calculateSmartWorkHours($punchTimes, Carbon $date)
    {
        if ($punchTimes->count() < 2) {
            return 0;
        }
        
        $sorted = $punchTimes->values();
        $totalMinutes = 0;
        
        for ($i = 0; $i < count($sorted) - 1; $i++) {
            $current = $sorted[$i];
            $next = $sorted[$i + 1];
            
            $diffMinutes = $current->diffInMinutes($next);
            
            if ($diffMinutes <= 90) {
                $totalMinutes += $diffMinutes;
            } elseif ($i == 0 && $diffMinutes > 90) {
                continue;
            }
        }
        
        if (count($sorted) % 2 != 0 && count($sorted) > 2) {
            $lastIndex = count($sorted) - 1;
            $prevIndex = $lastIndex - 1;
            
            $prevTime = $sorted[$prevIndex];
            $lastTime = $sorted[$lastIndex];
            
            $diffMinutes = $prevTime->diffInMinutes($lastTime);
            
            if ($diffMinutes <= 240 && $lastTime->hour >= 16) {
                $totalMinutes += $diffMinutes;
            }
        }
        
        return round($totalMinutes / 60, 2);
    }
    
    /**
     * Calculer les heures effectives
     */
    private function calculateEffectiveHours($punchTimes, Carbon $date)
    {
        if ($punchTimes->count() < 2) {
            return 0;
        }
        
        $sorted = $punchTimes->values();
        $totalMinutes = 0;
        
        if ($sorted->count() >= 4) {
            $morningIn = $sorted[0];
            $morningOut = $sorted[1];
            $afternoonIn = $sorted[$sorted->count() - 2];
            $afternoonOut = $sorted[$sorted->count() - 1];
            
            $morningMinutes = $morningIn->diffInMinutes($morningOut);
            $afternoonMinutes = $afternoonIn->diffInMinutes($afternoonOut);
            
            $totalMinutes = $morningMinutes + $afternoonMinutes;
        } else {
            $totalMinutes = $sorted->first()->diffInMinutes($sorted->last());
        }
        
        if ($totalMinutes > 240) {
            $totalMinutes -= 60;
        }
        
        return round($totalMinutes / 60, 2);
    }
    
    /**
     * Créer/mettre à jour un enregistrement d'absence
     */
    private function updateOrCreateAbsentRecord(Employee $employee, Carbon $date)
    {
        if ($date->isWeekend()) {
            DailyAttendance::where([
                'employee_id' => $employee->id,
                'attendance_date' => $date->format('Y-m-d')
            ])->delete();
            return null;
        }
        
        $hasLeave = $this->checkEmployeeLeave($employee, $date);
        
        $status = $hasLeave ? 'LEAVE' : 'ABSENT';
        $notes = $hasLeave ? 'Congé enregistré' : 'Aucun pointage';
        
        return DailyAttendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'attendance_date' => $date->format('Y-m-d')
            ],
            [
                'emp_code' => $employee->emp_code,
                'status' => $status,
                'notes' => $notes,
                'raw_data' => json_encode([]),
                'updated_at' => Carbon::now()
            ]
        );
    }
    
    /**
     * Vérifier si l'employé est en congé
     */
    private function checkEmployeeLeave(Employee $employee, Carbon $date)
    {
        return false;
    }
    
    /**
     * Synchroniser pour une date spécifique
     */
    public function syncForDate(Carbon $date)
    {
        $accessConfig = AccessConfig::first();
        
        if (!$accessConfig || !$accessConfig->general_token) {
            Log::warning("Pas de token configuré pour la synchronisation");
            return false;
        }
        
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();
        
        $employees = Employee::whereNotNull('emp_code')->where('is_active', true)->get();
        $successCount = 0;
        
        foreach ($employees as $employee) {
            try {
                $result = $this->syncEmployeeForDate($employee, $accessConfig->general_token, $startDate, $endDate);
                if ($result) {
                    $successCount++;
                }
            } catch (\Exception $e) {
                Log::error("Erreur {$employee->emp_code} le {$date->format('Y-m-d')}: " . $e->getMessage());
                continue;
            }
            
            usleep(50000);
        }
        
        Log::info("Date {$date->format('Y-m-d')}: {$successCount}/" . $employees->count() . " employés synchronisés");
        
        $this->updateDailySummaryForDate($date);
        
        return true;
    }
    
    /**
     * Synchroniser un employé pour une date
     */
    private function syncEmployeeForDate(Employee $employee, $token, Carbon $startDate, Carbon $endDate)
    {
        try {
            $response = $this->makeApiRequestWithRetry(
                $employee->emp_code,
                $token,
                $startDate,
                $endDate,
                1
            );
            
            if ($response && isset($response['data']) && count($response['data']) > 0) {
                $this->processEmployeeTransactions($employee, $response['data']);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error("Erreur syncEmployeeForDate {$employee->emp_code}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir le résumé des pointages
     */
    public function getEmployeeAttendanceSummary(Employee $employee, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();
        
        $attendances = DailyAttendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date', 'desc')
            ->get();
        
        $stats = [
            'total_days' => $attendances->count(),
            'present_days' => $attendances->whereIn('status', ['PRESENT', 'LATE', 'OVERTIME'])->count(),
            'absent_days' => $attendances->where('status', 'ABSENT')->count(),
            'late_days' => $attendances->where('status', 'LATE')->count(),
            'half_days' => $attendances->where('status', 'HALF_DAY')->count(),
            'leave_days' => $attendances->where('status', 'LEAVE')->count(),
            'total_work_hours' => round($attendances->sum('work_hours'), 2),
            'total_overtime' => round($attendances->sum('overtime_hours'), 2),
            'avg_work_hours' => round($attendances->avg('work_hours'), 2),
        ];
        
        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'employee' => [
                'id' => $employee->id,
                'emp_code' => $employee->emp_code,
                'name' => $employee->first_name . ' ' . $employee->last_name
            ],
            'stats' => $stats,
            'attendances' => $attendances->map(function ($attendance) {
                return [
                    'date' => $attendance->attendance_date,
                    'check_in' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : null,
                    'check_out' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : null,
                    'work_hours' => $attendance->work_hours,
                    'status' => $attendance->status,
                    'total_punches' => $attendance->total_punches,
                    'is_late' => $attendance->is_late,
                    'is_overtime' => $attendance->is_overtime,
                    'notes' => $attendance->notes,
                    'raw_data_count' => $attendance->raw_data ? count(json_decode($attendance->raw_data, true)) : 0
                ];
            })
        ];
    }
    
    /**
     * Nettoyer les anciennes données
     */
    public function cleanupOldTransactions($daysToKeep = 60)
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        $deleted = AttendanceTransaction::where('created_at', '<', $cutoffDate)
            ->delete();
        
        Log::info("Nettoyage: {$deleted} transactions supprimées (avant {$cutoffDate->format('Y-m-d')})");
        
        return $deleted;
    }
}