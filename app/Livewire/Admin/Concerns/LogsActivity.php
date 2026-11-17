<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit trail for admin screens (table activity_logs, shown at /admin/activity).
 *
 *   $this->logActivity('updated', $partner, $partner->getChanges());
 *   $this->logSaved($partner);     // after save(): "created" or "updated" with the changed attributes
 *   $this->logDeleted($partner);   // call BEFORE or AFTER delete(): keeps a short label of the row
 */
trait LogsActivity
{
    /** Attributes that never go into the log. */
    private const LOG_HIDDEN = ['password', 'remember_token', 'updated_at', 'created_at'];

    protected function logActivity(string $action, ?Model $subject = null, ?array $changes = null): ActivityLog
    {
        if ($changes !== null) {
            $changes = array_diff_key($changes, array_flip(self::LOG_HIDDEN));
        }

        return ActivityLog::record($action, $subject, $changes ?: null);
    }

    /** Log a save() that has just happened. Nothing is written when an update changed nothing. */
    protected function logSaved(Model $model, array $extra = []): ?ActivityLog
    {
        if ($model->wasRecentlyCreated) {
            return $this->logActivity('created', $model, $model->getAttributes() + $extra);
        }

        $changes = array_diff_key($model->getChanges(), array_flip(self::LOG_HIDDEN)) + $extra;

        return $changes ? $this->logActivity('updated', $model, $changes) : null;
    }

    protected function logDeleted(Model $model): ActivityLog
    {
        return $this->logActivity('deleted', $model, $model->getAttributes());
    }
}
