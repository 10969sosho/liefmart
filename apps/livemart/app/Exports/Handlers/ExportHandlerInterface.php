<?php

namespace App\Exports\Handlers;

interface ExportHandlerInterface
{
    /**
     * Get the export type identifier.
     */
    public function getType(): string;

    /**
     * Process data and return an array with:
     * - 'export' => the Laravel Excel export instance
     * - 'filename' => the filename for download
     * - 'filters' => array of filter data used (for logging)
     */
    public function handle(array $filters): array;
}
