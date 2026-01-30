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

namespace OrangeHRM\Admin\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\SlackIntegration;

/**
 * @OA\Schema(
 *     schema="Admin-SlackIntegrationModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="enabled", type="boolean"),
 *     @OA\Property(property="webhookUrl", type="string", description="Masked webhook URL"),
 *     @OA\Property(property="digestTime", type="string"),
 *     @OA\Property(property="timezone", type="string"),
 *     @OA\Property(property="leaveTypes", type="array", @OA\Items(type="integer")),
 *     @OA\Property(property="lastSentDate", type="string", format="date")
 * )
 */
class SlackIntegrationModel implements Normalizable
{
    use ModelTrait;

    /**
     * @param SlackIntegration $slackIntegration
     */
    public function __construct(SlackIntegration $slackIntegration)
    {
        $this->setEntity($slackIntegration);
        $this->setFilters(
            [
                'id',
                ['isEnabled'],
                ['getMaskedWebhookUrl'], // Return masked webhook URL for security
                'digestTime',
                'timezone',
                ['getLeaveTypesArray'],
                ['getDecorator', 'getLastSentDate'],
            ]
        );
        $this->setAttributeNames(
            [
                'id',
                'enabled',
                'webhookUrl',
                'digestTime',
                'timezone',
                'leaveTypes',
                'lastSentDate',
            ]
        );
    }
}
