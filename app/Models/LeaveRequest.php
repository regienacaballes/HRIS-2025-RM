<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Enums\LeaveType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    /** @use HasFactory<\Database\Factories\LeaveRequestFactory> */
    use HasFactory;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'leave_type' => LeaveType::class,
        'status' => RequestStatus::class,
        'shift_covered' => 'array'
    ];

    protected $attributes = [
        'status' => RequestStatus::PENDING,
        'proof_of_leave' => null,
    ];

    protected $fillable = [
        'employee_id',
        'updated_by',
        'leave_type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'shift_covered',
        'proof_of_leader_approval',
        'proof_of_confirmed_designatory_tasks',
        'proof_of_leave'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(Employee::class, 'updated_by');
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
        ->when($filters['leave_type'] ?? null, fn($q, $type) =>
            $q->where('leave_type', $type))
        ->when($filters['status'] ?? null, fn($q, $status) =>
            $q->where('status', $status))
        ->when($filters['start_date'] ?? null, fn($q, $date) =>
            $q->where('start_date', '>=', $date))
        ->when($filters['end_date'] ?? null, fn($q, $date) =>
            $q->where('end_date', '<=', $date))
        ->when($filters['user_id'] ?? null, fn($q, $userId) =>
            $q->where('user_id', $userId));
    }
    
    public function isPending()
    {
        return $this->status == RequestStatus::PENDING;
    }

    public function isRejected()
    {
        return $this->status == RequestStatus::REJECTED;
    }

    public function isApproved()
    {
        return $this->status == RequestStatus::APPROVED;
    }

    public static function countPending()
    {
        return self::where('status', RequestStatus::PENDING)->count();
    }

    public static function countApproved()
    {
        return self::where('status', RequestStatus::APPROVED)->count();
    }
    public static function countRejected()
    {
        return self::where('status', RequestStatus::REJECTED)->count();
    }
    public static function showPending()
    {
        return self::where('status', 'pending')->get();
    }

    
}
