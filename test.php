<?php 

require_once __DIR__ . '/vendor/autoload.php';

// composer require ramsey/uuid
// composer require symfony/uid
// composer update --optimize-autoloader

// php -dextension=modules/uuidv7.so -r "echo uuidv7(), PHP_EOL;"
// php -dextension=modules/uuidv7.so test.php

/*
echo \Ramsey\Uuid\Uuid::uuid7()->toString() . "\n";
echo vsprintf('%s%s%s%s-%s%s-%s%s-%s%s-%s%s%s%s%s%s', str_split(bin2hex(uuidv7_antonz()), 2)) . "\n";
echo uuidv7_gtp_o3() . "\n";
echo uuidv7_gtp_o3_v2() . "\n";
echo uuidv7_gpt_4o() . "\n";
echo uuidv7_xhit_php() . "\n";
echo UuidV7::generate() . "\n";
echo uuidv7(), PHP_EOL;
*/
$start = microtime(true);

//$unique = [];

for ($i = 0; $i < 4_000_000; $i++) {
    //$uuid = \Ramsey\Uuid\Uuid::uuid7()->toString(); // 4.03 sec, 4 MB
    $uuid = uuidv7_xhit();        // 1 sec, 4 MB
    //$uuid = uuidv7_xhit_php();        // 3 sec, 4 MB
    //$uuid = vsprintf('%s%s%s%s-%s%s-%s%s-%s%s-%s%s%s%s%s%s', str_split(bin2hex(uuidv7_antonz()), 2)); // 1.69 sec, 350 MB
    //$uuid = UuidV7::generate();   // 1.93 sec, 120 MB
    //$uuid = uuidv7_gtp_o3();      // 1.06 sec, 104 MB
    //$uuid = uuidv7_gtp_o3_v2();   // 2.02 sec, 350 MB
    //$uuid = uuidv7_gpt_4o();      // 1.13 sec, 104 MB

    if (isset($unique[$uuid])) {
        echo $uuid . ' not unique!!!';
        exit;
    }
    $unique[$uuid] = 1;

    // echo $uuid . PHP_EOL;
    if (!preg_match("/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[8-9a-b][0-9a-f]{3}-[0-9a-f]{12}$/m", $uuid)) {
        echo $uuid . ' not valid uuidv7';
        exit;
    }
}

$end = microtime(true);
echo "Execution time: " . round($end - $start, 2) . " seconds\n";
echo "Memory used: " . memory_get_peak_usage(true) / 1024 / 1024 . " MB\n\n";

function uuidv7_gpt_4o(): string
{
    // 1. Timestamp in milliseconds (48 bits = 12 hex chars)
    $timestampMs = (int)(microtime(true) * 1000);
    $timestampHex = str_pad(dechex($timestampMs), 12, '0', STR_PAD_LEFT);

    // 2. Generate 10 bytes (80 bits) of randomness
    $random = random_bytes(10);
    $randomHex = bin2hex($random);

    // 3. Split into required fields:
    $time_low = substr($timestampHex, 0, 8); // first 32 bits
    $time_mid = substr($timestampHex, 8, 4); // next 16 bits

    // 4. version 7: set high nibble of 3rd block to 0111
    $rand_a = substr($randomHex, 0, 4); // 16 bits
    $rand_a_int = hexdec($rand_a);
    $rand_a_int = ($rand_a_int & 0x0fff) | 0x7000; // set version (4 highest bits) to 0111
    $time_hi_and_version = str_pad(dechex($rand_a_int), 4, '0', STR_PAD_LEFT);

    // 5. variant field (RFC 4122): set two highest bits to 10
    $rand_b = substr($randomHex, 4, 4); // 16 bits
    $rand_b_int = hexdec($rand_b);
    $rand_b_int = ($rand_b_int & 0x3fff) | 0x8000; // set variant (2 highest bits) to 10
    $clock_seq = str_pad(dechex($rand_b_int), 4, '0', STR_PAD_LEFT);

    // 6. final node = remaining 12 hex digits (48 bits)
    $node = substr($randomHex, 8, 12);

    // 7. assemble
    return sprintf(
        '%s-%s-%s-%s-%s',
        $time_low,
        $time_mid,
        $time_hi_and_version,
        $clock_seq,
        $node
    );
}

// https://antonz.org/uuidv7/#php
function uuidv7_antonz()
{
    // random bytes
    $value = random_bytes(16);

    // current timestamp in ms
    $timestamp = intval(microtime(true) * 1000);

    // timestamp
    $value[0] = chr(($timestamp >> 40) & 0xFF);
    $value[1] = chr(($timestamp >> 32) & 0xFF);
    $value[2] = chr(($timestamp >> 24) & 0xFF);
    $value[3] = chr(($timestamp >> 16) & 0xFF);
    $value[4] = chr(($timestamp >> 8) & 0xFF);
    $value[5] = chr($timestamp & 0xFF);

    // version and variant
    $value[6] = chr((ord($value[6]) & 0x0F) | 0x70);
    $value[8] = chr((ord($value[8]) & 0x3F) | 0x80);

    return $value;
}

// https://gist.github.com/xhit/83f22ef5e7ab3971f7a35017cc5d31f9
function uuidv7_xhit_php()
{
    // current timestamp in ms
    $timestamp = intval(microtime(true) * 1000);

    return sprintf(
        '%02x%02x%02x%02x-%02x%02x-%04x-%04x-%012x',
        // first 48 bits are timestamp based
        ($timestamp >> 40) & 0xFF,
        ($timestamp >> 32) & 0xFF,
        ($timestamp >> 24) & 0xFF,
        ($timestamp >> 16) & 0xFF,
        ($timestamp >> 8) & 0xFF,
        $timestamp & 0xFF,

        // 16 bits: 4 bits for version (7) and 12 bits for rand_a
        mt_rand(0, 0x0FFF) | 0x7000,

        // 16 bits: 4 bits for variant where 2 bits are fixed 10 and next 2 are random to get (8-9, a-b)
        // next 12 are random
        mt_rand(0, 0x3FFF) | 0x8000,

        // random 48 bits
        mt_rand(0, 0xFFFFFFFFFFFF),
    );
}

/**
 * Fast, low‑overhead UUID v7 generator (RFC 9562 §5.7).
 *
 *  – 1× pack() call for the 48‑bit Unix‑timestamp
 *  – 1× random_bytes(10) for all entropy
 *  – in‑place bit‑twiddling for version & variant
 *  – single bin2hex() + substr() formatting pass
 *
 * @return string RFC 4122 textual UUID (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)
 */
function uuidv7_gtp_o3(): string
{
    /* -------- 48‑bit millisecond timestamp (big‑endian) -------- */
    $nowMs = (int) (microtime(true) * 1000);        // 64‑bit safe
    $ts    = pack('Nn', $nowMs >> 16, $nowMs & 0xFFFF); // 4 B + 2 B = 6 B

    /* -------- 74 bits of cryptographic entropy -------- */
    $rand  = random_bytes(10);                      // bytes 6‑15

    /* -------- inject version (7) and RFC‑4122 variant (10x) ----- */
    $rand[0] = $rand[0] & "\x0F" | "\x70";          // high‑nibble 0111
    $rand[2] = $rand[2] & "\x3F" | "\x80";          // 10xxxxxx

    /* -------- combine & stringify -------- */
    $hex = bin2hex($ts . $rand);                    // 32 chars

    return substr($hex, 0, 8)  . '-' .
        substr($hex, 8, 4)  . '-' .
        substr($hex, 12, 4) . '-' .
        substr($hex, 16, 4) . '-' .
        substr($hex, 20, 12);
}


function uuidv7_gtp_o3_v2(): string
{
    /* ----------------------------------------------------------------
        1) 48‑bit Unix‑epoch timestamp in milliseconds
        ---------------------------------------------------------------- */
    [$usec, $sec] = explode(' ', microtime());               // "µsec sec"
    $ts = ((int)$sec * 1000) + (int)round($usec * 1000);  // int‑safe
    $ts &= 0xFFFFFFFFFFFF;                                    // keep 48 bits

    $tsHex = str_pad(dechex($ts), 12, '0', STR_PAD_LEFT); // 12 hex
    $timeHigh = substr($tsHex, 0, 8);   // 32 bits
    $timeLow = substr($tsHex, 8, 4);   // 16 bits

    /* ----------------------------------------------------------------
       2) 80 random bits → rand_a + rand_b
       ---------------------------------------------------------------- */
    $rand = random_bytes(10);

    // ---- version 7 nibble + 12‑bit rand_a ----
    $randA = unpack('n', substr($rand, 0, 2))[1]; // 2 bytes → uint16
    $randA = ($randA & 0x0FFF) | 0x7000;          // set version to 0111

    // ---- variant 10 + high 14 bits of rand_b ----
    $randBhi = unpack('n', substr($rand, 2, 2))[1];
    $randBhi = ($randBhi & 0x3FFF) | 0x8000;      // set variant to 10

    // ---- remaining 48 bits of rand_b ----
    $randBlo = bin2hex(substr($rand, 4, 6));      // 6 bytes → 12 hex

    /* ----------------------------------------------------------------
       3) Assemble canonical UUID string
       ---------------------------------------------------------------- */

    return sprintf(
        '%s-%s-%04x-%04x-%s',
        $timeHigh,
        $timeLow,
        $randA,
        $randBhi,
        $randBlo
    );
}

class UuidV7
{
    protected const TYPE = 7;

    private static string $time = '';
    private static array $rand = [];
    private static string $seed;
    private static array $seedParts;
    private static int $seedIndex = 0;

    public static function generate(?\DateTimeInterface $time = null): string
    {
        if (null === $mtime = $time) {
            $time = microtime(false);
            $time = substr($time, 11) . substr($time, 2, 3);
        } elseif (0 > $time = $time->format('Uv')) {
            throw new \InvalidArgumentException('The timestamp must be positive.');
        }

        if ($time > self::$time || (null !== $mtime && $time !== self::$time)) {
            randomize:
            self::$rand = unpack('S*', isset(self::$seed) ? random_bytes(10) : self::$seed = random_bytes(16));
            self::$rand[1] &= 0x03FF;
            self::$time = $time;
        } else {
            // Within the same ms, we increment the rand part by a random 24-bit number.
            // Instead of getting this number from random_bytes(), which is slow, we get
            // it by sha512-hashing self::$seed. This produces 64 bytes of entropy,
            // which we need to split in a list of 24-bit numbers. unpack() first splits
            // them into 16 x 32-bit numbers; we take the first byte of each of these
            // numbers to get 5 extra 24-bit numbers. Then, we consume those numbers
            // one-by-one and run this logic every 21 iterations.
            // self::$rand holds the random part of the UUID, split into 5 x 16-bit
            // numbers for x86 portability. We increment this random part by the next
            // 24-bit number in the self::$seedParts list and decrement self::$seedIndex.

            if (!self::$seedIndex) {
                $s = unpack(\PHP_INT_SIZE >= 8 ? 'L*' : 'l*', self::$seed = hash('sha512', self::$seed, true));
                $s[] = ($s[1] >> 8 & 0xFF0000) | ($s[2] >> 16 & 0xFF00) | ($s[3] >> 24 & 0xFF);
                $s[] = ($s[4] >> 8 & 0xFF0000) | ($s[5] >> 16 & 0xFF00) | ($s[6] >> 24 & 0xFF);
                $s[] = ($s[7] >> 8 & 0xFF0000) | ($s[8] >> 16 & 0xFF00) | ($s[9] >> 24 & 0xFF);
                $s[] = ($s[10] >> 8 & 0xFF0000) | ($s[11] >> 16 & 0xFF00) | ($s[12] >> 24 & 0xFF);
                $s[] = ($s[13] >> 8 & 0xFF0000) | ($s[14] >> 16 & 0xFF00) | ($s[15] >> 24 & 0xFF);
                self::$seedParts = $s;
                self::$seedIndex = 21;
            }

            self::$rand[5] = 0xFFFF & $carry = self::$rand[5] + 1 + (self::$seedParts[self::$seedIndex--] & 0xFFFFFF);
            self::$rand[4] = 0xFFFF & $carry = self::$rand[4] + ($carry >> 16);
            self::$rand[3] = 0xFFFF & $carry = self::$rand[3] + ($carry >> 16);
            self::$rand[2] = 0xFFFF & $carry = self::$rand[2] + ($carry >> 16);
            self::$rand[1] += $carry >> 16;

            if (0xFC00 & self::$rand[1]) {
                if (\PHP_INT_SIZE >= 8 || 10 > \strlen($time = self::$time)) {
                    $time = (string)(1 + $time);
                } elseif ('999999999' === $mtime = substr($time, -9)) {
                    $time = (1 + substr($time, 0, -9)) . '000000000';
                } else {
                    $time = substr_replace($time, str_pad(++$mtime, 9, '0', \STR_PAD_LEFT), -9);
                }

                goto randomize;
            }

            $time = self::$time;
        }

        if (\PHP_INT_SIZE >= 8) {
            $time = dechex($time);
        } else {
            $time = bin2hex(BinaryUtil::fromBase($time, BinaryUtil::BASE10));
        }

        return substr_replace(
            \sprintf(
                '%012s-%04x-%04x-%04x%04x%04x',
                $time,
                0x7000 | (self::$rand[1] << 2) | (self::$rand[2] >> 14),
                0x8000 | (self::$rand[2] & 0x3FFF),
                self::$rand[3],
                self::$rand[4],
                self::$rand[5],
            ),
            '-',
            8,
            0
        );
    }
}
