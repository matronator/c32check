<?php

declare(strict_types=1);

namespace Matronator\C32check;

class Checksum
{
    /**
     * Get the c32check checksum of a hex-encoded string
     * @param string $dataHex - the hex string
     * @return string - the c32 checksum, as a bin-encoded string
     */
    public static function c32checksum($dataHex) {
        $dataHash = Utils::sha256(Utils::sha256(Utils::hexToBytes($dataHex)));
        $checksum = Utils::bytesToHex(array_slice($dataHash, 0, 4));
        return $checksum;
    }

    /**
     * Encode a hex string as a c32check string. This is a lot like how
     * base58check works in Bitcoin-land, but this algorithm uses the
     * z-base-32 alphabet instead of the base58 alphabet. The algorithm
     * is as follows:
     * * calculate the c32checksum of version + data
     * * c32encode version + data + c32checksum
     * @param int $version - the version string (between 0 and 31)
     * @param string $data - the data to encode
     * @return string - the c32check representation
     */
    public static function c32checkEncode(int $version, string $data): string
    {
        if ($version < 0 || $version >= 32) {
            throw new \InvalidArgumentException('Invalid version (must be between 0 and 31)');
        }
        if (!preg_match('/^[0-9a-fA-F]*$/', $data)) {
            throw new \InvalidArgumentException('Invalid data (not a hex string)');
        }

        $data = strtolower($data);
        if (strlen($data) % 2 !== 0) {
            $data = '0' . $data;
        }

        $versionHex = dechex($version);
        if (strlen($versionHex) === 1) {
            $versionHex = '0' . $versionHex;
        }

        $checksumHex = static::c32checksum($versionHex . $data);
        $c32str = Encoding::c32encode($data . $checksumHex);
        return Encoding::C32[$version] . $c32str;
    }

    /**
     * Decode a c32check string back into its version and data payload. This is
     * a lot like how base58check works in Bitcoin-land, but this algorithm uses
     * the z-base-32 alphabet instead of the base58 alphabet. The algorithm
     * is as follows:
     * * extract the version, data, and checksum
     * * verify the checksum matches c32checksum(version + data)
     * * return data
     * @param string $c32data - the c32check-encoded string
     * @return array - [version (int), data (string)]. The returned data
     * will be a hex string. Throws an exception if the checksum does not match.
     */
    public static function c32checkDecode(string $c32data): array
    {
        $c32data = Encoding::c32normalize($c32data);
        $dataHex = Encoding::c32decode(substr($c32data, 1));
        $versionChar = $c32data[0];
        $version = strpos(Encoding::C32, $versionChar);
        $checksum = substr($dataHex, -8);

        $versionHex = dechex($version);
        if (strlen($versionHex) === 1) {
            $versionHex = '0' . $versionHex;
        }

        if (static::c32checksum($versionHex . substr($dataHex, 0, -8)) !== $checksum) {
            throw new \InvalidArgumentException('Invalid c32check string: checksum mismatch');
        }

        return [$version, substr($dataHex, 0, -8)];
    }

}
