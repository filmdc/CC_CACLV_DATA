<?php

/**
 * PHP-based MySQL Export Script for Legacy Contract Database
 * 
 * This script connects to your old database using PHP and exports data to JSON files.
 * It's an alternative to the MySQL command-line approach.
 * 
 * Usage:
 * 1. Update the database connection details below
 * 2. Run: php php-export-script.php
 */

// =============================================================================
// DATABASE CONNECTION SETTINGS - UPDATE THESE
// =============================================================================
$config = [
    'host' => 'localhost',
    'username' => 'your_username',
    'password' => 'your_password', 
    'database' => 'your_old_contract_database',
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

function exportToJson($pdo, $query, $filename, $description) {
    echo "📊 Exporting {$description}...\n";
    
    try {
        $stmt = $pdo->query($query);
        $data = $stmt->fetchAll();
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($json === false) {
            throw new Exception("JSON encoding failed");
        }
        
        $filePath = $GLOBALS['exportDir'] . '/' . $filename;
        $bytesWritten = file_put_contents($filePath, $json);
        
        if ($bytesWritten === false) {
            throw new Exception("Failed to write file");
        }
        
        $count = count($data);
        echo "   ✅ Exported {$count} records to {$filename} ({$bytesWritten} bytes)\n";
        
    } catch (Exception $e) {
        echo "   ❌ Failed to export {$description}: " . $e->getMessage() . "\n";
        return false;
    }
    
    return true;
}

// =============================================================================
// MAIN EXPORT PROCESS
// =============================================================================

echo "🚀 Starting Legacy Contract Database Export (PHP Version)\n";
echo "📁 Export directory: {$exportDir}\n";
echo "🔗 Database: {$config['database']}@{$config['host']}\n\n";

// Connect to database
$pdo = connectToDatabase($config);

// Export Users
exportToJson($pdo, "
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
", 'users.json', 'Users');

// Export Programs
exportToJson($pdo, "
    SELECT 
        id,
        program_name,
        created_at,
        updated_at,
        deleted_at
    FROM programs 
    WHERE deleted_at IS NULL
", 'programs.json', 'Programs');

// Export Funders
exportToJson($pdo, "
    SELECT 
        id,
        funder_name,
        created_at,
        updated_at,
        deleted_at
    FROM funders 
    WHERE deleted_at IS NULL
", 'funders.json', 'Funders');

// Export Contracts
exportToJson($pdo, "
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
", 'contracts.json', 'Contracts');

// Export Contract-Program Relationships
exportToJson($pdo, "
    SELECT 
        cp.contract_id,
        cp.program_id,
        c.title as contract_title,
        p.program_name
    FROM contract_program cp
    INNER JOIN contracts c ON cp.contract_id = c.id
    INNER JOIN programs p ON cp.program_id = p.id
    WHERE c.deleted_at IS NULL AND p.deleted_at IS NULL
", 'contract_programs.json', 'Contract-Program Relationships');

// Export Report Due Dates
exportToJson($pdo, "
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
", 'report_due_dates.json', 'Report Due Dates');

// Export Due Date Emails
exportToJson($pdo, "
    SELECT 
        id,
        email,
        created_at,
        updated_at,
        deleted_at
    FROM due_date_emails
    WHERE deleted_at IS NULL
", 'due_date_emails.json', 'Due Date Emails');

// Export Due Date Email Assignments
exportToJson($pdo, "
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
", 'due_date_email_assignments.json', 'Due Date Email Assignments');

echo "\n🎉 Export completed successfully!\n";
echo "📋 Files created in: {$exportDir}\n\n";

// List created files
echo "📁 Exported files:\n";
$files = glob($exportDir . '/*.json');
foreach ($files as $file) {
    $size = filesize($file);
    $filename = basename($file);
    echo "   • {$filename} ({$size} bytes)\n";
}

echo "\n🚀 Next steps:\n";
echo "   1. Verify the exported files contain data\n";
echo "   2. Update program mappings in config/legacy-import.php\n";
echo "   3. Run: php artisan contract:import-legacy-data --config-check\n";
echo "   4. Run: php artisan contract:import-legacy-data --dry-run\n";