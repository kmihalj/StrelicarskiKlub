<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\RawMessage;

class ImapSentFolderService
{
    /**
     * Pokušava spremiti poslanu poruku u IMAP "Sent" folder.
     * Ne baca iznimku ako spremanje ne uspije.
     */
    public function appendSymfonyMessage(?RawMessage $message): bool
    {
        if (! $message instanceof RawMessage) {
            return false;
        }

        return $this->appendRawMessage($message->toString());
    }

    /**
     * Pokušava spremiti raw MIME poruku u IMAP "Sent" folder.
     */
    public function appendRawMessage(string $rawMessage): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $host = trim((string) config('mail.imap_sent.host', ''));
        $username = (string) config('mail.imap_sent.username');
        $password = (string) config('mail.imap_sent.password');
        $port = (int) config('mail.imap_sent.port', 993);
        $encryption = strtolower(trim((string) config('mail.imap_sent.encryption', 'ssl')));
        $validateCert = (bool) config('mail.imap_sent.validate_cert', true);
        $folderCandidates = $this->folderCandidates();
        $mime = $this->normalizeMime($rawMessage);
        $timeoutSeconds = $this->timeoutSeconds();

        if ($host === '' || $username === '' || $password === '') {
            Log::warning('IMAP append preskočen: nedostaju host/korisnik/lozinka za IMAP.');

            return false;
        }

        if (function_exists('imap_open') && function_exists('imap_append')) {
            $success = $this->appendViaPhpImap($host, $username, $password, $folderCandidates, $mime, $timeoutSeconds);
            if ($success) {
                return true;
            }

            if (! $this->isSocketFallbackEnabled()) {
                Log::warning('IMAP append preko PHP IMAP ekstenzije nije uspio; socket fallback je isključen.', [
                    'folders' => $folderCandidates,
                    'host' => $host,
                ]);

                return false;
            }

            Log::warning('IMAP append preko PHP imap ekstenzije nije uspio. Pokušavam socket fallback.');
        } else {
            if (! $this->isSocketFallbackEnabled()) {
                Log::warning('PHP IMAP ekstenzija nije dostupna, a socket fallback je isključen.');

                return false;
            }

            Log::warning('PHP IMAP ekstenzija nije dostupna, koristim socket fallback za Sent APPEND.');
        }

        return $this->appendViaSocket(
            $host,
            $port,
            $username,
            $password,
            $folderCandidates,
            $mime,
            $encryption,
            $validateCert,
            $timeoutSeconds
        );
    }

    /**
     * Pokušaj spremanja kroz PHP IMAP ekstenziju.
     */
    private function appendViaPhpImap(
        string $host,
        string $username,
        string $password,
        array $folderCandidates,
        string $mime,
        int $timeoutSeconds
    ): bool {
        $errors = [];
        $this->applyImapTimeouts($timeoutSeconds);

        foreach ($this->mailboxPrefixes() as $prefix) {
            $stream = @imap_open(
                $prefix.'INBOX',
                $username,
                $password,
                defined('OP_HALFOPEN') ? OP_HALFOPEN : 0,
                0,
                $this->imapOpenOptions()
            );

            if ($stream === false) {
                $errors[] = $this->imapLastError();
                $this->clearImapErrors();
                continue;
            }

            foreach ($folderCandidates as $folder) {
                $encodedFolder = function_exists('imap_utf7_encode') ? imap_utf7_encode($folder) : $folder;
                $mailbox = $prefix.$encodedFolder;
                if (@imap_append($stream, $mailbox, $mime, '\\Seen')) {
                    $this->clearImapErrors();
                    imap_close($stream);

                    return true;
                }

                $errors[] = $this->imapLastError();
                $this->clearImapErrors();
            }

            imap_close($stream);
        }

        Log::warning('IMAP append: poruku nije moguće spremiti ni u jedan Sent folder.', [
            'folders' => $folderCandidates,
            'error' => collect($errors)->filter()->last(),
            'host' => $host,
        ]);

        return false;
    }

    /**
     * Fallback bez PHP IMAP ekstenzije, koristeći IMAP protokol preko socketa.
     */
    private function appendViaSocket(
        string $host,
        int $port,
        string $username,
        string $password,
        array $folderCandidates,
        string $mime,
        string $encryption,
        bool $validateCert,
        int $timeoutSeconds
    ): bool {
        $stream = $this->openImapSocket($host, $port, $encryption, $validateCert, $timeoutSeconds);
        if (! is_resource($stream)) {
            Log::warning('IMAP socket fallback: neuspjelo spajanje na IMAP server.', [
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption,
            ]);

            return false;
        }

        try {
            $greeting = $this->readLine($stream);
            if ($greeting === null || ! str_starts_with($greeting, '*')) {
                Log::warning('IMAP socket fallback: neispravan greeting.');

                return false;
            }

            if (! $this->imapCommandOk($stream, 'A0001', 'LOGIN '.$this->imapQuote($username).' '.$this->imapQuote($password))) {
                Log::warning('IMAP socket fallback: LOGIN nije uspio.');

                return false;
            }

            foreach ($folderCandidates as $folder) {
                $encodedFolder = $this->encodeImapMailboxName($folder);
                if ($this->imapAppend($stream, 'A0002', $encodedFolder, $mime)) {
                    $this->imapCommandOk($stream, 'A0003', 'LOGOUT');

                    return true;
                }
            }

            $this->imapCommandOk($stream, 'A0003', 'LOGOUT');
        } finally {
            @fclose($stream);
        }

        Log::warning('IMAP socket fallback: APPEND nije uspio za sve Sent foldere.', [
            'folders' => $folderCandidates,
            'host' => $host,
        ]);

        return false;
    }

    private function openImapSocket(string $host, int $port, string $encryption, bool $validateCert, int $timeoutSeconds)
    {
        $transportCandidates = match ($encryption) {
            'tls' => ['tls', 'ssl', 'tcp'],
            'ssl' => ['ssl', 'tls', 'tcp'],
            default => ['tcp', 'ssl', 'tls'],
        };

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => $validateCert,
                'verify_peer_name' => $validateCert,
                'allow_self_signed' => ! $validateCert,
                'SNI_enabled' => true,
                'peer_name' => $host,
            ],
        ]);

        foreach ($transportCandidates as $transport) {
            $target = $transport.'://'.$host.':'.$port;
            $errno = 0;
            $errstr = '';
            $stream = @stream_socket_client($target, $errno, $errstr, $timeoutSeconds, STREAM_CLIENT_CONNECT, $context);
            if (is_resource($stream)) {
                stream_set_timeout($stream, $timeoutSeconds);

                return $stream;
            }
        }

        return null;
    }

    private function imapCommandOk($stream, string $tag, string $command): bool
    {
        if (! $this->writeAll($stream, $tag.' '.$command."\r\n")) {
            return false;
        }

        $status = $this->readTaggedStatus($stream, $tag);

        return $status === 'OK';
    }

    private function imapAppend($stream, string $tag, string $mailbox, string $mime): bool
    {
        $literalLength = strlen($mime);
        $command = $tag.' APPEND '.$this->imapQuote($mailbox).' (\\Seen) {'.$literalLength."}\r\n";
        if (! $this->writeAll($stream, $command)) {
            return false;
        }

        $continuation = $this->readUntilContinuationOrTag($stream, $tag);
        if ($continuation !== '+') {
            return false;
        }

        if (! $this->writeAll($stream, $mime)) {
            return false;
        }

        $status = $this->readTaggedStatus($stream, $tag);

        return $status === 'OK';
    }

    private function readUntilContinuationOrTag($stream, string $tag): ?string
    {
        for ($i = 0; $i < 500; $i++) {
            $line = $this->readLine($stream);
            if ($line === null) {
                return null;
            }

            if (str_starts_with($line, '+')) {
                return '+';
            }

            if (preg_match('/^'.preg_quote($tag, '/').'\s+(OK|NO|BAD)\b/i', $line) === 1) {
                return strtoupper((string) preg_replace('/^'.preg_quote($tag, '/').'\s+([A-Z]+).*/i', '$1', $line));
            }
        }

        return null;
    }

    private function readTaggedStatus($stream, string $tag): ?string
    {
        for ($i = 0; $i < 1000; $i++) {
            $line = $this->readLine($stream);
            if ($line === null) {
                return null;
            }

            if (preg_match('/^'.preg_quote($tag, '/').'\s+(OK|NO|BAD)\b/i', $line, $matches) === 1) {
                return strtoupper((string) $matches[1]);
            }
        }

        return null;
    }

    private function writeAll($stream, string $data): bool
    {
        $total = strlen($data);
        $written = 0;
        while ($written < $total) {
            $result = @fwrite($stream, substr($data, $written));
            if (! is_int($result) || $result <= 0) {
                return false;
            }
            $written += $result;
        }

        return true;
    }

    private function readLine($stream): ?string
    {
        $line = @fgets($stream, 16384);
        if ($line === false) {
            return null;
        }

        return rtrim($line, "\r\n");
    }

    private function imapQuote(string $value): string
    {
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

        return '"'.$escaped.'"';
    }

    private function encodeImapMailboxName(string $folder): string
    {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF7-IMAP', $folder);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        return $folder;
    }

    private function isEnabled(): bool
    {
        return (bool) config('mail.imap_sent.enabled', false);
    }

    private function isSocketFallbackEnabled(): bool
    {
        return (bool) config('mail.imap_sent.socket_fallback', false);
    }

    private function timeoutSeconds(): int
    {
        return max(1, (int) config('mail.imap_sent.timeout_seconds', 4));
    }

    /**
     * @return array<int,string>
     */
    private function folderCandidates(): array
    {
        $primary = trim((string) config('mail.imap_sent.sent_folder', 'Sent'));
        $fallback = (string) config('mail.imap_sent.fallback_folders', '');

        $candidates = array_filter(array_map(
            static fn (string $folder): string => trim($folder),
            array_merge([$primary], explode(',', $fallback))
        ), static fn (string $folder): bool => $folder !== '');

        $unique = [];
        foreach ($candidates as $candidate) {
            if (! in_array($candidate, $unique, true)) {
                $unique[] = $candidate;
            }
        }

        if ($unique === []) {
            return ['Sent', 'INBOX.Sent', 'Sent Items', 'INBOX.Sent Items'];
        }

        return $unique;
    }

    /**
     * @return array<int,string>
     */
    private function mailboxPrefixes(): array
    {
        $host = trim((string) config('mail.imap_sent.host', ''));
        $port = (int) config('mail.imap_sent.port', 993);
        $validateCert = (bool) config('mail.imap_sent.validate_cert', true);
        $preferredEncryption = strtolower(trim((string) config('mail.imap_sent.encryption', 'ssl')));

        $mode = in_array($preferredEncryption, ['ssl', 'tls'], true) ? $preferredEncryption : '';
        $flags = ['imap'];
        if ($mode === 'ssl') {
            $flags[] = 'ssl';
        } elseif ($mode === 'tls') {
            $flags[] = 'tls';
        }

        if (! $validateCert) {
            $flags[] = 'novalidate-cert';
        }

        return ['{'.$host.':'.$port.'/'.implode('/', $flags).'}'];
    }

    /**
     * @return array<string,string>
     */
    private function imapOpenOptions(): array
    {
        $disableGssapi = (bool) config('mail.imap_sent.disable_gssapi', true);
        if (! $disableGssapi) {
            return [];
        }

        return ['DISABLE_AUTHENTICATOR' => 'GSSAPI'];
    }

    private function normalizeMime(string $rawMessage): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $rawMessage);
        $normalized = str_replace("\n", "\r\n", $normalized);

        if (! str_ends_with($normalized, "\r\n")) {
            $normalized .= "\r\n";
        }

        return $normalized;
    }

    private function imapLastError(): ?string
    {
        $last = function_exists('imap_last_error') ? imap_last_error() : null;
        if (is_string($last) && trim($last) !== '') {
            return $last;
        }

        $errors = function_exists('imap_errors') ? imap_errors() : null;
        if (is_array($errors) && count($errors) > 0) {
            $tail = end($errors);

            return is_string($tail) ? $tail : null;
        }

        return null;
    }

    private function clearImapErrors(): void
    {
        if (! function_exists('imap_errors')) {
            return;
        }

        @imap_errors();
    }

    private function applyImapTimeouts(int $timeoutSeconds): void
    {
        if (! function_exists('imap_timeout')) {
            return;
        }

        $timeoutSeconds = max(1, $timeoutSeconds);
        if (defined('IMAP_OPENTIMEOUT')) {
            @imap_timeout(IMAP_OPENTIMEOUT, $timeoutSeconds);
        }
        if (defined('IMAP_READTIMEOUT')) {
            @imap_timeout(IMAP_READTIMEOUT, $timeoutSeconds);
        }
        if (defined('IMAP_WRITETIMEOUT')) {
            @imap_timeout(IMAP_WRITETIMEOUT, $timeoutSeconds);
        }
        if (defined('IMAP_CLOSETIMEOUT')) {
            @imap_timeout(IMAP_CLOSETIMEOUT, $timeoutSeconds);
        }
    }
}
