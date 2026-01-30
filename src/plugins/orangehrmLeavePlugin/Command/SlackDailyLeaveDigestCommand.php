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

use DateTimeZone;
use OrangeHRM\Core\Service\DateTimeHelperService;
use OrangeHRM\Core\Traits\ORM\EntityManagerTrait;
use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Entity\Config;
use OrangeHRM\Framework\Console\Command;
use OrangeHRM\Leave\Service\LeaveDigestService;
use OrangeHRM\Leave\Service\SlackService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to send daily leave digest to Slack
 */
class SlackDailyLeaveDigestCommand extends Command
{
    use ConfigServiceTrait;
    use DateTimeHelperTrait;
    use EntityManagerTrait;

    private const CONFIG_KEY_WEBHOOK_URL = 'slack_webhook_url';
    private const CONFIG_KEY_ENABLED = 'slack_digest_enabled';
    private const CONFIG_KEY_LAST_SENT_DATE = 'slack_digest_last_sent_date';

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
        // Check if Slack integration is enabled
        if (!$this->isSlackDigestEnabled()) {
            $this->getIO()->warning('Slack daily digest is not enabled');
            return self::SUCCESS;
        }

        // Get Slack webhook URL from config
        $webhookUrl = $this->getSlackWebhookUrl();
        if (empty($webhookUrl)) {
            $this->getIO()->error('Slack webhook URL is not configured');
            return self::FAILURE;
        }

        // Check if already sent today (idempotency)
        if ($this->isAlreadySentToday()) {
            $this->getIO()->note('Daily digest already sent today');
            return self::SUCCESS;
        }

        try {
            // Build the digest message
            $leaveDigestService = new LeaveDigestService();
            $message = $leaveDigestService->buildDailyDigestMessage();

            // Send to Slack
            $slackService = new SlackService();
            $success = $slackService->sendMessage($webhookUrl, $message);

            if ($success) {
                // Update last sent date
                $this->updateLastSentDate();
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
     * Check if Slack digest is enabled
     *
     * @return bool
     */
    private function isSlackDigestEnabled(): bool
    {
        $config = $this->getConfigValue(self::CONFIG_KEY_ENABLED);
        return $config === '1' || $config === 'true';
    }

    /**
     * Get Slack webhook URL from configuration
     *
     * @return string|null
     */
    private function getSlackWebhookUrl(): ?string
    {
        return $this->getConfigValue(self::CONFIG_KEY_WEBHOOK_URL);
    }

    /**
     * Check if digest was already sent today
     *
     * @return bool
     */
    private function isAlreadySentToday(): bool
    {
        $lastSentDate = $this->getConfigValue(self::CONFIG_KEY_LAST_SENT_DATE);
        if (empty($lastSentDate)) {
            return false;
        }

        $today = $this->getDateTimeHelper()
            ->getNow()
            ->setTimezone(new DateTimeZone(DateTimeHelperService::TIMEZONE_UTC))
            ->format('Y-m-d');

        return $lastSentDate === $today;
    }

    /**
     * Update the last sent date to today
     */
    private function updateLastSentDate(): void
    {
        $today = $this->getDateTimeHelper()
            ->getNow()
            ->setTimezone(new DateTimeZone(DateTimeHelperService::TIMEZONE_UTC))
            ->format('Y-m-d');

        $this->setConfigValue(self::CONFIG_KEY_LAST_SENT_DATE, $today);
    }

    /**
     * Get configuration value from database
     *
     * @param string $key
     * @return string|null
     */
    private function getConfigValue(string $key): ?string
    {
        $config = $this->getEntityManager()
            ->getRepository(Config::class)
            ->find($key);

        return $config?->getValue();
    }

    /**
     * Set configuration value in database
     *
     * @param string $key
     * @param string $value
     */
    private function setConfigValue(string $key, string $value): void
    {
        $config = $this->getEntityManager()
            ->getRepository(Config::class)
            ->find($key);

        if (!$config) {
            $config = new Config();
            $config->setName($key);
        }

        $config->setValue($value);
        $this->getEntityManager()->persist($config);
        $this->getEntityManager()->flush();
    }
}
