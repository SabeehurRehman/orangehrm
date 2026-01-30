<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace OrangeHRM\Leave\Command;

use DateTime;
use DateTimeZone;
use OrangeHRM\Admin\Service\SlackIntegrationService;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Entity\SlackIntegration;
use OrangeHRM\Framework\Console\Command;
use OrangeHRM\Leave\Service\LeaveDigestService;
use OrangeHRM\Leave\Service\SlackService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to send daily leave digest to Slack
 * Updated to read configuration from ohrm_slack_integration table
 */
class SlackDailyLeaveDigestCommand extends Command
{
    use DateTimeHelperTrait;

    /**
     * @inheritDoc
     */
    public function getCommandName(): string
    {
        return 'orangehrm:slack-daily-leave-digest';
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Send daily leave digest to Slack channel');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get Slack Integration settings from database
        $slackIntegrationService = new SlackIntegrationService();
        $slackIntegration = $slackIntegrationService->getSlackIntegration();

        // Check if Slack integration is configured and enabled
        if (!$slackIntegration instanceof SlackIntegration || !$slackIntegration->isEnabled()) {
            $this->getIO()->warning('Slack daily digest is not enabled');
            return self::SUCCESS;
        }

        // Get Slack webhook URL from settings
        $webhookUrl = $slackIntegration->getWebhookUrl();
        if (empty($webhookUrl)) {
            $this->getIO()->error('Slack webhook URL is not configured');
            return self::FAILURE;
        }

        // Check if already sent today (idempotency)
        if ($this->isAlreadySentToday($slackIntegration)) {
            $this->getIO()->note('Daily digest already sent today');
            return self::SUCCESS;
        }

        try {
            // Build the digest message with optional leave type filtering
            $leaveDigestService = new LeaveDigestService();
            $leaveTypes = $slackIntegration->getLeaveTypesArray();
            $message = $leaveDigestService->buildDailyDigestMessage($leaveTypes);

            // Send to Slack
            $slackService = new SlackService();
            $success = $slackService->sendMessage($webhookUrl, $message);

            if ($success) {
                // Update last sent date in database
                $slackIntegrationService->updateLastSentDate(
                    $slackIntegration,
                    $this->getDateTimeHelper()->getNow()
                );
                $this->getIO()->success('Daily leave digest sent to Slack successfully');
                return self::SUCCESS;
            } else {
                $this->getIO()->error('Failed to send message to Slack');
                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->getIO()->error('Error sending daily leave digest: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Check if digest was already sent today
     * Uses configured timezone for comparison
     *
     * @param SlackIntegration $slackIntegration
     * @return bool
     */
    private function isAlreadySentToday(SlackIntegration $slackIntegration): bool
    {
        $lastSentDate = $slackIntegration->getLastSentDate();
        if (!$lastSentDate instanceof DateTime) {
            return false;
        }

        // Get today's date in the configured timezone
        $timezone = new DateTimeZone($slackIntegration->getTimezone());
        $today = $this->getDateTimeHelper()
            ->getNow()
            ->setTimezone($timezone)
            ->format('Y-m-d');

        return $lastSentDate->format('Y-m-d') === $today;
    }
}
