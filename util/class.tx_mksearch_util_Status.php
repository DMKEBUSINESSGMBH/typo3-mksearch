<?php

declare(strict_types=1);

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * Status information for indexes.
 */
class tx_mksearch_util_Status
{
    public const STATUS_OKAY = 1;

    public const STATUS_UNKNOWN = 0;

    public const STATUS_ERROR = -1;

    private int $status = self::STATUS_UNKNOWN;

    private $message;

    /**
     * Get status id.
     * 0 - unknown, greater then 0 means okay, lower then 0 means error.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Returns a status message from core.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function setStatus(int $status, $message = ''): void
    {
        $this->status = $status;
        $this->message = $message;
    }
}
