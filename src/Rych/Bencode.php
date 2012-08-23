<?php

namespace Rych;

use Rych\Bencode\Decoder;
use Rych\Bencode\Encoder;

require_once 'Rych/Bencode/Decoder.php';
require_once 'Rych/Bencode/Encoder.php';

class Bencode
{

    const TYPE_ARRAY = 'array';
    const TYPE_OBJECT = 'object'; // NOT IMPLEMENTED

    public static function decode($encodedValue, $decodeType = self::TYPE_ARRAY)
    {
        return Decoder::decode($encodedValue, $decodeType);
    }

    public static function encode($valueToEncode)
    {
        return Encoder::encode($valueToEncode);
    }

}
