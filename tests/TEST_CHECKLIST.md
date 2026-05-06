# Reqon — Test Checklist 
## Pre-submission end-to-end verification

All passwords: **test1234**

---

## 1. Database
- [ ] `schema.sql` executed with 0 errors in phpMyAdmin
- [ ] `seed.sql` executed — 5 requisitions, 10 users visible in tables
- [ ] Run this check query — all counts should be > 0:
```sql
SELECT
  (SELECT COUNT(*) FROM users)            AS users,
  (SELECT COUNT(*) FROM departments)      AS departments,
  (SELECT COUNT(*) FROM requisitions)     AS requisitions,
  (SELECT COUNT(*) FROM requisition_items)AS items,
  (SELECT COUNT(*) FROM approval_history) AS approvals,
  (SELECT COUNT(*) FROM notifications)    AS notifications,
  (SELECT COUNT(*) FROM audit_log)        AS audit_entries;
```

---

## 2. Authentication
| Test | User | Expected |
|------|------|----------|
| Login with wrong password | any | Red error alert, no redirect |
| Login as Requester | sharon.kitavi@isuzu.co.ke | → dashboard.php |
| Login as Dept Head | elizabeth.wanjiku@isuzu.co.ke | → approvals/queue.php |
| Login as HR Director | hr.director@isuzu.co.ke | → approvals/queue.php |
| Login as Finance Director | finance.director@isuzu.co.ke | → approvals/queue.php |
| Login as MD | md@isuzu.co.ke | → approvals/queue.php |
| Visit /approvals/queue.php as Sharon | sharon | → redirected, flash error |
| Logout | any | → login.php, session cleared |

---

## 3. Dashboard (Screen 2)
- [ ] Stat cards show correct counts (Sharon: her own REQ-001, REQ-004)
- [ ] Recent table shows correct requisitions for the logged-in user
- [ ] Status badges display correct colour (green=approved, orange=pending, red=rejected)
- [ ] "View All Requisitions" link → list.php works
- [ ] "+ New Request" button → new.php works

---

## 4. Requisition List (list.php)
- [ ] Sharon sees only her 2 requisitions; Elizabeth sees all 5
- [ ] Search by "REQ-001" → returns 1 result
- [ ] Filter by Status = "approved" → returns REQ-001 only
- [ ] Filter by Type = "Personnel" → returns REQ-002, REQ-005
- [ ] Filter by Priority = "High" → returns REQ-001, REQ-003, REQ-004
- [ ] Clear filters → full list restored
- [ ] Active filter pills appear and each × removes that filter only
- [ ] Empty state shows when no results match

---

## 5. New Requisition Form — 3 steps (Screen 3)
- [ ] Step 1: Selecting "Personnel" shows Position Title field; other types hide it
- [ ] Step 1: Date picker minimum is tomorrow (today greyed out)
- [ ] Step 1: Choosing a date < 30 days away triggers the lead time warning
- [ ] Step 1: Submitting with no type → validation error shown, stay on step 1
- [ ] Step 2 (Procurement): Line items table loads; KES totals calculate live on input
- [ ] Step 2: Add item row works; Remove row works (can't remove last row)
- [ ] Step 2: Grand total updates correctly across all rows
- [ ] Step 3: Review shows all data entered correctly
- [ ] Step 3: Submit → REQ-006 created, redirects to view.php, success flash shown
- [ ] DB check: `SELECT * FROM requisitions WHERE requisition_number='REQ-006'` has data
- [ ] Back button on each step preserves previously entered data

---

## 6. Approval Queue (Screen 4)
### As Elizabeth (IT Dept Head — Level 1):
- [ ] Queue shows REQ-003 (Level 1, pending in IT dept)
- [ ] REQ-004 does NOT appear (already approved by her)
- [ ] Search "REQ-003" → filters correctly
- [ ] Filter by Priority = High → shows REQ-003
- [ ] Click "View Details" → goes to view.php
- [ ] Approve REQ-003 → confirm dialog → flash "forwarded to HR Director"
- [ ] REQ-003 no longer in her queue

### Reject flow:
- [ ] Click Reject → modal opens without page reload
- [ ] Submit reject with empty reason → browser/server blocks it
- [ ] Submit reject with reason → flash, req status = rejected in DB

---

## 7. Requisition Details (Screen 5)
- [ ] Approval progress tracker shows correct step states (✓ done, → current, grey waiting)
- [ ] REQ-001: all 4 steps show ✓ done with approver names and dates
- [ ] REQ-002: Level 1 ✓, Level 2 shows ✕ rejected in red with rejection comment
- [ ] REQ-003 (viewed as Elizabeth after approving): Level 1 now ✓
- [ ] Decision panel is visible to Grace (HR Director) for REQ-004, not to Sharon
- [ ] Sharon sees REQ-001 detail but NO decision panel
- [ ] Cancel Requisition (Actions menu) visible to Sharon on pending req only
- [ ] Cancelled requisition status changes to "cancelled" in DB

---

## 8. Notifications
- [ ] Bell badge in nav shows correct unread count per user
- [ ] Sharon: 1 unread (REQ-004 forwarded notification)
- [ ] Grace (HR Director): 1 unread (REQ-004 needs action)
- [ ] Clicking a notification marks it read (dot changes colour, no reload)
- [ ] "Mark all as read" clears all unread for that user
- [ ] Notification links to correct requisition view page

---

## 9. Audit Log (admin/audit.php)
- [ ] Accessible to Elizabeth, Grace, David, James (approvers)
- [ ] NOT accessible to Sharon — redirects with error flash
- [ ] 14 seed entries visible on first load
- [ ] Filter by Action = "REJECT" → shows 2 entries
- [ ] Filter by user name "Sharon" → shows her CREATE entries
- [ ] Record links to correct view.php

---

## 10. Final DB integrity check
Run in phpMyAdmin SQL tab:
```sql
-- Check no orphaned approval_history rows
SELECT COUNT(*) AS orphaned_history
FROM approval_history ah
LEFT JOIN requisitions r ON r.requisition_id = ah.requisition_id
WHERE r.requisition_id IS NULL;
-- Expected: 0

-- Check approved reqs have final_approver_id set
SELECT requisition_number, current_status, final_approver_id
FROM requisitions
WHERE current_status = 'approved';
-- Expected: REQ-001 with final_approver_id = 10 (MD)

-- Check rejected reqs have rejection_reason
SELECT requisition_number, current_status, rejection_reason
FROM requisitions
WHERE current_status = 'rejected';
-- Expected: REQ-002 with a non-null rejection_reason

-- Check total_cost generated column is correct
SELECT item_description, quantity, unit_cost, total_cost
FROM requisition_items;
-- Expected: total_cost = quantity × unit_cost for all rows
```

---

## Known acceptable gaps (out of scope for MVP)
- Email SMTP (notifications are in-app only)
- Password reset flow
- Admin user management panel
- PDF export of requisitions
- File attachments on requisition items