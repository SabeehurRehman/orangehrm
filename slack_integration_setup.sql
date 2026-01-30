-- ============================================================================
-- OrangeHRM Slack Daily Leave Digest - Configuration Setup
-- ============================================================================
-- This script sets up the necessary configuration in the OrangeHRM database
-- to enable the Slack daily leave digest feature.
--
-- BEFORE RUNNING:
-- 1. Create a Slack Incoming Webhook at: https://api.slack.com/messaging/webhooks
-- 2. Copy your webhook URL (it should look like: https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX)
-- 3. Replace 'YOUR_WEBHOOK_URL_HERE' below with your actual webhook URL
-- ============================================================================

-- Enable Slack daily digest feature
INSERT INTO hs_hr_config (name, value) 
VALUES ('slack_digest_enabled', '1')
ON DUPLICATE KEY UPDATE value = '1';

-- Set Slack webhook URL (REPLACE WITH YOUR ACTUAL WEBHOOK URL)
INSERT INTO hs_hr_config (name, value) 
VALUES ('slack_webhook_url', 'YOUR_WEBHOOK_URL_HERE')
ON DUPLICATE KEY UPDATE value = 'YOUR_WEBHOOK_URL_HERE';

-- Set digest time (optional - default is 09:00)
-- Format: HH:MM in 24-hour format
-- Examples: '09:00' for 9 AM, '14:30' for 2:30 PM, '08:00' for 8 AM
INSERT INTO hs_hr_config (name, value) 
VALUES ('slack_digest_time', '09:00')
ON DUPLICATE KEY UPDATE value = '09:00';

-- ============================================================================
-- Verify Configuration
-- ============================================================================
-- Run this query to verify your configuration:
SELECT name, value 
FROM hs_hr_config 
WHERE name LIKE 'slack_%' 
ORDER BY name;

-- Expected output:
-- +---------------------------+-----------------------------------------------------+
-- | name                      | value                                               |
-- +---------------------------+-----------------------------------------------------+
-- | slack_digest_enabled      | 1                                                   |
-- | slack_digest_time         | 09:00                                               |
-- | slack_webhook_url         | https://hooks.slack.com/services/T.../B.../XXX...   |
-- +---------------------------+-----------------------------------------------------+

-- ============================================================================
-- Disable Feature (if needed)
-- ============================================================================
-- To disable the feature without removing the webhook URL:
-- UPDATE hs_hr_config SET value = '0' WHERE name = 'slack_digest_enabled';

-- ============================================================================
-- Reset Last Sent Date (for testing)
-- ============================================================================
-- If you need to test sending multiple messages in one day:
-- DELETE FROM hs_hr_config WHERE name = 'slack_digest_last_sent_date';
