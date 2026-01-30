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

namespace OrangeHRM\Leave\Service;

use GuzzleHttp\Client;
use OrangeHRM\Core\Traits\LoggerTrait;

/**
 * Service for sending notifications to Slack via Incoming Webhooks
 */
class SlackService
{
    use LoggerTrait;

    /**
     * Send a message to Slack webhook
     *
     * @param string $webhookUrl Slack webhook URL
     * @param string $message Message text to send
     * @return bool True if message was sent successfully, false otherwise
     */
    public function sendMessage(string $webhookUrl, string $message): bool
    {
        if (empty($webhookUrl)) {
            $this->getLogger()->error('Slack webhook URL is empty');
            return false;
        }

        try {
            $client = new Client(['verify' => false]);
            $response = $client->post(
                $webhookUrl,
                [
                    'json' => ['text' => $message],
                    'headers' => ['Content-Type' => 'application/json'],
                ]
            );

            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $this->getLogger()->info('Slack message sent successfully');
                return true;
            } else {
                $this->getLogger()->error("Slack webhook returned status code: $statusCode");
                return false;
            }
        } catch (\Exception $e) {
            $this->getLogger()->error('Failed to send Slack message: ' . $e->getMessage());
            $this->getLogger()->error($e->getTraceAsString());
            return false;
        }
    }
}
