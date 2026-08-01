<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $title ?? __('Module Master') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-5">
                    <div class="grid gap-4 md:grid-cols-[1fr_auto_auto] md:items-center">
                        <div>
                            <p class="text-sm text-slate-600">{{ $description ?? __('Manage application modules and module assignments.') }}</p>
                        </div>
                        <div class="flex items-center justify-end rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"></path></svg>
                            <input id="module-search" type="text" placeholder="Search" class="ml-2 w-36 bg-transparent text-sm text-slate-900 placeholder:text-slate-400 outline-none" />
                        </div>
                        <button type="button" onclick="openModuleMasterModal()" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">Add New</button>
                    </div>
                </div>
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" onclick="exportModuleTable('csv')" class="rounded-lg border border-emerald-300 bg-emerald-100 px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-200">Export CSV</button>
                        <button type="button" onclick="exportModuleTable('xlsx')" class="rounded-lg border border-emerald-300 bg-emerald-100 px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-200">Export XLSX</button>
                        <button type="button" onclick="exportModuleTable('pdf')" class="rounded-lg border border-emerald-300 bg-emerald-100 px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-200">Export PDF</button>
                    </div>
                </div>
                @if(session('success') || session('error'))
                    <div class="px-5 py-4" id="module-message-container">
                        <div id="module-message" class="relative rounded-2xl px-4 py-3 text-sm font-semibold shadow-sm {{ session('success') ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                            <span>{{ session('success') ?? session('error') }}</span>
                            <button type="button" onclick="closeModuleMessage()" class="absolute right-3 top-3 rounded-full bg-white/80 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-white focus:outline-none">Close</button>
                        </div>
                    </div>
                @endif
                <div class="overflow-x-auto">
                    <div class="max-h-[420px] overflow-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-100 sticky top-0 z-10">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Module Name</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Application</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Owner</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Status</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr>
                                    <td class="px-5 py-3 text-sm font-semibold text-slate-900">User Management</td>
                                    <td class="px-5 py-3 text-sm text-slate-600">Issue Tracker</td>
                                    <td class="px-5 py-3 text-sm text-slate-600">Rohit Singh</td>
                                    <td class="px-5 py-3 text-sm"><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Active</span></td>
                                    <td class="px-5 py-3 text-sm">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" data-module-name="User Management" data-application="Issue Tracker" data-owner="Rohit Singh" data-status="active" onclick="editModule(this.dataset)" class="rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Edit</button>
                                            <button type="button" class="rounded-lg bg-rose-100 px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-200">Disable</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-3 text-sm font-semibold text-slate-900">Reporting</td>
                                    <td class="px-5 py-3 text-sm text-slate-600">Analytics Suite</td>
                                    <td class="px-5 py-3 text-sm text-slate-600">Sonal Mehta</td>
                                    <td class="px-5 py-3 text-sm"><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Inactive</span></td>
                                    <td class="px-5 py-3 text-sm">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" data-module-name="Reporting" data-application="Analytics Suite" data-owner="Sonal Mehta" data-status="inactive" onclick="editModule(this.dataset)" class="rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Edit</button>
                                            <button type="button" class="rounded-lg bg-emerald-100 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-200">Activate</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="module-master-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 px-4 py-8">
        <div class="mx-auto flex max-w-2xl flex-col rounded-3xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 id="module-modal-title" class="text-lg font-semibold text-slate-900">Add Module</h3>
                    <p class="text-sm text-slate-600">Create or update a module entry.</p>
                </div>
                <button type="button" onclick="closeModuleMasterModal()" class="rounded-full p-2 bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-900">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="space-y-4 px-5 py-5">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Module Name</label>
                    <input id="module_name" type="text" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-slate-400" placeholder="Enter module name" />
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Application</label>
                        <input id="module_application" type="text" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-slate-400" placeholder="Enter application" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Owner</label>
                        <input id="module_owner" type="text" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-slate-400" placeholder="Enter owner" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                    <textarea id="module_description" rows="4" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-slate-400" placeholder="Add description"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" onclick="closeModuleMasterModal()" class="rounded-xl border border-slate-300 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Cancel</button>
                    <button type="button" onclick="saveModuleEntry()" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" id="module-modal-submit">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModuleMasterModal() {
            document.getElementById('module-modal-title').textContent = 'Add Module';
            document.getElementById('module_name').value = '';
            document.getElementById('module_application').value = '';
            document.getElementById('module_owner').value = '';
            document.getElementById('module_description').value = '';
            document.getElementById('module-modal-submit').textContent = 'Save';
            document.getElementById('module-master-modal').classList.remove('hidden');
        }

        function closeModuleMasterModal() {
            document.getElementById('module-master-modal').classList.add('hidden');
        }

        function editModule(data) {
            document.getElementById('module-modal-title').textContent = 'Edit Module';
            document.getElementById('module_name').value = data.moduleName || '';
            document.getElementById('module_application').value = data.application || '';
            document.getElementById('module_owner').value = data.owner || '';
            document.getElementById('module_description').value = '';
            document.getElementById('module-modal-submit').textContent = 'Update';
            document.getElementById('module-master-modal').classList.remove('hidden');
        }

        function exportModuleTable(format) {
            const params = new URLSearchParams({ format });
            window.location.href = window.location.pathname + '?' + params.toString();
        }

        function closeModuleMessage() {
            const container = document.getElementById('module-message-container');
            if (container) {
                container.style.transition = 'opacity 0.4s ease';
                container.style.opacity = '0';
                setTimeout(() => container.remove(), 400);
            }
        }

        function filterModules() {
            const query = document.getElementById('module-search').value.trim().toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            let visibleCount = 0;
            rows.forEach((row) => {
                const text = row.textContent.toLowerCase();
                const match = query === '' || text.includes(query);
                row.classList.toggle('hidden', !match);
                if (match) visibleCount += 1;
            });
            const emptyRow = document.querySelector('tbody tr.empty-row');
            if (emptyRow) emptyRow.classList.toggle('hidden', visibleCount !== 0);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('module-search');
            if (searchInput) {
                searchInput.addEventListener('input', filterModules);
            }
        });
    </script>
</x-app-layout>
