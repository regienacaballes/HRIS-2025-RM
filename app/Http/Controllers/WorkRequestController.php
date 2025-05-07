<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Enums\ShiftRequest;
use App\Enums\WorkType;
use App\Models\WorkRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'work_type' => 'sometimes|in:' . implode(',', WorkType::values()),
            'status' => 'sometimes|in:' . implode(',', RequestStatus::values()),
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'employee_id' => 'sometimes|exists:employees,id'    
        ]);

        $query = WorkRequest::with('Employee')
            ->when($request->user()->isEmployee(), fn($q) => $q->where('employee_id', $request->user()->employee->id))
            ->filter($validated)
            ->latest();

        return view('work-request.index', ['workRequests' => $query->paginate(15)]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if ($request->user()->cannot('create', WorkRequest::class)) abort(403);

        // Get the authenticated user's employee record
        $employee = $request->user()->employee;
        
        return view('work-request.create', compact('employee'));
    }

    /**
     * Store a newly created work request in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'request_date' => 'required|date',
            'work_type' => 'required|string|in:' . implode(',', WorkType::values()),
            'shift_request' => 'required|string|in:' . implode(',', ShiftRequest::values()),
            'reason' => 'required|string',
            'proof_of_team_leader_approval' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
            'proof_of_group_leader_approval' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
            'proof_of_school_approval' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);
        
        // Set default status to pending
        $validated['status'] = RequestStatus::PENDING;
        
        // Set updated_by to current authenticated user
        $employeeId = $request->user()->employee->id;
        $validated['updated_by'] = $employeeId;
        
        $now = now()->format('Y-d-M');
        
        // Handle file uploads if provided
        if ($request->hasFile('proof_of_team_leader_approval')) {
            $validated['proof_of_team_leader_approval'] = $request->file('proof_of_team_leader_approval')
                ->store("work_requests/$employeeId/team_leader_approvals/$now", 'public');
        }
        
        if ($request->hasFile('proof_of_group_leader_approval')) {
            $validated['proof_of_group_leader_approval'] = $request->file('proof_of_group_leader_approval')
                ->store("work_requests/$employeeId/group_leader_approvals/$now", 'public');
        }
        
        if ($request->hasFile('proof_of_school_approval')) {
            $validated['proof_of_school_approval'] = $request->file('proof_of_school_approval')
                ->store("work_requests/$employeeId/school_approvals/$now", 'public');
        }
        
        $workRequest = WorkRequest::create($validated);
        
        return redirect()->route('work-request.index')
            ->with('success', "Work request [{$workRequest->id}] created successfully.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, WorkRequest $workRequest)
    {
        if ($request->user()->cannot('view', $workRequest)) abort(403);
    
        return view('work-request.show',  compact('workRequest'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WorkRequest $workRequest)
    {
        if (request()->user()->cannot('update', $workRequest)) abort(403);
        
        $employee = $workRequest->employee;
        
        return view('work-request.edit', compact('workRequest', 'employee'));
    }

    /**
     * Update the specified work request in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WorkRequest  $workRequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, WorkRequest $workRequest)
    {
        if ($request->user()->cannot('update', $workRequest)) abort(403);

        $validated = $request->validate([
            'request_date' => 'sometimes|date',
            'work_type' => 'sometimes|string|in:' . implode(',', WorkType::values()),
            'shift_request' => 'sometimes|string|in:' . implode(',', ShiftRequest::values()),
            'reason' => 'sometimes|string',
            'status' => 'sometimes|string|in:' . implode(',', RequestStatus::values()),
            'proof_of_team_leader_approval' => 'sometimes|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'proof_of_group_leader_approval' => 'sometimes|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'proof_of_school_approval' => 'sometimes|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);
        
        // Set updated_by to current authenticated user
        $employeeId = $request->user()->employee->id;
        $validated['updated_by'] = $employeeId;
        
        $now = now()->format('Y-d-M');
        
        // Handle file uploads if provided
        if ($request->hasFile('proof_of_team_leader_approval')) {
            // Delete old file if exists
            if ($workRequest->proof_of_team_leader_approval) {
                Storage::disk('public')->delete($workRequest->proof_of_team_leader_approval);
            }
            
            $validated['proof_of_team_leader_approval'] = $request->file('proof_of_team_leader_approval')
                ->store("work_requests/$employeeId/team_leader_approvals/$now", 'public');
        }
        
        if ($request->hasFile('proof_of_group_leader_approval')) {
            // Delete old file if exists
            if ($workRequest->proof_of_group_leader_approval) {
                Storage::disk('public')->delete($workRequest->proof_of_group_leader_approval);
            }
            
            $validated['proof_of_group_leader_approval'] = $request->file('proof_of_group_leader_approval')
                ->store("work_requests/$employeeId/group_leader_approvals/$now", 'public');
        }
        
        if ($request->hasFile('proof_of_school_approval')) {
            // Delete old file if exists
            if ($workRequest->proof_of_school_approval) {
                Storage::disk('public')->delete($workRequest->proof_of_school_approval);
            }
            
            $validated['proof_of_school_approval'] = $request->file('proof_of_school_approval')
                ->store("work_requests/$employeeId/school_approvals/$now", 'public');
        }
        
        $workRequest->update($validated);
        
        return redirect()->route('work-request.index')
            ->with('success', "Work request [{$workRequest->id}] updated successfully.");
    }

    /**
     * Remove the specified work request from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WorkRequest  $workRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, WorkRequest $workRequest)
    {
        if ($request->user()->cannot('delete', $workRequest)) abort(403);

        // Delete associated files
        if ($workRequest->proof_of_team_leader_approval) {
            Storage::disk('public')->delete($workRequest->proof_of_team_leader_approval);
        }
        
        if ($workRequest->proof_of_group_leader_approval) {
            Storage::disk('public')->delete($workRequest->proof_of_group_leader_approval);
        }
        
        if ($workRequest->proof_of_school_approval) {
            Storage::disk('public')->delete($workRequest->proof_of_school_approval);
        }
        
        $workRequestId = $workRequest->id;
        $workRequest->delete();
        
        return redirect()->route('work-request.index')
            ->with('success', "Work request [{$workRequestId}] deleted successfully.");
    }
}
