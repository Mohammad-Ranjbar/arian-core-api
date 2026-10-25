<?php

namespace Tests\Unit;

use App\Services\RawPrinterService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RawPrinterServiceTest extends TestCase
{
    public function test_entire_file_is_written_and_socket_is_closed(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'printer-test-');
        $content = str_repeat('test-pdf-data', 10000);
        file_put_contents($path, $content);

        // A real socket pair exercises writes, including payloads larger than one chunk.
        [$sender, $receiver] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $service = new class($sender) extends RawPrinterService
        {
            public function __construct(public $socket) {}

            protected function connect(string $ip)
            {
                return $this->socket;
            }
        };

        $pid = pcntl_fork();
        if ($pid === 0) {
            fclose($sender);
            $received = stream_get_contents($receiver);
            fclose($receiver);
            exit($received === $content ? 0 : 1);
        }

        fclose($receiver);
        try {
            $service->sendFile('local-test', $path);
            $this->assertFalse(is_resource($sender));
            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
        } finally {
            unlink($path);
        }
    }

    public function test_closed_printer_socket_fails_and_is_closed(): void
    {
        [$sender, $receiver] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        fclose($receiver);
        $path = tempnam(sys_get_temp_dir(), 'printer-test-');
        file_put_contents($path, '%PDF-test');
        $service = new class($sender) extends RawPrinterService
        {
            public function __construct(private $socket) {}

            protected function connect(string $ip)
            {
                return $this->socket;
            }
        };

        // PHP emits an expected broken-pipe notice before returning false/zero.
        set_error_handler(static fn (int $severity, string $message): bool => str_contains($message, 'fwrite()'));
        try {
            $service->sendFile('local-test', $path);
            $this->fail('A disconnected printer must fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('ارسال کامل فایل به چاپگر انجام نشد.', $exception->getMessage());
            $this->assertFalse(is_resource($sender));
        } finally {
            restore_error_handler();
            unlink($path);
        }
    }

    public function test_connected_printer_that_does_not_read_cannot_hold_the_worker_forever(): void
    {
        [$sender, $receiver] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $path = tempnam(sys_get_temp_dir(), 'printer-stall-');
        file_put_contents($path, str_repeat('x', 8 * 1024 * 1024));
        $service = new class($sender) extends RawPrinterService
        {
            public function __construct(private $socket) {}

            protected function connect(string $ip)
            {
                return $this->socket;
            }
        };

        $started = microtime(true);
        try {
            $service->sendFile('local-test', $path);
            $this->fail('The unresponsive printer must time out.');
        } catch (RuntimeException $exception) {
            $this->assertLessThan(13, microtime(true) - $started);
            $this->assertFalse(is_resource($sender));
        } finally {
            fclose($receiver);
            unlink($path);
        }
    }
}
