# PcAxis

[![Build Status](https://travis-ci.org/z38/pc-axis.png?branch=master)](https://travis-ci.org/z38/pc-axis)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/z38/pc-axis/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/z38/pc-axis/?branch=master)

**PcAxis** is a PHP library to read PC-Axis (PX) files.

## Installation

Just install [Composer](http://getcomposer.org) and run `composer require z38/pc-axis` in your project directory.

## Usage

```php
use Z38\PcAxis\Px;

$px = new Px('yourfile.px');

$px->variables(); // returns a list of all variables
$px->values($variable) // returns a list of all values for a given variable
$px->datum([0, 5, 3]); // returns the datum for a given index
```

### Low-Level Access
```php
$px->keywords(); // returns all keywords
$px->data(); // returns all data cells
```

## Contributing

If you want to get your hands dirty, great! Here's a couple of steps/guidelines:

- Fork this repository
- Add your changes & tests for those changes (in `tests/`).
- Send me a pull request!

If you don't want to go through all this, but still found something wrong or missing, please
let me know, and/or **open a new issue report** so that I or others may take care of it.

## Further Resources

- [PX file format specification (2013)](http://www.scb.se/Upload/PC-Axis/Support/Documents/PX-file_format_specification_2013.pdf)
