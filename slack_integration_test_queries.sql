-- ============================================================================
-- Test Data for Slack Daily Leave Digest
-- ============================================================================
-- This script provides example SQL queries to understand what data the
-- Slack integration will fetch and display.
--
-- NOTE: This is for reference only. DO NOT RUN these INSERT statements 
-- in a production database. Use OrangeHRM's UI to create leave requests.
-- ============================================================================

-- ============================================================================
-- Understanding the Query
-- ============================================================================
-- The LeaveDigestService queries leaves with these criteria:
-- 1. Leave date = TODAY
-- 2. Leave status = APPROVED (2) or TAKEN (3)
-- 3. Excludes REJECTED (-1), CANCELLED (0), PENDING (1)
--
-- Query structure:
/*
SELECT l.*, e.firstName, e.lastName, lt.name as leaveTypeName
FROM ohrm_leave l
INNER JOIN ohrm_leave_request lr ON l.leave_request_id = lr.id
INNER JOIN ohrm_leave_type lt ON l.leave_type_id = lt.id
INNER JOIN hs_hr_employee e ON l.emp_number = e.emp_number
WHERE l.date = CURDATE()
  AND l.status IN (2, 3)  -- APPROVED or TAKEN
ORDER BY lt.name, e.lastName, e.firstName;
*/

-- ============================================================================
-- Example: Check Today's Leaves
-- ============================================================================
-- Run this query to see what would be included in today's digest:

SELECT 
    lt.name AS 'Leave Type',
    CONCAT(e.firstName, ' ', e.lastName) AS 'Employee Name',
    DATE_FORMAT(lr.date_applied, '%d/%m/%Y') AS 'Applied Date',
    DATE_FORMAT(
        (SELECT MIN(date) FROM ohrm_leave WHERE leave_request_id = lr.id),
        '%d/%m/%Y'
    ) AS 'Leave From',
    DATE_FORMAT(
        (SELECT MAX(date) FROM ohrm_leave WHERE leave_request_id = lr.id),
        '%d/%m/%Y'
    ) AS 'Leave To',
    CASE l.status
        WHEN -1 THEN 'REJECTED'
        WHEN 0 THEN 'CANCELLED'
        WHEN 1 THEN 'PENDING'
        WHEN 2 THEN 'APPROVED'
        WHEN 3 THEN 'TAKEN'
        WHEN 4 THEN 'WEEKEND'
        WHEN 5 THEN 'HOLIDAY'
    END AS 'Status'
FROM ohrm_leave l
INNER JOIN ohrm_leave_request lr ON l.leave_request_id = lr.id
INNER JOIN ohrm_leave_type lt ON l.leave_type_id = lt.id
INNER JOIN hs_hr_employee e ON l.emp_number = e.emp_number
WHERE l.date = CURDATE()
  AND l.status IN (2, 3)
ORDER BY lt.name, e.lastName, e.firstName;

-- ============================================================================
-- Example: Expected Slack Message Format
-- ============================================================================
-- Based on sample data, the message would look like:
/*
🕘 WHO IS OUT TODAY 🕘

Holiday 🌴
• Jerome Manzano (26/01/2026 → 04/02/2026)
• Kasia Kowalak (21/01/2026 → 30/01/2026)

Paternity 👶
• Jose Lopez (27/01/2026 → 29/01/2026)

Sick 🤒
• Sarah Johnson (30/01/2026 → 31/01/2026)
*/

-- ============================================================================
-- Example: Check Leaves for Specific Date Range
-- ============================================================================
-- To see all approved leaves in the next week:

SELECT 
    DATE_FORMAT(l.date, '%a %d/%m/%Y') AS 'Date',
    lt.name AS 'Leave Type',
    CONCAT(e.firstName, ' ', e.lastName) AS 'Employee',
    CASE l.status
        WHEN 2 THEN 'APPROVED'
        WHEN 3 THEN 'TAKEN'
    END AS 'Status'
FROM ohrm_leave l
INNER JOIN ohrm_leave_request lr ON l.leave_request_id = lr.id
INNER JOIN ohrm_leave_type lt ON l.leave_type_id = lt.id
INNER JOIN hs_hr_employee e ON l.emp_number = e.emp_number
WHERE l.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
  AND l.status IN (2, 3)
ORDER BY l.date, lt.name, e.lastName;

-- ============================================================================
-- Example: Check Leave Types Available
-- ============================================================================
-- To see what leave types are configured:

SELECT 
    id,
    name,
    CASE 
        WHEN deleted = 1 THEN 'Deleted'
        ELSE 'Active'
    END AS status
FROM ohrm_leave_type
WHERE deleted = 0
ORDER BY name;

-- ============================================================================
-- Example: Count Employees on Leave Today
-- ============================================================================
-- To get a summary count:

SELECT 
    lt.name AS 'Leave Type',
    COUNT(DISTINCT l.emp_number) AS 'Employee Count'
FROM ohrm_leave l
INNER JOIN ohrm_leave_type lt ON l.leave_type_id = lt.id
WHERE l.date = CURDATE()
  AND l.status IN (2, 3)
GROUP BY lt.name
ORDER BY COUNT(DISTINCT l.emp_number) DESC;

-- ============================================================================
-- Leave Status Reference
-- ============================================================================
/*
Status Values in ohrm_leave table:
-1 = REJECTED
 0 = CANCELLED  
 1 = PENDING APPROVAL
 2 = APPROVED (SCHEDULED) - Included in digest
 3 = TAKEN - Included in digest
 4 = WEEKEND - Not a leave day
 5 = HOLIDAY - Not a leave day
*/
