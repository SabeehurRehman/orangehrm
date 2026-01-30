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

namespace OrangeHRM\Admin\Controller;

use OrangeHRM\Admin\Service\SlackIntegrationService;
use OrangeHRM\Core\Controller\AbstractVueController;
use OrangeHRM\Core\Vue\Component;
use OrangeHRM\Core\Vue\Prop;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Leave\Service\LeaveTypeService;

/**
 * Controller for Slack Integration settings page
 */
class ViewSlackIntegrationController extends AbstractVueController
{
    /**
     * @var null|LeaveTypeService
     */
    protected ?LeaveTypeService $leaveTypeService = null;

    /**
     * @return LeaveTypeService
     */
    public function getLeaveTypeService(): LeaveTypeService
    {
        if (!$this->leaveTypeService instanceof LeaveTypeService) {
            $this->leaveTypeService = new LeaveTypeService();
        }
        return $this->leaveTypeService;
    }

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        // Get all active leave types for the multi-select dropdown
        $leaveTypes = $this->getLeaveTypeService()
            ->getLeaveTypeDao()
            ->getLeaveTypeList();
        
        $leaveTypeList = [];
        foreach ($leaveTypes as $leaveType) {
            $leaveTypeList[] = [
                'id' => $leaveType->getId(),
                'label' => $leaveType->getName(),
            ];
        }

        // Get list of supported timezones
        $timezones = \DateTimeZone::listIdentifiers();
        
        $component = new Component('slack-integration-view');
        $component->addProp(new Prop('leave-types', Prop::TYPE_ARRAY, $leaveTypeList));
        $component->addProp(new Prop('timezones', Prop::TYPE_ARRAY, $timezones));
        $this->setComponent($component);
    }
}
