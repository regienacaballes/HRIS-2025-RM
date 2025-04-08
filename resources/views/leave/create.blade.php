@php
    use App\Enums\ShiftType;
    use App\Enums\LeaveType;
    use App\Enums\Department;
@endphp

@extends('components.layout.auth')

@section('title') Create Leave Request @endsection

@section('content')
<div class=" sm:px-6 lg:px-8 max-w-5xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg p-10">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">New Leave Request</h1>
                <p class="mt-2 text-sm text-gray-600">Submit a new leave request for approval.</p>
            </div>
            <a href="{{ route('leave.index') }}">
                <x-button class="p-3" text="Back to List" containerColor="primary" contentColor="white" />
            </a>
        </div>
        <p>Request Information</p>
        <hr class="mt-2">

        <form action="{{ route('leave.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <!-- Employee Information (readonly) -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mt-6">
                <div>
                    <x-form.input type="text" name="employee_name" label="Employee" required />
                </div>

                <div>
                    <x-form.select class="h-9" containerColor="primary" type="select" name="department" label="Department" required>
                        <option value="" selected disabled hidden>Choose an option</option> 
                        @foreach (Department::values() as $key => $value)
                            <option value="{{ $value }}" {{ old('department') == $value ? 'selected' : '' }}>{{ ucfirst($value) }}</option>
                        @endforeach
                    </x-form.select>
                </div>
            </div>

            <!-- Leave Type -->
            <div x-data="{ leaveType: '{{ old('leave_type') }}' }">
                <x-form.select name="leave_type" label="Leave Type" x-model="leaveType" required>
                    <option value="" selected disabled hidden>Choose a leave type</option>
                    @foreach (LeaveType::options() as $key => $value)
                    <option value="{{ $key }}" {{ old('leave_type')==$key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </x-form.select>

                <!-- Date Range -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mt-6" x-data="dateRangePicker()">
                    <div>
                        <x-form.input type="date" name="start_date" label="Start Date" x-model="startDate"
                            @change="updateEndDateMin" value="{{ old('start_date') }}" min="{{ date('Y-m-d') }}"
                            required />
                    </div>

                    <div>
                        <x-form.input type="date" name="end_date" label="End Date" x-model="endDate"
                            x-bind:min="minEndDate" value="{{ old('end_date') }}" required />
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-sm text-gray-600" x-show="daysCount > 0">
                            Duration: <span x-text="daysCount"></span> day<span x-show="daysCount > 1">s</span>
                        </p>
                    </div>
                </div>

                <!-- Reason -->
                <div class="mt-4">
                    <x-form.label name="reason" label="Reason for Leave" />
                    <div class="mt-2">
                        <textarea id="reason" name="reason" rows="3" required
                            class="p-2 text-gray-600 text-sm block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:outline-primary/30 focus:ring-opacity-50"
                            placeholder="State the reason for your leave.">{{ old('reason') }}</textarea>
                    </div>
                    <x-form.error name="reason" />
                </div>

                <!-- Shift Coverage -->
                <div class="mt-6">
                    <x-form.label name="shift_covered" label="Shift Coverage"/>

                    <fieldset>
                        <legend class="sr-only ">
                            Shift Covered
                        </legend>
                        <div class="space-x-10 flex mt-3">
                            @foreach (ShiftType::options() as $key => $value)
                                <div class="flex gap-3">
                                    <div class="flex h-6 shrink-0 items-center">
                                        <div class="group grid size-4 grid-cols-1">
                                            <input 
                                                value="{{ $key }}"
                                                class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-primary checked:bg-primary indeterminate:border-primary indeterminate:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                                id="shift_covered[{{ $loop->index }}]" 
                                                name="shift_covered[{{ $loop->index }}]" 
                                                type="checkbox" required />
                                            <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25"
                                                fill="none" viewbox="0 0 14 14">
                                                <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5"
                                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                                </path>
                                                <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11"
                                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                                </path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="text-sm/6">
                                        <label class="font-medium text-gray-900" for="shift_covered[{{ $loop->index }}]">
                                            {{ $value }} shift
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                    
                    <x-form.error name="shift_covered" />
                </div>

                <!-- Required Proof Documents -->
                <div class="mt-6 text-1xl mb-2">Attachments</div>
                <hr>
                {{-- <p>Accepting image format file only.</p> --}}

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mt-6">
                    <x-form.file label="Proof of Leader Approval" name="proof_of_leader_approval"
                        accept=".jpeg,.jpg,.png" required />

                    <x-form.file label="Proof of Confirmed Designatory Tasks"
                        name="proof_of_confirmed_designatory_tasks" accept=".jpeg,.jpg,.png" required />
                </div>

                <!-- Academic Leave Proof - Only shown when leave type is academic -->
                <div x-show="leaveType === '{{ LeaveType::ACADEMIC->value }}'" class="mt-6">
                    <x-form.file label="Proof of Academic Leave" name="proof_of_leave"
                        accept=".jpeg,.jpg,.png" x-bind:required="leaveType === '{{ LeaveType::ACADEMIC->value }}'" />
                    <p class="mt-1 text-sm text-gray-600">Please upload documentation supporting your academic leave
                        request</p>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-3 pt-6 border-t mt-6">

                    <x-button type="submit" text="Submit Request" containerColor="primary" contentColor="white"
                        size="md" roundness="md" />
                </div>
            </div>
        </form>
        <div class="flex justify-end mr-35 -mt-8">
            <a href="{{ route('leave.index') }}" onclick="event.preventDefault(); 
                if (confirm('Are you sure you want to go back without completing the request?')) {
                    window.location.href = '{{ route('leave.index') }}';
                }">
                <x-button text="Cancel" containerColor="gray-200" contentColor="gray-700" size="md" roundness="md" />
            </a>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dateRangePicker', () => ({
            startDate: '',
            endDate: '',
            minEndDate: '',
            daysCount: 0,
            
            init() {
                this.startDate = this.$el.querySelector('[name="start_date"]').value;
                this.endDate = this.$el.querySelector('[name="end_date"]').value;
                this.updateEndDateMin();
                this.calculateDays();
                
                this.$watch('startDate', () => {
                    this.updateEndDateMin();
                    this.calculateDays();
                });
                
                this.$watch('endDate', () => {
                    this.calculateDays();
                });
            },
            
            updateEndDateMin() {
                if (this.startDate) {
                    this.minEndDate = this.startDate;
                    if (this.endDate && new Date(this.endDate) < new Date(this.startDate)) {
                        this.endDate = this.startDate;
                    }
                }
            },
            
            calculateDays() {
                if (this.startDate && this.endDate) {
                    const start = new Date(this.startDate);
                    const end = new Date(this.endDate);
                    const diffTime = Math.abs(end - start);
                    this.daysCount = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                } else {
                    this.daysCount = 0;
                }
            }
        }));
    });
</script>
@endsection