

  Database Schema Comparison Report

  Legacy Contract Database vs. Current CC_CAP_TRACK Implementation

  ---
  Executive Summary

  The current contract management system in CC_CAP_TRACK has significant structural differences from the legacy "Contract Database" system. While many
  improvements were made, several issues were introduced during the migration, particularly:

  1. Contract number confusion - Legacy IDs incorrectly stored as contract numbers
  2. Relationship model changes - Contract-to-Program relationship changed from many-to-many to hybrid approach
  3. Lost functionality - Microsoft Calendar integration fields not carried over
  4. Field naming inconsistencies - Multiple similar fields with different names

  ---
  Critical Issues

  1. CONTRACT NUMBER vs ID CONFUSION ⚠️

  Issue: During data import, when legacy contracts had no contract_number, the legacy database ID was incorrectly used as the contract number.

  Evidence (from 2025_07_21_import_report_due_dates_with_mapping.php:23-66):
  306 => ['number' => '306', 'title' => '2021_SHFB_CSFP_SeniorFoodBoxes'],
  327 => ['number' => '327', 'title' => '2023-2025SHFB_NCCDBG_FreshFoodDistribution'],
  335 => ['number' => '335', 'title' => '2024SHFB_StarbucksFoodShareCapacity'],
  349 => ['number' => '349', 'title' => '2024SHFB_AldiRetailRecovery'],

  Impact: At least 10+ contracts have their legacy database ID (e.g., 306, 327, 335) stored as their contract_number, which is semantically incorrect.
  Contract numbers should be official identifiers from funders, not internal database IDs.

  ---
  Structural Differences

  2. CONTRACTS TABLE

  | Aspect              | Legacy System                        | Current System                            | Status                  |
  |---------------------|--------------------------------------|-------------------------------------------|-------------------------|
  | Table Name          | contracts                            | contracts                                 | ✓ Same                  |
  | Purpose Field       | LONGTEXT purpose                     | TEXT purpose                              | ⚠️ Different type       |
  | Dates               | All nullable from start              | Required initially, later made nullable   | ⚠️ Migration complexity |
  | Funder Relationship | Added via separate migration         | funder_id from start                      | ✓ Improved              |
  | Archive Fields      | archived VARCHAR, date_archived DATE | archived BOOLEAN, date_archived TIMESTAMP | ⚠️ Type mismatch        |
  | Audit Fields        | None initially                       | created_by, updated_by                    | ✓ Improved              |
  | Sync Fields         | None                                 | legacy_id, last_synced_at                 | ✓ Added                 |
  | Code Field          | grant_code                           | Both grant_code AND fund_code             | ⚠️ DUPLICATION BUG      |

  Issues:
  - fund_code vs grant_code: Current system has BOTH fields, creating confusion
  - Archive date type changed from DATE to TIMESTAMP (may cause comparison issues)
  - Archive status changed from VARCHAR to BOOLEAN (legacy could have had other values)

  ---
  3. CONTRACT-PROGRAM RELATIONSHIP ⚠️

  | Aspect            | Legacy System                    | Current System                                            |
  |-------------------|----------------------------------|-----------------------------------------------------------|
  | Relationship Type | Many-to-Many ONLY                | Hybrid (both one-to-many AND many-to-many)                |
  | Primary Link      | Via contract_program pivot       | program_id foreign key on contracts                       |
  | Pivot Table       | contract_program (no timestamps) | contract_program (with timestamps, id, unique constraint) |

  Critical Issue: The current system has TWO ways to link contracts to programs:
  1. Direct: contracts.program_id → single program
  2. Pivot: contract_program table → multiple programs

  This creates:
  - Data integrity confusion
  - Difficulty determining "primary" program
  - The model has methods to handle both (program(), programs(), getAllPrograms())

  Evidence (Contract model lines 99-293): Multiple methods needed to reconcile both approaches.

  ---
  4. FUNDERS TABLE

  | Field             | Legacy               | Current                                      | Impact          |
  |-------------------|----------------------|----------------------------------------------|-----------------|
  | Name Field        | funder_name (UNIQUE) | name                                         | ✓ Better naming |
  | Contact Info      | None                 | contact_person, contact_email, contact_phone | ✓ Enhanced      |
  | Additional Fields | None                 | code, description, address, website          | ✓ Enhanced      |
  | Active Flag       | None                 | active (boolean)                             | ✓ Enhanced      |
  | Audit Fields      | None                 | created_by, updated_by                       | ✓ Enhanced      |
  | Sync Fields       | None                 | legacy_id, last_synced_at                    | ✓ Added         |

  Status: ✓ IMPROVED - Current system is much more comprehensive

  ---
  5. REPORT DUE DATES TABLE

  | Aspect                | Legacy                          | Current                                                    | Impact                |
  |-----------------------|---------------------------------|------------------------------------------------------------|-----------------------|
  | Table Name            | report_due_dates                | contract_report_due_dates                                  | Name change           |
  | Status Values         | incomplete, completed           | pending, completed, overdue                                | ⚠️ Different values   |
  | Status Type           | STRING                          | STRING                                                     | Same                  |
  | Report Classification | None                            | report_type, report_name                                   | ✓ Enhanced            |
  | Reminder System       | invite_sent (boolean)           | reminder_sent, reminder_sent_at, reminder_intervals (JSON) | ✓ Enhanced            |
  | Microsoft Calendar    | microsoft_event_id, invite_sent | MISSING                                                    | ⚠️ LOST FUNCTIONALITY |
  | Active Flag           | None                            | active                                                     | ✓ Added               |
  | Notes                 | None                            | notes                                                      | ✓ Added               |
  | Audit Fields          | None                            | created_by, updated_by                                     | ✓ Added               |

  Critical Loss: Microsoft Calendar integration fields (microsoft_event_id, invite_sent) were NOT migrated to the new system.

  ---
  6. REPORTS TABLE

  | Aspect        | Legacy                              | Current
                 |
  |---------------|-------------------------------------|---------------------------------------------------------------------------------------------------
  ---------------|
  | Table Name    | reports                             | contract_reports
                 |
  | Complexity    | Minimal (just ID and relationships) | Comprehensive
                 |
  | Fields        | id, contract_id, due_date_id        | id, contract_id, contract_report_due_date_id, title, description, submitted_date, status, notes,
  metadata (JSON) |
  | Status System | None                                | draft, submitted, approved, rejected
                 |
  | Audit Fields  | None                                | created_by, updated_by
                 |

  Status: ✓ GREATLY IMPROVED - Much more functional

  ---
  7. DUE DATE EMAIL RELATIONSHIPS

  | Aspect            | Legacy                             | Current                                           |
  |-------------------|------------------------------------|---------------------------------------------------|
  | Table Name        | due_date_email_report_due_date     | contract_report_due_date_emails                   |
  | Structure         | Simple pivot (2 foreign keys only) | Enhanced pivot with metadata                      |
  | Additional Fields | None                               | id, reminder_intervals (JSON), active, timestamps |
  | Unique Constraint | None                               | Yes                                               |
  | Indexes           | Foreign key indexes only           | Multiple indexes for performance                  |

  Status: ✓ IMPROVED - More flexible and performant

  ---
  8. PROGRAMS TABLE

  | Field                                                | Both Systems     |
  |------------------------------------------------------|------------------|
  | id, program_name, created_at, updated_at, deleted_at | ✓ Same structure |

  Additional in Current:
  - contract_management_only flag added (migration 2025_07_07_174204)

  ---
  Data Migration Issues Found

  Import Mapping Problems

  From 2025_07_21_import_report_due_dates_with_mapping.php:

  1. Hardcoded contract mapping (lines 20-66) - Brittle, not scalable
  2. Fallback to ID matching (lines 92-94) - Dangerous assumption
  3. Status conversion (line 105) - incomplete → pending (correct)
  4. Report type inference (lines 150-175) - Pattern matching from description (imperfect)

  Specific Contract Number Issues

  Contracts with numeric-only contract numbers (likely IDs):
  - 306, 327, 335, 349, 354, 355, 370, 379, 383, 384

  Contracts with NULL contract numbers: ~60% of the mapping

  ---
  Summary of Discrepancies

  Lost Features ⚠️

  1. Microsoft Calendar integration (microsoft_event_id, invite_sent)
  2. Original status terminology change may break legacy integrations

  Data Quality Issues ⚠️

  1. Contract numbers contain legacy database IDs
  2. Dual program relationship model (one-to-many + many-to-many)
  3. Field duplication (fund_code vs grant_code)
  4. Archive date type mismatch (DATE vs TIMESTAMP)

  Improvements ✓

  1. Much richer funder information
  2. Enhanced report tracking and workflow
  3. Comprehensive audit trail
  4. Better reminder/notification system
  5. Active/inactive flags for better data lifecycle management

  ---
  Recommended Remediation Plan

  I'll prepare a detailed remediation plan after you review this analysis. Key areas will include:

  1. Contract number cleanup - Identify and fix ID-as-number entries
  2. Program relationship consolidation - Decide on single vs multiple programs
  3. Field consolidation - Merge fund_code/grant_code
  4. Microsoft integration - Restore calendar fields or document removal
  5. Data validation - Verify all imported records

  Would you like me to proceed with creating a detailed remediation plan, or would you prefer to review the frontend/interface differences first?