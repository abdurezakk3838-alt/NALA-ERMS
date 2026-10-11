<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Reports</h1>
        <p class="mt-1 text-slate-500">Generate operational and compliance summaries from current system records.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <button type="button" onclick="generateReport('daily')" class="rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-sky-300 hover:shadow-md">
            <i class="fas fa-calendar-day text-2xl text-sky-600"></i><h2 class="mt-3 font-bold text-slate-800">Daily Register</h2><p class="mt-1 text-sm text-slate-500">Today’s incoming, outgoing, and internal records.</p>
        </button>
        <button type="button" onclick="generateReport('monthly')" class="rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-sky-300 hover:shadow-md">
            <i class="fas fa-chart-column text-2xl text-indigo-600"></i><h2 class="mt-3 font-bold text-slate-800">Monthly Summary</h2><p class="mt-1 text-sm text-slate-500">Record volumes for the current month.</p>
        </button>
        <button type="button" onclick="generateReport('workflow')" class="rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-sky-300 hover:shadow-md">
            <i class="fas fa-diagram-project text-2xl text-amber-600"></i><h2 class="mt-3 font-bold text-slate-800">Workflow Performance</h2><p class="mt-1 text-sm text-slate-500">Approval workload and workflow progress.</p>
        </button>
        <button type="button" onclick="generateReport('compliance')" class="rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-sky-300 hover:shadow-md">
            <i class="fas fa-shield-halved text-2xl text-emerald-600"></i><h2 class="mt-3 font-bold text-slate-800">Compliance</h2><p class="mt-1 text-sm text-slate-500">Audit activity and records-management status.</p>
        </button>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-800">Report preview</h2>
        <div id="reportPreview" class="mt-4 min-h-28 rounded-xl bg-slate-50 p-5 text-slate-500">Choose a report above to generate its current summary.</div>
    </div>
</div>
