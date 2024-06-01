<?php

declare(strict_types=1);

namespace Matronator\C32check;

use Tuupola\Base58;

class Encoding
{

    public const string C32 = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    public const string HEX = '0123456789abcdef';

    /**
     * Encode a hex string as a c32 string. Note that the hex string is assumed
     * to be big-endian (and the resulting c32 string will be as well).
     * @param string $inputHex - the input to encode
     * @param int|null $minLength - the minimum length of the c32 string
     * @return string - the c32check-encoded representation of the data, as a string
     */
    public static function c32encode(string $inputHex, ?int $minLength = null): string
    {
        // must be hex
        if (!preg_match('/^[0-9a-fA-F]*$/', $inputHex)) {
            throw new \InvalidArgumentException('Not a hex-encoded string');
        }

        if (strlen($inputHex) % 2 !== 0) {
            $inputHex = '0' . $inputHex;
        }

        $inputHex = strtolower($inputHex);

        $res = [];
        $carry = 0;
        for ($i = strlen($inputHex) - 1; $i >= 0; $i--) {
            if ($carry < 4) {
                $currentCode = strpos(static::HEX, $inputHex[$i]) >> $carry;
                $nextCode = 0;
                if ($i !== 0) {
                    $nextCode = strpos(static::HEX, $inputHex[$i - 1]);
                }
                $nextBits = 1 + $carry;
                $nextLowBits = ($nextCode % (1 << $nextBits)) << (5 - $nextBits);
                $curC32Digit = static::C32[$currentCode + $nextLowBits];
                $carry = $nextBits;
                array_unshift($res, $curC32Digit);
            } else {
                $carry = 0;
            }
        }

        $C32leadingZeros = 0;
        for ($i = 0; $i < count($res); $i++) {
            if ($res[$i] !== '0') {
                break;
            } else {
                $C32leadingZeros++;
            }
        }

        $res = array_slice($res, $C32leadingZeros);

        $zeroPrefix = Utils::hexToBytes($inputHex);
        $numLeadingZeroBytesInHex = 0;
        foreach ($zeroPrefix as $byte) {
            if ($byte === 0) {
                $numLeadingZeroBytesInHex++;
            } else {
                break;
            }
        }

        for ($i = 0; $i < $numLeadingZeroBytesInHex; $i++) {
            array_unshift($res, static::C32[0]);
        }

        if ($minLength) {
            $count = $minLength - count($res);
            for ($i = 0; $i < $count; $i++) {
                array_unshift($res, static::C32[0]);
            }
        }

        return implode('', $res);
    }

    public static function c32normalize(string $c32input): string
    {
        $upper = strtoupper($c32input);
        return str_replace('O', '0', str_replace('L', '1', str_replace('I', '1', $upper)));
    }

    /**
     * Decode a c32 string back into a hex string. Note that the c32 input
     * string is assumed to be big-endian (and the resulting hex string will
     * be as well).
     * @param string $c32input - the c32-encoded input to decode
     * @param int|null $minLength - the minimum length of the output hex string (in bytes)
     * @return string - the hex-encoded representation of the data, as a string
     */
    public static function c32decode(string $c32input, ?int $minLength = null): string
    {   
        $c32input = static::c32normalize($c32input);

        if (!preg_match('/^[' . static::C32 . ']*$/', $c32input)) {
            throw new \InvalidArgumentException('Not a c32-encoded string');
        }

        $zeroPrefix = [];
        if (preg_match('/^' . static::C32[0] . '*/', $c32input, $zeroPrefix)) {
            $numLeadingZeroBytes = strlen($zeroPrefix[0]);
        } else {
            $numLeadingZeroBytes = 0;
        }

        $res = [];
        $carry = 0;
        $carryBits = 0;
        for ($i = strlen($c32input) - 1; $i >= 0; $i--) {
            if ($carryBits === 4) {
                array_unshift($res, static::HEX[$carry]);
                $carryBits = 0;
                $carry = 0;
            }
            $currentCode = strpos(static::C32, $c32input[$i]) << $carryBits;
            $currentValue = $currentCode + $carry;
            $currentHexDigit = static::HEX[$currentValue % 16];
            $carryBits += 1;
            $carry = $currentValue >> 4;
            if ($carry > (1 << $carryBits)) {
                throw new \RuntimeException('Panic error in decoding.');
            }
            array_unshift($res, $currentHexDigit);
        }

        array_unshift($res, static::HEX[$carry]);

        if (count($res) % 2 === 1) {
            array_unshift($res, '0');
        }

        $hexLeadingZeros = 0;
        for ($i = 0; $i < count($res); $i++) {
            if ($res[$i] !== '0') {
                break;
            } else {
                $hexLeadingZeros++;
            }
        }

        $res = array_slice($res, $hexLeadingZeros - ($hexLeadingZeros % 2));

        $hexStr = implode('', $res);
        for ($i = 0; $i < $numLeadingZeroBytes; $i++) {
            $hexStr = '00' . $hexStr;
        }

        if ($minLength) {
            $count = ($minLength * 2) - strlen($hexStr);
            for ($i = 0; $i < $count; $i += 2) {
                $hexStr = '00' . $hexStr;
            }
        }

        return $hexStr;
    }

    public static function b58decode(string $string): array
    {
        $base58 = new Base58(['characters' => Base58::BITCOIN, 'check' => true]);
        $bytes = Utils::hexToBytes(bin2hex($base58->decode($string)));
        $prefixBytes = array_slice($bytes, 0, 1, true);
        $dataBytes = array_slice($bytes, 1, -4, true);

        $merged = $prefixBytes;
        array_push($merged, ...$dataBytes);

        $checksum = Utils::sha256(Utils::sha256($merged));
        foreach (array_slice($bytes, -4) as $index => $check) {
            if ($check !== $checksum[$index]) {
                throw new \RuntimeException('Checksum mismatch! Expected ' . $checksum[$index] . ', got ' . $check . ' at index ' . $index);
            }
        }
        return [ 'prefix' => $prefixBytes, 'data' => $dataBytes ];
    }
}
