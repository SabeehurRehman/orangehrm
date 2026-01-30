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

namespace OrangeHRM\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * SlackIntegration - Entity for Slack integration settings
 *
 * @ORM\Table(name="ohrm_slack_integration")
 * @ORM\Entity
 */
class SlackIntegration
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", options={"default": false})
     */
    private bool $enabled = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="webhook_url", type="string", length=500, nullable=true)
     */
    private ?string $webhookUrl = null;

    /**
     * @var string
     *
     * @ORM\Column(name="digest_time", type="string", length=5, options={"default": "09:00"})
     */
    private string $digestTime = '09:00';

    /**
     * @var string
     *
     * @ORM\Column(name="timezone", type="string", length=100, options={"default": "UTC"})
     */
    private string $timezone = 'UTC';

    /**
     * @var string|null
     * Comma-separated leave type IDs
     *
     * @ORM\Column(name="leave_types", type="string", length=255, nullable=true)
     */
    private ?string $leaveTypes = null;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="last_sent_date", type="date", nullable=true)
     */
    private ?DateTime $lastSentDate = null;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private DateTime $updatedAt;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return string|null
     */
    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    /**
     * @param string|null $webhookUrl
     */
    public function setWebhookUrl(?string $webhookUrl): void
    {
        $this->webhookUrl = $webhookUrl;
    }

    /**
     * @return string
     */
    public function getDigestTime(): string
    {
        return $this->digestTime;
    }

    /**
     * @param string $digestTime
     */
    public function setDigestTime(string $digestTime): void
    {
        $this->digestTime = $digestTime;
    }

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     */
    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
    }

    /**
     * @return string|null
     */
    public function getLeaveTypes(): ?string
    {
        return $this->leaveTypes;
    }

    /**
     * Get leave types as array
     *
     * @return array
     */
    public function getLeaveTypesArray(): array
    {
        if (empty($this->leaveTypes)) {
            return [];
        }
        return array_map('intval', explode(',', $this->leaveTypes));
    }

    /**
     * @param string|null $leaveTypes
     */
    public function setLeaveTypes(?string $leaveTypes): void
    {
        $this->leaveTypes = $leaveTypes;
    }

    /**
     * Set leave types from array
     *
     * @param array $leaveTypesArray
     */
    public function setLeaveTypesArray(array $leaveTypesArray): void
    {
        $this->leaveTypes = empty($leaveTypesArray) ? null : implode(',', $leaveTypesArray);
    }

    /**
     * @return DateTime|null
     */
    public function getLastSentDate(): ?DateTime
    {
        return $this->lastSentDate;
    }

    /**
     * @param DateTime|null $lastSentDate
     */
    public function setLastSentDate(?DateTime $lastSentDate): void
    {
        $this->lastSentDate = $lastSentDate;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get masked webhook URL (show only last 6 characters)
     *
     * @return string|null
     */
    public function getMaskedWebhookUrl(): ?string
    {
        if (empty($this->webhookUrl)) {
            return null;
        }

        $length = strlen($this->webhookUrl);
        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 6) . substr($this->webhookUrl, -6);
    }
}
