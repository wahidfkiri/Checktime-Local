<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendWeeklySmsReports extends Command
{
    protected $signature = 'attendance:send-weekly-sms';
    protected $description = 'Envoyer les rapports de présence hebdomadaires par SMS chaque vendredi à 9h';
    
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    public function handle()
    {
        $this->info('📱 Début de l\'envoi des rapports de présence hebdomadaires par SMS...');
        
        $today = Carbon::now();
        $currentDayOfWeek = $today->dayOfWeekIso;

        // Calcul de la période : toujours Lundi → Vendredi (5 jours)
        if ($currentDayOfWeek == 6 || $currentDayOfWeek == 7) {
            $startOfWeek = $today->copy()->previous(Carbon::MONDAY);
        } else {
            $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
        }
        $endOfWeek = $startOfWeek->copy()->addDays(4); // Vendredi
        
        $this->info("📊 Période du rapport: " . $startOfWeek->format('d/m/Y') . " au " . $endOfWeek->format('d/m/Y'));
        
        // Récupérer les paramètres globaux (singleton)
        $settings = Setting::first();
        
        if (!$settings) {
            $this->warn("⚠️  Aucun paramètre trouvé");
            Log::info('Aucun paramètre trouvé - SMS non envoyés');
            return;
        }
        
        if (!$settings->sms_is_active) {
            $this->warn("❌  SMS désactivés dans les paramètres");
            Log::info('SMS désactivés');
            return;
        }
        
        if ($settings->sms_credit <= 0) {
            $this->warn("⚠️  Crédit SMS épuisé (" . $settings->sms_credit . " crédits)");
            Log::warning('Crédit SMS épuisé');
            return;
        }
        
        $this->info("✅  SMS activés (" . $settings->sms_credit . " crédits restants)");
        
        // Vérifier le solde global
        $this->info("💰 Vérification du solde SMS...");
        $balanceCheck = $this->smsService->checkBalance();
        
        if (!$balanceCheck['success']) {
            $this->error("❌ Impossible de vérifier le solde SMS: " . $balanceCheck['error']);
            Log::error('Impossible de vérifier le solde SMS pour rapports hebdomadaires');
            return;
        }
        
        $globalBalance = $balanceCheck['balance'];
        $this->info("✅ Solde SMS disponible: " . $globalBalance . " crédits");
        
        // Calculer les stats par employé
        $employeesStats = $this->calculateEmployeeStats($startOfWeek, $endOfWeek);
        
        if (empty($employeesStats)) {
            $this->warn("⚠️  Aucune statistique calculée");
            return;
        }
        
        $this->info("✅  Statistiques calculées pour " . count($employeesStats) . " employé(s)");
        
        // Filtrer les employés avec téléphone valide
        $employeesWithPhone = [];
        foreach ($employeesStats as $empCode => $stats) {
            if (!empty($stats['employee']->phone) && $this->isValidPhoneNumber($stats['employee']->phone)) {
                $employeesWithPhone[$empCode] = $stats;
            }
        }
        
        if (empty($employeesWithPhone)) {
            $this->warn("⚠️  Aucun employé avec téléphone valide");
            return;
        }
        
        $this->info("📱  " . count($employeesWithPhone) . " employé(s) avec téléphone valide");
        
        $smsNeeded = count($employeesWithPhone);
        if ($smsNeeded > $settings->sms_credit) {
            $this->warn("⚠️  Crédit insuffisant: besoin de " . $smsNeeded . " SMS, crédit: " . $settings->sms_credit);
            return;
        }
        
        if ($smsNeeded > $globalBalance) {
            $this->warn("⚠️  Solde global insuffisant: besoin de " . $smsNeeded . " SMS, solde global: " . $globalBalance);
            return;
        }
        
        // ENVOI DES SMS
        $totalSmsSent = 0;
        $totalSmsCost = 0;
        $smsErrors    = 0;
        
        foreach ($employeesWithPhone as $empCode => $stats) {
            $employee = $stats['employee'];
            
            try {
                $formattedPhone = $this->formatPhoneNumber($employee->phone);
                $message        = $this->prepareSmsMessage($employee, $stats, $settings);
                $smsCount       = $this->calculateSmsCount($message);

                $this->info("📝  Message: " . strlen($message) . " caractères, " . $smsCount . " SMS");
                
                if (strlen($message) > 160) {
                    $message  = $this->truncateMessage($message);
                    $smsCount = 1;
                }
                
                $employeeName = $employee->first_name ?? $employee->name ?? 'Employé';
                $this->info("📤  Envoi SMS à " . $employeeName . " (" . $formattedPhone . ")...");
                
                $smsResult = $this->smsService->sendSms(
                    $formattedPhone,
                    $message,
                    $settings->sms_sender_id ?: config('sms.fastway.default_sender', 'CHECKTIME')
                );
                
                if ($smsResult['success']) {
                    $totalSmsSent++;
                    $totalSmsCost += 1;
                    $globalBalance -= 1;
                    
                    $newCredit = max(0, $settings->sms_credit - 1);
                    $settings->sms_credit = $newCredit;
                    $settings->save();
                    
                    Log::info('SMS hebdomadaire envoyé', [
                        'employee_first_name'  => $employee->first_name ?? null,
                        'employee_name'        => $employee->name ?? null,
                        'employee_phone'       => $formattedPhone,
                        'employee_code'        => $employee->emp_code ?? null,
                        'message_length'       => strlen($message),
                        'sms_used'             => 1,
                        'message_id'           => $smsResult['message_id'] ?? null,
                        'remaining_credit'     => $newCredit,
                        'stats' => [
                            'presence' => $stats['presence_count'],
                            'delay'    => $stats['delay_count'],
                            'absence'  => $stats['absence_count'],
                        ],
                    ]);
                    
                    $this->info("✅  SMS envoyé à " . $employeeName . " (coût: 1 SMS, crédit restant: " . $newCredit . ")");

                } else {
                    $smsErrors++;
                    $this->error("❌  Erreur: " . $smsResult['error']);
                    Log::error('Erreur SMS pour ' . $employeeName .
                              ' (' . $formattedPhone . '): ' . $smsResult['error']);
                }
                
                sleep(1);
                
            } catch (\Exception $e) {
                $smsErrors++;
                $this->error("❌  Exception: " . $e->getMessage());
                Log::error('Exception SMS pour ' . ($employee->name ?? 'Employé') .
                          ': ' . $e->getMessage());
            }
        }
        
        // RÉSUMÉ FINAL
        $this->line('');
        $this->info("═══════════════════════════════════════════════");
        $this->info("📋  RÉSUMÉ DES SMS HEBDOMADAIRES");
        $this->info("═══════════════════════════════════════════════");
        $this->info("Date d'exécution     : " . $today->format('d/m/Y H:i'));
        $this->info("Période analysée     : " . $startOfWeek->format('d/m/Y') . " au " . $endOfWeek->format('d/m/Y'));
        $this->info("Employés traités     : " . count($employeesWithPhone));
        $this->info("SMS envoyés          : " . $totalSmsSent);
        $this->info("Coût total           : " . $totalSmsCost . " SMS");
        $this->info("Solde global restant : " . $globalBalance . " SMS");
        
        if ($totalSmsSent > 0) {
            $this->info("✅  Rapports SMS hebdomadaires envoyés avec succès !");
        } else {
            $this->warn("⚠️  Aucun SMS n'a été envoyé");
        }
        
        Log::info('SMS hebdomadaires terminés', [
            'sms_sent'       => $totalSmsSent,
            'sms_cost'       => $totalSmsCost,
            'global_balance' => $globalBalance,
            'date'           => $today->format('Y-m-d'),
        ]);
    }
    
    // =========================================================================
    //  HELPERS
    // =========================================================================
    
    /**
     * Calcule les stats par employé sur la période Lundi → Vendredi (5 jours).
     */
    private function calculateEmployeeStats(Carbon $startOfWeek, Carbon $endOfWeek): array
    {
        $employees = Employee::whereNotNull('emp_code')
            ->where('emp_code', '!=', '')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get()
            ->keyBy('emp_code');

        if ($employees->isEmpty()) return [];

        $employeeIds = $employees->pluck('id')->toArray();

        // Requête DailyAttendance sur Lundi → Vendredi uniquement
        $attendances = \App\Models\DailyAttendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('attendance_date', [
                $startOfWeek->format('Y-m-d'),
                $endOfWeek->format('Y-m-d'),
            ])
            ->get()
            ->filter(fn ($att) => Carbon::parse($att->attendance_date)->dayOfWeekIso <= 5)
            ->groupBy('employee_id');

        // Nombre de jours ouvrés réels dans la période (Lun-Ven = 5)
        $totalWorkingDays = $this->countWorkingDays(
            $startOfWeek->format('Y-m-d'),
            $endOfWeek->format('Y-m-d')
        );

        $statsByEmployee = [];

        foreach ($employees as $empCode => $employee) {
            $employeeAttendances = $attendances->get($employee->id, collect());

            $presenceCount = 0;
            $lateCount     = 0;

            foreach ($employeeAttendances as $att) {
                $status = strtoupper($att->status);

                if (in_array($status, ['PRESENT', 'LATE', 'EARLY_LEAVE', 'HALF_DAY'])) {
                    $presenceCount++;
                    if ($att->is_late) {
                        $lateCount++;
                    }
                }
            }

            $absenceCount = max(0, $totalWorkingDays - $presenceCount);

            $statsByEmployee[$empCode] = [
                'employee'       => $employee,
                'presence_count' => $presenceCount,
                'delay_count'    => $lateCount,
                'absence_count'  => $absenceCount,
            ];
        }

        return $statsByEmployee;
    }

    /**
     * Compte uniquement les jours ouvrés du Lundi au Vendredi.
     */
    private function countWorkingDays(string $startDate, string $endDate): int
    {
        $days = 0;
        $cur  = Carbon::parse($startDate);
        $end  = Carbon::parse($endDate);
        while ($cur->lte($end)) {
            if ($cur->dayOfWeekIso >= 1 && $cur->dayOfWeekIso <= 5) {
                $days++;
            }
            $cur->addDay();
        }
        return $days;
    }
    
    private function prepareSmsMessage($employee, $stats, $settings)
    {
        $firstName = $employee->first_name ?? 
                    ($employee->name ? $this->getFirstName($employee->name) : 'Collaborateur');
        
        $message  = $firstName . ", voici le point des présences de la semaine.\n";
        $message .= "Presence: " . $stats['presence_count'] . "\n";
        $message .= "Retard: "   . $stats['delay_count']    . "\n";
        $message .= "Absence: "  . $stats['absence_count'];
        
        return $message;
    }
    
    private function getFirstName($fullName)
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?: $fullName;
    }
    
    private function calculateSmsCount($message)
    {
        $length = strlen($message);
        return $length <= 160 ? 1 : ceil($length / 153);
    }
    
    private function truncateMessage($message, $maxLength = 160)
    {
        if (strlen($message) <= $maxLength) return $message;
        
        $truncated   = substr($message, 0, $maxLength - 3);
        $lastNewline = strrpos($truncated, "\n");
        
        if ($lastNewline !== false && $lastNewline > $maxLength - 20) {
            $truncated = substr($truncated, 0, $lastNewline);
        }
        
        return $truncated . '...';
    }
    
    private function isValidPhoneNumber($phone)
    {
        if (empty($phone)) return false;
        
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        $cleanPhone = str_replace(' ', '', $cleanPhone);
        $length     = strlen($cleanPhone);
        
        if (str_starts_with($cleanPhone, '+225') || str_starts_with($cleanPhone, '225')) {
            $digitsOnly = preg_replace('/[^0-9]/', '', $cleanPhone);
            return strlen($digitsOnly) === 11;
        }
        
        if (str_starts_with($cleanPhone, '0')) {
            return $length === 9;
        }
        
        return $length >= 8 && $length <= 15;
    }
    
    private function formatPhoneNumber($phone)
    {
        if (empty($phone)) return null;
        
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        if (str_starts_with($cleanPhone, '0') && strlen($cleanPhone) === 9) {
            $cleanPhone = '225' . substr($cleanPhone, 1);
        }
        
        if (!str_starts_with($cleanPhone, '225') && strlen($cleanPhone) === 8) {
            $cleanPhone = '225' . $cleanPhone;
        }
        
        return $cleanPhone;
    }
}