<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Enums\LeaveType;
use App\Enums\ShiftType;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaveRequestController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;
        
        return view('leave.create', compact('employee'));
    }
    
    /**
     * Display a listing of the leave requests.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'leave_type' => 'sometimes|in:' . implode(',', LeaveType::values()),
            'status' => 'sometimes|in:' . implode(',', RequestStatus::values()),
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'employee_id' => 'sometimes|exists:employees,id'
        ]);

        // Employees see only their own leave requests.
        if ($user->isEmployee()) {
            $leaveRequests = LeaveRequest::where('employee_id', $user->employee->id)
                ->with('Employee')
                ->filter($validated)
                ->latest();
        }
        // Admin sees all leave requests with full access.
        else {
            $leaveRequests = LeaveRequest::with('Employee')
                ->filter($validated)
                ->latest();
        }

        return view('leave.index', ['leave_requests' => $leaveRequests->paginate(15)]);
    }

    public function store(Request $request)
    {
        if ($request->user()->cannot('create', LeaveRequest::class)) abort(403);
        
        $validationRules = [
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|in:' . implode(',', LeaveType::values()),
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'shift_covered' => 'required|array|max:2|min:1',
            'shift_covered.*' => 'required|string|distinct|in:' . implode(',', ShiftType::values()),
            'proof_of_leader_approval' => 'required|file|mimes:jpeg,jpg,png',
            'proof_of_confirmed_designatory_tasks' => 'required|file|mimes:jpeg,jpg,png',
        ];

        
        // Add proof_of_leave validation only for academic leave type
        if ($request->leave_type === LeaveType::ACADEMIC->value) {
            $validationRules['proof_of_leave'] = 'required|file|mimes:jpeg,jpg,png';
        }
        
        $validated = $request->validate($validationRules);
        
        // Handle file uploads
        $employeeId = $validated['employee_id'];
        $now = now()->format('Y-d-M');
        $leaveType = $validated['leave_type'];
        $prefixPath = "leave_proofs/$employeeId/$leaveType/$now";
        $proofLeaderPath = $request->file('proof_of_leader_approval')->store($prefixPath, 'public');
        $proofTasksPath = $request->file('proof_of_confirmed_designatory_tasks')->store($prefixPath, 'public');
        
        $leaveData = [
            'employee_id' => $validated['employee_id'],
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'shift_covered' => $validated['shift_covered'],
            'proof_of_leader_approval' => $proofLeaderPath,
            'proof_of_confirmed_designatory_tasks' => $proofTasksPath,
            'status' => RequestStatus::PENDING
        ];
        
        // Handle optional proof_of_leave file
        if ($request->leave_type === LeaveType::ACADEMIC->value && $request->hasFile('proof_of_leave')) {
            $proofLeavePath = $request->file('proof_of_leave')->store($leaveType, 'public');
            $leaveData['proof_of_leave'] = $proofLeavePath;
        }
        
        $leave = LeaveRequest::create($leaveData);
        
        $successMessage = "Your leave request is now soaring through the approval clouds.";
        
        return response(
            view('leave.create-success', [
                'success' => $successMessage,
                'successObject' => $leave,
                'redirectTo' => 'leave.index',
            ])
        );
    }

    public function show(Request $request, LeaveRequest $leave)
    {
        if ($request->user()->cannot('view', $leave)) abort(403);
    
        return view('leave.show', ['leave' => $leave]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveRequest $leave)
    {
        if (request()->user()->cannot('update', $leave)) abort(403);

        return view('leave.edit', compact('leave'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveRequest $leave)
    {
        if ($request->user()->cannot('update', $leave)) {
            abort(403);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|in:' . implode(',', LeaveType::values()),
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'shift_covered' => 'required|array',
            'shift_covered.*' => 'string|max:255',
            'status' => 'sometimes|in:' . implode(',', RequestStatus::values()),
            'proof_of_leader_approval' => 'sometimes|file|mimes:jpeg,jpg,png,pdf',
            'proof_of_confirmed_designatory_tasks' => 'sometimes|file|mimes:jpeg,jpg,png,pdf',
            'proof_of_leave' => 'sometimes|file|mimes:jpeg,jpg,png,pdf',
        ]);

        // Handle file uploads if they exist
        if ($request->hasFile('proof_of_leader_approval')) {
            $validated['proof_of_leader_approval'] = $request->file('proof_of_leader_approval')->store('leave_proofs', 'public');
        }
        
        if ($request->hasFile('proof_of_confirmed_designatory_tasks')) {
            $validated['proof_of_confirmed_designatory_tasks'] = $request->file('proof_of_confirmed_designatory_tasks')->store('leave_proofs', 'public');
        }
        
        if ($request->hasFile('proof_of_leave')) {
            $validated['proof_of_leave'] = $request->file('proof_of_leave')->store('leave_proofs', 'public');
        }

        $leave->update([
            ...$validated,
            'updated_by' => $request->user()->employee->id
        ]);

        return redirect()->route('leave.index')->with('success', 'Leave request updated successfully');
    }


    public function destroy(Request $request, LeaveRequest $leave)
    {
        if ($request->user()->cannot('delete', $leave)) abort(403);

        Storage::delete([
            $leave->proof_of_leader_approval,
            $leave->proof_of_confirmed_designatory_tasks,
        ]);

        if ($leave->proof_of_leave) {
            Storage::delete($leave->proof_of_leave);
        }
        
        $leave->delete();
        
        return redirect()->route('leave.index')
            ->with('success', 'Leave request deleted successfully');
    }

    public function updateStatus(Request $request, string $id)
    {
        $leave = LeaveRequest::where('id', '=', $id)->first() ?? abort(404);
        if ($request->user()->cannot('updateStatus', $leave)) abort(403);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', RequestStatus::values()),
            'rejection_reason' => 'required_if:status,'.RequestStatus::REJECTED->value.'|string|max:255'
        ]);

        $leave->update([
            'status' => $validated['status'],
            'reason' => $validated['rejection_reason'] ?? $leave->reason,
            'updated_by' => $request->user()->employee->id
        ]);

        return response()->json($leave);
    }

    

}
