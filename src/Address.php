<?php

declare(strict_types=1);

namespace Matronator\C32check;

use Tuupola\Base58;

class Address
{
    public const MAINNET = 'mainnet';
    public const TESTNET = 'testnet';

    public const NETWORKS = [
        static::MAINNET => static::MAINNET,
        static::TESTNET => static::TESTNET,
    ];

    public const P2PKH = 'p2pkh';
    public const P2SH = 'p2sh';

    public const VERSIONS = [
        static::MAINNET => [
            static::P2PKH => 22, // 'P'
            static::P2SH => 20, // 'M'
        ],
        static::TESTNET => [
            static::P2PKH => 26, // 'T'
            static::P2SH => 21, // 'N'
        ],
    ];

    // address conversion : bitcoin to stacks
    public const ADDR_BITCOIN_TO_STACKS = [
        0 => static::VERSIONS[static::MAINNET][static::P2PKH],
        5 => static::VERSIONS[static::MAINNET][static::P2SH],
        111 => static::VERSIONS[static::TESTNET][static::P2PKH],
        196 => static::VERSIONS[static::TESTNET][static::P2SH],
    ];

    // address conversion : stacks to bitcoin
    public const ADDR_STACKS_TO_BITCOIN = [
        static::VERSIONS[static::MAINNET][static::P2PKH] => 0,
        static::VERSIONS[static::MAINNET][static::P2SH] => 5,
        static::VERSIONS[static::TESTNET][static::P2PKH] => 111,
        static::VERSIONS[static::TESTNET][static::P2SH] => 196,
    ];

    public Base58 $base58Check;

    public function __construct(private int $version, private string $hash160hex)
    {
        $this->base58Check = new Base58([
            "characters" => Base58::BITCOIN,
            'check' => true,
            'version' => $this->version,
        ]);
    }

    public function toC32Address(): string
    {
        return $this->c32address($this->version, $this->hash160hex);
    }

    public function toBase58Address(): string
    {
        return $this->base58Check->encode($this->hash160hex, dechex($this->version));
    }

    public static function fromC32Address($c32addr): static
    {
        list($version, $hash160hex) = static::c32addressDecode($c32addr);
        return new Address($version, $hash160hex);
    }

    public static function fromBase58Address($b58addr): static
    {
        $base58Check = new Base58([
            "characters" => Base58::BITCOIN,
            'check' => true,
        ]);

        $addrInfo = $base58Check->decode($b58addr);
        $hash160hex = bin2hex($addrInfo['data']);
        $version = hexdec(bin2hex($addrInfo['prefix']));
        return new Address($version, $hash160hex);
    }

    /**
     * Make a c32check address with the given version and hash160
     * The only difference between a c32check string and c32 address
     * is that the letter 'S' is pre-pended.
     * @param int $version - the address version number
     * @param string $hash160hex - the hash160 to encode (must be a hash160)
     * @return string the address
     */
    public function c32address(int $version, string $hash160hex): string {
        if (!preg_match('/^[0-9a-fA-F]{40}$/', $hash160hex)) {
            throw new \Exception('Invalid argument: not a hash160 hex string');
        }

        $c32string = Checksum::c32checkEncode($version, $hash160hex);
        return 'S' . $c32string;
    }

    /**
     * Decode a c32 address into its version and hash160
     * @param string $c32addr - the c32check-encoded address
     * @return array a tuple with the version and hash160
     */
    public static function c32addressDecode(string $c32addr): array {
        if (strlen($c32addr) <= 5) {
            throw new \Exception('Invalid c32 address: invalid length');
        }
        if ($c32addr[0] != 'S') {
            throw new \Exception('Invalid c32 address: must start with "S"');
        }
        return Checksum::c32checkDecode(substr($c32addr, 1));
    }

    /**
     * Convert a base58check address to a c32check address.
     * Try to convert the version number if one is not given.
     * @param string $b58check - the base58check encoded address
     * @param int $version - the version number, if not inferred from the address
     * @return string the c32 address with the given version number (or the
     *   semantically-equivalent c32 version number, if not given)
     */
    public function b58ToC32(string $b58check, int $version = -1): string
    {
        $addrInfo = $this->base58Check->decode($b58check);
        $hash160String = bin2hex($addrInfo['data']);
        $addrVersion = hexdec(bin2hex($addrInfo['prefix']));
        $stacksVersion = $version;
    
        if ($version < 0) {
            $stacksVersion = $addrVersion;
            if (isset(static::ADDR_BITCOIN_TO_STACKS[$addrVersion])) {
                $stacksVersion = static::ADDR_BITCOIN_TO_STACKS[$addrVersion];
            }
        }
    
        return $this->c32address($stacksVersion, $hash160String);
    }
    
    /**
     * Convert a c32check address to a base58check address.
     * @param string $c32string - the c32check address
     * @param int $version - the version number, if not inferred from the address
     * @return string the base58 address with the given version number (or the
     *    semantically-equivalent bitcoin version number, if not given)
     */
    public function c32ToB58(string $c32string, int $version = -1): string
    {
        $addrInfo = $this->c32addressDecode($c32string);
        $stacksVersion = $addrInfo[0];
        $hash160String = $addrInfo[1];
        $bitcoinVersion = $version;
    
        if ($version < 0) {
            $bitcoinVersion = $stacksVersion;
            if (isset(static::ADDR_STACKS_TO_BITCOIN[$stacksVersion])) {
                $bitcoinVersion = static::ADDR_STACKS_TO_BITCOIN[$stacksVersion];
            }
        }
    
        $prefix = dechex($bitcoinVersion);
        if (strlen($prefix) === 1) {
            $prefix = '0' . $prefix;
        }

        return $this->base58Check->encode($hash160String, $prefix);
    }
}
