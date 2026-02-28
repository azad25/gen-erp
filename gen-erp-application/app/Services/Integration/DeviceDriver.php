<?php

namespace App\Services\Integration;

/** Standard interface for all IoT/hardware device drivers. */
interface DeviceDriver
{
    /** Establish connection to the device. Returns false on failure (never throws). */
    public function connect(array $credentials): bool;

    /** Check if the device is reachable. */
    public function ping(): bool;

    /**
     * Fetch data FROM the device (e.g. attendance records, weight readings).
     *
     * @return array Array of data records from the device
     */
    public function pull(): array;

    /**
     * Send data TO the device (e.g. print receipt, open cash drawer).
     * Not all devices support push — return false if unsupported.
     */
    public function push(array $data): bool;

    /** Close the connection to the device. */
    public function disconnect(): void;

    /**
     * Get current device status.
     *
     * @return array{status: string, message: string, last_data_at: ?string}
     */
    public function getStatus(): array;
}
