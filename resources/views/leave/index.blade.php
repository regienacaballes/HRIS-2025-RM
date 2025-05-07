@php
    use App\Enums\RequestStatus;
    use App\Enums\LeaveType;
    
@endphp

@extends('components.layout.auth')

@section('title') Leave Request Dashboard @endsection

@section('content')
<div class="py-2 px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="flex flex-row justify-between align-middle sm:items-center w-full">
        <div>
            <button id="btn_action" class="bg-transparent py-0 text-2xl font-semibold">
                <span id="arrowIcon">&#11167;</span> 
                <span class="ml-2">Request Summary</span>
            </button>
        </div>
        <div class="mr-10 py-4">
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
    </div>
    
    <div class="flex flex-col sm:flex-row justify-between mb-4">
        <div id="reqSummary" class="flex flex-wrap gap-4">
            <button class="focus:outline-none">
                <div id="allApr" class="bg-green-200 p-4 rounded-lg text-center w-32">
                    <p class="text-gray-700">Approved</p>
                    <p id="totalApr" class="text-2xl font-bold">{{ \App\Models\LeaveRequest::countApproved() }}</p>
                </div>
            </button>
            <button id="allPen" class="focus:outline-none">
                <div class="bg-yellow-200 p-4 rounded-lg text-center w-32">
                    <p class="text-gray-700">Pending</p>
                    <p id="totalPen" class="text-2xl font-bold">{{ \App\Models\LeaveRequest::countPending() }}</p>
                </div>
            </button>
            <button id="allRej" class="focus:outline-none">
                <div class="bg-red-200 p-4 rounded-lg text-center w-32">
                    <p class="text-gray-700">Rejected</p>
                    <p id="totalRej" class="text-2xl font-bold">{{ \App\Models\LeaveRequest::countRejected() }}</p>
                </div>
            </button>
            <button id="allReqs" class="focus:outline-none">
                <div class="bg-gray-300 p-4 rounded-lg text-center w-32">
                    <p class="text-gray-700">Requests</p>
                    <p id="totalReqs" class="text-2xl font-bold">{{ \App\Models\LeaveRequest::count(); }}</p>
                </div>
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow-sm rounded-lg p-4 space-y-10 mb-6">
        <form action="{{ route('leave.index') }}" method="GET" class="space-y-4 sm:flex sm:flex-wrap sm:space-y-0 sm:gap-3">
            {{-- Employee --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isHr())
                <div class="sm:w-auto">
                    <x-form.select name="employee_id" label="Employee" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                        <option value="">All Employees</option>
                        @foreach (\App\Models\Employee::orderBy('id')->get() as $employee)
                            <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->getFullName() }}</option>
                        @endforeach
                    </x-form.select>
                </div>
            @endif
            {{-- Leave Type --}}
            <div class="sm:w-auto">
                <x-form.select name="leave_type" label="Leave Type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="">All Types</option>
                    @foreach (LeaveType::options() as $key => $value)
                        <option value="{{ $key }}" {{ request('leave_type') == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </x-form.select>
            </div>
            {{-- Status --}}
            <div class="sm:w-auto">
                <x-form.select name="status" label="Status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="">All Statuses</option>
                    @foreach (RequestStatus::options() as $key => $value)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </x-form.select>
            </div>
            {{-- Start Date --}}
            <div class="sm:w-auto">
                <x-form.input type="date" name="start_date" label="Start Date" value="{{ request('start_date') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50"/>
            </div>
            {{-- End Date --}}
            <div class="sm:w-auto">
                <x-form.input type="date" name="end_date" label="End Date" value="{{ request('end_date') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50"/>
            </div>

            <div class=" ml-2 self-end mb-2 sm:mt-0">
                <x-button text="Filter" containerColor="primary" contentColor="white" size="md" roundness="md" type="submit"/>
            </div>
        </form>
    </div>

    <!-- Leave Requests Table -->
    <div id="lr_table" class="bg-white shadow-sm rounded-lg overflow-hidden align-middle items-center">
        <div class="min-w-full divide-y divide-gray-200">
            <div class="bg-gray-50">
                <div class="flex px-4 py-3 text-sm sm:text-sm font-semibold text-gray-800 tracking-wider">
                    <div class="basis-64">Employee</div>
                    <div class="basis-42">Leave Type</div>
                    <div class="basis-64">Period</div>
                    {{-- <div>Duration</div> --}}
                    <div class="basis-42">Created</div>
                    <div class="basis-80">Reason for Leave</div>
                    <div class="basis-54">Attachments</div>
                    <div class="basis-40">Status</div>
                    <div class="basis-36">Actions</div>
                </div>
            </div>
            <div class="bg-white divide-y divide-gray-200">
                @forelse($leave_requests as $request)
                <div class="hover:bg-gray-50 transition-colors duration-150 pb-3 items-center">
                    <div class="flex px-4 mt-5 whitespace-nowrap">
                        <div class="basis-64 text-sm font-medium text-gray-900">{{ $request->employee->getFullName() }}</div>
                        <div class="basis-42 text-sm text-gray-600">{{ LeaveType::getLabel($request->leave_type) }}</div>
                        <div class="basis-64 text-sm text-gray-600 relative group">
                            {{ $request->start_date->format('M d, Y') }} - {{ $request->end_date->format('M d, Y') }}
                            <div class="absolute left-5 -mt-3 hidden group-hover:flex bg-gray-800 text-white text-sm p-2 rounded shadow-lg whitespace-nowrap px-3 py-1">
                                {{ $request->start_date->diffInDays($request->end_date) + 1 }} days
                            </div>
                        </div>
                        <div class="basis-42 text-sm text-gray-600">{{ $request->created_at->format('M d, Y') }}</div>
                        <div class="basis-80 text-sm text-gray-600 relative group">
                            <span class="cursor-pointer">{{ \Illuminate\Support\Str::limit($request->reason, 40, '...') }}</span>
                            <div class="absolute -right-3 -mt-2 hidden group-hover:flex bg-gray-800 text-white text-sm rounded shadow-lg w-72 max-w-xl whitespace-normal z-20">
                                {{ $request->reason}}
                            </div>
                        </div>
                        <div class="basis-54 text-primary cursor-pointer">
                            <span class="preview-link text-sm" data-image="{{$request->proof_of_leader_approval}}" onclick="previewImage(this)">Proof 1</span>
                            <span class="preview-link text-sm" data-image="{{$request->proof_of_confirmed_designatory_tasks}}" onclick="previewImage(this)">| Proof 2</span>
                            @if ($request->leave_type === 'sick')
                                <span class="preview-link" data-image="{{ $request->proof_of_leave }}" onclick="previewImage(this)">| Proof 2</span>
                            @endif

                        </div>
                        <div id="modal" class="hidden fixed inset-0 bg-white bg-opacity-50 flex items-center justify-center z-50">
                            <div class="bg-white p-6 rounded-lg shadow-lg relative">
                                <span id="close-modal" class="absolute top-2 right-4 text-gray-600 text-xl cursor-pointer">&times;</span>
                                <img id="modal-image" src="" alt="Preview" class="w-96 h-auto">
                            </div>
                        </div>
                        <div class="basis-40">
                            <span @class([
                                'px-3 py-1 text-sm font-medium rounded-full',
                                'bg-yellow-100 text-yellow-800' => $request->status === RequestStatus::PENDING,
                                'bg-green-100 text-green-800' => $request->status === RequestStatus::APPROVED,
                                'bg-red-100 text-red-800' => $request->status === RequestStatus::REJECTED,
                            ])>
                                {{ RequestStatus::getLabel($request->status) }}
                            </span>
                        </div>
                        <div class="basis-36">
                            <div class="text-right space-x-2 flex flex-row -mt-1">
                                <div><a href="{{ route('leave.show', $request) }}" target="_blank">
                                    <button class="py-2 p-0 text-sm text-primary">View</button>
                                </a></div>
                                <div class="flex relative -mt-1">
                                    <button class="px-3 bg-[#F5F5F5] static text-xl" onclick="toggleDropdown(this)">
                                        &#8942;
                                    </button>
                                    <div class="text-left text-sm hidden absolute -right-20 -mt-2 bg-white border border-gray-200 rounded-lg shadow-md w-26 px-4 z-10">
                                        <div>
                                            <a href="{{ route('leave.edit', $request) }}" target="_blank">
                                                <x-button text="Edit" size="sm" containerColor="white" contentColor="amber-900" class="p-2 cursor-pointer hover:bg-gray-200"/>
                                            </a>
                                        </div>
                                        
                                        <form action="{{ route('leave.destroy', $request) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Are you sure you want to delete this request?');">
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" text="Delete" size="sm" class="p-2 cursor-pointer hover:bg-gray-200" containerColor="white" contentColor="primary" />
                                        </form>

                                        @if(auth()->user()->isAdmin() || auth()->user()->isHr())
                                            @if($request->status === RequestStatus::PENDING)
                                                <div>
                                                    <form action="{{ route('leave.update', $request) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="{{ RequestStatus::APPROVED->value }}">
                                                        <x-button type="submit" text="Approve" size="sm" containerColor="white" contentColor="green-700" class="text-green-500 p-2 cursor-pointer hover:bg-green-200"/>
                                                    </form>
                                                </div>
                                                <div> 
                                                    <form action="{{ route('leave.update', $request) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="{{ RequestStatus::REJECTED->value }}">
                                                        <x-button type="submit" text="Reject" size="sm" containerColor="white" contentColor="red-700" class="text-red-600 p-2 cursor-pointer hover:bg-red-200"/>
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
    // Function that previews image
    function previewImage(element) {  
        var imageUrl = element.getAttribute('data-image'); // Get the URL from the data-image attribute

        // Find the modal and the modal image element
        var modal = document.getElementById('modal');
        var modalImage = document.getElementById('modal-image');
        
        modalImage.src = imageUrl;

        modal.classList.remove('hidden');// Show the modal
    }

    // Close modal when x button is clicked
    document.getElementById('close-modal').addEventListener('click', function() {
        var modal = document.getElementById('modal');
        modal.classList.add('hidden');
    });

    // Close where ever when modal is open
    document.getElementById('modal').addEventListener('click', function(event) {
        if (event.target === this) {
            this.classList.add('hidden');
        }
    });

    //Hide request summary
    const reqSummarybtn = document.getElementById("btn_action");
    const requestSummary = document.getElementById("reqSummary");
    const arrowIcon = document.getElementById("arrowIcon");

    reqSummarybtn.addEventListener("click", () => {
        requestSummary.classList.toggle("hidden"); // visibility

        arrowIcon.innerHTML = requestSummary.classList.contains("hidden") ? "&#11165;" : "&#11167;";  // Change arrow direction
    });

</script>
@endsection

{{-- //Reset filter textfields
    document.querySelector('form').addEventListener('submit', function () {
        setTimeout(() => {
            document.querySelector('form').reset(); // Resets the form fields
        }, 500);
    }); --}}