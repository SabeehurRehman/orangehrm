# Slack Integration Architecture

## Component Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                        ORANGEHRM APPLICATION                         │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    Configuration                              │   │
│  │  (hs_hr_config table)                                        │   │
│  │  • slack_webhook_url                                         │   │
│  │  • slack_digest_enabled                                      │   │
│  │  • slack_digest_time (default: 09:00)                        │   │
│  │  • slack_digest_last_sent_date (auto-managed)                │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                            ↓                                          │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │              LeavePluginConfiguration                        │   │
│  │  • Registers SlackDailyLeaveDigestCommand                    │   │
│  │  • Implements SchedulerConfigurationInterface                │   │
│  │  • Schedules cron job based on config                        │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                            ↓                                          │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    Scheduler (Cron)                          │   │
│  │  php bin/console orangehrm:run-schedule                      │   │
│  │  Runs every hour via system cron                             │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                            ↓                                          │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │          SlackDailyLeaveDigestCommand                        │   │
│  │  1. Check if enabled                                         │   │
│  │  2. Check if already sent today (idempotency)                │   │
│  │  3. Get webhook URL from config                              │   │
│  │  4. Build digest message                                     │   │
│  │  5. Send to Slack                                            │   │
│  │  6. Update last sent date                                    │   │
│  └─────────────────────────────────────────────────────────────┘   │
│            ↓                                    ↓                    │
│  ┌──────────────────────┐         ┌──────────────────────┐         │
│  │ LeaveDigestService   │         │    SlackService      │         │
│  │ • Query DB for leaves│         │ • HTTP POST request  │         │
│  │ • Group by type      │         │ • Validate webhook   │         │
│  │ • Format message     │         │ • SSL verification   │         │
│  │ • Add emojis         │         │ • Error handling     │         │
│  └──────────────────────┘         └──────────────────────┘         │
│            ↓                                    ↓                    │
│  ┌──────────────────────┐         ┌──────────────────────┐         │
│  │   Database           │         │  Slack Webhook       │         │
│  │   ohrm_leave         │         │  hooks.slack.com     │         │
│  │   ohrm_leave_request │         │                      │         │
│  │   ohrm_leave_type    │         └──────────────────────┘         │
│  │   hs_hr_employee     │                    ↓                      │
│  └──────────────────────┘         ┌──────────────────────┐         │
│                                    │  Slack Channel       │         │
│                                    │  #hr-announcements   │         │
│                                    └──────────────────────┘         │
└─────────────────────────────────────────────────────────────────────┘
```

## Data Flow

```
1. System Cron (every hour)
   ↓
2. php bin/console orangehrm:run-schedule
   ↓
3. Check scheduled tasks (reads slack_digest_time config)
   ↓
4. If current time matches scheduled time
   ↓
5. Execute: orangehrm:slack-daily-leave-digest
   ↓
6. SlackDailyLeaveDigestCommand
   ├─→ Check slack_digest_enabled config
   ├─→ Check slack_digest_last_sent_date (idempotency)
   ├─→ Get slack_webhook_url config
   ↓
7. LeaveDigestService.buildDailyDigestMessage()
   ├─→ Query ohrm_leave WHERE date = TODAY AND status IN (2,3)
   ├─→ JOIN ohrm_leave_request for date range
   ├─→ JOIN ohrm_leave_type for leave type names
   ├─→ JOIN hs_hr_employee for employee names
   ├─→ Group by leave type
   ├─→ Format with emojis
   ↓
8. SlackService.sendMessage(webhookUrl, message)
   ├─→ Validate webhook URL (must be hooks.slack.com)
   ├─→ HTTP POST with JSON payload
   ├─→ SSL verification enabled
   ↓
9. Slack receives message
   ↓
10. Message posted to configured channel
    ↓
11. Update slack_digest_last_sent_date = TODAY
```

## Database Schema Interaction

```
┌─────────────────────┐
│   ohrm_leave        │
│  ─────────────────  │
│  id (PK)            │
│  date               │◄──── Filtered by CURDATE()
│  status             │◄──── Filtered by IN (2, 3)
│  emp_number (FK)    │
│  leave_type_id (FK) │
│  leave_request_id   │
└─────────────────────┘
          │
          │ Many-to-One
          ↓
┌──────────────────────┐
│  ohrm_leave_request  │
│  ──────────────────  │
│  id (PK)             │
│  emp_number (FK)     │
│  leave_type_id (FK)  │
│  date_applied        │
└──────────────────────┘
          │
          ├─────────────────────┐
          │                     │
          ↓                     ↓
┌──────────────────┐  ┌──────────────────┐
│ ohrm_leave_type  │  │ hs_hr_employee   │
│ ──────────────── │  │ ──────────────── │
│ id (PK)          │  │ emp_number (PK)  │
│ name             │  │ firstName        │
│ deleted          │  │ lastName         │
└──────────────────┘  └──────────────────┘
```

## Message Format Flow

```
Raw Data from DB:
┌─────────────┬────────────┬────────────┬────────────┐
│ Leave Type  │ Employee   │ Start Date │ End Date   │
├─────────────┼────────────┼────────────┼────────────┤
│ Holiday     │ Jerome M.  │ 2026-01-26 │ 2026-02-04 │
│ Holiday     │ Kasia K.   │ 2026-01-21 │ 2026-01-30 │
│ Paternity   │ Jose L.    │ 2026-01-27 │ 2026-01-29 │
└─────────────┴────────────┴────────────┴────────────┘
                    ↓
         LeaveDigestService Processing
         • Group by leave type
         • Add emojis
         • Format dates (DD/MM/YYYY)
                    ↓
Formatted Slack Message:
┌────────────────────────────────────────────┐
│ 🕘 WHO IS OUT TODAY 🕘                     │
│                                            │
│ Holiday 🌴                                 │
│ • Jerome Manzano (26/01/2026 → 04/02/2026)│
│ • Kasia Kowalak (21/01/2026 → 30/01/2026) │
│                                            │
│ Paternity 👶                               │
│ • Jose Lopez (27/01/2026 → 29/01/2026)    │
└────────────────────────────────────────────┘
                    ↓
            JSON Payload to Slack:
            {
              "text": "🕘 WHO IS OUT TODAY..."
            }
```

## Emoji Mapping Logic

```
Leave Type Name (case-insensitive) → Emoji
────────────────────────────────────────────
Contains "holiday" or "vacation"   → 🌴
Contains "sick"                    → 🤒
Contains "paternity" or "maternity"→ 👶
Contains "casual"                  → ☕
Contains "bereavement"             → 🖤
Default (no match)                 → 📅
```

## Scheduling Logic

```
Cron Job (System Level):
0 * * * * php bin/console orangehrm:run-schedule

↓ Every hour, check all scheduled tasks

LeavePluginConfiguration.schedule():
IF slack_digest_enabled = '1'
   READ slack_digest_time (e.g., "09:00")
   VALIDATE format with regex: /^([0-1][0-9]|2[0-3]):[0-5][0-9]$/
   EXTRACT hour and minute
   CREATE cron expression: "0 9 * * *" (minute=0, hour=9)
   SCHEDULE command to run at that time daily

↓ When scheduled time matches current time

Execute: orangehrm:slack-daily-leave-digest
```

## Error Handling Flow

```
Command Execution
   ↓
CHECK: Is enabled?
   NO → Log warning, exit with SUCCESS
   YES ↓
CHECK: Webhook URL configured?
   NO → Log error, exit with FAILURE
   YES ↓
CHECK: Already sent today?
   YES → Log note, exit with SUCCESS
   NO ↓
BUILD: Message from LeaveDigestService
   ERROR → Log exception, exit with FAILURE
   SUCCESS ↓
SEND: Message via SlackService
   ├─ Invalid URL → Log error, return false
   ├─ HTTP Error → Log exception, return false
   ├─ Non-200 status → Log error, return false
   └─ Success → Log info, return true
   ↓
UPDATE: Last sent date
RETURN: SUCCESS
```

## Security Layers

```
1. Configuration Level
   └─→ Webhook URL stored in database (not in code)

2. Validation Level
   ├─→ URL format validation (filter_var)
   └─→ URL prefix validation (must be hooks.slack.com)

3. Transport Level
   ├─→ HTTPS only (enforced by Slack webhook URL)
   └─→ SSL certificate verification enabled

4. Time Configuration Level
   └─→ Regex validation for HH:MM format

5. Data Level
   ├─→ Empty employee name fallback
   └─→ SQL parameterized queries (via Doctrine)
```
