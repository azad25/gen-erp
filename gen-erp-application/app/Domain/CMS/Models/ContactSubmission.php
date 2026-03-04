<?php

namespace App\Domain\CMS\Models;

use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contact form submission model.
 *
 * @property int $id
 * @property int $site_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $company
 * @property string|null $subject
 * @property string $message
 * @property array|null $form_data
 * @property string $status
 * @property string $source
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon|null $contacted_at
 * @property int|null $assigned_to
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Site $site
 * @property-read User|null $assignedUser
 */
class ContactSubmission extends Model
{
    use HasFactory;

    protected $table = 'cms_contact_submissions';

    protected $fillable = [
        'site_id',
        'name',
        'email',
        'phone',
        'company',
        'subject',
        'message',
        'form_data',
        'status',
        'source',
        'ip_address',
        'user_agent',
        'contacted_at',
        'assigned_to',
        'notes',
    ];

    protected $casts = [
        'form_data' => 'array',
        'contacted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the site that owns the contact submission.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the user assigned to this submission.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope for filtering by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by site.
     */
    public function scopeForSite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope for recent submissions.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for unassigned submissions.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Mark submission as contacted.
     */
    public function markAsContacted(?int $userId = null): void
    {
        $this->update([
            'status' => 'contacted',
            'contacted_at' => now(),
            'assigned_to' => $userId,
        ]);
    }

    /**
     * Mark submission as resolved.
     */
    public function markAsResolved(?string $notes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'notes' => $notes,
        ]);
    }

    /**
     * Mark submission as spam.
     */
    public function markAsSpam(): void
    {
        $this->update([
            'status' => 'spam',
        ]);
    }

    /**
     * Assign submission to a user.
     */
    public function assignTo(int $userId): void
    {
        $this->update([
            'assigned_to' => $userId,
        ]);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new' => 'New',
            'contacted' => 'Contacted',
            'resolved' => 'Resolved',
            'spam' => 'Spam',
            default => 'Unknown',
        };
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new' => 'blue',
            'contacted' => 'yellow',
            'resolved' => 'green',
            'spam' => 'red',
            default => 'gray',
        };
    }
}