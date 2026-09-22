<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class HrmInterviewEvaluation extends Model
{
    protected $table = 'hrm_interview_evaluations';
    protected $fillable = [
        'interview_id', 'communication_skills', 'relevant_skills', 'technical_knowledge',
        'problem_solving', 'work_experience', 'adaptability', 'teamwork', 'professionalism',
        'strengths', 'concerns', 'interview_notes', 'result', 'recommendation', 'evaluated_by',
    ];
}
