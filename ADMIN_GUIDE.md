# CAP TRACK Administrator Guide

## Table of Contents

1. [Administrator Overview](#administrator-overview)
2. [User Management](#user-management)
3. [Role and Permission Management](#role-and-permission-management)
4. [Program Configuration](#program-configuration)
5. [Data Management](#data-management)
6. [System Maintenance](#system-maintenance)
7. [Security Best Practices](#security-best-practices)
8. [Troubleshooting Admin Issues](#troubleshooting-admin-issues)

---

## 1. Administrator Overview

As a CAP TRACK administrator, you have full control over:
- User accounts and permissions
- Program and service configuration
- System settings and maintenance
- Data imports and exports
- Report generation and customization

### Admin-Only Features

Access admin features through:
- **Settings** menu (gear icon in sidebar)
- **Manage** buttons in Programs & Work Plans hub
- **User Management** section
- **System Configuration** panel

---

## 2. User Management

### Creating New Users

Users self-register, but admins manage their access:

1. Have user register at `/register` URL
2. Navigate to Settings > User Management
3. Find the new user (they'll be inactive)
4. Edit user to:
   - Activate account
   - Assign roles
   - Assign programs
   - Set permissions

### Managing Existing Users

#### Edit User Access
1. Go to User Management
2. Click "Edit" on user row
3. Modify:
   - Name and email
   - Active/Inactive status
   - Assigned roles
   - Assigned programs
4. Click "Update User"

#### Bulk User Management
1. Select multiple users with checkboxes
2. Click "Bulk Assign"
3. Choose to add or replace:
   - Roles
   - Programs
4. Click "Apply Changes"

### Password Management

Three options for passwords:
1. **Set Password Directly**
   - Click "Set Password" 
   - Enter new password
   - User can log in immediately

2. **Send Password Reset**
   - Click "Reset Password"
   - System emails reset link
   - More secure option

3. **User Self-Service**
   - Users can reset via login page
   - "Forgot your password?" link

### Deactivating Users

Instead of deleting:
1. Click "Deactivate" on user row
2. User retains data but cannot log in
3. Can reactivate anytime

---

## 3. Role and Permission Management

### Default Roles

1. **Administrator**
   - Full system access
   - All permissions enabled
   - Can manage other users

2. **Program Manager**
   - Manage assigned programs
   - View/edit participants
   - Generate reports
   - Cannot manage users

3. **Case Worker**
   - Create/edit participants
   - Manage enrollments
   - View reports
   - Limited to assigned programs

4. **Data Entry Clerk**
   - Create participants
   - Basic enrollment management
   - Cannot delete records

5. **Report Viewer**
   - Read-only access
   - Can generate reports
   - Cannot modify data

### Permission Categories

Permissions are organized by function:

#### Participants
- `participants.view` - View participant records
- `participants.create` - Add new participants
- `participants.edit` - Modify participant data
- `participants.delete` - Remove participants

#### Households
- `households.view` - View household information
- `households.create` - Create new households
- `households.edit` - Update household data
- `households.delete` - Delete households

#### Programs
- `programs.view` - See program listings
- `programs.create` - Add new programs
- `programs.edit` - Modify programs
- `programs.delete` - Remove programs

#### Services
- `services.view` - View available services
- `services.create` - Add new services
- `services.edit` - Update services
- `services.delete` - Remove services

#### Enrollments
- `enrollments.view` - See enrollments
- `enrollments.create` - Create enrollments
- `enrollments.edit` - Modify enrollments
- `enrollments.delete` - Cancel enrollments

#### Work Plans
- `work_plans.view` - View work plans
- `work_plans.create` - Create plans
- `work_plans.edit` - Update plans
- `work_plans.delete` - Delete plans

#### Work Plan Goals
- `work_plan_goals.view` - View goals
- `work_plan_goals.create` - Add goals
- `work_plan_goals.edit` - Update goals
- `work_plan_goals.delete` - Remove goals

#### Reports
- `reports.view` - Generate reports
- `reports.export` - Export report data

#### Users (Admin Only)
- `users.view` - See user list
- `users.create` - Add users
- `users.edit` - Modify users
- `users.delete` - Remove users

### Creating Custom Roles

Currently, roles are defined in the database seeder. To add custom roles:
1. Modify `database/seeders/InitialSetupSeeder.php`
2. Add role definition
3. Run `php artisan db:seed`

---

## 4. Program Configuration

### Setting Up Programs

1. Navigate to Programs & Work Plans hub
2. Click "Manage Programs"
3. Click "Create Program"
4. Enter:
   - Program Name*
   - Description
   - Active status
   - Housing module enabled (if applicable)
5. Click "Create Program"

### Program Best Practices

- Use clear, descriptive names
- Include funding source in description
- Group related services together
- Keep inactive programs for historical data

### Service Configuration

1. From Programs hub, click "Manage Services"
2. Click "Create Service"
3. Enter:
   - Service Name*
   - Associated Program*
   - Description
   - Active status
4. Click "Create Service"

### Service Categories Examples

**Emergency Services Program**
- Food Pantry
- Utility Assistance
- Emergency Shelter
- Clothing Bank

**Employment Program**
- Job Search Assistance
- Resume Writing
- Skills Training
- Work Experience

**Education Program**
- GED Preparation
- Computer Classes
- Financial Literacy
- Parenting Classes

---

## 5. Data Management

### Importing Legacy Data

1. Prepare data in CSV format
2. Use import templates from `/storage/import/`
3. Navigate to Settings > Data Import
4. Select file and mapping
5. Preview import
6. Confirm import

### Data Export

Regular backups recommended:
1. Go to Settings > Data Export
2. Select data types:
   - Participants
   - Households
   - Enrollments
   - All data
3. Choose format (CSV/JSON)
4. Click "Export"

### Database Maintenance

#### Regular Tasks
- Weekly: Sync work plan goals
- Monthly: Archive old enrollments
- Quarterly: Database optimization
- Yearly: Data retention review

#### Using Artisan Commands

```bash
# Sync work plan goal actuals
php artisan sync:work-plan-goals

# Setup admin permissions
php artisan setup:admin-permissions

# Assign role to user
php artisan user:assign-role user@email.com administrator
```

### Data Integrity

Monitor for:
- Duplicate participants
- Orphaned household members
- Incomplete enrollments
- Missing required fields

---

## 6. System Maintenance

### Regular Maintenance Tasks

#### Daily
- Monitor error logs
- Check failed login attempts
- Verify backup completion

#### Weekly
- Review user activity
- Check disk space
- Update work plan progress

#### Monthly
- Run data quality reports
- Archive old sessions
- Clear temporary files

### Performance Optimization

1. **Database Optimization**
   - Run `php artisan optimize`
   - Clear old cache
   - Index frequently searched fields

2. **Application Cache**
   - Clear config: `php artisan config:clear`
   - Clear routes: `php artisan route:clear`
   - Clear views: `php artisan view:clear`

3. **Queue Management**
   - Monitor queue workers
   - Clear failed jobs
   - Restart workers after updates

### System Updates

1. **Before Updating**
   - Backup database
   - Test in staging environment
   - Notify users of maintenance

2. **Update Process**
   ```bash
   # Put application in maintenance mode
   php artisan down
   
   # Pull updates
   git pull origin main
   
   # Update dependencies
   composer install --no-dev
   npm install && npm run build
   
   # Run migrations
   php artisan migrate
   
   # Clear caches
   php artisan optimize:clear
   
   # Bring application back online
   php artisan up
   ```

---

## 7. Security Best Practices

### User Account Security

1. **Password Requirements**
   - Minimum 8 characters
   - Enforce complexity
   - Regular password changes
   - No password sharing

2. **Account Monitoring**
   - Review login activity
   - Disable inactive accounts
   - Monitor failed attempts
   - Check unusual patterns

### Data Protection

1. **Access Control**
   - Principle of least privilege
   - Program-based restrictions
   - Regular permission audits
   - Remove unnecessary access

2. **Data Handling**
   - Encrypt sensitive data
   - Secure file uploads
   - Sanitize user inputs
   - Log data access

### System Security

1. **Infrastructure**
   - Keep software updated
   - Use HTTPS only
   - Configure firewalls
   - Regular security scans

2. **Backup Strategy**
   - Daily automated backups
   - Off-site backup storage
   - Test restore procedures
   - Encrypt backup files

### Compliance

Ensure compliance with:
- CSBG data requirements
- State privacy laws
- HIPAA (if applicable)
- Agency policies

---

## 8. Troubleshooting Admin Issues

### Common Admin Problems

#### Users Can't Log In
1. Check account is active
2. Verify email is correct
3. Reset password if needed
4. Check role assignments

#### Missing Permissions
1. Review user's roles
2. Check role permissions
3. Verify program assignments
4. Clear permission cache

#### Report Generation Fails
1. Check date ranges
2. Verify data completeness
3. Review error logs
4. Check memory limits

#### Import Errors
1. Validate CSV format
2. Check required fields
3. Look for duplicates
4. Review error messages

### System Diagnostics

#### Check Application Health
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check queue status
php artisan queue:work --stop-when-empty

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check disk space
df -h

# Monitor memory usage
free -m
```

#### Debug Mode (Development Only)
1. Set `APP_DEBUG=true` in `.env`
2. Check detailed error messages
3. Review stack traces
4. **Remember**: Turn off in production

### Getting Support

When requesting support, provide:
1. Error messages (exact text)
2. Steps to reproduce
3. User role and permissions
4. Browser and version
5. Recent system changes

### Emergency Procedures

#### System Down
1. Check server status
2. Verify database connection
3. Check disk space
4. Review error logs
5. Contact hosting provider

#### Data Loss
1. Stop all operations
2. Assess damage scope
3. Restore from backup
4. Verify data integrity
5. Document incident

#### Security Breach
1. Disable affected accounts
2. Change admin passwords
3. Review access logs
4. Document breach details
5. Notify stakeholders

---

## Appendices

### A. Useful Database Queries

```sql
-- Find users without roles
SELECT * FROM users 
WHERE id NOT IN (SELECT user_id FROM role_user);

-- Check enrollment counts by program
SELECT p.name, COUNT(pe.id) as enrollment_count 
FROM programs p 
LEFT JOIN program_enrollments pe ON p.id = pe.program_id 
GROUP BY p.id;

-- Find duplicate participants
SELECT first_name, last_name, date_of_birth, COUNT(*) 
FROM participants 
GROUP BY first_name, last_name, date_of_birth 
HAVING COUNT(*) > 1;
```

### B. Configuration Files

Key configuration files:
- `.env` - Environment variables
- `config/app.php` - Application settings
- `config/database.php` - Database configuration
- `config/legacy-import.php` - Import settings

### C. Admin Checklist

#### New Installation
- [ ] Create admin account
- [ ] Configure organization settings
- [ ] Set up programs and services
- [ ] Create user roles
- [ ] Import legacy data
- [ ] Test all functions
- [ ] Train staff

#### Monthly Tasks
- [ ] Review user accounts
- [ ] Check data quality
- [ ] Run integrity reports
- [ ] Update documentation
- [ ] Backup verification
- [ ] Security review

---

*Administrator Guide Version 1.0*
*Last Updated: January 2025*