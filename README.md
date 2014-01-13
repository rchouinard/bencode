What is this?
=============

This project allows developers to encode or decode bencoded data strings in
PHP 5.3+. More information about bencode can be found at [Wikipedia](http://en.wikipedia.org/wiki/Bencode).
The format is primarily used in the .torrent file specification.

Build status
------------

[![Build Status](https://secure.travis-ci.org/rchouinard/bencode.png?branch=master)](http://travis-ci.org/rchouinard/bencode)

Why?
----

At one time I was involved in building a torrent tracker/index, and I wrote this
library to read and manipulate uploaded torrent files. I originally bundled it
in my [rchouinard/rych-components](https://github.com/rchouinard/rych-components)
project, but I've recently decided to break that package up into standalone
components.

How does it work?
-----------------

### Encoding an array

```php
<?php

use Rych\Bencode;
require 'Rych/Bencode.php';

$data = array (
    'string' => 'bar',
    'integer' => 42,
    'array' => array (
        'one',
        'two',
        'three',
    ),
);

echo Bencode::encode($data);
```

The above outputs the bencoded string `d5:arrayl3:one3:two5:threee7:integeri42e6:string3:bare`.

### Decoding a string

```php
<?php

use Rych\Bencode;
require 'Rych/Bencode.php';

$string = 'd5:arrayl3:one3:two5:threee7:integeri42e6:string3:bare';

print_r(Bencode::decode($string);
```

The above results the the following output:
```
Array
(
    [array] => Array
        (
            [0] => one
            [1] => two
            [2] => three
        )

    [integer] => 42
    [string] => bar
)
```

Installation via [Composer](http://getcomposer.org/)
------------

 * Install Composer to your project root:
    ```bash
    curl -sS https://getcomposer.org/installer | php
    ```

 * Add a `composer.json` file to your project:
    ```json
    {
      "require" {
        "rych/bencode": "1.0.*"
      }
    }
    ```

 * Run the Composer installer:
    ```bash
    php composer.phar install
    ```
