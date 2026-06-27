<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array sendSms(string $to, string $message, ?string $sender = null, array $options = [])
 * @method static array ping()
 * @method static array checkBalance()
 * @method static array getBalance(bool $forceRefresh = false)
 * @method static array hasSufficientBalance(int $smsCount = 1, bool $forceCheck = false)
 * @method static array healthCheck()
 * @method static array sendBulkSms(array $recipients, string $message, ?string $sender = null, array $options = [])
 * @method static array checkStatus(string $messageId)
 * @method static string formatPhoneNumber(string $phone, ?string $countryCode = null)
 * @method static int calculateSmsCount(string $message)
 * @method static array getUsageStatistics(int $days = 30)
 * 
 * @see \App\Services\SmsService
 */
class Sms extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sms.service';
    }
}