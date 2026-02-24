<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IntraCare HMS Configuration
    |--------------------------------------------------------------------------
    |
    | Central configuration for the IntraCare Hospital Management System.
    | All offline-first, LAN-based settings are managed here.
    |
    */

    // Hospital information (set via Setup Wizard)
    'hospital_name' => env('APP_NAME', 'IntraCare HMS'),

    // MR Number auto-generation prefix and format
    'mr_number_prefix' => 'MR-',
    'mr_number_padding' => 6, // MR-000001

    // Visit number format
    'visit_number_prefix' => 'V-',

    // Lab order number format
    'lab_order_prefix' => 'LO-',

    // Invoice number format
    'invoice_prefix' => 'INV-',

    // Currency
    'currency' => [
        'code' => 'BDT',
        'symbol' => '৳',
        'decimal_places' => 2,
        'smallest_unit' => 100, // 100 paisa = 1 taka
    ],

    // Session security
    'session_timeout_minutes' => (int) env('SESSION_LIFETIME', 15),

    // File upload settings
    'uploads' => [
        'max_file_size_kb' => 5120, // 5 MB
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'webp'],
        'allowed_document_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'organize_by_date' => true, // Year/Month folders (WordPress-style)
    ],

    // Backup settings
    'backup' => [
        'enabled' => true,
        'path' => env('BACKUP_PATH', storage_path('app/backups')),
        'retention_days' => 30,
        'schedule_time' => '02:00', // Daily at 2:00 AM
    ],

    // Queue / Token settings
    'queue_token' => [
        'reset_daily' => true,
        'format' => '{department_code}-{sequence}', // e.g. LAB-001, OPD-042
    ],

    // Thermal POS printing
    'printing' => [
        'thermal_width_mm' => 80,
        'page_size' => 'A4', // For lab reports, prescriptions
    ],

    // Barcode settings
    'barcode' => [
        'type' => 'CODE128', // CODE128, QR
        'prefix' => 'IC-',
    ],

];
