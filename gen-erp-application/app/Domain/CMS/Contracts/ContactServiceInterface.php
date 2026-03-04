<?php

namespace App\Domain\CMS\Contracts;

use App\Domain\CMS\Models\ContactSubmission;
use App\Domain\CMS\DTOs\ContactSubmissionData;
use Illuminate\Pagination\LengthAwarePaginator;

interface ContactServiceInterface
{
    /**
     * Submit a contact form.
     */
    public function submitContactForm(ContactSubmissionData $data): ContactSubmission;

    /**
     * Get contact submissions for a site.
     */
    public function getSubmissionsForSite(int $siteId, ?string $status = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get submission by ID.
     */
    public function getSubmission(int $id): ?ContactSubmission;

    /**
     * Mark submission as contacted.
     */
    public function markAsContacted(int $id, ?int $userId = null): bool;

    /**
     * Mark submission as resolved.
     */
    public function markAsResolved(int $id, ?string $notes = null): bool;

    /**
     * Mark submission as spam.
     */
    public function markAsSpam(int $id): bool;

    /**
     * Assign submission to a user.
     */
    public function assignSubmission(int $id, int $userId): bool;

    /**
     * Get contact statistics for a site.
     */
    public function getContactStatistics(int $siteId): array;

    /**
     * Delete a submission.
     */
    public function deleteSubmission(int $id): bool;

    /**
     * Bulk update submissions.
     */
    public function bulkUpdateSubmissions(array $ids, array $data): int;

    /**
     * Export submissions to CSV.
     */
    public function exportSubmissions(int $siteId, ?string $status = null): string;
}