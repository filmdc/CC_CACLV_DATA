# Corrected Access Model - Universal Directory with Field-Level Security

## ✅ Updated Implementation (October 7, 2025)

### The Problem (Original Implementation)
- Staff with no program assignments saw EMPTY participant/household lists
- Could not search for basic contact information
- But could navigate from program pages to view participant public data
- Inconsistent UX and prevented basic directory functionality

### The Solution (Corrected Implementation)
- **ALL staff can see ALL participants and households in lists**
- **Program assignment controls SENSITIVE DATA access only**
- Staff without program access see "Limited" badge and restricted detail views
- Consistent UX - search and directory work for everyone

---

## 📋 New Access Model

### Participant & Household Lists
**WHO CAN SEE:** All authenticated staff (with participants.view or households.view permission)

**WHAT THEY SEE:**
- ✅ All participants in the list (searchable)
- ✅ All households in the list (searchable)
- ✅ Public data columns: Name, DOB, Contact, Household Code
- ⚠️ "Limited" badge on participants they can't fully access
- ⚠️ Disabled edit/delete buttons for inaccessible participants

**PURPOSE:** Universal directory for basic contact lookup and family finding

---

### Participant Detail Pages
**WHO CAN SEE:** All authenticated staff (with participants.view permission)

**WHAT THEY SEE:**

#### If User Has Program Access (enrolled in any of participant's programs):
- ✅ Green "Full Access" banner
- ✅ All public data (name, DOB, contact)
- ✅ All sensitive data (demographics, health, income, CSBG)
- ✅ All enrollment data (programs, services, notes)
- ✅ Enabled Edit button

#### If User Lacks Program Access:
- ⚠️ Yellow "Limited Access" banner
- ✅ Public data (name, DOB, contact, household code, family members)
- 🔒 Sensitive data replaced with lock icon placeholder
- 🔒 No enrollment data visible
- 🔒 Disabled Edit button with tooltip

---

### Household Detail Pages
**WHO CAN SEE:** All authenticated staff (with households.view permission)

**WHAT THEY SEE:**

#### If User Has Program Access (any household member enrolled in user's programs):
- ✅ Green "Full Access" banner
- ✅ Household code, members list
- ✅ Household type, income, housing status
- ✅ Address information
- ✅ Enabled Edit button

#### If User Lacks Program Access:
- ⚠️ Yellow "Limited Access" banner
- ✅ Household code, members list (names, DOB, relationships)
- ✅ Address information
- 🔒 Household type, income, housing status hidden
- 🔒 Disabled Edit button with tooltip

---

### Enrollment Management
**WHO CAN SEE:** Only staff with enrollments.view permission

**WHAT THEY SEE:**

#### For All Users:
- ⚠️ Header message about program filtering
- Only enrollments in their assigned programs
- Cannot create enrollments in programs they don't have access to

#### For Users Without Program Assignments:
- Empty enrollment lists (correct behavior)
- Cannot create any enrollments
- Message explaining program assignment needed

**PURPOSE:** Enrollment data is ALWAYS restricted - this is intentional and secure

---

## 🔄 Access Flow Examples

### Example 1: Reception Staff (No Program Assignments)
**User:** receptionist@agency.org
**Programs:** None assigned

**Can Do:**
- ✅ Search for any participant by name
- ✅ View participant contact information
- ✅ See household members and relationships
- ✅ Look up household codes
- ✅ Find family information

**Cannot Do:**
- 🔒 View demographic data (education, race, etc.)
- 🔒 View health/insurance information
- 🔒 View income or employment details
- 🔒 View ANY enrollment data
- 🔒 Edit participant or household information

**Visual Indicators:**
- All participants show "Limited" badge
- Yellow "Limited Access" banners on detail pages
- Lock icon placeholders for hidden sections

---

### Example 2: Program Staff (Housing Program Only)
**User:** housing-staff@agency.org
**Programs:** Housing Rehabilitation Program

**Can Do:**
- ✅ Search ALL participants (basic info)
- ✅ View full details for Housing Program participants
- ✅ Edit Housing Program participants
- ✅ Create/view Housing Program enrollments
- ✅ View limited info for other program participants

**Cannot Do:**
- 🔒 View sensitive data for non-Housing participants
- 🔒 Edit participants not enrolled in Housing Program
- 🔒 View enrollments in other programs
- 🔒 Create enrollments in other programs

**Visual Indicators:**
- Housing participants: Green "Full Access" banner
- Other participants: Yellow "Limited Access" banner, "Limited" badge
- Edit buttons disabled for non-Housing participants

---

### Example 3: Multi-Program Staff
**User:** case-manager@agency.org
**Programs:** LIHEAP, CSBG Emergency Services, Food Assistance

**Can Do:**
- ✅ Search ALL participants
- ✅ View full details for participants in ANY of their 3 programs
- ✅ Edit participants enrolled in any of their programs
- ✅ View enrollments across all 3 programs
- ✅ Create enrollments in their 3 programs only

**Cannot Do:**
- 🔒 View sensitive data for participants NOT in any of their 3 programs
- 🔒 View enrollments in programs they're not assigned to
- 🔒 Create enrollments in other programs

**Visual Indicators:**
- Participants in their programs: Green "Full Access" banner
- Participants in other programs only: Yellow "Limited Access" banner

---

### Example 4: Administrator
**User:** admin@agency.org
**Role:** Admin/Administrator

**Can Do:**
- ✅ **EVERYTHING** - No restrictions whatsoever

**Visual Indicators:**
- All participants show green "Full Access" banners
- No "Limited" badges shown
- All buttons enabled
- No filtering applied

---

## 🎯 Benefits of This Model

### 1. Universal Directory Functionality
- ✅ Staff can find anyone for basic contact purposes
- ✅ No "participant doesn't exist" confusion
- ✅ Search works across entire organization
- ✅ Family relationships visible to all

### 2. Field-Level Security
- 🔒 Sensitive data protected by program enrollment
- 🔒 Enrollment data always program-restricted
- 🔒 Clear visual indicators of access level
- 🔒 Cannot accidentally view restricted information

### 3. Flexible Workflows
- ✅ Reception can look up contact info
- ✅ Program staff see full details for their clients
- ✅ Multi-program staff have appropriate access
- ✅ Admin maintains full system visibility

### 4. Compliance Ready
- ✅ Need-to-know enforcement for sensitive data
- ✅ Public contact info appropriately accessible
- ✅ Audit trail for data access
- ✅ HIPAA-aligned privacy controls

---

## 🔧 Technical Implementation

### Key Changes from Original Plan

**CHANGED:**
```php
// OLD - Too restrictive
protected function getAccessibleParticipants(): Builder {
    if (empty($userProgramIds)) {
        return Participant::query()->whereRaw('1 = 0'); // Empty!
    }
}

// NEW - Universal directory
protected function getAccessibleParticipants(): Builder {
    return Participant::query(); // All participants visible
}
```

**KEPT:**
```php
// Field-level access control (unchanged)
public function canViewSensitiveData($participant): bool {
    return $user->canAccessParticipantData($participantId);
}
```

### Where Filtering Still Applies

**Enrollment Management:**
- ✅ Program enrollment lists filtered by program access
- ✅ Service enrollment lists filtered by program access
- ✅ Cannot create enrollments in non-assigned programs
- ✅ EnrollmentShow blocks unauthorized access with 403

**Reason:** Enrollment data is ALWAYS sensitive - no public equivalent

---

## 📖 User Guide Updates

### For Staff Without Program Assignments

**You Can:**
1. Search for any participant or household
2. View names, birthdates, and contact information
3. See household codes and family relationships
4. Look up addresses

**You Cannot:**
5. View demographic, health, or income information
6. See any enrollment data
7. Edit participant or household records
8. Create new enrollments

**What You'll See:**
- Yellow "Limited Access" banners on all detail pages
- "Limited" badges next to participants in lists
- Disabled edit/delete buttons
- Lock icon placeholders where sensitive data would appear

### For Program Staff

**You Can:**
1. Everything reception staff can do, PLUS:
2. View full details for participants in YOUR programs
3. Edit participants in your programs
4. View enrollments in your programs
5. Create enrollments in your programs

**What You'll See:**
- Green "Full Access" banners for your program participants
- Yellow "Limited Access" banners for other program participants
- "Limited" badges on participants outside your programs
- Mix of enabled/disabled buttons based on access

---

## 🧪 Testing Results

### Test Case 1: Staff with No Programs ✅
```
User: program-staff role, NO program assignments
Result:
- ✅ Can see participant list (all participants)
- ✅ Can search participants
- ✅ Clicking participant shows yellow "Limited Access" banner
- ✅ Only public data visible (name, DOB, contact)
- ✅ Edit button disabled
- ✅ "Limited" badge shown in list
```

### Test Case 2: Staff with Program A ✅
```
User: program-staff role, Program A assigned
Result:
- ✅ Can see all participants in list
- ✅ Program A participants show green "Full Access" banner
- ✅ Can edit Program A participants
- ✅ Other participants show yellow "Limited Access" banner
- ✅ Cannot edit non-Program A participants
```

### Test Case 3: Admin ✅
```
User: admin role
Result:
- ✅ All participants visible
- ✅ All show green "Full Access" banners
- ✅ All data accessible
- ✅ All buttons enabled
- ✅ No restrictions
```

---

## 📊 Summary of Changes

### What Changed from Original Plan:
- ❌ **REMOVED**: Filtering participant/household lists by program
- ✅ **KEPT**: Field-level sensitive data restrictions
- ✅ **KEPT**: Enrollment data program filtering (unchanged)
- ✅ **KEPT**: All visual indicators and access banners
- ✅ **KEPT**: Authorization policies and access checks

### Why This Is Better:
1. ✅ Maintains universal directory functionality
2. ✅ Still protects all sensitive data
3. ✅ Better user experience for basic staff
4. ✅ Consistent with real-world agency workflows
5. ✅ Enrollment data remains fully protected

---

**Implementation Status:** COMPLETE and TESTED
**Access Model:** Universal Directory with Field-Level Security
**Rollout Ready:** YES
