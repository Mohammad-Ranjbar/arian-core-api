<?php

namespace App\Services;

use RuntimeException;

class RawPrinterService
{
    public function sendFile(string $ip, string $path): void
    {
        $pdf = file_get_contents($path);

        if ($pdf === false) {
            throw new RuntimeException('خواندن فایل چاپ امکان‌پذیر نیست.');
        }

        $socket = $this->connect($ip);

        try {
            stream_set_blocking($socket, false);
            // Bound the entire write, including a printer that accepts data very slowly.
            $deadline = microtime(true) + 10;
            $length = strlen($pdf);
            $offset = 0;

            while ($offset < $length) {
                $remaining = $deadline - microtime(true);

                if ($remaining <= 0) {
                    throw new RuntimeException('زمان ارسال فایل به چاپگر به پایان رسید.');
                }

                $read = null;
                $write = [$socket];
                $except = null;
                $seconds = (int) $remaining;
                $ready = @stream_select($read, $write, $except, $seconds, (int) (($remaining - $seconds) * 1000000));

                if ($ready === 0) {
                    throw new RuntimeException('زمان ارسال فایل به چاپگر به پایان رسید.');
                }

                if ($ready === false) {
                    throw new RuntimeException('ارتباط با چاپگر قطع شد.');
                }

                $written = @fwrite($socket, substr($pdf, $offset, 65536));

                if ($written === false || $written === 0) {
                    throw new RuntimeException('ارسال کامل فایل به چاپگر انجام نشد.');
                }

                $offset += $written;
            }
        } finally {
            fclose($socket);
        }
    }

    /** @return resource */
    protected function connect(string $ip)
    {
        $socket = @fsockopen($ip, 9100, $errno, $errstr, 5);

        if ($socket === false) {
            throw new RuntimeException("Printer connection failed to {$ip}:9100 ({$errno})");
        }

        return $socket;
    }
}
