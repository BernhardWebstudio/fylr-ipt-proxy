<?php

namespace App\Exception;

/**
 * Exception thrown when trying to import an entity type that has no defined DWC mapping.
 */
class UnsupportedObjectTypeException extends \RuntimeException
{
}
