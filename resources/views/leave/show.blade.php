@php
use App\Enums\LeaveType;
use App\Enums\RequestStatus;
use App\Enums\ShiftType;
use Illuminate\Support\Str;
@endphp

@extends('components.layout.auth')

@section('title') Leave Request #{{ $leave->id }} @endsection

@section('content')
<div class="py-4 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto" x-cloak x-data="{ previewImage: null, isPdf: false }">
    <!-- Image Preview Modal -->
    <div x-show="previewImage" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
         @click.self="previewImage = null">
        <div class="relative bg-white rounded-lg shadow-xl max-w-4xl max-h-[90vh] p-4">
            <button @click="previewImage = null" 
                    class="absolute -top-3 -right-3 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <template x-if="!isPdf">
                <img :src="previewImage" class="max-w-full max-h-[80vh] rounded-lg" alt="Document preview">
            </template>
            <template x-if="isPdf">
                <embed :src="previewImage" 
                       type="application/pdf" 
                       class="w-full h-[80vh] rounded-lg"
                       frameborder="0">
            </template>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-10">
        <div class="flex justify-between items-center -ml-3 -mt-3 -mr-3">
            <h1 class="text-3xl font-bold text-gray-800">Leave Request Details</h1>
            <a href="{{ route('leave.index') }}">
                <x-button text="Back to List" containerColor="gray-500" />
            </a>
        </div>
        <div class="text-gray-500 -ml-3">
        <p>Request ID: {{ $leave->id }}</p>
        </div>
        <div class="space-y-6 mt-6">
            <h1 class="text-lg text-gray-800">Request Information</h1>
            <hr class="-mt-3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- <!-- Leave ID -->
                <div>
                    <x-form.label name="id" label="Leave Request ID" />
                    <p class="mt-2 px-3 py-2">
                        {{ $leave->id }}
                    </p>
                </div> --}}
                <!-- Employee -->
                <div>
                    <label class="text-gray-500 text-sm">Employee</label>
                    <p class="py-1">
                        {{ $leave->employee->getFullName() }}
                    </p>
                </div>
                <!-- Leave Type -->
                <div>
                    <label class="text-gray-500 text-sm">Leave Type</label>
                    <p class="py-1">
                        {{ LeaveType::getLabel($leave->leave_type) }}
                    </p>
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Status -->
                <div>
                    <label class="text-gray-500 text-sm">Status</label>
                    <p class="py-1">
                        {{ RequestStatus::getLabel($leave->status) }}
                    </p>
                </div>
                <!-- Dates -->
                <div>
                    <label class="text-gray-500 text-sm">Leave Period</label>
                    <p class="py-1">
                        {{ $leave->start_date->format('M d, Y') }} - {{ $leave->end_date->format('M d, Y') }}
                    </p>
                </div>
            </div>

            <!-- Reason -->
            <div>
                <label class="text-gray-500 text-sm">Reason for Leave</label>
                <p class="py-1 whitespace-pre-line -mt-4 mb-0">
                    {{ $leave->reason }}
                </p>
            </div>
            <!-- Shift Covered -->
            <div class="-mt-0">
                <label class="text-gray-500 text-sm">Shift Coverage</label>
                <div class="mt-3 flex flex-row">
                    @foreach (ShiftType::options() as $key => $value)
                        <div class="flex gap-6 items-center ">
                            <div class="flex h-6 gap-2 items-center">
                                <input type="checkbox" 
                                       class="w-4 h-4 text-primary border-gray-300 rounded ml-5" 
                                       disabled
                                       {{ in_array($key, $leave->shift_covered) ? 'checked' : '' }}>
                                       <div class="text-sm">
                                        <span class="font-medium text-gray-900">
                                            {{ $value }} shift  
                                        </span>
                                    </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <p>Attachments</p>
            <hr class="-mt-2 ">
            <!-- Document Previews -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach (['proof_of_leader_approval', 'proof_of_confirmed_designatory_tasks', 'proof_of_leave'] as $doc)
                    @if($leave->$doc)
                        <div class="text-gray-600 text-sm">
                            <label>{{ Str::title(str_replace('_', ' ', $doc)) }}</label>
                            <div class="mt-2">
                                @if(Str::endsWith($leave->$doc, ['.jpg', '.jpeg', '.png']))
                                    <div class="group relative cursor-pointer" 
                                         @click="previewImage = '{{ Storage::url($leave->$doc) }}'; isPdf = false">
                                        <img src="{{ Storage::url($leave->$doc) }}" 
                                             class="h-32 w-full object-cover rounded-lg border-2 border-gray-200 group-hover:border-primary transition-colors">
                                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="text-white font-medium">Click to Preview</span>
                                        </div>
                                    </div>
                                @else
                                    <a href="{{ Storage::url($leave->$doc) }}" 
                                       download
                                       class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Download File
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
            <hr>
            @if(auth()->user()->isAdmin() || auth()->user()->isHr())
                @if($leave->isPending())
                    <div class="mt-1 flex justify-end gap-3">
                        <form action="{{ route('leave.update', $leave) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ RequestStatus::APPROVED->value }}">
                            <x-button type="submit" text="Reject Request" containerColor="red-600" contentColor="white" />
                        </form>
                        <form action="{{ route('leave.update', $leave) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ RequestStatus::APPROVED->value }}">
                            <x-button type="submit" text="Approve Request" containerColor="green-600" contentColor="white" />
                        </form>
                        
                        {{-- <button class="cursor-pointer rounded-sm bg-red-600 font-semibold text-white shadow-xs focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 px-3 py-1.5 text-sm 
                        transition delay-150 duration-300 ease-in-out hover:-translate-y-1 hover:scale-110" type="submit">Reject</button>   
                        <button class="cursor-pointer rounded-sm bg-green-600 font-semibold text-white shadow-xs focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 px-3 py-1.5 text-sm 
                        transition delay-150 duration-300 ease-in-out hover:-translate-y-1 hover:scale-110" type="submit">Approve</button>                               
                        </div> --}}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection