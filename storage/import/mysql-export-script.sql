-- MySQL Export Script for Legacy Contract Database
-- Run this script on your old database to export data for import into CAP TRACK
-- 
-- Instructions:
-- 1. Connect to your old contract database
-- 2. Run each section separately and save results as JSON files
-- 3. Place the resulting files in /var/www/CC_CAP_TRACK/storage/import/
--
-- Example usage:
-- mysql -u username -p old_database_name < mysql-export-script.sql

-- Export Users
-- Creates: users.json
SELECT JSON_ARRAYAGG(
    JSON_OBJECT(
        'id', id,
        'name', name,
        'email', email,
        'email_verified_at', email_verified_at,
        'approved', approved,
        'created_at', created_at,
        'updated_at', updated_at,
        'deleted_at', deleted_at
    )
) as users_data
FROM users
WHERE deleted_at IS NULL
INTO OUTFILE '/tmp/users.json';

-- Export Programs  
-- Creates: programs.json
SELECT JSON_ARRAYAGG(
    JSON_OBJECT(
        'id', id,
        'program_name', program_name,
        'created_at', created_at,
        'updated_at', updated_at,
        'deleted_at', deleted_at
    )
) as programs_data
FROM programs
WHERE deleted_at IS NULL
INTO OUTFILE '/tmp/programs.json';

-- Export Funders
-- Creates: funders.json
SELECT JSON_ARRAYAGG(
    JSON_OBJECT(
        'id', id,
        'funder_name', funder_name,
        'created_at', created_at,
        'updated_at', updated_at,
        'deleted_at', deleted_at
    )
) as funders_data
FROM funders
WHERE deleted_at IS NULL
INTO OUTFILE '/tmp/funders.json';

-- Export Contracts with Funder relationship
-- Creates: contracts.json
SELECT JSON_ARRAYAGG(
    JSON_OBJECT(
        'id', c.id,
        'title', c.title,
        'contract_number', c.contract_number,
        'cfda_number', c.cfda_number,
        'purpose', c.purpose,
        'funder_id', c.funder_id,
        'funder_name', f.funder_name,
        'date_of_award', c.date_of_award,
        'amount', c.amount,
        'start_date', c.start_date,
        'end_date', c.end_date,
        'grant_code', c.grant_code,
        'notes', c.notes,
        'archived', c.archived,
        'date_archived', c.date_archived,
        'created_at', c.created_at,
        'updated_at', c.updated_at,
        'deleted_at', c.deleted_at
    )
) as contracts_data
FROM contracts c
LEFT JOIN funders f ON c.funder_id = f.id
WHERE c.deleted_at IS NULL
INTO OUTFILE '/tmp/contracts.json';

-- Export Contract-Program relationships
-- Creates: contract_programs.json
SELECT JSON_ARRAYAGG(
    JSON_OBJECT(
        'contract_id', cp.contract_id,
        'program_id', cp.program_id,
        'contract_title', c.title,
        'program_name', p.program_name
    )
) as contract_programs_data
FROM contract_program cp
INNER JOIN contracts c ON cp.contract_id = c.id
INNER JOIN programs p ON cp.program_id = p.id
WHERE c.deleted_at IS NULL AND p.deleted_at IS NULL
INTO OUTFILE '/tmp/contract_programs.json';

-- Export Report Due Dates with Contract and Email relationships
-- Creates: report_due_dates.json
SELECT JSON_ARRAYAGG(
    JSON_OBJECT(
        'id', rdd.id,
        'due_date', rdd.due_date,
        'status', rdd.status,
        'description', rdd.description,
        'contract_id', rdd.contract_id,
        'contract_title', c.title,
        'created_at', rdd.created_at,
        'updated_at', rdd.updated_at,
        'deleted_at', rdd.deleted_at
    )
) as report_due_dates_data
FROM report_due_dates rdd
INNER JOIN contracts c ON rdd.contract_id = c.id
WHERE rdd.deleted_at IS NULL AND c.deleted_at IS NULL
INTO OUTFILE '/tmp/report_due_dates.json';

-- Export Due Date Emails
-- Creates: due_date_emails.json
SELECT JSON_ARRAYAGG(
    JSON_OBJECT(
        'id', id,
        'email', email,
        'created_at', created_at,
        'updated_at', updated_at,
        'deleted_at', deleted_at
    )
) as due_date_emails_data
FROM due_date_emails
WHERE deleted_at IS NULL
INTO OUTFILE '/tmp/due_date_emails.json';

-- Export Due Date Email to Report Due Date relationships
-- Creates: due_date_email_assignments.json
SELECT JSON_ARRAYAGG(
    JSON_OBJECT(
        'report_due_date_id', dderdd.report_due_date_id,
        'due_date_email_id', dderdd.due_date_email_id,
        'email', dde.email,
        'report_description', rdd.description,
        'due_date', rdd.due_date,
        'contract_title', c.title
    )
) as due_date_assignments_data
FROM due_date_email_report_due_date dderdd
INNER JOIN due_date_emails dde ON dderdd.due_date_email_id = dde.id
INNER JOIN report_due_dates rdd ON dderdd.report_due_date_id = rdd.id
INNER JOIN contracts c ON rdd.contract_id = c.id
WHERE dde.deleted_at IS NULL 
  AND rdd.deleted_at IS NULL 
  AND c.deleted_at IS NULL
INTO OUTFILE '/tmp/due_date_email_assignments.json';

-- Alternative CSV Export (if JSON export doesn't work)
-- Uncomment these sections if your MySQL version doesn't support JSON_ARRAYAGG

/*
-- Export Users as CSV
SELECT 
    id,
    name,
    email,
    email_verified_at,
    approved,
    created_at,
    updated_at,
    deleted_at
FROM users
WHERE deleted_at IS NULL
INTO OUTFILE '/tmp/users.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Export Programs as CSV  
SELECT 
    id,
    program_name,
    created_at,
    updated_at,
    deleted_at
FROM programs
WHERE deleted_at IS NULL
INTO OUTFILE '/tmp/programs.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Export Funders as CSV
SELECT 
    id,
    funder_name,
    created_at,
    updated_at,
    deleted_at
FROM funders
WHERE deleted_at IS NULL
INTO OUTFILE '/tmp/funders.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Export Contracts as CSV
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
    c.archived,
    c.date_archived,
    c.created_at,
    c.updated_at,
    c.deleted_at
FROM contracts c
LEFT JOIN funders f ON c.funder_id = f.id
WHERE c.deleted_at IS NULL
INTO OUTFILE '/tmp/contracts.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Export Contract-Program relationships as CSV
SELECT 
    cp.contract_id,
    cp.program_id,
    c.title as contract_title,
    p.program_name
FROM contract_program cp
INNER JOIN contracts c ON cp.contract_id = c.id
INNER JOIN programs p ON cp.program_id = p.id
WHERE c.deleted_at IS NULL AND p.deleted_at IS NULL
INTO OUTFILE '/tmp/contract_programs.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Export Report Due Dates as CSV
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
INTO OUTFILE '/tmp/report_due_dates.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Export Due Date Emails as CSV
SELECT 
    id,
    email,
    created_at,
    updated_at,
    deleted_at
FROM due_date_emails
WHERE deleted_at IS NULL
INTO OUTFILE '/tmp/due_date_emails.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Export Due Date Email Assignments as CSV
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
INTO OUTFILE '/tmp/due_date_email_assignments.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
*/

-- End of export script
-- 
-- After running this script, copy the generated files to:
-- /var/www/CC_CAP_TRACK/storage/import/
--
-- Then run the import command:
-- php artisan contract:import-legacy-data --dry-run