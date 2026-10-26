<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;
use Spatie\DbDumper\Compressors\GzipCompressor;

/*
 * Nightly backups (spatie/laravel-backup): the database and the uploaded files, zipped (and
 * password-protected when BACKUP_ARCHIVE_PASSWORD is set) onto the "backups" disk, which lives
 * outside the release folders (BACKUP_PATH). Problems are reported through the monitoring alerts
 * (AppServiceProvider), not by e-mail.
 */
$name = env('BACKUP_NAME', 'infokobuleti-'.env('APP_ENV', 'local'));

return [

    'backup' => [
        'name' => $name,

        'source' => [
            'files' => [
                // Uploads (photos, logos), private files (CVs, message photos). Code is in git.
                'include' => [
                    storage_path('app'),
                ],

                'exclude' => [
                    storage_path('app/backups'),
                    storage_path('app/backup-temp'),
                    storage_path('app/restore-test'),
                    storage_path('app/public/og'),
                    storage_path('app/private/livewire-tmp'),
                    storage_path('app/livewire-tmp'),
                    storage_path('app/private/exports'),
                ],

                'follow_links' => false,

                'ignore_unreadable_directories' => true,

                'relative_path' => storage_path(),
            ],

            'databases' => [
                env('DB_CONNECTION', 'pgsql'),
            ],
        ],

        'database_dump_compressor' => GzipCompressor::class,

        'database_dump_file_timestamp_format' => null,

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,

            'compression_level' => 6,

            'filename_prefix' => '',

            'disks' => [
                'backups',
            ],

            'continue_on_failure' => false,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD'),

        'encryption' => 'default',

        'verify_backup' => true,

        'tries' => 2,

        'retry_delay' => 60,
    ],

    // No e-mail yet: failures become monitoring alerts (see AppServiceProvider).
    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => [],
            UnhealthyBackupWasFoundNotification::class => [],
            CleanupHasFailedNotification::class => [],
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL', 'contact@infokobuleti.com'),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'contact@infokobuleti.com'),
                'name' => env('MAIL_FROM_NAME', 'infokobuleti'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],

        'webhook' => [
            'url' => '',
        ],
    ],

    'log_channel' => null,

    'monitor_backups' => [
        [
            'name' => $name,
            'disks' => ['backups'],
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => 20000,
            ],
        ],
    ],

    // Every backup for 7 days, then one a day for 7 more days, one a week for 4 weeks and one a
    // month for 6 months.
    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,

            'keep_daily_backups_for_days' => 7,

            'keep_weekly_backups_for_weeks' => 4,

            'keep_monthly_backups_for_months' => 6,

            'keep_yearly_backups_for_years' => 0,

            'delete_oldest_backups_when_using_more_megabytes_than' => 20000,
        ],

        'tries' => 1,

        'retry_delay' => 0,
    ],

];
