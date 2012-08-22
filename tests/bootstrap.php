<?php

$libPath = dirname(__DIR__);

require_once 'PHPUnit/Framework/TestCase.php';

require $libPath . '/src/Rych/Bencode.php';
require $libPath . '/src/Rych/Bencode/Encoder.php';
require $libPath . '/src/Rych/Bencode/Decoder.php';

require $libPath . '/src/Rych/Bencode/Exception.php';
require $libPath . '/src/Rych/Bencode/Exception/RuntimeException.php';
