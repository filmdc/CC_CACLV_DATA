-- Alternative MySQL Export Script for Legacy Contract Database
-- This version uses SELECT statements that you can copy/paste the results from
-- 
-- Instructions:
-- 1. Connect to your old contract database using a MySQL client
-- 2. Run each query below individually
-- 3. Copy the results and save them as JSON files in the specified format
-- 4. OR use the command-line approach at the bottom

-- ================================================================
-- USERS EXPORT
-- ================================================================
-- Copy the output and save as: users.json
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'id', id,
            'name', name,
            'email', email,
            'email_verified_at', email_verified_at,
            'approved', CASE WHEN approved = 1 THEN true ELSE false END,
            'created_at', created_at,
            'updated_at', updated_at,
            'deleted_at', deleted_at
        ) SEPARATOR ','
    ),
']') as users_json
FROM users
WHERE deleted_at IS NULL;

-- ================================================================
-- PROGRAMS EXPORT  
-- ================================================================
-- Copy the output and save as: programs.json
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'id', id,
            'program_name', program_name,
            'created_at', created_at,
            'updated_at', updated_at,
            'deleted_at', deleted_at
        ) SEPARATOR ','
    ),
']') as programs_json
FROM programs
WHERE deleted_at IS NULL;

-- ================================================================
-- FUNDERS EXPORT
-- ================================================================
-- Copy the output and save as: funders.json
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'id', id,
            'funder_name', funder_name,
            'created_at', created_at,
            'updated_at', updated_at,
            'deleted_at', deleted_at
        ) SEPARATOR ','
    ),
']') as funders_json
FROM funders
WHERE deleted_at IS NULL;

-- ================================================================
-- CONTRACTS EXPORT
-- ================================================================
-- Copy the output and save as: contracts.json
SELECT CONCAT('[',
    GROUP_CONCAT(
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
            'archived', CASE WHEN c.archived = 1 THEN true ELSE false END,
            'date_archived', c.date_archived,
            'created_at', c.created_at,
            'updated_at', c.updated_at,
            'deleted_at', c.deleted_at
        ) SEPARATOR ','
    ),
']') as contracts_json
FROM contracts c
LEFT JOIN funders f ON c.funder_id = f.id
WHERE c.deleted_at IS NULL;

-- ================================================================
-- CONTRACT-PROGRAM RELATIONSHIPS EXPORT
-- ================================================================
-- Copy the output and save as: contract_programs.json
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'contract_id', cp.contract_id,
            'program_id', cp.program_id,
            'contract_title', c.title,
            'program_name', p.program_name
        ) SEPARATOR ','
    ),
']') as contract_programs_json
FROM contract_program cp
INNER JOIN contracts c ON cp.contract_id = c.id
INNER JOIN programs p ON cp.program_id = p.id
WHERE c.deleted_at IS NULL AND p.deleted_at IS NULL;

-- ================================================================
-- REPORT DUE DATES EXPORT
-- ================================================================
-- Copy the output and save as: report_due_dates.json
SELECT CONCAT('[',
    GROUP_CONCAT(
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
        ) SEPARATOR ','
    ),
']') as report_due_dates_json
FROM report_due_dates rdd
INNER JOIN contracts c ON rdd.contract_id = c.id
WHERE rdd.deleted_at IS NULL AND c.deleted_at IS NULL;

-- ================================================================
-- DUE DATE EMAILS EXPORT
-- ================================================================
-- Copy the output and save as: due_date_emails.json
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'id', id,
            'email', email,
            'created_at', created_at,
            'updated_at', updated_at,
            'deleted_at', deleted_at
        ) SEPARATOR ','
    ),
']') as due_date_emails_json
FROM due_date_emails
WHERE deleted_at IS NULL;

-- ================================================================
-- DUE DATE EMAIL ASSIGNMENTS EXPORT
-- ================================================================
-- Copy the output and save as: due_date_email_assignments.json
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'report_due_date_id', dderdd.report_due_date_id,
            'due_date_email_id', dderdd.due_date_email_id,
            'email', dde.email,
            'report_description', rdd.description,
            'due_date', rdd.due_date,
            'contract_title', c.title
        ) SEPARATOR ','
    ),
']') as due_date_assignments_json
FROM due_date_email_report_due_date dderdd
INNER JOIN due_date_emails dde ON dderdd.due_date_email_id = dde.id
INNER JOIN report_due_dates rdd ON dderdd.report_due_date_id = rdd.id
INNER JOIN contracts c ON rdd.contract_id = c.id
WHERE dde.deleted_at IS NULL 
  AND rdd.deleted_at IS NULL 
  AND c.deleted_at IS NULL;