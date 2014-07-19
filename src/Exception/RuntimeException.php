<?php
/**
 * Rych Bencode
 *
 * Bencode serializer for PHP 5.3+.
 *
 * @package   Rych\Bencode
 * @copyright Copyright (c) 2014, Ryan Chouinard
 * @author    Ryan Chouinard <rchouinard@gmail.com>
 * @license   MIT License - http://www.opensource.org/licenses/mit-license.php
 */

namespace Rych\Bencode\Exception;

use Rych\Bencode\Exception;

/**
 * Runtime exception
 */
class RuntimeException extends \RuntimeException implements Exception
{
}
