# Reqon — System Test Plan
**Version:** 1.0  
**System:** Requisition Management System — Isuzu East Africa Limited  
**Environment:** XAMPP · MariaDB 10.4 · PHP 8.2  
**Base URL:** `http://localhost/Reqon`

---

## Test Accounts

| User | Email | Role | Approval Level | Notes |
|---|---|---|---|---|
| System Administrator | admin@isuzu.co.ke | System Admin | — | Lands on Admin Dashboard |
| Sharon Kitavi | sharon.kitavi@isuzu.co.ke | Requester | — | IT dept · cannot see IT Asset reqs she submits herself |
| Jane Smith | jane.smith@isuzu.co.ke | Requester | — | HR dept · no IT Asset option |
| John Muchai | john.muchai@isuzu.co.ke | Requester | — | Procurement dept |
| Elizabeth Wanjiku | elizabeth.wanjiku@isuzu.co.ke | Approver | L1 — IT Dept Head | Fixed L1 for IT Asset reqs |
| Peter Kamau | peter.kamau@isuzu.co.ke | Approver | L1 — HR Dept Head | L1 for Personnel reqs from HR dept |
| Mary Wambua | mary.wambua@isuzu.co.ke | Approver | L1 (Proc Dept Head) + L2 (Procurement Head) | LPO generation rights |
| Grace Odhiambo | hr.director@isuzu.co.ke | HR Admin | L2 — HR Director | Personnel reqs only |
| David Kariuki | finance.director@isuzu.co.ke | Approver | L3 — Finance Director | All types |
| James Ngugi | md@isuzu.co.ke | Approver | L4 — Managing Director | Final sign-off; auto-approves own submissions |

**Password for all accounts:** `password` (bcrypt hash in DB)

---

## Module 1 — Authentication & Session

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 1.1 | Successful login — Requester | Navigate to `/login.php`, enter Sharon's credentials, click Login | Redirected to `/dashboard.php`; nav shows "Sharon Kitavi · IT · Requester" | |
| 1.2 | Successful login — Approver | Login as Elizabeth | Redirected to `/approvals/queue.php` | |
| 1.3 | Successful login — Admin | Login as admin | Redirected to `/admin/dashboard.php` | |
| 1.4 | Successful login — MD | Login as James Ngugi | Redirected to `/approvals/queue.php` | |
| 1.5 | Invalid credentials | Enter wrong password | Error message shown; stays on login page | |
| 1.6 | Empty fields | Submit login form with blank fields | Validation error shown | |
| 1.7 | Session expiry guard | Access `/dashboard.php` without logging in | Redirected to `/login.php` | |
| 1.8 | Logout | Click Log out from nav dropdown | Session destroyed; redirected to `/login.php`; back button does not restore session | |
| 1.9 | Role label in nav | Login as each test account | Nav shows correct dept · role label (e.g. "Finance · Finance Director") | |

---

## Module 2 — Role-Based Access & Visibility

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 2.1 | Requester sees own reqs only | Login as Sharon; open List | Only Sharon's requisitions visible | |
| 2.2 | Dept Head sees own dept | Login as Elizabeth; open List | Only IT dept requisitions visible | |
| 2.3 | Procurement Head visibility | Login as Mary; open List | Procurement + IT Asset + Merchandise reqs visible; no Personnel | |
| 2.4 | HR Director visibility | Login as Grace; open List | Personnel requisitions only | |
| 2.5 | Finance Director sees all | Login as David; open List | All types from all departments visible | |
| 2.6 | MD sees all | Login as James; open List | All requisitions visible | |
| 2.7 | Admin blocked from dashboard | Login as admin; navigate to `/dashboard.php` | Redirected to `/admin/dashboard.php` | |
| 2.8 | Non-admin blocked from admin dashboard | Login as Sharon; navigate to `/admin/dashboard.php` | Redirected to `/dashboard.php` with error | |
| 2.9 | Approvers blocked from audit log | Login as Elizabeth; navigate to `/admin/audit.php` | Redirected to `/dashboard.php` with "Access denied" | |
| 2.10 | Non-Mary blocked from LPO queue | Login as Sharon; navigate to `/procurement/lpo_queue.php` | Redirected to `/dashboard.php` with "Access denied" | |
| 2.11 | Non-Mary blocked from generate_lpo.php | Login as David; navigate to `/api/generate_lpo.php?id=1` | "Access denied" message | |

---

## Module 3 — New Requisition Wizard

### 3A — IT Asset gating

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 3.1 | IT dept user sees IT Asset option | Login as Sharon (IT dept); go to New Requisition Step 1 | "IT Asset" visible in type dropdown | |
| 3.2 | Non-IT dept user does not see IT Asset | Login as Jane (HR dept); Step 1 | "IT Asset" NOT in dropdown; hint text shown | |

### 3B — Step 1 validation

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 3.3 | Submit with no type selected | Leave type blank, submit Step 1 | "Please select a valid requisition type" error | |
| 3.4 | Personnel requires position title | Select Personnel, leave title blank, submit | "Position title is required" error | |
| 3.5 | Date in the past rejected | Set date required to yesterday, submit | "Date required must be at least tomorrow" error | |
| 3.6 | Lead time warning | Set date required to 10 days from today | Yellow warning shown below date field | |

### 3C — Step 2 catalog picker

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 3.7 | Catalog search shows results | Type "laptop" in search box | Matching catalog items appear in dropdown | |
| 3.8 | Selecting catalog item fills row | Click "Dell Latitude 5540 Laptop" | Item name and unit cost auto-fill in row; cost field is read-only | |
| 3.9 | No match shows message | Type "xyz123" | "No matching items found in catalog." shown (non-selectable) | |
| 3.10 | Submit with no item selected | Leave item row blank, submit Step 2 | "Please add at least one item" error | |
| 3.11 | Submit with item but no catalog_id | Manually clear hidden catalog_id and submit | "All items must be selected from the catalog" error | |
| 3.12 | Personnel shows seniority catalog | Select Personnel in Step 1; go to Step 2 | Catalog shows seniority levels (Intern, Associate, etc.) with monthly costs | |
| 3.13 | No "Other (unlisted item)" option | Search any term | "Other (unlisted item)" entry never appears | |

### 3D — Step 3 review & submission

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 3.14 | Approval route preview — Procurement | Complete Steps 1-2 for a Procurement req as Sharon | Step 3 shows: Procurement Head → Finance Director → Managing Director (no Dept Head for Procurement type) | |
| 3.15 | Approval route preview — IT Asset | As Sharon (IT dept) complete an IT Asset req | Shows: IT Dept Head → Procurement Head → Finance Director → MD | |
| 3.16 | Approval route preview — Personnel | As Jane (HR dept) complete a Personnel req | Shows: HR Dept Head → HR Director → Finance Director → MD | |
| 3.17 | Skip-self shown in preview | Login as Elizabeth; submit IT Asset req | Step 3 shows IT Dept Head slot numbered starting from Procurement Head (Elizabeth's own slot not shown) | |
| 3.18 | Successful submission redirects | Click Submit on Step 3 as Sharon | Redirected to `/dashboard.php`; flash "REQ-XXX submitted successfully"; new row in list | |
| 3.19 | Double-submit prevented | Click Submit quickly twice | Submit button disabled after first click; only one requisition created | |
| 3.20 | Back button preserves data | Fill Step 1, go to Step 2, click Back | Step 1 pre-filled with previous values | |

---

## Module 4 — Approval Routing Engine

### 4A — IT Asset chain (Sharon submitting from IT dept)

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 4.1 | Level 1 assigned to Elizabeth | Submit IT Asset req as Sharon | `approval_history` row with `approver_id=5`, `level_id=1`, `decision='pending'` | |
| 4.2 | Elizabeth approves → goes to Mary | Login as Elizabeth; approve REQ | `current_approval_level` advances to 2; Mary gets notification | |
| 4.3 | Mary approves at L2 → goes to David | Login as Mary; check queue; approve | `current_approval_level` advances to 3; David gets notification | |
| 4.4 | David approves → goes to James | Login as David; approve | `current_approval_level` advances to 4 | |
| 4.5 | James final approval → status=approved | Login as James; approve | `current_status='approved'`; Sharon gets "fully approved" notification | |

### 4B — Procurement chain (Sharon submitting)

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 4.6 | No Dept Head at L1 | Submit Procurement req as Sharon | First `approval_history` row has `level_id=2` (Mary), not level 1 | |
| 4.7 | Mary appears in queue at L2 | Login as Mary; open Approval Queue | Sharon's procurement req visible | |
| 4.8 | Mary can approve Procurement req | Click View Details; approve | Advances to Finance Director | |

### 4C — Personnel chain (Jane submitting from HR dept)

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 4.9 | Peter (HR Dept Head) gets L1 | Submit Personnel req as Jane | Notification sent to Peter (user 6) | |
| 4.10 | Grace sees Personnel in queue at L2 | After Peter approves; login as Grace | Req visible in Grace's queue | |
| 4.11 | Grace does NOT see IT Asset or Procurement | Login as Grace; check queue | Only Personnel reqs in queue | |

### 4D — Skip-self rules

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 4.12 | Mary submitting Procurement — skips L1 and L2 | Login as Mary; submit Procurement req | Chain starts at Finance Director (L3); no L1 or L2 rows in approval_history | |
| 4.13 | MD submitting — auto-approved | Login as James; submit any req | `current_status='approved'` immediately; no approval_history rows | |
| 4.14 | Dept Head submitting Personnel from own dept | Login as Peter; submit Personnel req for HR dept | Peter's own L1 slot skipped; chain starts at Grace (L2) | |

### 4E — Rejection flow

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 4.15 | Reject without comment blocked | Open View Details as approver; click Reject with empty comment box | "Please enter a reason before rejecting" alert | |
| 4.16 | Rejection with comment sets status | Enter comment, confirm rejection | `current_status='rejected'`; `rejection_reason` populated; requester notified | |
| 4.17 | Rejected req not in queue | After rejection; login as next approver | Req no longer appears in their queue | |

---

## Module 5 — Approval Queue

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 5.1 | Requester cannot access queue | Login as Sharon; navigate to `/approvals/queue.php` | Redirected to dashboard with error | |
| 5.2 | Queue shows only user's pending reqs | Login as Elizabeth; open queue | Only IT dept reqs at L1 visible | |
| 5.3 | Mary's combined queue | Login as Mary; open queue | Sees L1 Procurement-dept reqs AND L2 goods reqs together | |
| 5.4 | Filter by type works | Apply "IT Asset" filter in queue | Only IT Asset reqs shown | |
| 5.5 | Filter by priority works | Apply "High" priority filter | Only high-priority reqs shown | |
| 5.6 | No Approve/Reject buttons in queue | Open queue as any approver | Only "View Details" button visible per card | |
| 5.7 | View Details opens correct req | Click View Details | Correct requisition detail page opens | |
| 5.8 | Decision panel shown correctly | Login as Elizabeth; open a L1 req | Approve/Reject panel visible in right sidebar | |
| 5.9 | Decision panel hidden for wrong level | Login as David; open a req at L1 | No decision panel visible | |
| 5.10 | After approval, redirect to queue | Approve a req from view.php | Redirected back to `/approvals/queue.php` | |

---

## Module 6 — Requisition Detail View

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 6.1 | Approval tracker shows correct steps | Open REQ-001 (fully approved) | Tracker shows 4 steps, all with ✓ done state; correct names beside each | |
| 6.2 | Skipped levels hidden | Open a Procurement req (no L1) | Tracker shows only 3 active steps (Procurement Head / Finance Dir / MD) with correct x/3 numbering | |
| 6.3 | Current level label is type-specific | Open a Procurement req at L2 | Quick Info shows "Level 2 — Procurement Head" (not "HR Director / Procurement Head") | |
| 6.4 | Personnel req shows HR Director at L2 | Open a Personnel req at L2 | Shows "Level 2 — HR Director" | |
| 6.5 | Export PDF available to all | Login as Sharon; open own req; click Export PDF | New tab opens with printable summary containing all req details | |
| 6.6 | LPO Queue button for Mary | Login as Mary; open an approved goods req | "Go to LPO Queue" button visible in status strip | |
| 6.7 | LPO Queue button for Admin | Login as admin; open an approved goods req | "View LPO Queue" button visible | |
| 6.8 | No LPO button for others | Login as Sharon; open an approved goods req | No LPO button in status strip | |
| 6.9 | Cancel only for owner while pending | Login as Sharon; open own pending req | Actions menu → Cancel Requisition visible | |
| 6.10 | Cancel blocked after approval | Sharon opens an approved req | No Actions menu | |

---

## Module 7 — LPO Management (Mary / Admin)

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 7.1 | LPO Queue accessible to Mary | Login as Mary; navigate to `/procurement/lpo_queue.php` | Page loads; stat cards and table visible | |
| 7.2 | LPO Queue accessible to Admin (view-only) | Login as admin; navigate to LPO Queue | Page loads; Generate LPO buttons not visible | |
| 7.3 | Pending LPOs stat card count | Before generating any LPO | "Pending LPOs" count equals number of approved goods reqs with no lpo_log entry | |
| 7.4 | Generate LPO records to DB | Click Generate LPO for a req | `lpo_log` table gets new row; `lpo_number` = "LPO-XXX"; `generated_by = 7` | |
| 7.5 | LPO document opens in new tab | After clicking Generate LPO | Printable LPO opens in new tab with correct data (org header, items, VAT, signatures) | |
| 7.6 | Generated LPO count increments | Refresh LPO Queue after generating | "LPOs Generated" stat card value increased by 1 | |
| 7.7 | Pending count decrements | Refresh LPO Queue after generating | "Pending LPOs" count decreased by 1 | |
| 7.8 | Duplicate LPO prevented | Click Generate LPO on same req twice | Second click opens existing LPO; no duplicate in `lpo_log` | |
| 7.9 | Re-print LPO available | After generation; LPO Queue row shows "Re-print LPO" | Opens LPO document again | |
| 7.10 | Requester notified on LPO generation | Check notifications as Sharon after Mary generates LPO | "LPO LPO-XXX has been generated for your requisition REQ-XXX" notification | |
| 7.11 | Filter by LPO status | Apply "Pending LPO" filter | Only reqs without an LPO shown | |
| 7.12 | LPO document VAT calculation | Open any LPO | Grand Total = Subtotal × 1.16 (16% VAT) | |

---

## Module 8 — Role-Specific Dashboard Analytics

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 8.1 | Mary's dashboard shows LPO cards | Login as Mary | Insight row shows: Total Requisitions (goods) · Approved This Month · Pending LPOs · LPOs Generated | |
| 8.2 | Mary's "Approved This Month" is goods-only | Check value against DB | Count matches `SELECT COUNT(*) FROM requisitions WHERE current_status='approved' AND requisition_type IN ('procurement','it_asset','merchandise') AND MONTH(final_decision_date)=MONTH(NOW())` | |
| 8.3 | Finance Director gets insight cards | Login as David | Two cards visible: Approved This Month (org-wide + KES) · Top Spending Dept | |
| 8.4 | MD gets executive cards | Login as James | Three cards: Total Requisitions (org-wide) · Approved This Month · Top Spending Dept | |
| 8.5 | Top Spending Dept shows correct dept | Login as David or James | Card shows department name with highest approved KES this month | |
| 8.6 | No insight cards for Requesters | Login as Sharon | No extra insight section above main stat grid | |
| 8.7 | No insight cards for HR Director | Login as Grace | No extra insight section | |
| 8.8 | Standard 4-card grid always present | Login as any non-admin user | Total · Pending · Approved · Rejected cards always shown for scoped data | |

---

## Module 9 — Admin Dashboard & Audit Log

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 9.1 | All 8 insight cards render | Login as admin | All 8 cards present: Total Reqs · Pending Now · Approved This Month · Avg Approval Time · Stale >7 days · Active Users · Top Spending Dept · Audit Events Today | |
| 9.2 | Stale card highlights when > 0 | If any req has been pending > 7 days | Card has red left border (`stat-card-danger`) | |
| 9.3 | Audit log filters work | Apply "APPROVE" action filter | Only APPROVE entries shown | |
| 9.4 | Department filter | Select "Finance" in dept filter | Only audit entries for Finance requisitions shown | |
| 9.5 | User name filter | Type "Sharon" | Only Sharon's entries shown | |
| 9.6 | Date range filter | Set from/to dates | Only entries within range shown | |
| 9.7 | Audit log linked to requisition | Click requisition link in audit table | Opens correct requisition detail page | |
| 9.8 | Approvers cannot access admin/audit.php | Login as Elizabeth; navigate to `/admin/audit.php` | Redirected to dashboard with "Access denied" | |

---

## Module 10 — Notifications

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 10.1 | Notification sent on submission | Submit req as Sharon | Elizabeth (IT Dept Head) gets "New requisition REQ-XXX requires your approval" | |
| 10.2 | Notification on approval advance | Elizabeth approves | Mary gets notification for next level | |
| 10.3 | Notification on rejection | Any approver rejects | Requester gets "Your requisition REQ-XXX was rejected at [Level]" | |
| 10.4 | Notification on full approval | James approves final | Requester gets "Your requisition REQ-XXX has been fully approved" (no "Congratulations!") | |
| 10.5 | Bell badge shows unread count | Login with unread notifications | Badge on bell icon shows correct count (max "9+" display) | |
| 10.6 | Mark single notification as read | Click a notification | Dot changes; notification marked read in DB; count decreases | |
| 10.7 | Mark all as read | Click "Mark all as read" button | All notifications marked read; unread count drops to 0 | |
| 10.8 | No "Congratulations!" in any message | Check all approval notifications | Word "Congratulations" does not appear anywhere in notification messages | |

---

## Module 11 — Export PDF & LPO Document

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 11.1 | Export PDF opens in new tab | Open any req; click Export PDF | New tab with print-ready document | |
| 11.2 | PDF contains correct data | Check document content | REQ number, type, status, submitter, department, items, approval trail all correct | |
| 11.3 | PDF available on all statuses | Open a rejected req; click Export PDF | Document opens including rejection reason | |
| 11.4 | LPO document structure | Open an LPO via LPO Queue | Contains: org header, LPO number, 10-field meta grid, items table, VAT totals, T&Cs, signature block | |
| 11.5 | LPO number format | Check LPO number | Matches "LPO-" + same suffix as the REQ number (REQ-005 → LPO-005) | |
| 11.6 | Print button present | Both documents | "Print / Save as PDF" button visible; triggers browser print dialog | |

---

## Module 12 — Data Integrity & Edge Cases

| # | Test Case | Steps | Expected Result | Pass/Fail |
|---|---|---|---|---|
| 12.1 | REQ numbers are sequential | Submit 3 requisitions | REQ-009, REQ-010, REQ-011 (or next in sequence) | |
| 12.2 | Cancelled req removed from active views | Cancel a pending req | Status shows "Cancelled"; no longer in approver queues | |
| 12.3 | Only owner can cancel | Login as Jane; try to cancel Sharon's req via direct URL | "You can only cancel your own requisitions" error | |
| 12.4 | Cannot cancel approved req | Try to cancel an approved req | "Only pending requisitions can be cancelled" error | |
| 12.5 | Employment type null for non-personnel | Submit a Procurement req | `employment_type` column is NULL in DB (not empty string) | |
| 12.6 | Catalog_id stored correctly | Submit req with catalog item | `requisition_items.catalog_id` matches the selected item's `catalog_id` | |
| 12.7 | SQL injection resistance | Enter `'; DROP TABLE requisitions;--` in description field | Stored as literal text; no DB error; data retrieved correctly | |
| 12.8 | XSS resistance | Enter `<script>alert('xss')</script>` in description | Rendered as escaped text in all views; no alert fires | |

---

## Regression Checklist (run after any code change)

- [ ] Login works for all 10 test accounts
- [ ] Sharon (IT) can submit all 4 types; Jane (HR) cannot submit IT Asset
- [ ] A Procurement req from Sharon routes to Mary → David → James (skips L1)
- [ ] Mary can approve at both L1 (Procurement dept reqs) and L2 (goods reqs)
- [ ] Mary's LPO Queue loads; Generate LPO creates a DB record and opens document
- [ ] Admin dashboard loads with all 8 cards; audit log is filterable
- [ ] Export PDF and LPO document both open and display correct data
- [ ] Notifications are sent at each stage; no "Congratulations!" in messages

---

*Test plan covers: Authentication · RBAC · Requisition wizard · Routing engine · Skip-self rules · Approval queue · LPO management · Role analytics · Admin audit · Notifications · PDF export · Data integrity*
