<?php

declare(strict_types=1);

use Matronator\C32check\Encoding;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    public function testC32encoding() {
        $hexStrings = [
            'a46ff88886c2ef9762d970b4d2c63678835bd39d',
            '',
            '0000000000000000000000000000000000000000',
            '0000000000000000000000000000000000000001',
            '1000000000000000000000000000000000000001',
            '1000000000000000000000000000000000000000',
            '1',
            '22',
            '001',
            '0001',
            '00001',
            '000001',
            '0000001',
            '00000001',
            '10',
            '100',
            '1000',
            '10000',
            '100000',
            '1000000',
            '10000000',
            '100000000',
        ];

        $c32minLengths = [
            null,
            null,
            20,
            20,
            32,
            32,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        ];

        $c32Strings = [
            'MHQZH246RBQSERPSE2TD5HHPF21NQMWX',
            '',
            '00000000000000000000',
            '00000000000000000001',
            '20000000000000000000000000000001',
            '20000000000000000000000000000000',
            '1',
            '12',
            '01',
            '01',
            '001',
            '001',
            '0001',
            '0001',
            'G',
            '80',
            '400',
            '2000',
            '10000',
            'G0000',
            '800000',
            '4000000',
        ];

        $hexMinLengths = [
            null,
            null,
            20,
            20,
            20,
            20,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        ];

        foreach ($hexStrings as $i => $hexString) {
            $z = Encoding::c32encode(strtolower($hexString), $c32minLengths[$i]);
            $this->assertEquals($c32Strings[$i], $z, "c32encode: expected {$c32Strings[$i]}, got {$z}");

            $zPadded = Encoding::c32encode(strtolower($hexString), strlen($z) + 5);
            $this->assertEquals("00000{$c32Strings[$i]}", $zPadded, "c32encode padded: expected 00000{$c32Strings[$i]}, got {$zPadded}");

            $zNoLength = Encoding::c32encode(strtoupper($hexString));
            $this->assertEquals($c32Strings[$i], $zNoLength, "c32encode length deduced: expected {$c32Strings[$i]}, got {$zNoLength}");
        }

        foreach ($c32Strings as $i => $c32String) {
            $h = Encoding::c32decode($c32String, $hexMinLengths[$i]);
            $paddedHexString = strlen($hexStrings[$i]) % 2 === 0 ? $hexStrings[$i] : "0{$hexStrings[$i]}";
            $this->assertEquals($paddedHexString, $h, "c32decode: expected {$paddedHexString}, got {$h}");

            $hPadded = Encoding::c32decode($c32String, strlen($h) / 2 + 5);
            $this->assertEquals("0000000000{$paddedHexString}", $hPadded, "c32decode padded: expected 0000000000{$paddedHexString}, got {$hPadded}");

            $hNoLength = Encoding::c32decode($c32String);
            $this->assertEquals($paddedHexString, $hNoLength, "c32decode length deduced: expected {$paddedHexString}, got {$hNoLength}");
        }
    }

    public function testC32encodingRandomBytes() {
        $testData = json_decode(file_get_contents(__DIR__ . '/data/random.json'), true);

        foreach ($testData as $data) {
            $actualC32 = Encoding::c32encode($data['hex'], strlen($data['c32']));
            $expectedC32 = $data['c32'];
            if (strlen($actualC32) === strlen($expectedC32) + 1) {
                $this->assertEquals("0{$expectedC32}", $actualC32, 'Should match test data from external library.');
            } else {
                $this->assertEquals($expectedC32, $actualC32, 'Should match test data from external library.');
            }
        }

        foreach ($testData as $data) {
            $actualHex = Encoding::c32decode($data['c32'], strlen($data['hex']) / 2);
            $expectedHex = $data['hex'];
            $this->assertEquals($expectedHex, $actualHex, 'Should match test hex data from external library.');
            if ($actualHex !== $expectedHex) {
                throw new Exception('FAILING FAST HERE');
            }
        }
    }

}
