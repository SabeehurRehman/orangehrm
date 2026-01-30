# Slack Daily Leave Digest - Quick Start Guide

This feature automatically sends a daily message to your Slack channel listing who is on leave today.

## 🚀 Quick Setup (3 steps)

### Step 1: Get Slack Webhook URL
1. Go to https://api.slack.com/messaging/webhooks
2. Create an Incoming Webhook for your workspace
3. Select the channel where you want notifications
4. Copy the webhook URL

### Step 2: Configure Database
```bash
# Edit slack_integration_setup.sql and add your webhook URL
mysql -u root -p orangehrm < slack_integration_setup.sql
```

Or manually run:
```sql
INSERT INTO hs_hr_config (name, value) VALUES ('slack_digest_enabled', '1');
INSERT INTO hs_hr_config (name, value) VALUES ('slack_webhook_url', 'YOUR_WEBHOOK_URL');
INSERT INTO hs_hr_config (name, value) VALUES ('slack_digest_time', '09:00');
```

### Step 3: Set Up Cron Job
```bash
# Add to crontab (runs scheduler every hour)
0 * * * * cd /path/to/orangehrm && php bin/console orangehrm:run-schedule
```

## ✅ Test It

```bash
cd /path/to/orangehrm
php bin/console orangehrm:slack-daily-leave-digest
```

## 📱 Example Output

When employees are on leave:
```
🕘 WHO IS OUT TODAY 🕘

Holiday 🌴
• Jerome Manzano (26/01/2026 → 04/02/2026)
• Kasia Kowalak (21/01/2026 → 30/01/2026)

Paternity 👶
• Jose Lopez (27/01/2026 → 29/01/2026)
```

When no one is on leave:
```
🎉 Everyone is working today!
```

## 📚 More Information

- **Full Documentation**: See [SLACK_INTEGRATION.md](SLACK_INTEGRATION.md)
- **Test Queries**: See [slack_integration_test_queries.sql](slack_integration_test_queries.sql)
- **Configuration**: See [slack_integration_setup.sql](slack_integration_setup.sql)

## 🔧 Configuration Options

| Setting | Description | Default |
|---------|-------------|---------|
| `slack_digest_enabled` | Enable/disable feature | - |
| `slack_webhook_url` | Your Slack webhook URL | - |
| `slack_digest_time` | Time to send (HH:MM) | 09:00 |

## 🐛 Troubleshooting

**Message not sending?**
1. Check if enabled: `SELECT * FROM hs_hr_config WHERE name = 'slack_digest_enabled';`
2. Verify webhook URL is correct
3. Check logs: `/path/to/orangehrm/src/log/orangehrm.log`

**Test multiple times in one day?**
```sql
DELETE FROM hs_hr_config WHERE name = 'slack_digest_last_sent_date';
```

## 🔐 Security Notes

- ✅ SSL certificate verification enabled
- ✅ Webhook URL validation (must be hooks.slack.com)
- ✅ Time format validation
- ✅ Proper error handling and logging

## 📦 What Was Added

- `src/plugins/orangehrmLeavePlugin/Service/SlackService.php`
- `src/plugins/orangehrmLeavePlugin/Service/LeaveDigestService.php`
- `src/plugins/orangehrmLeavePlugin/Command/SlackDailyLeaveDigestCommand.php`
- `src/plugins/orangehrmLeavePlugin/config/LeavePluginConfiguration.php` (updated)
- Documentation and SQL scripts

---

Need help? Check the full documentation in [SLACK_INTEGRATION.md](SLACK_INTEGRATION.md)
