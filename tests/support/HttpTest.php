<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

class TestBrowser
{
    private $curl;
    public array $headers = [];
    public string $ip = '';

    public function __construct()
    {
        $this->curl = curl_init();
        curl_setopt_array($this->curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEFILE => '',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HEADERFUNCTION => function ($curl, string $header): int {
                $this->headers[] = trim($header);
                return strlen($header);
            },
        ]);
    }

    public function request(string $base, string $path, ?array $data = null): array
    {
        $this->headers = [];
        curl_setopt($this->curl, CURLOPT_URL, $base . $path);
        if ($data === null) {
            curl_setopt($this->curl, CURLOPT_HTTPGET, true);
        } else {
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        $body = curl_exec($this->curl);
        if ($body === false) {
            throw new RuntimeException('No se pudo contactar al servidor HTTP de prueba.');
        }
        $this->ip = curl_getinfo($this->curl, CURLINFO_PRIMARY_IP);
        ensure(strpos($body, 'Fatal error:') === false && strpos($body, 'Warning:') === false, 'Respuesta HTTP sin errores PHP expuestos.', false);
        return [(int) curl_getinfo($this->curl, CURLINFO_HTTP_CODE), $body];
    }

    public function sessionId(): string
    {
        foreach (curl_getinfo($this->curl, CURLINFO_COOKIELIST) as $cookie) {
            $parts = explode("\t", $cookie);
            if (($parts[5] ?? '') === 'DIRISESSID') {
                return $parts[6];
            }
        }
        return '';
    }

    public function setSessionId(string $id): void
    {
        curl_setopt($this->curl, CURLOPT_COOKIE, 'DIRISESSID=' . $id);
    }

    public function headerContains(string $expected): bool
    {
        foreach ($this->headers as $header) {
            if (stripos($header, $expected) !== false) {
                return true;
            }
        }
        return false;
    }
}

function ensure(bool $condition, string $description, bool $print = true): void
{
    if (!$condition) {
        throw new RuntimeException($description);
    }
    if ($print) {
        echo 'PASS: ' . $description . "\n";
    }
}

function token(string $html): string
{
    if (!preg_match('/name="_token" value="([a-f0-9]{64})"/', $html, $matches)) {
        throw new RuntimeException('El formulario no contiene un token CSRF valido.');
    }
    return $matches[1];
}

