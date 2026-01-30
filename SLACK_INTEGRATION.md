# Slack Daily Leave Digest Integration

## Overview
This integration sends a daily digest message to a Slack channel listing all employees who are on leave for the current day.

## Configuration

### 1. Set Up Slack Incoming Webhook
1. Go to your Slack workspace settings
2. Navigate to "Apps" → "Custom Integrations" → "Incoming Webhooks"
3. Create a new webhook and select the channel where you want to receive notifications
4. Copy the webhook URL (e.g., `https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX`)

### 2. Configure OrangeHRM Database Settings
Add the following configuration entries to the `hs_hr_config` table in your OrangeHRM database:

```sql
-- Enable Slack digest
INSERT INTO hs_hr_config (name, value) VALUES ('slack_digest_enabled', '1');

-- Set Slack webhook URL (replace with your actual webhook URL)
INSERT INTO hs_hr_config (name, value) VALUES ('slack_webhook_url', 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL');

-- Set digest time (optional, default: 09:00)
-- Format: HH:MM (24-hour format)
INSERT INTO hs_hr_config (name, value) VALUES ('slack_digest_time', '09:00');
```

### 3. Set Up Cron Job
Add this cron job to your server to run the scheduler every hour:

```bash
0 * * * * cd /path/to/orangehrm && php bin/console orangehrm:run-schedule >> /var/log/orangehrm-scheduler.log 2>&1
```

The scheduler will automatically run the Slack digest command at the configured time (default: 9:00 AM).

## Manual Testing
You can manually trigger the digest command for testing:

```bash
cd /path/to/orangehrm
php bin/console orangehrm:slack-daily-leave-digest
```

## Message Format
The Slack message will look like this:

```
🕘 WHO IS OUT TODAY 🕘

Holiday 🌴
• Jerome Manzano (26/01/2026 → 04/02/2026)
• Kasia Kowalak (21/01/2026 → 30/01/2026)

Paternity 👶
• Jose Lopez (27/01/2026 → 29/01/2026)
```

If no one is on leave, the message will be:
```
🎉 Everyone is working today!
```

## Example Slack Payload JSON
The service sends a POST request to the Slack webhook URL with the following JSON structure:

```json
{
  "text": "🕘 WHO IS OUT TODAY 🕘\n\nHoliday 🌴\n• Jerome Manzano (26/01/2026 → 04/02/2026)\n• Kasia Kowalak (21/01/2026 → 30/01/2026)\n\nPaternity 👶\n• Jose Lopez (27/01/2026 → 29/01/2026)"
}
```

## Features
- **Daily Digest**: Automatically sent once per day at the configured time
- **Leave Type Grouping**: Employees are grouped by their leave type (e.g., Holiday, Sick, Paternity)
- **Emoji Support**: Each leave type is displayed with a relevant emoji for visual appeal
- **Date Range**: Shows the start and end dates of each employee's leave
- **Idempotency**: Prevents duplicate messages on the same day
- **Error Handling**: Includes proper logging and error handling

## Configuration Keys

| Key | Description | Default | Required |
|-----|-------------|---------|----------|
| `slack_digest_enabled` | Enable/disable Slack digest | - | Yes |
| `slack_webhook_url` | Slack webhook URL | - | Yes |
| `slack_digest_time` | Time to send digest (HH:MM format) | 09:00 | No |
| `slack_digest_last_sent_date` | Last sent date (managed automatically) | - | No |

## Troubleshooting

### Message Not Sending
1. Check if `slack_digest_enabled` is set to '1'
2. Verify the `slack_webhook_url` is correct
3. Check the logs at `/path/to/orangehrm/src/log/orangehrm.log`
4. Manually run the command to see error output

### Duplicate Messages
The system tracks the last sent date to prevent duplicates. If you need to send multiple messages in one day for testing, you can reset the last sent date:

```sql
DELETE FROM hs_hr_config WHERE name = 'slack_digest_last_sent_date';
```

## Technical Details

### Files Created
- `src/plugins/orangehrmLeavePlugin/Service/SlackService.php` - Handles Slack webhook communication
- `src/plugins/orangehrmLeavePlugin/Service/LeaveDigestService.php` - Builds the daily digest message
- `src/plugins/orangehrmLeavePlugin/Command/SlackDailyLeaveDigestCommand.php` - CLI command for sending digest
- `src/plugins/orangehrmLeavePlugin/config/LeavePluginConfiguration.php` - Updated to register command and schedule

### Database Query
The digest includes all leaves with status:
- `LEAVE_STATUS_LEAVE_APPROVED` (2)
- `LEAVE_STATUS_LEAVE_TAKEN` (3)

Excludes:
- `LEAVE_STATUS_LEAVE_REJECTED` (-1)
- `LEAVE_STATUS_LEAVE_CANCELLED` (0)
- `LEAVE_STATUS_LEAVE_PENDING_APPROVAL` (1)
