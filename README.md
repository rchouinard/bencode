What is this?
=============

This project allows developers to encode or decode bencoded data strings in
PHP 5.3+. More information about bencode ca be found at [Wikipedia](http://en.wikipedia.org/wiki/Bencode).
The format is primarily used in the .torrent file specification.

Why?
----

At one time I was involved in building a torrent tracker/index, and I wrote this
library to read and manipulate uploaded torrent files. I originally bundled it
in my [rchouinard/rych-components](https://github.com/rchouinard/rych-components)
project, but I've recently decided to break that package up into standalone
components.
