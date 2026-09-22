<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantJobApplication extends Model
{
    protected $table = 'applicant_job_applications';
    protected $fillable = [
        'applicant_id', 'job_posting_id', 'status', 'cover_letter',
        'notice_period', 'availability_date', 'resume_path',
    ];
    protected $casts = ['availability_date' => 'date'];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(HrmJobPosting::class, 'job_posting_id');
    }

    public function getIsWithdrawnAttribute(): bool
    {
        return strtolower((string) $this->status) === 'withdrawn';
    }
}
