<?php

$libPath = dirname(__DIR__);

// Pre-load PHPUnit classes
require_once 'PHPUnit/Framework/TestCase.php';

// Pre-load Bencode classes
require $libPath . '/src/Rych/Bencode.php';
require $libPath . '/src/Rych/Bencode/Decoder.php';
require $libPath . '/src/Rych/Bencode/Encoder.php';

require $libPath . '/src/Rych/Bencode/Exception.php';
require $libPath . '/src/Rych/Bencode/Exception/RuntimeException.php';
