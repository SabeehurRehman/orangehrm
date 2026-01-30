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

use DateTime;
use OrangeHRM\Core\Traits\ORM\EntityManagerTrait;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Entity\Leave;

/**
 * Service for building daily leave digest messages
 */
class LeaveDigestService
{
    use EntityManagerTrait;
    use DateTimeHelperTrait;

    // Leave type emoji mappings
    private const LEAVE_TYPE_EMOJIS = [
        'holiday' => '🌴',
        'vacation' => '🌴',
        'sick' => '🤒',
        'paternity' => '👶',
        'maternity' => '👶',
        'casual' => '☕',
        'bereavement' => '🖤',
    ];

    /**
     * Build the daily leave digest message for today
     *
     * @return string Formatted message for Slack
     */
    public function buildDailyDigestMessage(): string
    {
        $today = $this->getDateTimeHelper()->getNow();
        $leavesForToday = $this->getLeavesForDate($today);

        if (empty($leavesForToday)) {
            return "🎉 Everyone is working today!";
        }

        // Group leaves by leave type
        $leavesByType = $this->groupLeavesByType($leavesForToday);

        // Build the message
        $message = "🕘 WHO IS OUT TODAY 🕘\n\n";

        foreach ($leavesByType as $leaveTypeName => $leaves) {
            $emoji = $this->getEmojiForLeaveType($leaveTypeName);
            $message .= "{$leaveTypeName} {$emoji}\n";

            foreach ($leaves as $leave) {
                $employeeName = $this->getEmployeeFullName($leave);
                $startDate = $this->formatDate($leave->getLeaveRequest()->getDecorator()->getFromDate());
                $endDate = $this->formatDate($leave->getLeaveRequest()->getDecorator()->getToDate());
                $message .= "• {$employeeName} ({$startDate} → {$endDate})\n";
            }

            $message .= "\n";
        }

        return trim($message);
    }

    /**
     * Get all approved leaves that overlap with the given date
     *
     * @param DateTime $date
     * @return Leave[]
     */
    private function getLeavesForDate(DateTime $date): array
    {
        $qb = $this->getEntityManager()
            ->getRepository(Leave::class)
            ->createQueryBuilder('l');

        $qb->leftJoin('l.leaveRequest', 'lr')
            ->leftJoin('l.leaveType', 'lt')
            ->leftJoin('l.employee', 'e')
            ->where('l.date = :date')
            ->andWhere($qb->expr()->in('l.status', ':statuses'))
            ->setParameter('date', $date->format('Y-m-d'))
            // Include approved (2) and taken (3) leaves
            ->setParameter('statuses', [Leave::LEAVE_STATUS_LEAVE_APPROVED, Leave::LEAVE_STATUS_LEAVE_TAKEN])
            ->orderBy('lt.name', 'ASC')
            ->addOrderBy('e.lastName', 'ASC')
            ->addOrderBy('e.firstName', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Group leaves by leave type name
     *
     * @param Leave[] $leaves
     * @return array<string, Leave[]> Array keyed by leave type name
     */
    private function groupLeavesByType(array $leaves): array
    {
        $grouped = [];
        
        foreach ($leaves as $leave) {
            $leaveTypeName = $leave->getLeaveType()->getName();
            
            if (!isset($grouped[$leaveTypeName])) {
                $grouped[$leaveTypeName] = [];
            }
            
            $grouped[$leaveTypeName][] = $leave;
        }

        return $grouped;
    }

    /**
     * Get emoji for a leave type
     *
     * @param string $leaveTypeName
     * @return string Emoji character
     */
    private function getEmojiForLeaveType(string $leaveTypeName): string
    {
        $normalizedName = strtolower($leaveTypeName);
        
        foreach (self::LEAVE_TYPE_EMOJIS as $keyword => $emoji) {
            if (str_contains($normalizedName, $keyword)) {
                return $emoji;
            }
        }
        
        // Default emoji
        return '📅';
    }

    /**
     * Get employee full name
     *
     * @param Leave $leave
     * @return string Full name
     */
    private function getEmployeeFullName(Leave $leave): string
    {
        $employee = $leave->getEmployee();
        $firstName = $employee->getFirstName() ?? '';
        $lastName = $employee->getLastName() ?? '';
        $fullName = trim($firstName . ' ' . $lastName);
        
        // Fallback if both names are empty
        return empty($fullName) ? 'Unknown Employee' : $fullName;
    }

    /**
     * Format date in DD/MM/YYYY format
     *
     * @param DateTime $date
     * @return string Formatted date
     */
    private function formatDate(DateTime $date): string
    {
        return $date->format('d/m/Y');
    }
}
