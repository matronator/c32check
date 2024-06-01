<?php

use Matronator\C32check\Address;
use Matronator\C32check\Checksum;
use Matronator\C32check\Encoding;
use PHPUnit\Framework\TestCase;

class C32ConvertTest extends TestCase
{
    public function testC32Address()
    {
        $hexStrings = [
            'a46ff88886c2ef9762d970b4d2c63678835bd39d',
            '0000000000000000000000000000000000000000',
            '0000000000000000000000000000000000000001',
            '1000000000000000000000000000000000000001',
            '1000000000000000000000000000000000000000',
        ];

        $versions = [22, 0, 31, 20, 26, 21];

        $c32addresses = [
            [
                'SP2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKNRV9EJ7',
                'SP000000000000000000002Q6VF78',
                'SP00000000000000000005JA84HQ',
                'SP80000000000000000000000000000004R0CMNV',
                'SP800000000000000000000000000000033H8YKK',
            ],
            [
                'S02J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKPVKG2CE',
                'S0000000000000000000002AA028H',
                'S000000000000000000006EKBDDS',
                'S080000000000000000000000000000007R1QC00',
                'S080000000000000000000000000000003ENTGCQ',
            ],
            [
                'SZ2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKQ9H6DPR',
                'SZ000000000000000000002ZE1VMN',
                'SZ00000000000000000005HZ3DVN',
                'SZ80000000000000000000000000000004XBV6MS',
                'SZ800000000000000000000000000000007VF5G0',
            ],
            [
                'SM2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKQVX8X0G',
                'SM0000000000000000000062QV6X',
                'SM00000000000000000005VR75B2',
                'SM80000000000000000000000000000004WBEWKC',
                'SM80000000000000000000000000000000JGSYGV',
            ],
            [
                'ST2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKQYAC0RQ',
                'ST000000000000000000002AMW42H',
                'ST000000000000000000042DB08Y',
                'ST80000000000000000000000000000006BYJ4R4',
                'ST80000000000000000000000000000002YBNPV3',
            ],
            [
                'SN2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKP6D2ZK9',
                'SN000000000000000000003YDHWKJ',
                'SN00000000000000000005341MC8',
                'SN800000000000000000000000000000066KZWY0',
                'SN800000000000000000000000000000006H75AK',
            ],
        ];


        foreach ($hexStrings as $i => $hex) {
            foreach ($versions as $j => $version) {
                $address = new Address($version, $hex);
                $expectedAddress = $c32addresses[$j][$i];
                $generatedAddress = $address->c32address($version, $hex);
                $this->assertEquals($expectedAddress, $generatedAddress, "c32address version=$version $hex: expect $expectedAddress, got $generatedAddress");

                list($decodedVersion, $decodedHex) = $address->c32addressDecode($generatedAddress);
                $paddedExpectedHex = strlen($hex) % 2 !== 0 ? "0$hex" : $hex;

                $this->assertEquals($version, $decodedVersion, "c32addressDecode $generatedAddress: expect ver $version, got $decodedVersion");
                $this->assertEquals($paddedExpectedHex, $decodedHex, "c32addressDecode $generatedAddress: expect hex $paddedExpectedHex, got $decodedHex");
            }
        }
    }

    public function testC32AddressInvalidInput()
    {
        $address = new Address(-1, '');
        $invalidInputs = [
            function () use ($address) {
                $address->c32address(-1, 'a46ff88886c2ef9762d970b4d2c63678835bd39d');
            },
            function () use ($address) {
                $address->c32address(32, 'a46ff88886c2ef9762d970b4d2c63678835bd39d');
            },
            function () use ($address) {
                $address->c32address(5, 'a46ff88886c2ef9762d970b4d2c63678835bd39d00');
            },
            function () use ($address) {
                $address->c32address(5, 'a46ff88886c2ef9762d970b4d2c63678835bd3');
            },
            function () use ($address) {
                $address->c32address(5, 'a46ff88886c2ef9762d970b4d2c63678835bd39d0');
            },
            function () use ($address) {
                $address->c32address(5, 'a46ff88886c2ef9762d970b4d2c63678835bd39');
            },
        ];

        foreach ($invalidInputs as $index => $invalid) {
            try {
                $invalid();
                $this->fail("parsed invalid input");
            } catch (Exception $e) {
                $this->assertTrue(true, "invalid input case $index");
            }
        }

        $invalidDecodes = [
            function () use ($address) {
                $address->c32addressDecode('ST2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKQYAC0RQ0');
            },
            function () use ($address) {
                $address->c32addressDecode('ST2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKQYAC0RR');
            },
            function () use ($address) {
                $address->c32addressDecode('ST2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKQYAC0R');
            },
            function () use ($address) {
                $address->c32addressDecode('ST2J');
            },
            function () use ($address) {
                $address->c32addressDecode('bP2CT665Q0JB7P39TZ7BST0QYCAQSMJWBZK8QT35J');
            },
        ];

        foreach ($invalidDecodes as $index => $invalidDecode) {
            try {
                $invalidDecode();
                $this->fail("decoded invalid address");
            } catch (Exception $e) {
                $this->assertTrue(true, "invalid address decode case $index");
            }
        }
    }

    // public function testC32ToB58AndB58ToC32()
    // {
    //     $hexStrings = [
    //         'a46ff88886c2ef9762d970b4d2c63678835bd39d',
    //         '0000000000000000000000000000000000000000',
    //         '0000000000000000000000000000000000000001',
    //         '1000000000000000000000000000000000000001',
    //         '1000000000000000000000000000000000000000',
    //     ];

    //     $versions = [22, 0, 31, 20, 26, 21];

    //     $c32addresses = [
    //         [
    //             'SP2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKNRV9EJ7',
    //             'SP000000000000000000002Q6VF78',
    //             'SP00000000000000000005JA84HQ',
    //             'SP80000000000000000000000000000004R0CMNV',
    //             'SP800000000000000000000000000000033H8YKK',
    //         ],
    //         [
    //             'S02J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKPVKG2CE',
    //             'S0000000000000000000002AA028H',
    //             'S000000000000000000006EKBDDS',
    //             'S080000000000000000000000000000007R1QC00',
    //             'S080000000000000000000000000000003ENTGCQ',
    //         ],
    //         [
    //             'SZ2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKQ9H6DPR',
    //             'SZ000000000000000000002ZE1VMN',
    //             'SZ00000000000000000005HZ3DVN',
    //             'SZ80000000000000000000000000000004XBV6MS',
    //             'SZ800000000000000000000000000000007VF5G0',
    //         ],
    //         [
    //             'SM2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKQVX8X0G',
    //             'SM0000000000000000000062QV6X',
    //             'SM00000000000000000005VR75B2',
    //             'SM80000000000000000000000000000004WBEWKC',
    //             'SM80000000000000000000000000000000JGSYGV',
    //         ],
    //         [
    //             'ST2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKQYAC0RQ',
    //             'ST000000000000000000002AMW42H',
    //             'ST000000000000000000042DB08Y',
    //             'ST80000000000000000000000000000006BYJ4R4',
    //             'ST80000000000000000000000000000002YBNPV3',
    //         ],
    //         [
    //             'SN2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKP6D2ZK9',
    //             'SN000000000000000000003YDHWKJ',
    //             'SN00000000000000000005341MC8',
    //             'SN800000000000000000000000000000066KZWY0',
    //             'SN800000000000000000000000000000006H75AK',
    //         ],
    //     ];

    //     $btcaddresses = [
    //         [
    //             'A7RjcihhakxJfAqgwTVsLTyc8kbhDJPMVY',
    //             '9rSGfPZLcyCGzY4uYEL1fkzJr6fkicS2rs',
    //             '9rSGfPZLcyCGzY4uYEL1fkzJr6fkoGa2eS',
    //             '9stsUTaRHnyTRFWnbwiyCWwfpkkKCFYBD4',
    //             '9stsUTaRHnyTRFWnbwiyCWwfpkkK9ZxEPC',
    //         ],
    //         [
    //             '1FzTxL9Mxnm2fdmnQEArfhzJHevwbvcH6d',
    //             '1111111111111111111114oLvT2',
    //             '11111111111111111111BZbvjr',
    //             '12Tbp525fpnBRiSt4iPxXkxMyf5Ze1UeZu',
    //             '12Tbp525fpnBRiSt4iPxXkxMyf5ZWzA5TC',
    //         ],
    //         [
    //             'DjUAUhPHyP8C256UAEVjhbRgoHvBetzPRR',
    //             'DUUhXNEw1bNAMSKgm1Kt2tSPWdzF8952Np',
    //             'DUUhXNEw1bNAMSKgm1Kt2tSPWdzFCMncsE',
    //             'DVwJLSG1gR9Ln9mZpiiqZePkVJ4obdg7UC',
    //             'DVwJLSG1gR9Ln9mZpiiqZePkVJ4oTzMnyD',
    //         ],
    //         [
    //             '9JkXeW78AQ2Z2JZWtcqENDS2sk5orG4ggw',
    //             '93m4hAxmCcGXMfnjVPfNhWSjb69sDziGSY',
    //             '93m4hAxmCcGXMfnjVPfNhWSjb69sPHPDTX',
    //             '95DfWEyqsS3hnPEcZ74LEGQ6ZkERn1FuUo',
    //             '95DfWEyqsS3hnPEcZ74LEGQ6ZkERexa3xe',
    //         ],
    //         [
    //             'Bin9Z9trRUoovuQ338q9Gy4kemdU7ni2FG',
    //             'BTngbpkVTh3nGGdFdufHcG5TN7hXYuX31z',
    //             'BTngbpkVTh3nGGdFdufHcG5TN7hXbks9tq',
    //             'BVFHQtma8Wpxgz58hd4F922pLmn65qtPy5',
    //             'BVFHQtma8Wpxgz58hd4F922pLmn5zEwasC',
    //         ],
    //         [
    //             '9i68dcQQsaVRqjhbv3AYrLhpWFLkWkzrCG',
    //             '9T6fgHG3unjQB6vpWozhBdiXDbQp3P7F8M',
    //             '9T6fgHG3unjQB6vpWozhBdiXDbQp5FwEH5',
    //             '9UZGVMH8acWabpNhaXPeiPftCFVNXQAYoZ',
    //             '9UZGVMH8acWabpNhaXPeiPftCFVNMacQDQ',
    //         ],
    //     ];

    //     $equivalentVersions = [22, 20, 26, 21];

    //     $c32addressesEquivalentVersion = [
    //         [
    //             'SP2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKNRV9EJ7',
    //             'SP000000000000000000002Q6VF78',
    //             'SP00000000000000000005JA84HQ',
    //             'SP80000000000000000000000000000004R0CMNV',
    //             'SP800000000000000000000000000000033H8YKK',
    //         ],
    //         [
    //             'SM2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKQVX8X0G',
    //             'SM0000000000000000000062QV6X',
    //             'SM00000000000000000005VR75B2',
    //             'SM80000000000000000000000000000004WBEWKC',
    //             'SM80000000000000000000000000000000JGSYGV',
    //         ],
    //         [
    //             'ST2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKQYAC0RQ',
    //             'ST000000000000000000002AMW42H',
    //             'ST000000000000000000042DB08Y',
    //             'ST80000000000000000000000000000006BYJ4R4',
    //             'ST80000000000000000000000000000002YBNPV3',
    //         ],
    //         [
    //             'SN2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKP6D2ZK9',
    //             'SN000000000000000000003YDHWKJ',
    //             'SN00000000000000000005341MC8',
    //             'SN800000000000000000000000000000066KZWY0',
    //             'SN800000000000000000000000000000006H75AK',
    //         ],
    //     ];

    //     $b58addressesEquivalentVersion = [
    //         [
    //             '1FzTxL9Mxnm2fdmnQEArfhzJHevwbvcH6d',
    //             '1111111111111111111114oLvT2',
    //             '11111111111111111111BZbvjr',
    //             '12Tbp525fpnBRiSt4iPxXkxMyf5Ze1UeZu',
    //             '12Tbp525fpnBRiSt4iPxXkxMyf5ZWzA5TC',
    //         ],
    //         [
    //             '3GgUssdoWh5QkoUDXKqT6LMESBDf8aqp2y',
    //             '31h1vYVSYuKP6AhS86fbRdMw9XHieotbST',
    //             '31h1vYVSYuKP6AhS86fbRdMw9XHiiQ93Mb',
    //             '339cjcWXDj6ZWt9KBp4YxPKJ8BNH7gn2Nw',
    //             '339cjcWXDj6ZWt9KBp4YxPKJ8BNH14Nnx4',
    //         ],
    //         [
    //             'mvWRFPELmpCHSkFQ7o9EVdCd9eXeUTa9T8',
    //             'mfWxJ45yp2SFn7UciZyNpvDKrzbhyfKrY8',
    //             'mfWxJ45yp2SFn7UciZyNpvDKrzbi36LaVX',
    //             'mgyZ7874UrDSCpvVnHNLMgAgqegGZBks3w',
    //             'mgyZ7874UrDSCpvVnHNLMgAgqegGQUXx9c',
    //         ],
    //         [
    //             '2N8EgwcZq89akxb6mCTTKiHLVeXRpxjuy98',
    //             '2MsFDzHRUAMpjHxKyoEHU3aMCMsVtMqs1PV',
    //             '2MsFDzHRUAMpjHxKyoEHU3aMCMsVtXMsfu8',
    //             '2MthpoMSYqBbuifmrrwgRaLJZLXaSyK2Rai',
    //             '2MthpoMSYqBbuifmrrwgRaLJZLXaSoxBM5T',
    //         ],
    //     ];

    //     foreach ($hexStrings as $i => $hexString) {
    //         foreach ($versions as $j => $version) {
    //             $address = new Address($version, $hexString);

    //             $expectedB58 = $btcaddresses[$j][$i];
    //             $generatedB58 = $address->c32ToB58($c32addresses[$j][$i], $versions[$j]);
    //             $this->assertEquals($expectedB58, $generatedB58, "c32ToB58 {$c32addresses[$j][$i]}: expect $expectedB58, got $generatedB58");

    //             $expectedC32 = $c32addresses[$j][$i];
    //             $generatedC32 = $address->b58ToC32($btcaddresses[$j][$i], $versions[$j]);
    //             $this->assertEquals($expectedC32, $generatedC32, "b58ToC32 {$btcaddresses[$j][$i]}: expect $expectedC32, got $generatedC32");
    //         }
    //     }

    //     foreach ($hexStrings as $i => $hexString) {
    //         foreach ($equivalentVersions as $j => $version) {
    //             $address = new Address($version, $hexString);

    //             $expectedB58 = $b58addressesEquivalentVersion[$j][$i];
    //             $generatedB58 = $address->c32ToB58($c32addressesEquivalentVersion[$j][$i], $version);
    //             $this->assertEquals($expectedB58, $generatedB58, "c32ToB58 eq {$c32addressesEquivalentVersion[$j][$i]}: expect $expectedB58, got $generatedB58");

    //             $expectedC32 = $c32addressesEquivalentVersion[$j][$i];
    //             $generatedC32 = $address->b58ToC32($b58addressesEquivalentVersion[$j][$i], $version);
    //             $this->assertEquals($expectedC32, $generatedC32, "b58ToC32 eq {$b58addressesEquivalentVersion[$j][$i]}: expect $expectedC32, got $generatedC32");
    //         }
    //     }
    // }

    // public function testReadmeExamplesWithLegacyBuffer()
    // {
    //     // ## c32encode, c32decode
    //     $this->assertEquals(Encoding::c32encode(bin2hex('hello world')), '38CNP6RVS0EXQQ4V34');
    //     $this->assertEquals(Encoding::c32decode('38CNP6RVS0EXQQ4V34'), '68656c6c6f20776f726c64');
    //     $this->assertEquals(hex2bin('68656c6c6f20776f726c64'), 'hello world');

    //     // ## c32checkEncode, c32checkDecode
    //     $version = 12;
    //     $this->assertEquals(Checksum::c32checkEncode($version, bin2hex('hello world')), 'CD1JPRV3F41VPYWKCCGRMASC8');
    //     list($decodedVersion, $decodedHex) = Checksum::c32checkDecode('CD1JPRV3F41VPYWKCCGRMASC8');
    //     $this->assertEquals($decodedVersion, 12);
    //     $this->assertEquals($decodedHex, '68656c6c6f20776f726c64');
    //     $this->assertEquals(hex2bin('68656c6c6f20776f726c64'), 'hello world');

    //     // ## c32address, c32addressDecode
    //     $version = 22;
    //     $hash160 = 'a46ff88886c2ef9762d970b4d2c63678835bd39d';
    //     $address = new Address($version, $hash160);
    //     $this->assertEquals(22, $version); // Assuming you have c32check.versions.mainnet.p2pkh equivalent in PHP
    //     $this->assertEquals($address->c32address($version, $hash160), 'SP2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKNRV9EJ7');
    //     list($decodedVersion, $decodedHash160) = $address->c32addressDecode('SP2J6ZY48GV1EZ5V2V5RB9MP66SW86PYKKNRV9EJ7');
    //     $this->assertEquals($decodedVersion, $version);
    //     $this->assertEquals($decodedHash160, $hash160);

    //     // ## c32ToB58, b58ToC32
    //     $b58addr = '16EMaNw3pkn3v6f2BgnSSs53zAKH4Q8YJg';
    //     $this->assertEquals($address->b58ToC32($b58addr), 'SPWNYDJ3STG7XH7ERWXMV6MQ7Q6EATWVY5Q1QMP8');
    //     $this->assertEquals($address->c32ToB58('SPWNYDJ3STG7XH7ERWXMV6MQ7Q6EATWVY5Q1QMP8'), $b58addr);

    //     $b58addr = '3D2oetdNuZUqQHPJmcMDDHYoqkyNVsFk9r';
    //     $this->assertEquals($address->b58ToC32($b58addr), 'SM1Y6EXF21RZ9739DFTEQKB1H044BMM0XVCM4A4NY');
    //     $this->assertEquals($address->c32ToB58('SM1Y6EXF21RZ9739DFTEQKB1H044BMM0XVCM4A4NY'), $b58addr);
    // }
}
