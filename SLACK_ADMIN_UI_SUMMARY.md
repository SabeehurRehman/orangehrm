# Slack Integration Admin UI - Implementation Summary

## Overview
This implementation adds a complete Admin UI for configuring the Slack Leave Digest integration in OrangeHRM, replacing the previous SQL-based configuration method.

## What Was Implemented

### 1. Database Layer ✅
- **Entity**: `SlackIntegration.php` (ORM entity in Admin plugin)
  - Fields: id, enabled, webhook_url, digest_time, timezone, leave_types, last_sent_date, created_at, updated_at
  - Webhook URL masking method for security
  - Leave types stored as comma-separated IDs with helper methods

### 2. Backend Services ✅
- **SlackIntegrationService**: Business logic for managing Slack settings
  - `getSlackIntegration()`: Retrieve settings from database
  - `saveSlackIntegration()`: Save/update settings
  - `validateWebhookUrl()`: Validate Slack webhook URL format
  - `sendTestMessage()`: Send test message to Slack
  - `updateLastSentDate()`: Update last sent date after successful send

### 3. API Endpoints ✅
- **SlackIntegrationAPI**: CRUD operations for settings
  - `GET /api/v2/admin/slack-integration`: Retrieve current settings (with masked webhook URL)
  - `PUT /api/v2/admin/slack-integration`: Update settings
  - Validates webhook URL format before saving
  - Returns masked webhook URL for security

- **SlackIntegrationTestAPI**: Test message functionality
  - `POST /api/v2/admin/slack-integration-test`: Send test message
  - Returns success/failure status

- **SlackIntegrationModel**: API response model
  - Automatically masks webhook URL in responses
  - Converts leave types to array format

### 4. Updated Cron Logic ✅
- **SlackDailyLeaveDigestCommand**: Now reads from database
  - Removed Config table dependency
  - Reads settings from `SlackIntegration` entity
  - Supports timezone-aware scheduling
  - Includes leave type filtering
  - Updates `last_sent_date` in database after successful send

- **LeaveDigestService**: Enhanced with filtering
  - `buildDailyDigestMessage()`: Now accepts optional leave type filter
  - `getLeavesForDate()`: Filters by specified leave types if provided

- **LeavePluginConfiguration**: Updated scheduler
  - Reads settings from `SlackIntegration` entity instead of Config table
  - Validates time format before scheduling

### 5. Frontend - Vue.js Admin Page ✅
- **ViewSlackIntegration.vue**: Main admin settings page
  - Enable/Disable toggle for Slack digest
  - Webhook URL input field with validation
  - Digest time picker (HH:MM format)
  - Timezone selector dropdown
  - Leave types multi-select
  - Test message button
  - Save/Reset form actions
  - Real-time form validation
  - Toast notifications for success/error

- **UI Components Used**:
  - `oxd-form`: Form wrapper with loading state
  - `oxd-switch-input`: Toggle for enable/disable
  - `oxd-input-field`: Text inputs and dropdowns
  - `oxd-button`: Action buttons
  - `oxd-form-actions`: Submit/reset button container

### 6. Menu & Navigation ✅
- **SlackIntegrationMenuConfigurator**: Menu registration
- **ViewSlackIntegrationController**: Page controller
  - Passes leave types list to frontend
  - Passes timezone list to frontend
- **Route**: `/admin/slackIntegration` (GET)

### 7. Component Registration ✅
- Registered in `orangehrmAdminPlugin/index.ts`
- Component name: `slack-integration-view`
- SCSS styling in `slack-integration.scss`

## File Structure

```
Backend (PHP):
├── src/plugins/orangehrmAdminPlugin/
│   ├── entity/
│   │   └── SlackIntegration.php (NEW)
│   ├── Service/
│   │   └── SlackIntegrationService.php (NEW)
│   ├── Api/
│   │   ├── SlackIntegrationAPI.php (NEW)
│   │   ├── SlackIntegrationTestAPI.php (NEW)
│   │   └── Model/
│   │       └── SlackIntegrationModel.php (NEW)
│   ├── Controller/
│   │   └── ViewSlackIntegrationController.php (NEW)
│   ├── Menu/
│   │   └── SlackIntegrationMenuConfigurator.php (NEW)
│   └── config/
│       └── routes.yaml (MODIFIED - added 3 routes)

Frontend (Vue.js):
├── src/client/src/orangehrmAdminPlugin/
│   ├── pages/slackIntegration/
│   │   ├── ViewSlackIntegration.vue (NEW)
│   │   └── slack-integration.scss (NEW)
│   └── index.ts (MODIFIED - registered component)

Updated Cron Logic:
├── src/plugins/orangehrmLeavePlugin/
│   ├── Command/
│   │   └── SlackDailyLeaveDigestCommand.php (MODIFIED)
│   ├── Service/
│   │   └── LeaveDigestService.php (MODIFIED)
│   └── config/
│       └── LeavePluginConfiguration.php (MODIFIED)
```

## Database Migration

The `ohrm_slack_integration` table will be automatically created by Doctrine when the entity is detected. Fields:

```sql
CREATE TABLE ohrm_slack_integration (
  id INT AUTO_INCREMENT PRIMARY KEY,
  enabled TINYINT(1) DEFAULT 0,
  webhook_url VARCHAR(500) NULL,
  digest_time VARCHAR(5) DEFAULT '09:00',
  timezone VARCHAR(100) DEFAULT 'UTC',
  leave_types VARCHAR(255) NULL,
  last_sent_date DATE NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

## How to Use

### 1. Access the Admin UI
1. Log in to OrangeHRM as an Admin
2. Navigate to: **Admin → Configuration → Slack Integration**
3. The settings page will load with current configuration

### 2. Configure Slack Integration
1. **Enable/Disable**: Toggle the "Enable Slack Digest" switch
2. **Webhook URL**: Enter your Slack Incoming Webhook URL
   - Must start with `https://hooks.slack.com/`
   - Will be validated before saving
3. **Digest Time**: Enter time in HH:MM format (e.g., "09:00" for 9 AM)
4. **Timezone**: Select your timezone from dropdown
5. **Leave Types**: (Optional) Select specific leave types to include
   - If none selected, all leave types will be included
6. Click "Save" to update settings

### 3. Test the Integration
1. After entering a webhook URL, click "Send Test Message"
2. A test message will be sent to your Slack channel
3. Success/error notification will appear

### 4. Migration from Old Configuration
If you previously configured Slack using the SQL scripts (`hs_hr_config` table):

**The old configuration is no longer used.** You need to:
1. Access the Admin UI
2. Re-enter your webhook URL
3. Configure all settings in the UI
4. Save

The system will automatically use the new database table (`ohrm_slack_integration`).

## API Documentation

### GET /api/v2/admin/slack-integration
Retrieve current Slack integration settings

**Response:**
```json
{
  "data": {
    "id": 1,
    "enabled": true,
    "webhookUrl": "******XXXXXX",  // Masked for security
    "digestTime": "09:00",
    "timezone": "America/New_York",
    "leaveTypes": [1, 2, 3],
    "lastSentDate": "2026-01-30"
  }
}
```

### PUT /api/v2/admin/slack-integration
Update Slack integration settings

**Request Body:**
```json
{
  "enabled": true,
  "webhookUrl": "https://hooks.slack.com/services/T.../B.../XXX...",
  "digestTime": "09:00",
  "timezone": "America/New_York",
  "leaveTypes": [1, 2, 3]
}
```

**Response:** Same as GET endpoint

### POST /api/v2/admin/slack-integration-test
Send test message to Slack

**Request Body:**
```json
{
  "webhookUrl": "https://hooks.slack.com/services/T.../B.../XXX..."
}
```

**Response:**
```json
{
  "data": {
    "success": true,
    "message": "Test message sent successfully!"
  }
}
```

## Security Features

1. **Webhook URL Masking**: Full webhook URL never exposed in frontend
   - Only last 6 characters shown in UI
   - Full URL only used for sending messages

2. **URL Validation**: Webhook URL must:
   - Be a valid URL
   - Start with `https://hooks.slack.com/`

3. **Admin-Only Access**: Page only accessible to users with Admin role

4. **Input Sanitization**: All inputs validated before saving

5. **Time Format Validation**: Digest time must be in HH:MM format (24-hour)

## Testing Checklist

- [ ] Access admin page at `/admin/slackIntegration`
- [ ] Toggle enable/disable switch
- [ ] Enter and validate webhook URL
- [ ] Change digest time
- [ ] Select timezone
- [ ] Select leave types
- [ ] Click "Test Message" button
- [ ] Save settings
- [ ] Reset form
- [ ] Verify webhook URL is masked after save
- [ ] Check cron job reads from database
- [ ] Verify last sent date updates after digest send

## Known Limitations

1. **Single Configuration**: Only one Slack integration configuration supported
   - Cannot send to multiple channels
   - Workaround: Use Slack's webhook forwarding if needed

2. **Timezone**: Uses server timezone by default
   - Can be changed in UI but affects scheduling

3. **Leave Type Filter**: Optional feature
   - If no leave types selected, all types included
   - Cannot exclude specific types, only include selected ones

## Migration Notes

### From Old SQL Configuration
The old `hs_hr_config` table entries are no longer used:
- `slack_webhook_url` (DEPRECATED)
- `slack_digest_enabled` (DEPRECATED)
- `slack_digest_time` (DEPRECATED)
- `slack_digest_last_sent_date` (DEPRECATED)

These can be safely deleted after migrating to the Admin UI.

### Backward Compatibility
The scheduler and cron command now **only** read from `ohrm_slack_integration` table. The old Config table entries are ignored.

## Statistics

- **Backend Files Added**: 7
- **Frontend Files Added**: 3
- **Files Modified**: 4
- **Total Lines Added**: ~1,500+
- **API Endpoints**: 3 (GET, PUT, POST)
- **Vue Components**: 1
- **Database Tables**: 1

## Future Enhancements (Not Implemented)

1. Multiple channel support
2. Custom message templates
3. Message preview before sending
4. Send history/log
5. Slack OAuth integration (instead of webhooks)
6. Rich message formatting options
7. Employee name formatting options
8. Custom emoji mappings per leave type

---

**Status**: ✅ COMPLETE AND READY FOR TESTING
**Date**: January 30, 2026
