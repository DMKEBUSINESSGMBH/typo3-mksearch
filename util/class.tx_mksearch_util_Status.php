<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 das Medienkombinat
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Status information for indexes.
 */
class tx_mksearch_util_Status
{
    public const STATUS_OKAY = 1;
    public const STATUS_UNKNOWN = 0;
    public const STATUS_ERROR = -1;

    private $status;
    private $message;

    public function __construct()
    {
        $this->status = self::STATUS_UNKNOWN;
    }

    /**
     * Get status id.
     * 0 - unknown, greater then 0 means okay, lower then 0 means error.
     *
     * @return int
     */
    public function getStatus()
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

    public function setStatus($status, $message = '')
    {
        $this->status = $status;
        $this->message = $message;
    }
}
