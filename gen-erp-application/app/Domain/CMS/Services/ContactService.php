<?php

namespace App\Domain\CMS\Services;

use App\Domain\CMS\Contracts\ContactServiceInterface;
use App\Domain\CMS\Models\ContactSubmission;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\DTOs\ContactSubmissionData;
use App\Domain\CMS\Events\ContactFormSubmitted;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Service for handling contact form submissions.
 */
class ContactService implements ContactServiceInterface
{
    /**
     * Submit a contact form.
     */
    public function submitContactForm(ContactSubmissionData $data): ContactSubmission
    {
        $submission = ContactSubmission::create([
            'site_id' => $data->siteId,
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'company' => $data->company,
            'subject' => $data->subject,
            'message' => $data->message,
            'form_data' => $data->formData,
            'source' => $data->source,
            'ip_address' => $data->ipAddress,
            'user_agent' => $data->userAgent,
        ]);

        event(new ContactFormSubmitted($submission));

        return $submission;
    }

    /**
     * Get contact submissions for a site.
     */
    public function getSubmissionsForSite(int $siteId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = ContactSubmission::forSite($siteId)
            ->with(['assignedUser'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->withStatus($status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get submission by ID.
     */
    public function getSubmission(int $id): ?ContactSubmission
    {
        return ContactSubmission::with(['site', 'assignedUser'])->find($id);
    }

    /**
     * Mark submission as contacted.
     */
    public function markAsContacted(int $id, ?int $userId = null): bool
    {
        $submission = ContactSubmission::find($id);
        
        if (!$submission) {
            return false;
        }

        $submission->markAsContacted($userId);
        return true;
    }

    /**
     * Mark submission as resolved.
     */
    public function markAsResolved(int $id, ?string $notes = null): bool
    {
        $submission = ContactSubmission::find($id);
        
        if (!$submission) {
            return false;
        }

        $submission->markAsResolved($notes);
        return true;
    }

    /**
     * Mark submission as spam.
     */
    public function markAsSpam(int $id): bool
    {
        $submission = ContactSubmission::find($id);
        
        if (!$submission) {
            return false;
        }

        $submission->markAsSpam();
        return true;
    }

    /**
     * Assign submission to a user.
     */
    public function assignSubmission(int $id, int $userId): bool
    {
        $submission = ContactSubmission::find($id);
        
        if (!$submission) {
            return false;
        }

        $submission->assignTo($userId);
        return true;
    }

    /**
     * Get contact statistics for a site.
     */
    public function getContactStatistics(int $siteId): array
    {
        $total = ContactSubmission::forSite($siteId)->count();
        $new = ContactSubmission::forSite($siteId)->withStatus('new')->count();
        $contacted = ContactSubmission::forSite($siteId)->withStatus('contacted')->count();
        $resolved = ContactSubmission::forSite($siteId)->withStatus('resolved')->count();
        $spam = ContactSubmission::forSite($siteId)->withStatus('spam')->count();
        $recent = ContactSubmission::forSite($siteId)->recent(30)->count();
        $unassigned = ContactSubmission::forSite($siteId)->unassigned()->count();

        // Get submissions by month for the last 12 months
        $monthlyStats = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = ContactSubmission::forSite($siteId)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            
            $monthlyStats[] = [
                'month' => $date->format('M Y'),
                'count' => $count,
            ];
        }

        // Get top sources
        $topSources = ContactSubmission::forSite($siteId)
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();

        return [
            'total_submissions' => $total,
            'new_submissions' => $new,
            'contacted_submissions' => $contacted,
            'resolved_submissions' => $resolved,
            'spam_submissions' => $spam,
            'recent_submissions' => $recent,
            'unassigned_submissions' => $unassigned,
            'monthly_stats' => $monthlyStats,
            'top_sources' => $topSources,
        ];
    }

    /**
     * Delete a submission.
     */
    public function deleteSubmission(int $id): bool
    {
        $submission = ContactSubmission::find($id);
        
        if (!$submission) {
            return false;
        }

        return $submission->delete();
    }

    /**
     * Bulk update submissions.
     */
    public function bulkUpdateSubmissions(array $ids, array $data): int
    {
        return ContactSubmission::whereIn('id', $ids)->update($data);
    }

    /**
     * Export submissions to CSV.
     */
    public function exportSubmissions(int $siteId, ?string $status = null): string
    {
        $query = ContactSubmission::forSite($siteId)
            ->with(['assignedUser'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->withStatus($status);
        }

        $submissions = $query->get();

        $csv = "Name,Email,Phone,Company,Subject,Message,Status,Source,Created At,Assigned To\n";
        
        foreach ($submissions as $submission) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $submission->name),
                str_replace('"', '""', $submission->email),
                str_replace('"', '""', $submission->phone ?? ''),
                str_replace('"', '""', $submission->company ?? ''),
                str_replace('"', '""', $submission->subject ?? ''),
                str_replace('"', '""', $submission->message),
                str_replace('"', '""', $submission->status),
                str_replace('"', '""', $submission->source),
                $submission->created_at->format('Y-m-d H:i:s'),
                str_replace('"', '""', $submission->assignedUser?->name ?? '')
            );
        }

        return $csv;
    }
}