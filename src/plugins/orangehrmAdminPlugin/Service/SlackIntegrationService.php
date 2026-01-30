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

namespace OrangeHRM\Admin\Service;

use DateTime;
use OrangeHRM\Core\Traits\ORM\EntityManagerTrait;
use OrangeHRM\Entity\SlackIntegration;
use OrangeHRM\Leave\Service\SlackService;

/**
 * Service for managing Slack Integration settings
 */
class SlackIntegrationService
{
    use EntityManagerTrait;

    /**
     * Get Slack Integration settings
     * Returns the first (and should be only) record
     *
     * @return SlackIntegration|null
     */
    public function getSlackIntegration(): ?SlackIntegration
    {
        return $this->getEntityManager()
            ->getRepository(SlackIntegration::class)
            ->findOneBy([], ['id' => 'ASC']);
    }

    /**
     * Save Slack Integration settings
     *
     * @param SlackIntegration $slackIntegration
     * @return SlackIntegration
     */
    public function saveSlackIntegration(SlackIntegration $slackIntegration): SlackIntegration
    {
        $slackIntegration->setUpdatedAt(new DateTime());
        $this->getEntityManager()->persist($slackIntegration);
        $this->getEntityManager()->flush();
        return $slackIntegration;
    }

    /**
     * Validate webhook URL format
     *
     * @param string $webhookUrl
     * @return bool
     */
    public function validateWebhookUrl(string $webhookUrl): bool
    {
        // Check if URL is valid
        if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Check if URL starts with https://hooks.slack.com/
        return str_starts_with($webhookUrl, 'https://hooks.slack.com/');
    }

    /**
     * Send test message to Slack
     *
     * @param string $webhookUrl
     * @return bool
     */
    public function sendTestMessage(string $webhookUrl): bool
    {
        $slackService = new SlackService();
        $testMessage = "🧪 Test Message from OrangeHRM\n\nYour Slack integration is working correctly!";
        return $slackService->sendMessage($webhookUrl, $testMessage);
    }

    /**
     * Update last sent date
     *
     * @param SlackIntegration $slackIntegration
     * @param DateTime $date
     */
    public function updateLastSentDate(SlackIntegration $slackIntegration, DateTime $date): void
    {
        $slackIntegration->setLastSentDate($date);
        $slackIntegration->setUpdatedAt(new DateTime());
        $this->getEntityManager()->persist($slackIntegration);
        $this->getEntityManager()->flush();
    }
}
