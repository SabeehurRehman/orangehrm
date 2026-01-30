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

use OrangeHRM\Admin\Api\Model\SlackIntegrationModel;
use OrangeHRM\Admin\Service\SlackIntegrationService;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Entity\SlackIntegration;

/**
 * API endpoint for Slack Integration settings
 */
class SlackIntegrationAPI extends Endpoint implements ResourceEndpoint
{
    public const PARAMETER_ENABLED = 'enabled';
    public const PARAMETER_WEBHOOK_URL = 'webhookUrl';
    public const PARAMETER_DIGEST_TIME = 'digestTime';
    public const PARAMETER_TIMEZONE = 'timezone';
    public const PARAMETER_LEAVE_TYPES = 'leaveTypes';

    public const PARAM_RULE_WEBHOOK_URL_MAX_LENGTH = 500;
    public const PARAM_RULE_DIGEST_TIME_MAX_LENGTH = 5;
    public const PARAM_RULE_TIMEZONE_MAX_LENGTH = 100;

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
     * @param SlackIntegrationService $slackIntegrationService
     */
    public function setSlackIntegrationService(SlackIntegrationService $slackIntegrationService): void
    {
        $this->slackIntegrationService = $slackIntegrationService;
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResourceResult
    {
        $slackIntegration = $this->getSlackIntegrationService()->getSlackIntegration();
        
        // Return default settings if none exist
        if (!$slackIntegration instanceof SlackIntegration) {
            $slackIntegration = new SlackIntegration();
            $slackIntegration->setEnabled(false);
            $slackIntegration->setDigestTime('09:00');
            $slackIntegration->setTimezone('UTC');
        }

        return new EndpointResourceResult(SlackIntegrationModel::class, $slackIntegration);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID),
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResourceResult
    {
        $slackIntegration = $this->getSlackIntegrationService()->getSlackIntegration();
        
        // Create new record if doesn't exist
        if (!$slackIntegration instanceof SlackIntegration) {
            $slackIntegration = new SlackIntegration();
        }

        // Get parameters from request
        $enabled = $this->getRequestParams()->getBoolean(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_ENABLED
        );
        $webhookUrl = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_WEBHOOK_URL
        );
        $digestTime = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_DIGEST_TIME
        );
        $timezone = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_TIMEZONE,
            'UTC'
        );
        $leaveTypes = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_LEAVE_TYPES,
            []
        );

        // Validate webhook URL if provided
        if (!empty($webhookUrl) && !$this->getSlackIntegrationService()->validateWebhookUrl($webhookUrl)) {
            throw $this->getBadRequestException('Invalid Slack webhook URL format');
        }

        // Update entity
        $slackIntegration->setEnabled($enabled);
        $slackIntegration->setWebhookUrl($webhookUrl);
        $slackIntegration->setDigestTime($digestTime);
        $slackIntegration->setTimezone($timezone);
        $slackIntegration->setLeaveTypesArray($leaveTypes);

        // Save to database
        $slackIntegration = $this->getSlackIntegrationService()->saveSlackIntegration($slackIntegration);

        return new EndpointResourceResult(SlackIntegrationModel::class, $slackIntegration);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID),
            $this->getValidationDecorator()->requiredParamRule(
                new ParamRule(
                    self::PARAMETER_ENABLED,
                    new Rule(Rules::BOOL_TYPE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_WEBHOOK_URL,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_WEBHOOK_URL_MAX_LENGTH])
                ),
                true
            ),
            $this->getValidationDecorator()->requiredParamRule(
                new ParamRule(
                    self::PARAMETER_DIGEST_TIME,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_DIGEST_TIME_MAX_LENGTH]),
                    new Rule(Rules::REGEX, ['/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/']) // HH:MM format
                )
            ),
            $this->getValidationDecorator()->requiredParamRule(
                new ParamRule(
                    self::PARAMETER_TIMEZONE,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_TIMEZONE_MAX_LENGTH])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_LEAVE_TYPES,
                    new Rule(Rules::ARRAY_TYPE)
                )
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResourceResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
