<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Submit general, HMIS, or RX Office data uploads to the Community Action Partnership data team.">

        <title>Program Manager Uploads</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 min-h-screen">
        <div class="bg-white border-b shadow-sm">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center space-x-3 text-slate-900">
                        <x-app-logo :show-text="false" class="h-10 w-auto" />
                        <div>
                            <p class="text-base font-semibold">{{ \App\Models\SiteSetting::get('site_name', 'CAP TRACK') }}</p>
                            <p class="text-sm text-slate-500">{{ \App\Models\SiteSetting::get('program_upload_portal_tagline', 'Program Manager Upload Portal') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Sign In</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-md shadow">Register</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <main class="py-12">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-slate-100">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-10 text-white">
                        <h1 class="text-3xl font-bold">Upload Program Data Files</h1>
                        <p class="mt-3 text-base text-blue-100 max-w-2xl">
                            Use this portal to securely submit General Uploads, HMIS Pennsylvania exports, or RX Office Housing Counseling reports. Our data team will confirm receipt and process the files in CAP TRACK.
                        </p>
                    </div>

                    <div class="p-8 space-y-6">
                        @if (session('success'))
                            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                <p class="font-semibold">Please correct the highlighted fields.</p>
                            </div>
                        @endif

                        <form action="{{ route('program-manager-upload.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                            @csrf

                            <section>
                                <h2 class="text-lg font-semibold text-slate-900">1. Select Upload Type</h2>
                                <p class="mt-1 text-sm text-slate-500">Choose the data source that best matches your file.</p>
                                <div class="mt-4 grid gap-4 md:grid-cols-3">
                                    @php
                                        $options = [
                                            'general' => [
                                                'label' => 'General Upload',
                                                'description' => 'For standard CSV or spreadsheet files prepared for CAP TRACK imports.'
                                            ],
                                            'hmis' => [
                                                'label' => 'HMIS Upload',
                                                'description' => 'For Pennsylvania ClientTrack Active Client List exports or similar HMIS data.'
                                            ],
                                            'rx_office' => [
                                                'label' => 'RX Office Upload',
                                                'description' => 'For PHFA RX Office Housing Counseling program data exports.'
                                            ],
                                        ];
                                        $current = old('submission_type', 'general');
                                    @endphp

                                    @foreach ($options as $value => $option)
                                        <label class="relative rounded-xl border {{ $current === $value ? 'border-blue-500 ring-2 ring-blue-200' : 'border-slate-200' }} bg-white p-5 shadow-sm focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-200 transition">
                                            <div class="flex items-start">
                                                <input type="radio"
                                                       name="submission_type"
                                                       value="{{ $value }}"
                                                       class="mt-1 h-4 w-4 text-blue-600 border-slate-300 focus:ring-blue-500"
                                                       {{ $current === $value ? 'checked' : '' }}>
                                                <div class="ml-3">
                                                    <span class="text-sm font-semibold text-slate-900">{{ $option['label'] }}</span>
                                                    <p class="mt-1 text-sm text-slate-500">{{ $option['description'] }}</p>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                @error('submission_type')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </section>

                            <section id="template-links" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-5">
                                <div data-template="general" class="hidden">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h3 class="text-sm font-semibold text-slate-900">General Upload Template</h3>
                                            <p class="text-sm text-slate-500">Download the standard CSV layout for CAP TRACK imports.</p>
                                        </div>
                                        <a href="{{ route('program-manager-upload.template', ['type' => 'general']) }}"
                                           class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700">
                                            Download Template
                                        </a>
                                    </div>
                                </div>
                                <div data-template="hmis" class="hidden">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h3 class="text-sm font-semibold text-slate-900">HMIS Pennsylvania Template</h3>
                                            <p class="text-sm text-slate-500">Download the ClientTrack Active Client List template for HMIS imports.</p>
                                        </div>
                                        <a href="{{ route('program-manager-upload.template', ['type' => 'hmis']) }}"
                                           class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700">
                                            Download Template
                                        </a>
                                    </div>
                                </div>
                                <div data-template="rx_office" class="hidden">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h3 class="text-sm font-semibold text-slate-900">RX Office Housing Template</h3>
                                            <p class="text-sm text-slate-500">Download the detailed case CSV layout for PHFA RX Office uploads.</p>
                                        </div>
                                        <a href="{{ route('program-manager-upload.template', ['type' => 'rx_office']) }}"
                                           class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700">
                                            Download Template
                                        </a>
                                    </div>
                                </div>
                            </section>

                            <section class="grid gap-6 md:grid-cols-2">
                                <div>
                                    <label for="program_manager_name" class="block text-sm font-semibold text-slate-700">Program Manager Name<span class="text-rose-500">*</span></label>
                                    <input type="text"
                                           id="program_manager_name"
                                           name="program_manager_name"
                                           value="{{ old('program_manager_name') }}"
                                           class="mt-2 block w-full rounded-lg border @error('program_manager_name') border-rose-500 @else border-slate-300 @enderror bg-white px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                           required>
                                    @error('program_manager_name')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="program_manager_email" class="block text-sm font-semibold text-slate-700">Email Address<span class="text-rose-500">*</span></label>
                                    <input type="email"
                                           id="program_manager_email"
                                           name="program_manager_email"
                                           value="{{ old('program_manager_email') }}"
                                           class="mt-2 block w-full rounded-lg border @error('program_manager_email') border-rose-500 @else border-slate-300 @enderror bg-white px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                           required>
                                    @error('program_manager_email')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="organization" class="block text-sm font-semibold text-slate-700">Organization</label>
                                    <input type="text"
                                           id="organization"
                                           name="organization"
                                           value="{{ old('organization') }}"
                                           class="mt-2 block w-full rounded-lg border @error('organization') border-rose-500 @else border-slate-300 @enderror bg-white px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    @error('organization')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-semibold text-slate-700">Phone</label>
                                    <input type="text"
                                           id="phone"
                                           name="phone"
                                           value="{{ old('phone') }}"
                                           class="mt-2 block w-full rounded-lg border @error('phone') border-rose-500 @else border-slate-300 @enderror bg-white px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    @error('phone')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </section>

                            <section>
                                <label for="notes" class="block text-sm font-semibold text-slate-700">Notes for Data Team</label>
                                <textarea id="notes"
                                          name="notes"
                                          rows="4"
                                          class="mt-2 block w-full rounded-lg border @error('notes') border-rose-500 @else border-slate-300 @enderror bg-white px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </section>

                            <section>
                                <h2 class="text-lg font-semibold text-slate-900">2. Attach Your File</h2>
                                <p class="mt-1 text-sm text-slate-500">Accepted formats: CSV, TXT, XLSX, XLS, PDF, ZIP, DOC, DOCX, JSON (max 100MB).</p>
                                <div class="mt-4">
                                    <label class="flex flex-col items-center justify-center w-full p-6 border-2 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition @error('upload_file') border-rose-400 bg-rose-50 @else border-slate-300 @enderror">
                                        <svg class="h-10 w-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                                        </svg>
                                        <span class="mt-3 text-sm font-medium text-blue-600">Click to upload</span>
                                        <span class="text-xs text-slate-500 mt-1">or drag and drop your file here</span>
                                        <input id="upload_file" name="upload_file" type="file" class="hidden" required>
                                    </label>
                                    @error('upload_file')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </section>

                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-t border-slate-200 pt-6">
                                <div class="text-xs text-slate-500">
                                    <p>Files are stored securely and reviewed by the CAP TRACK data team.</p>
                                    <p class="mt-1">We typically respond within one business day.</p>
                                </div>
                                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Submit Upload
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <footer class="py-8 text-center text-xs text-slate-400">
            &copy; {{ now()->year }} {{ \App\Models\SiteSetting::get('site_name', 'CAP TRACK') }}. All rights reserved.
        </footer>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const templateContainer = document.getElementById('template-links');
                if (!templateContainer) {
                    return;
                }

                const templateBlocks = templateContainer.querySelectorAll('[data-template]');
                const typeInputs = document.querySelectorAll('input[name="submission_type"]');

                const updateTemplateVisibility = (selectedType) => {
                    if (!selectedType) {
                        templateContainer.classList.add('hidden');
                        return;
                    }

                    templateContainer.classList.remove('hidden');
                    templateBlocks.forEach((block) => {
                        block.classList.toggle('hidden', block.dataset.template !== selectedType);
                    });
                };

                typeInputs.forEach((input) => {
                    input.addEventListener('change', (event) => updateTemplateVisibility(event.target.value));
                });

                const initiallyChecked = document.querySelector('input[name="submission_type"]:checked');
                if (initiallyChecked) {
                    updateTemplateVisibility(initiallyChecked.value);
                }
            });
        </script>
    </body>
</html>
