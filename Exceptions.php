<?php

/**
 * @package Quakelive API
 * @author Adam Klvač <adam@klva.cz>
 */

namespace Quakelive;
use ErrorException;

/**
 * Global exception for Quakelive API
 */
class ApiException extends ErrorException {}

/**
 * Exception for Quakelive API requests
 */
class RequestException extends ErrorException {}