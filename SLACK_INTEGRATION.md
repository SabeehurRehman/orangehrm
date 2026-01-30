# Slack Daily Leave Digest Integration

## Overview
This integration sends a daily digest message to a Slack channel listing all employees who are on leave for the current day.

## Configuration

### Option A: Using the Admin UI (Recommended - New!)

1. **Log in as Admin** to your OrangeHRM instance

2. **Navigate to Admin → Configuration → Slack Integration**

3. **Configure Settings**:
   - **Enable Slack Digest**: Toggle ON
   - **Webhook URL**: Enter your Slack Incoming Webhook URL
     - Get this from: https://api.slack.com/messaging/webhooks
     - Must start with `https://hooks.slack.com/`
   - **Digest Time**: Set the time to send daily digest (HH:MM format, e.g., "09:00")
   - **Timezone**: Select your timezone
   - **Leave Types**: (Optional) Select specific leave types to include in digest
     - Leave empty to include all leave types

4. **Test the Integration**:
   - Click "Send Test Message" button
   - Check your Slack channel for the test message

5. **Save Settings**:
   - Click "Save" to apply configuration

**Important Security Notes:**
- The webhook URL is masked in the UI (only last 6 characters visible)
- Full URL is securely stored in database and never exposed to frontend
- Only Admin users can access this configuration page

### Option B: Using SQL Scripts (Legacy Method)

If you prefer manual database configuration or for automated deployments:

1. **Set Up Slack Incoming Webhook**
1. Go to your Slack workspace settings
2. Navigate to "Apps" → "Custom Integrations" → "Incoming Webhooks"
3. Create a new webhook and select the channel where you want to receive notifications
4. Copy the webhook URL (e.g., `https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX`)

**Important Security Notes:**
- The webhook URL must start with `https://hooks.slack.com/` for security validation
- Keep your webhook URL private - anyone with access can post to your channel
- SSL certificate verification is enabled by default for secure communication

2. **Configure OrangeHRM Database Settings**

#### Option A: Using the provided SQL script (Recommended)
```bash
# Edit the SQL script and replace YOUR_WEBHOOK_URL_HERE with your actual webhook URL
nano slack_integration_setup.sql

# Run the script
mysql -u username -p database_name < slack_integration_setup.sql
```

#### Option B: Manual SQL commands
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

### 3. Set Up Cron Job (Both Options)
Add this cron job to your server to run the scheduler every hour:

```bash
0 * * * * cd /path/to/orangehrm && php bin/console orangehrm:run-schedule >> /var/log/orangehrm-scheduler.log 2>&1
```

The scheduler will automatically run the Slack digest command at the configured time.

**Note**: If using the Admin UI (Option A), the cron job reads settings from the `ohrm_slack_integration` table. If using SQL scripts (Option B), it reads from the `hs_hr_config` table.

## Admin UI Features (New!)

The Admin UI provides a user-friendly interface for managing Slack integration:

### Features:
- **Visual Configuration**: No need to write SQL queries
- **Real-time Validation**: Instant feedback on webhook URL format and time format
- **Test Message**: Send a test message directly from the UI
- **Timezone Support**: Select from all available timezones
- **Leave Type Filtering**: Choose which leave types to include in digest
- **Secure Display**: Webhook URL is masked for security
- **Form Reset**: Easily revert changes before saving

### Access Requirements:
- Must be logged in with **Admin** role
- Navigate to: **Admin → Configuration → Slack Integration**

### UI Components:
1. **Enable Toggle**: Turn digest on/off without deleting configuration
2. **Webhook URL**: Text input with validation (must start with https://hooks.slack.com/)
3. **Digest Time**: Time input in HH:MM format (24-hour)
4. **Timezone**: Dropdown with all PHP timezones
5. **Leave Types**: Multi-select dropdown (optional)
6. **Test Button**: Sends "🧪 Test Message from OrangeHRM" to your channel
7. **Save/Reset Buttons**: Standard form actions

## Manual Testing
You can manually trigger the digest command for testing:

```bash
cd /path/to/orangehrm
php bin/console orangehrm:slack-daily-leave-digest
```

**Expected Outputs:**
- Success: "Daily leave digest sent to Slack successfully"
- No config: "Slack webhook URL is not configured"
- Already sent: "Daily digest already sent today"
- Disabled: "Slack daily digest is not enabled"

**Testing Tips:**
1. Use `slack_integration_test_queries.sql` to verify what data will be included
2. To test multiple sends in one day, reset the last sent date (see SQL script)
3. Check logs at `/path/to/orangehrm/src/log/orangehrm.log` for detailed information

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
- `slack_integration_setup.sql` - SQL script for easy configuration
- `slack_integration_test_queries.sql` - SQL queries for testing and verification
- `SLACK_INTEGRATION.md` - This documentation file

### Database Query
The digest includes all leaves with status:
- `LEAVE_STATUS_LEAVE_APPROVED` (2)
- `LEAVE_STATUS_LEAVE_TAKEN` (3)

Excludes:
- `LEAVE_STATUS_LEAVE_REJECTED` (-1)
- `LEAVE_STATUS_LEAVE_CANCELLED` (0)
- `LEAVE_STATUS_LEAVE_PENDING_APPROVAL` (1)

### Security Features
- SSL certificate verification enabled for all Slack webhook requests
- Webhook URL validation ensures only valid Slack URLs are accepted
- Time format validation prevents malformed cron schedules
- Proper error handling and logging throughout
- Empty employee name handling with fallback text
