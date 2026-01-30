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

namespace OrangeHRM\Admin\Api;

use OrangeHRM\Admin\Service\SlackIntegrationService;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;

/**
 * API endpoint for testing Slack webhook
 */
class SlackIntegrationTestAPI extends Endpoint
{
    public const PARAMETER_WEBHOOK_URL = 'webhookUrl';
    public const PARAM_RULE_WEBHOOK_URL_MAX_LENGTH = 500;

    /**
     * @var null|SlackIntegrationService
     */
    protected ?SlackIntegrationService $slackIntegrationService = null;

    /**
     * @return SlackIntegrationService
     */
    public function getSlackIntegrationService(): SlackIntegrationService
    {
        if (is_null($this->slackIntegrationService)) {
            $this->slackIntegrationService = new SlackIntegrationService();
        }
        return $this->slackIntegrationService;
    }

    /**
     * Send test message to Slack
     *
     * @return EndpointResult
     */
    public function handleRequest(): EndpointResult
    {
        $webhookUrl = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_WEBHOOK_URL
        );

        // Validate webhook URL
        if (!$this->getSlackIntegrationService()->validateWebhookUrl($webhookUrl)) {
            return new EndpointResult(
                ['success' => false, 'message' => 'Invalid Slack webhook URL format'],
                400
            );
        }

        // Send test message
        $success = $this->getSlackIntegrationService()->sendTestMessage($webhookUrl);

        return new EndpointResult(
            [
                'success' => $success,
                'message' => $success 
                    ? 'Test message sent successfully!' 
                    : 'Failed to send test message. Please check your webhook URL.'
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRules(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->requiredParamRule(
                new ParamRule(
                    self::PARAMETER_WEBHOOK_URL,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_WEBHOOK_URL_MAX_LENGTH])
                )
            )
        );
    }
}
