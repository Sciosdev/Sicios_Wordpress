<?php

namespace Scios\GitBridge\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper to manage transient-based locking with configurable TTL.
 */
class SCIOS_Lock
{
    private string $key;

    private int $ttl;

    private $value;

    public function __construct(string $key, int $ttl = 300, $value = 1)
    {
        $this->key   = $key;
        $this->ttl   = max(1, $ttl);
        $this->value = $value;
    }

    public function set_ttl(int $ttl): void
    {
        $this->ttl = max(1, $ttl);
    }

    public function get_ttl(): int
    {
        return $this->ttl;
    }

    public function is_locked(): bool
    {
        return (bool) get_transient($this->key);
    }

    public function acquire(): bool
    {
        if ($this->is_locked()) {
            return false;
        }

        return (bool) set_transient($this->key, $this->value, $this->ttl);
    }

    public function force(): bool
    {
        return (bool) set_transient($this->key, $this->value, $this->ttl);
    }

    public function refresh(): bool
    {
        return (bool) set_transient($this->key, $this->value, $this->ttl);
    }

    public function release(): void
    {
        delete_transient($this->key);
    }
}
