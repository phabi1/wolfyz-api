<?php

namespace App\Membership\HelloAsso;

/**
 * Error that every other file would hit too (rate limit, access refused):
 * stop the transfer instead of sending more requests to HelloAsso.
 */
class TransferAbortedException extends \RuntimeException
{
}
