<?php

declare(strict_types=1);

namespace Matronator\C32check;

class Utils
{
    public static function hexToBytes(string $hex): array
    {
        // Remove any leading 0x if present
        if (strpos($hex, '0x') === 0) {
            $hex = substr($hex, 2);
        }
    
        // Ensure the hex string has an even length
        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }
    
        // Convert hex string to bytes
        $bytes = [];
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $bytes[] = hexdec(substr($hex, $i, 2));
        }
    
        return $bytes;
    }

    public static function bytesToHex(array $bytes)
    {
        return implode('', array_map(function($byte) {
            return str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
        }, $bytes));
    }

    /**
     * Compute the SHA-256 hash of the given data.
     * @param string|array $data - the data to hash, either as a string or an array of bytes
     * @return array - the SHA-256 hash as an array of bytes
     */
    public static function sha256(string|array $data): array
    {
        if (is_array($data)) {
            // Convert byte array to a binary string
            $data = implode(array_map("chr", $data));
        }
        // Compute the SHA-256 hash
        $hash = hash('sha256', $data, true);
        // Convert the binary string to an array of bytes
        return array_values(unpack('C*', $hash));
    }
}
