@php
    use App\Enums\RequestStatus;
    use App\Enums\LeaveType;
@endphp

@extends('components.layout.auth')

@section('title') Leave Request Dashboard @endsection

@section('content')
<div class="py-4 px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Request Summary</h1>
        
        @if(auth()->user()->isEmployee() || auth()->user()->isHr() || auth()->user()->isAdmin())
            <a href="{{ route('leave.create') }}">
                <x-button
                    text="New Request"
                    containerColor="primary"
                    contentColor="white"
                    size="md"
                    roundness="md"
                />
            </a>
        @endif
    </div>

    <!-- Filters -->
    <div class="bg-white shadow-sm rounded-lg p-4 mb-6">
        <form action="{{ route('leave.index') }}" method="GET" class="space-y-4 sm:flex sm:flex-wrap sm:space-y-0 sm:gap-3">
            <div class="sm:w-auto">
                <label for="leave_type" class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
                <select id="leave_type" name="leave_type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="">All Types</option>
                    @foreach (LeaveType::options() as $key => $value)
                        <option value="{{ $key }}" {{ request('leave_type') == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:w-auto">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="">All Statuses</option>
                    @foreach (RequestStatus::options() as $key => $value)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:w-auto">
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
            </div>

            <div class="sm:w-auto">
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
            </div>

            @if(auth()->user()->isAdmin() || auth()->user()->isHr())
            <div class="sm:w-auto">
                <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                <select id="employee_id" name="employee_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="">All Employees</option>
                    @foreach (\App\Models\Employee::orderBy('id')->get() as $employee)
                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->getFullName() }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="self-end mt-4 sm:mt-0">
                <x-button
                    text="Filter"
                    containerColor="primary"
                    contentColor="white"
                    size="md"
                    roundness="md"
                    type="submit"
                />
            </div>
        </form>
    </div>

    <!-- Leave Requests Table -->
    <div class="bg-white shadow-sm rounded-lg overflow-hidden items-center">
        <div class="min-w-full divide-y divide-gray-200">
            <div class="bg-gray-50">
                <div class="flex px-4 py-3 text-xs sm:text-sm font-semibold text-gray-800 tracking-wider">
                    <div class="basis-64">Employee</div>
                    <div class="basis-42">Leave Type</div>
                    <div class="basis-64">Period</div>
                    {{-- <div>Duration</div> --}}
                    <div class="basis-42">Created</div>
                    <div class="basis-72">Reason for Leave</div>
                    <div class="basis-36">Status</div>
                    <div class="basis-36">Attachments</div>
                    <div class="basis-36">Actions</div>
                </div>
            </div>
            <div class="bg-white divide-y divide-gray-200">
                @forelse($leave_requests as $request)
                <div class="hover:bg-gray-50 transition-colors duration-150">
                    <div class="flex px-4 py-3 whitespace-nowrap">
                        <div class="basis-64 text-sm font-medium text-gray-900">{{ $request->employee->getFullName() }}</div>
                        <div class="basis-42 text-sm text-gray-600">{{ LeaveType::getLabel($request->leave_type) }}</div>
                        <div class="basis-64 text-sm text-gray-600 relative group">
                            {{ $request->start_date->format('M d, Y') }} - {{ $request->end_date->format('M d, Y') }}
                            <div class="absolute left-5 -mt-3 hidden group-hover:flex bg-gray-800 text-white text-sm p-2 rounded shadow-lg whitespace-nowrap px-3 py-1">
                                {{ $request->start_date->diffInDays($request->end_date) + 1 }} days
                            </div>
                        </div>
                        <div class="basis-42 text-sm text-gray-600">{{ $request->created_at->format('M d, Y') }}</div>
                        <div class="basis-72 text-sm text-gray-600 relative group">
                            <span class="cursor-pointer">{{ \Illuminate\Support\Str::limit($request->reason, 35, '...') }}</span>
                            <div class="absolute -right-7 -mt-2 hidden group-hover:flex bg-gray-800 text-white text-sm rounded shadow-lg w-72 max-w-xl whitespace-normal z-20">
                                {{ $request->reason}}
                            </div>
                        </div>
                        <div class="basis-36 items-center">
                            <span @class([
                                'px-2 py-1 text-xs font-medium rounded-full',
                                'bg-yellow-100 text-yellow-800' => $request->status === RequestStatus::PENDING,
                                'bg-green-100 text-green-800' => $request->status === RequestStatus::APPROVED,
                                'bg-red-100 text-red-800' => $request->status === RequestStatus::REJECTED,
                            ])>
                                {{ RequestStatus::getLabel($request->status) }}
                            </span>
                        </div>
                        <div class="basis-36">
                            <div class="text-right space-x-2 flex flex-row items-center">
                                <div><a href="{{ route('leave.show', $request) }}" target="_blank">
                                    <button class="py-2 p-0 text-sm text-primary">View</button>
                                </a></div>
                                <div class="flex relative">
                                    <button class="px-3 py-2 bg-[#F5F5F5] static text-xl" onclick="toggleDropdown(this)">
                                        &#8942;
                                    </button>
                                    <div class="text-left space-y-2 hidden absolute -right-20 -mt-2 bg-white border border-gray-200 rounded-lg shadow-md w-26 px-4 py-2 z-10">
                                        <div>
                                            @if(auth()->user()->isAdmin() || 
                                            (auth()->user()->isHr()) || 
                                            (auth()->user()->isEmployee() && $request->employee_id === auth()->user()->employee->id && $request->status === RequestStatus::PENDING))
                                            <a href="{{ route('leave.edit', $request) }}">
                                                <input type="button" value="Edit" class="p-2 cursor-pointer hover:bg-gray-200">
                                            </a>
                                            @endif
                                        </div>
    
                                        @if(auth()->user()->isAdmin() || auth()->user()->isHr())
                                            @if($request->status === RequestStatus::PENDING)
                                                <div>
                                                    <form action="{{ route('leave.update.status', $request) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="{{ RequestStatus::APPROVED->value }}">
                                                        <input type="submit" value="Approve" class="text-green-500 p-2 cursor-pointer hover:bg-green-200">
                                                    </form>
                                                </div>
    
                                                <div>
                                                    <form action="{{ route('leave.update.status', $request) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="{{ RequestStatus::REJECTED->value }}">
                                                        <input type="submit" value="Reject" class="text-red-600 p-2 cursor-pointer hover:bg-red-200">
                                                    </form>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-4 py-6 text-center text-gray-500">
                    <p>No leave requests found.</p>
                    <a href="{{ route('leave.create') }}" class="text-indigo-600 hover:text-indigo-900 mt-2 inline-block">
                        Create your first leave request
                    </a>
                </div>
                @endforelse
            </div>
        </div>
        
        <div class="px-4 py-4 bg-white border-t border-gray-200">
            {{ $leave_requests->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('leaveRequestsDashboard', () => ({
            showDetails: null,
            toggleDetails(id) {
                this.showDetails = this.showDetails === id ? null : id;
            }
        }))
    })

    function toggleDropdown(button) {
                        const dropdown = button.nextElementSibling;
                        dropdown.classList.toggle("hidden");

                        // Close dropdown when clicking outside
                        document.addEventListener("click", function hideDropdown(event) {
                            if (!button.contains(event.target) && !dropdown.contains(event.target)) {
                                dropdown.classList.add("hidden");
                                document.removeEventListener("click", hideDropdown);
                            }
                        });
                    }
</script>
@endsection