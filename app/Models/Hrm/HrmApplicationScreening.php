<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class HrmApplicationScreening extends Model
{
    protected $table = 'hrm_application_screenings';
    protected $fillable = [
        'applicant_id', 'job_posting_id', 'resume_reviewed', 'education_verified',
        'work_experience_reviewed', 'skills_reviewed', 'qualifications_met',
        'applicant_info_reviewed', 'result', 'notes', 'screened_by',
    ];
    protected $casts = [
        'resume_reviewed' => 'boolean', 'education_verified' => 'boolean',
        'work_experience_reviewed' => 'boolean', 'skills_reviewed' => 'boolean',
        'qualifications_met' => 'boolean', 'applicant_info_reviewed' => 'boolean',
    ];
}
