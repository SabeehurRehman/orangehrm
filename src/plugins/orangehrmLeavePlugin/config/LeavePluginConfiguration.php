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

use OrangeHRM\Core\Traits\EventDispatcherTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerTrait;
use OrangeHRM\Core\Traits\ServiceContainerTrait;
use OrangeHRM\Entity\Config;
use OrangeHRM\Framework\Console\Console;
use OrangeHRM\Framework\Console\ConsoleConfigurationInterface;
use OrangeHRM\Framework\Console\Scheduling\CommandInfo;
use OrangeHRM\Framework\Console\Scheduling\Schedule;
use OrangeHRM\Framework\Console\Scheduling\SchedulerConfigurationInterface;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\PluginConfigurationInterface;
use OrangeHRM\Framework\Services;
use OrangeHRM\Leave\Command\SlackDailyLeaveDigestCommand;
use OrangeHRM\Leave\Service\HolidayService;
use OrangeHRM\Leave\Service\LeaveConfigurationService;
use OrangeHRM\Leave\Service\LeaveEntitlementService;
use OrangeHRM\Leave\Service\LeavePeriodService;
use OrangeHRM\Leave\Service\LeaveRequestService;
use OrangeHRM\Leave\Service\LeaveTypeService;
use OrangeHRM\Leave\Service\WorkScheduleService;
use OrangeHRM\Leave\Service\WorkWeekService;
use OrangeHRM\Leave\Subscriber\LeaveEventSubscriber;

class LeavePluginConfiguration implements
    PluginConfigurationInterface,
    ConsoleConfigurationInterface,
    SchedulerConfigurationInterface
{
    use ServiceContainerTrait;
    use EventDispatcherTrait;
    use EntityManagerTrait;

    /**
     * @inheritDoc
     */
    public function initialize(Request $request): void
    {
        $this->getContainer()->register(
            Services::LEAVE_CONFIG_SERVICE,
            LeaveConfigurationService::class
        );
        $this->getContainer()->register(
            Services::LEAVE_TYPE_SERVICE,
            LeaveTypeService::class
        );
        $this->getContainer()->register(
            Services::LEAVE_ENTITLEMENT_SERVICE,
            LeaveEntitlementService::class
        );
        $this->getContainer()->register(
            Services::LEAVE_PERIOD_SERVICE,
            LeavePeriodService::class
        );
        $this->getContainer()->register(
            Services::LEAVE_REQUEST_SERVICE,
            LeaveRequestService::class
        );
        $this->getContainer()->register(
            Services::WORK_SCHEDULE_SERVICE,
            WorkScheduleService::class
        );
        $this->getContainer()->register(
            Services::HOLIDAY_SERVICE,
            HolidayService::class
        );
        $this->getContainer()->register(
            Services::WORK_WEEK_SERVICE,
            WorkWeekService::class
        );

        $this->getEventDispatcher()->addSubscriber(new LeaveEventSubscriber());
    }

    /**
     * @inheritDoc
     */
    public function registerCommands(Console $console): void
    {
        $console->add(new SlackDailyLeaveDigestCommand());
    }

    /**
     * @inheritDoc
     */
    public function schedule(Schedule $schedule): void
    {
        // Get Slack Integration settings from database
        $slackIntegration = $this->getEntityManager()
            ->getRepository(\OrangeHRM\Entity\SlackIntegration::class)
            ->findOneBy([], ['id' => 'ASC']);

        // Only schedule if Slack integration is configured and enabled
        if ($slackIntegration && $slackIntegration->isEnabled()) {
            $digestTime = $slackIntegration->getDigestTime();
            
            // Validate time format (HH:MM)
            if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $digestTime)) {
                // Invalid format, use default
                $digestTime = '09:00';
            }
            
            [$hour, $minute] = explode(':', $digestTime);

            // Schedule daily at configured time (cron format: minute hour * * *)
            $schedule->add(new CommandInfo('orangehrm:slack-daily-leave-digest'))
                ->cron("$minute $hour * * *");
        }
    }
}
