<?php

/**
 * Fixed PHP-based MySQL Export Script for Legacy Contract Database
 * 
 * This version properly handles large datasets without truncation issues.
 * It exports records individually and assembles them into proper JSON arrays.
 * 
 * Usage:
 * 1. Update the database connection details below
 * 2. Run: php php-export-script-fixed.php
 */

// =============================================================================
// DATABASE CONNECTION SETTINGS - UPDATE THESE
// =============================================================================
$config = [
    'host' => '10.55.0.10',
    'username' => 'root',
    'password' => 'Socrates1982!', 
    'database' => 'Contracts',
    'charset' => 'utf8mb4'
];

// Export directory
$exportDir = __DIR__;

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

function connectToDatabase($config) {
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        echo "✅ Connected to database: {$config['database']}\n";
        return $pdo;
    } catch (PDOException $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function exportToJsonSafe($pdo, $query, $filename, $description) {
    echo "📊 Exporting {$description}...\n";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        $data = [];
        $count = 0;
        
        echo "   📋 Processing records";
        
        while ($row = $stmt->fetch()) {
            $data[] = $row;
            $count++;
            
            // Show progress for large datasets
            if ($count % 100 == 0) {
                echo ".";
            }
        }
        
        echo "\n   📝 Encoding JSON...\n";
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($json === false) {
            throw new Exception("JSON encoding failed: " . json_last_error_msg());
        }
        
        $filePath = $GLOBALS['exportDir'] . '/' . $filename;
        $bytesWritten = file_put_contents($filePath, $json);
        
        if ($bytesWritten === false) {
            throw new Exception("Failed to write file");
        }
        
        echo "   ✅ Exported {$count} records to {$filename} (" . formatBytes($bytesWritten) . ")\n\n";
        
    } catch (Exception $e) {
        echo "   ❌ Failed to export {$description}: " . $e->getMessage() . "\n\n";
        return false;
    }
    
    return true;
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

// =============================================================================
// MAIN EXPORT PROCESS
// =============================================================================

echo "🚀 Starting Legacy Contract Database Export (Fixed PHP Version)\n";
echo "📁 Export directory: {$exportDir}\n";
echo "🔗 Database: {$config['database']}@{$config['host']}\n\n";

// Connect to database
$pdo = connectToDatabase($config);

// Set longer timeout for large datasets
$pdo->exec("SET SESSION group_concat_max_len = 1048576");

$startTime = microtime(true);

// Export Users
exportToJsonSafe($pdo, "
    SELECT 
        id,
        name,
        email,
        email_verified_at,
        CASE WHEN approved = 1 THEN true ELSE false END as approved,
        created_at,
        updated_at,
        deleted_at
    FROM users 
    WHERE deleted_at IS NULL
    ORDER BY id
", 'users.json', 'Users');

// Export Programs
exportToJsonSafe($pdo, "
    SELECT 
        id,
        program_name,
        created_at,
        updated_at,
        deleted_at
    FROM programs 
    WHERE deleted_at IS NULL
    ORDER BY id
", 'programs.json', 'Programs');

// Export Funders
exportToJsonSafe($pdo, "
    SELECT 
        id,
        funder_name,
        created_at,
        updated_at,
        deleted_at
    FROM funders 
    WHERE deleted_at IS NULL
    ORDER BY id
", 'funders.json', 'Funders');

// Export Contracts
exportToJsonSafe($pdo, "
    SELECT 
        c.id,
        c.title,
        c.contract_number,
        c.cfda_number,
        c.purpose,
        c.funder_id,
        f.funder_name,
        c.date_of_award,
        c.amount,
        c.start_date,
        c.end_date,
        c.grant_code,
        c.notes,
        CASE WHEN c.archived = 1 THEN true ELSE false END as archived,
        c.date_archived,
        c.created_at,
        c.updated_at,
        c.deleted_at
    FROM contracts c
    LEFT JOIN funders f ON c.funder_id = f.id
    WHERE c.deleted_at IS NULL
    ORDER BY c.id
", 'contracts.json', 'Contracts');

// Export Contract-Program Relationships
exportToJsonSafe($pdo, "
    SELECT 
        cp.contract_id,
        cp.program_id,
        c.title as contract_title,
        p.program_name
    FROM contract_program cp
    INNER JOIN contracts c ON cp.contract_id = c.id
    INNER JOIN programs p ON cp.program_id = p.id
    WHERE c.deleted_at IS NULL AND p.deleted_at IS NULL
    ORDER BY cp.contract_id, cp.program_id
", 'contract_programs.json', 'Contract-Program Relationships');

// Export Report Due Dates
exportToJsonSafe($pdo, "
    SELECT 
        rdd.id,
        rdd.due_date,
        rdd.status,
        rdd.description,
        rdd.contract_id,
        c.title as contract_title,
        rdd.created_at,
        rdd.updated_at,
        rdd.deleted_at
    FROM report_due_dates rdd
    INNER JOIN contracts c ON rdd.contract_id = c.id
    WHERE rdd.deleted_at IS NULL AND c.deleted_at IS NULL
    ORDER BY rdd.id
", 'report_due_dates.json', 'Report Due Dates');

// Export Due Date Emails
exportToJsonSafe($pdo, "
    SELECT 
        id,
        email,
        created_at,
        updated_at,
        deleted_at
    FROM due_date_emails
    WHERE deleted_at IS NULL
    ORDER BY id
", 'due_date_emails.json', 'Due Date Emails');

// Export Due Date Email Assignments
exportToJsonSafe($pdo, "
    SELECT 
        dderdd.report_due_date_id,
        dderdd.due_date_email_id,
        dde.email,
        rdd.description as report_description,
        rdd.due_date,
        c.title as contract_title
    FROM due_date_email_report_due_date dderdd
    INNER JOIN due_date_emails dde ON dderdd.due_date_email_id = dde.id
    INNER JOIN report_due_dates rdd ON dderdd.report_due_date_id = rdd.id
    INNER JOIN contracts c ON rdd.contract_id = c.id
    WHERE dde.deleted_at IS NULL 
      AND rdd.deleted_at IS NULL 
      AND c.deleted_at IS NULL
    ORDER BY dderdd.report_due_date_id, dderdd.due_date_email_id
", 'due_date_email_assignments.json', 'Due Date Email Assignments');

// Export Media (Contract Documents)
exportToJsonSafe($pdo, "
    SELECT 
        m.id,
        m.model_type,
        m.model_id,
        m.uuid,
        m.collection_name,
        m.name,
        m.file_name,
        m.mime_type,
        m.disk,
        m.conversions_disk,
        m.size,
        m.manipulations,
        m.custom_properties,
        m.generated_conversions,
        m.responsive_images,
        m.order_column,
        m.created_at,
        m.updated_at,
        c.title as contract_title,
        c.contract_number
    FROM media m
    INNER JOIN contracts c ON m.model_id = c.id
    WHERE m.model_type = 'App\\\\Models\\\\Contract'
      AND c.deleted_at IS NULL
    ORDER BY m.model_id, m.order_column, m.id
", 'media.json', 'Contract Documents (Media)');

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "🎉 Export completed successfully in {$executionTime} seconds!\n";
echo "📋 Files created in: {$exportDir}\n\n";

// List created files with sizes
echo "📁 Exported files:\n";
$files = glob($exportDir . '/*.json');
$totalSize = 0;

foreach ($files as $file) {
    $size = filesize($file);
    $totalSize += $size;
    $filename = basename($file);
    
    // Count records in file to verify completeness
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    $recordCount = is_array($data) ? count($data) : 0;
    
    echo "   • {$filename} - " . formatBytes($size) . " ({$recordCount} records)\n";
}

echo "\n📊 Total export size: " . formatBytes($totalSize) . "\n";

echo "\n🚀 Next steps:\n";
echo "   1. Verify the exported files contain complete data\n";
echo "   2. Update program mappings in config/legacy-import.php\n";
echo "   3. Run: php artisan contract:import-legacy-data --config-check\n";
echo "   4. Run: php artisan contract:import-legacy-data --dry-run\n";

// Basic validation
echo "\n🔍 Quick validation:\n";
foreach ($files as $file) {
    $filename = basename($file);
    $content = file_get_contents($file);
    
    if (empty($content)) {
        echo "   ❌ {$filename} is empty\n";
    } else {
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "   ❌ {$filename} contains invalid JSON\n";
        } else {
            echo "   ✅ {$filename} is valid JSON\n";
        }
    }
}