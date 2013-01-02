<?php

/**
 * @package Quakelive API
 * @author Adam Klvač <adam@klva.cz>
 * @version 1.0.0
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