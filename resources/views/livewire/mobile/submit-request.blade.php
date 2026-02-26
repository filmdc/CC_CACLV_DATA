<div class="p-4 lg:py-8 page-enter">
    <div class="lg:max-w-2xl lg:mx-auto">
        <h1 class="text-lg lg:text-2xl font-semibold text-white mb-1">Submit Request</h1>

        {{-- Step Indicator --}}
        <div class="flex items-center gap-2 mb-5 lg:mb-8">
            @foreach(['Type', 'Details', 'Files', 'Review'] as $i => $label)
                @php $stepNum = $i + 1; @endphp
                <div class="flex items-center gap-2 {{ $stepNum < 4 ? 'flex-1' : '' }}">
                    <div class="flex items-center justify-center h-7 w-7 lg:h-8 lg:w-8 min-h-0 min-w-0 rounded-full text-xs font-semibold
                        {{ $step > $stepNum ? 'bg-green-600 text-white' : '' }}
                        {{ $step === $stepNum ? 'bg-indigo-600 text-white' : '' }}
                        {{ $step < $stepNum ? 'bg-zinc-800 text-zinc-500' : '' }}
                    ">
                        @if($step > $stepNum)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        @else
                            {{ $stepNum }}
                        @endif
                    </div>
                    <span class="text-xs {{ $step === $stepNum ? 'text-white font-medium' : 'text-zinc-500' }} hidden sm:inline">{{ $label }}</span>
                    @if($stepNum < 4)
                        <div class="flex-1 h-px {{ $step > $stepNum ? 'bg-green-600' : 'bg-zinc-800' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Step 1: Select Type --}}
        @if($step === 1)
        <div>
            <p class="text-sm text-zinc-400 mb-4">Choose the type of approval you need</p>
            @forelse($approvalTypes as $category => $types)
                <div class="mb-4">
                    <h3 class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-2">{{ $category ?: 'General' }}</h3>
                    <div class="space-y-2 lg:grid lg:grid-cols-2 lg:gap-3 lg:space-y-0">
                        @foreach($types as $type)
                        <button
                            wire:click="selectType({{ $type->id }})"
                            class="w-full text-left p-4 rounded-xl bg-zinc-900 border border-zinc-800 hover:border-indigo-500/50 active:bg-zinc-800 transition-colors approval-card"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-white">{{ $type->display_name }}</p>
                                    @if($type->description)
                                        <p class="text-xs text-zinc-400 mt-0.5">{{ $type->description }}</p>
                                    @endif
                                    @if($type->requires_document)
                                        <span class="inline-flex items-center gap-1 mt-1 text-[10px] text-amber-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                            </svg>
                                            Requires attachment
                                        </span>
                                    @endif
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </div>
                        </button>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-zinc-900 rounded-xl border border-zinc-800 p-6 text-center">
                    <p class="text-sm text-zinc-500">No approval types available.</p>
                </div>
            @endforelse
        </div>
        @endif

        {{-- Step 2: Details --}}
        @if($step === 2)
        <div>
            <div class="mb-4 p-3 rounded-lg bg-indigo-500/10 border border-indigo-500/20">
                <p class="text-sm text-indigo-300">{{ $this->approvalType?->display_name }}</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-zinc-300 mb-1.5">Title <span class="text-red-400">*</span></label>
                    <input
                        wire:model="title"
                        id="title"
                        type="text"
                        class="w-full h-12 lg:h-10 px-3 text-base lg:text-sm rounded-lg bg-zinc-900 border border-zinc-800 text-white placeholder-zinc-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 focus:outline-none"
                        placeholder="Brief title for your request"
                    >
                    @error('title') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-zinc-300 mb-1.5">Description</label>
                    <textarea
                        wire:model="description"
                        id="description"
                        rows="4"
                        class="w-full px-3 py-2 text-base lg:text-sm rounded-lg bg-zinc-900 border border-zinc-800 text-white placeholder-zinc-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 focus:outline-none resize-none"
                        placeholder="Describe your request in detail..."
                    ></textarea>
                    @error('description') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="submitterNotes" class="block text-sm font-medium text-zinc-300 mb-1.5">Notes for Reviewer</label>
                    <textarea
                        wire:model="submitterNotes"
                        id="submitterNotes"
                        rows="2"
                        class="w-full px-3 py-2 text-base lg:text-sm rounded-lg bg-zinc-900 border border-zinc-800 text-white placeholder-zinc-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 focus:outline-none resize-none"
                        placeholder="Any notes or context for the reviewer..."
                    ></textarea>
                    @error('submitterNotes') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="programId" class="block text-sm font-medium text-zinc-300 mb-1.5">Program</label>
                    <select
                        wire:model="programId"
                        id="programId"
                        class="w-full h-12 lg:h-10 px-3 text-base lg:text-sm rounded-lg bg-zinc-900 border border-zinc-800 text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 focus:outline-none"
                    >
                        <option value="">None</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                        @endforeach
                    </select>
                    @error('programId') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                @if($qualifiedApprovers->isNotEmpty())
                <div>
                    <label for="approverId" class="block text-sm font-medium text-zinc-300 mb-1.5">Send To</label>
                    <select
                        wire:model="approverId"
                        id="approverId"
                        class="w-full h-12 lg:h-10 px-3 text-base lg:text-sm rounded-lg bg-zinc-900 border border-zinc-800 text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 focus:outline-none"
                    >
                        <option value="">All Qualified Approvers</option>
                        @foreach($qualifiedApprovers as $approver)
                            <option value="{{ $approver->id }}">{{ $approver->name }}</option>
                        @endforeach
                    </select>
                    @error('approverId') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                @endif
            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="previousStep" class="flex-1 lg:flex-none lg:px-6 py-3 lg:py-2.5 text-sm font-medium text-zinc-400 bg-zinc-800 rounded-lg hover:bg-zinc-700 transition-colors">
                    Back
                </button>
                <button wire:click="nextStep" class="flex-1 lg:flex-none lg:px-6 py-3 lg:py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 active:bg-indigo-700 transition-colors">
                    Next
                </button>
            </div>
        </div>
        @endif

        {{-- Step 3: Attachments --}}
        @if($step === 3)
        <div>
            <div class="mb-4">
                <p class="text-sm text-zinc-400">
                    @if($this->approvalType?->requires_document)
                        Upload required documents for this request.
                    @else
                        Optionally attach supporting documents.
                    @endif
                </p>
            </div>

            {{-- Upload Area --}}
            <label class="block mb-4 cursor-pointer">
                <div class="border-2 border-dashed border-zinc-700 rounded-xl p-8 text-center hover:border-indigo-500/50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-zinc-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    <p class="text-sm text-zinc-400"><span class="hidden lg:inline">Click</span><span class="lg:hidden">Tap</span> to select files</p>
                    <p class="text-xs text-zinc-600 mt-1">Max 100MB per file</p>
                </div>
                <input
                    wire:model="attachments"
                    type="file"
                    multiple
                    accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt"
                    capture="environment"
                    class="hidden"
                >
            </label>

            {{-- Upload Progress --}}
            <div wire:loading wire:target="attachments" class="mb-4 p-3 rounded-lg bg-indigo-500/10 border border-indigo-500/20">
                <div class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span class="text-sm text-indigo-300">Uploading files...</span>
                </div>
            </div>

            @error('attachments') <p class="mb-3 text-sm text-red-400">{{ $message }}</p> @enderror

            {{-- File List --}}
            @if(count($attachments) > 0)
            <div class="space-y-2 mb-4">
                @foreach($attachments as $index => $attachment)
                <div class="flex items-center gap-3 p-3 rounded-lg bg-zinc-900 border border-zinc-800">
                    <div class="flex-shrink-0 h-8 w-8 rounded bg-indigo-500/20 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-white truncate">{{ $attachment->getClientOriginalName() }}</p>
                        <p class="text-xs text-zinc-500">{{ number_format($attachment->getSize() / 1024, 1) }} KB</p>
                    </div>
                    <button wire:click="removeAttachment({{ $index }})" class="flex-shrink-0 h-8 w-8 min-h-0 min-w-0 flex items-center justify-center rounded-full text-zinc-500 hover:text-red-400 hover:bg-red-500/10 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                @endforeach
            </div>
            @endif

            <div class="flex gap-3 mt-6">
                <button wire:click="previousStep" class="flex-1 lg:flex-none lg:px-6 py-3 lg:py-2.5 text-sm font-medium text-zinc-400 bg-zinc-800 rounded-lg hover:bg-zinc-700 transition-colors">
                    Back
                </button>
                <button wire:click="nextStep" class="flex-1 lg:flex-none lg:px-6 py-3 lg:py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 active:bg-indigo-700 transition-colors">
                    Review
                </button>
            </div>
        </div>
        @endif

        {{-- Step 4: Review & Submit --}}
        @if($step === 4)
        <div>
            <p class="text-sm text-zinc-400 mb-4">Review your request before submitting</p>

            <div class="bg-zinc-900 rounded-xl border border-zinc-800 divide-y divide-zinc-800">
                <div class="p-4">
                    <span class="text-xs text-zinc-500">Type</span>
                    <p class="text-sm text-white">{{ $this->approvalType?->display_name }}</p>
                </div>
                <div class="p-4">
                    <span class="text-xs text-zinc-500">Title</span>
                    <p class="text-sm text-white">{{ $title }}</p>
                </div>
                @if($description)
                <div class="p-4">
                    <span class="text-xs text-zinc-500">Description</span>
                    <p class="text-sm text-zinc-300">{{ $description }}</p>
                </div>
                @endif
                @if($submitterNotes)
                <div class="p-4">
                    <span class="text-xs text-zinc-500">Notes for Reviewer</span>
                    <p class="text-sm text-zinc-300">{{ $submitterNotes }}</p>
                </div>
                @endif
                @if($programId)
                <div class="p-4">
                    <span class="text-xs text-zinc-500">Program</span>
                    <p class="text-sm text-white">{{ $programs->find($programId)?->name }}</p>
                </div>
                @endif
                <div class="p-4">
                    <span class="text-xs text-zinc-500">Send To</span>
                    <p class="text-sm text-white">
                        @if($approverId)
                            {{ $qualifiedApprovers->firstWhere('id', $approverId)?->name ?? 'Selected Approver' }}
                        @else
                            All Qualified Approvers
                        @endif
                    </p>
                </div>
                @if(count($attachments) > 0)
                <div class="p-4">
                    <span class="text-xs text-zinc-500">Attachments ({{ count($attachments) }})</span>
                    <div class="mt-1 space-y-1">
                        @foreach($attachments as $attachment)
                            <p class="text-sm text-zinc-300">{{ $attachment->getClientOriginalName() }}</p>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="previousStep" class="flex-1 lg:flex-none lg:px-6 py-3 lg:py-2.5 text-sm font-medium text-zinc-400 bg-zinc-800 rounded-lg hover:bg-zinc-700 transition-colors">
                    Back
                </button>
                <button
                    wire:click="submit"
                    class="flex-1 lg:flex-none lg:px-8 py-3 lg:py-2.5 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-500 active:bg-green-700 transition-colors"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-75 cursor-wait"
                >
                    <span wire:loading.remove wire:target="submit">Submit Request</span>
                    <span wire:loading wire:target="submit" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Submitting...
                    </span>
                </button>
            </div>
        </div>
        @endif
    </div>
</div>
