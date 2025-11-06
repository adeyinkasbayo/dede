# 🔧 Fix "No Shops Assigned" Issue

## Problem
Staff members see "No shops assigned. Contact your manager." message in Daily Operations form.

## Root Cause
The new multi-shop assignment system (v1.0.9) uses a new table `staff_shop_assignments`, but existing staff may only have assignments in the old `users.shop_id` field.

---

## ✅ SOLUTION 1: Automatic Fallback (ALREADY IMPLEMENTED)

The system now automatically falls back to the old `users.shop_id` if no entries exist in `staff_shop_assignments`.

**What This Means:**
- Staff with `shop_id` in their user record will see that shop
- No action required if staff have `shop_id` set
- System works with both old and new assignment methods

---

## ✅ SOLUTION 2: One-Time Migration (RECOMMENDED)

Migrate all existing staff assignments to the new system:

### Step 1: Run Migration Script
1. Login as **Admin**
2. Navigate to: `http://yourdomain.com/migrate_staff_assignments.php`
3. The script will automatically:
   - Find all staff with `shop_id` assigned
   - Create entries in `staff_shop_assignments` table
   - Mark them as 'active'
   - Show success confirmation

### Step 2: Verify
1. Login as a staff member
2. Go to Daily Operations → Add New
3. Check Shop Code dropdown
4. Should now show assigned shops

---

## ✅ SOLUTION 3: Manual Assignment (FOR NEW STAFF)

Assign shops to staff members manually:

### For Managers:
1. Login to system
2. Go to **"Shop Assignments"** (in sidebar)
3. Click **"Assign Staff to Shop"**
4. Select staff member
5. Select shop
6. Click **"Assign Shop"**
7. Repeat for multiple shops if needed

---

## 🔍 Troubleshooting

### Issue: Still showing "No shops assigned"

**Check 1: Is staff_shop_assignments table created?**
```sql
SHOW TABLES LIKE 'staff_shop_assignments';
```
If not found, run: `sql/migration_v1.0.9_multi_shop_tips.sql`

**Check 2: Does staff have shop_id?**
```sql
SELECT id, full_name, shop_id FROM users WHERE role = 'staff';
```
If `shop_id` is NULL, assign via Shop Assignments page

**Check 3: Are there entries in staff_shop_assignments?**
```sql
SELECT * FROM staff_shop_assignments WHERE staff_id = [STAFF_ID];
```
If empty, run migration script or manually assign

---

## 📋 Quick Fixes

### Quick Fix A: Assign via users.shop_id (OLD METHOD)
```sql
UPDATE users SET shop_id = 1 WHERE id = [STAFF_ID];
```
System will automatically use this as fallback.

### Quick Fix B: Create assignment entry (NEW METHOD)
```sql
INSERT INTO staff_shop_assignments (staff_id, shop_id, assigned_by, assigned_date, status)
VALUES ([STAFF_ID], [SHOP_ID], [ADMIN_ID], CURDATE(), 'active');
```

### Quick Fix C: Run migration script
Navigate to: `migrate_staff_assignments.php` (as admin)

---

## ✅ Files Modified

**Modified:**
- `src/controllers/staff_assignment.php`
  - Added fallback logic
  - Checks staff_shop_assignments first
  - Falls back to users.shop_id if empty

**Created:**
- `migrate_staff_assignments.php`
  - One-time migration script
  - Admin only access
  - Migrates old assignments to new system

---

## 🎯 What Happens After Fix

**For Staff:**
- ✅ See assigned shop codes in dropdown
- ✅ Can create daily operations
- ✅ Can be assigned to multiple shops

**For Managers:**
- ✅ Can assign staff to shops via UI
- ✅ Can see which shops each staff is assigned to
- ✅ Can remove or add assignments easily

---

## 💡 Best Practices

1. **Run Migration Script Once**
   - Migrates all existing assignments
   - One-time operation
   - Safe to run multiple times (won't create duplicates)

2. **Use Shop Assignments Page Going Forward**
   - More flexible than users.shop_id
   - Supports multiple shops per staff
   - Better tracking and management

3. **Keep users.shop_id for Backup**
   - System uses it as fallback
   - No need to remove old data
   - Provides redundancy

---

## 🔐 Security Notes

**Migration Script:**
- Only accessible to Admin users
- Protected by `require_permission(['admin'])`
- Safe to leave on server or delete after migration
- Records who performed migration (assigned_by field)

**Fallback Logic:**
- Transparent to users
- No performance impact
- Maintains backward compatibility
- Safe for production use

---

## 📊 Expected Results

### Before Fix:
```
Shop Code Dropdown:
└─ No shops assigned. Contact your manager.
```

### After Fix:
```
Shop Code Dropdown:
├─ SH001 - Main Shop
├─ SH002 - Branch Shop
└─ SH003 - Downtown Location
```

---

## 🚀 Deployment Steps

1. **Upload Modified Files:**
   - `src/controllers/staff_assignment.php`
   - `migrate_staff_assignments.php`

2. **Run Migration (if needed):**
   - Access: `http://yourdomain.com/migrate_staff_assignments.php`
   - Login as Admin
   - Verify migration success

3. **Test:**
   - Login as staff
   - Check Daily Operations form
   - Verify shops appear

4. **Clean Up (optional):**
   - Delete `migrate_staff_assignments.php` after migration
   - Or keep for reference

---

## ✅ Verification Checklist

- [ ] staff_shop_assignments table exists
- [ ] Migration script run successfully
- [ ] Staff can see their assigned shops
- [ ] Admin can assign shops via UI
- [ ] Daily operations work for staff
- [ ] Reports show correct shop codes

---

## 📞 Still Having Issues?

If staff still can't see shops:

1. Check PHP error logs
2. Verify database connection
3. Check user role (must be 'staff')
4. Verify shops exist in shops table
5. Test with admin account (should see all shops)

---

**The fallback solution is already in place. Upload the files and staff should see their shops!** ✅
