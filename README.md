# C32Check

PHP library for encoding and decoding C32 addresses which are used on the Stacks blockchain. It provides functionality to convert between base-32 encoded strings and their underlying byte representations, including checksum verification.

## Requirements

- PHP 8.0 or higher
- Composer

## Installation

```bash
composer require matronator/c32check
```

## Usage

```php

use Matronator\C32Check\Address;

$version = 22;

$hexString = '0x1e2a7c8a7e0d61c7a7d679c4e0c8f2e7';

$address = new Address($version, $hexString);

echo $address->toBase58Address(); // 1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2

echo $address->toC32Address(); // ST1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2Z

```

## Testing

```bash
composer test
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgements

- [Bitcoin Wiki](https://en.bitcoin.it/wiki/Technical_background_of_version_1_Bitcoin_addresses)
- [Base58](https://github.com/tuupola/base58)
- [Stacks](https://stacks.co/)
