<script setup>
</script>

<template>
  <!-- Page Header -->
  <div class="flex flex-wrap justify-between items-end gap-4">
    <div class="space-y-1">
      <h1 class="text-3xl font-black tracking-tight">Job Postings</h1>
      <p class="text-slate-500 dark:text-slate-400">Review and moderate job listings across the entire ecosystem.</p>
    </div>
    <div class="flex gap-3">
      <button class="flex items-center gap-2 px-4 py-2.5 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl text-sm font-bold hover:bg-slate-300 transition-all">
        <span class="material-symbols-outlined text-xl">file_download</span> Export
      </button>
      <button class="flex items-center gap-2 px-6 py-2.5 bg-[#2463eb] text-white rounded-xl text-sm font-bold shadow-lg shadow-[#2463eb]/25 hover:bg-[#2463eb]/90 transition-all">
        <span class="material-symbols-outlined text-xl">add</span> Post New Job
      </button>
    </div>
  </div>

  <!-- Statistics Bar -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
      <p class="text-slate-500 text-xs font-bold uppercase mb-1">Total Postings</p>
      <div class="flex items-baseline gap-2">
        <span class="text-2xl font-black">1,284</span>
        <span class="text-emerald-500 text-xs font-bold">+12%</span>
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
      <p class="text-slate-500 text-xs font-bold uppercase mb-1">Active Now</p>
      <div class="flex items-baseline gap-2">
        <span class="text-2xl font-black">842</span>
        <span class="text-slate-400 text-xs font-medium">65% of total</span>
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
      <p class="text-slate-500 text-xs font-bold uppercase mb-1">Avg AI Match</p>
      <div class="flex items-baseline gap-2">
        <span class="text-2xl font-black">88%</span>
        <span class="text-[#2463eb] text-xs font-bold">High Quality</span>
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
      <p class="text-slate-500 text-xs font-bold uppercase mb-1">Flagged</p>
      <div class="flex items-baseline gap-2">
        <span class="text-2xl font-black">24</span>
        <span class="text-red-500 text-xs font-bold">Needs Review</span>
      </div>
    </div>
  </div>

  <!-- Tabs & Filters -->
  <div class="flex flex-col gap-4">
    <div class="border-b border-slate-200 dark:border-slate-800 flex gap-8 overflow-x-auto">
      <button class="pb-3 text-sm font-bold border-b-2 border-[#2463eb] text-[#2463eb] whitespace-nowrap">All Jobs</button>
      <button class="pb-3 text-sm font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-700 whitespace-nowrap">Active Listings</button>
      <button class="pb-3 text-sm font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-700 whitespace-nowrap">Expired</button>
      <button class="pb-3 text-sm font-bold border-b-2 border-transparent text-red-500 hover:text-red-600 whitespace-nowrap flex items-center gap-1">
        Flagged <span class="bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">24</span>
      </button>
    </div>
    <div class="flex flex-wrap gap-3">
      <button class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-sm font-medium">
        <span class="material-symbols-outlined text-lg">filter_list</span> Filter
      </button>
      <button class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[#2463eb]/10 border border-[#2463eb]/20 text-[#2463eb] text-sm font-medium">
        AI Match: 90%+ <span class="material-symbols-outlined text-lg">close</span>
      </button>
    </div>
  </div>

  <!-- Job Table -->
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
          <tr>
            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Job Title &amp; ID</th>
            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Company</th>
            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Date Posted</th>
            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">Status</th>
            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider">AI Match</th>
            <th class="px-6 py-4 text-xs font-bold uppercase text-slate-500 tracking-wider text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-4"><div class="flex flex-col"><span class="text-sm font-bold">Senior Product Designer</span><span class="text-xs text-slate-400">#JB-48293 • Remote</span></div></td>
            <td class="px-6 py-4"><div class="flex items-center gap-2"><div class="h-8 w-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 font-bold text-xs">TC</div><span class="text-sm font-medium">TechCorp Solutions</span></div></td>
            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Oct 24, 2023</td>
            <td class="px-6 py-4"><span class="px-2.5 py-1 text-[11px] font-bold uppercase rounded-full bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-400">Active</span></td>
            <td class="px-6 py-4"><div class="flex items-center gap-2"><div class="w-24 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden"><div class="bg-[#2463eb] h-full rounded-full" style="width: 92%"></div></div><span class="text-xs font-bold text-[#2463eb]">92%</span><span class="text-xs text-slate-400">(42)</span></div></td>
            <td class="px-6 py-4 text-right"><div class="flex justify-end gap-2"><button class="p-2 text-slate-400 hover:text-[#2463eb] transition-colors"><span class="material-symbols-outlined text-xl">star</span></button><button class="p-2 text-slate-400 hover:text-slate-600 transition-colors"><span class="material-symbols-outlined text-xl">edit</span></button><button class="p-2 text-slate-400 hover:text-slate-600 transition-colors"><span class="material-symbols-outlined text-xl">more_vert</span></button></div></td>
          </tr>
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-4"><div class="flex flex-col"><span class="text-sm font-bold">Marketing Strategist</span><span class="text-xs text-slate-400">#JB-48301 • New York, NY</span></div></td>
            <td class="px-6 py-4"><div class="flex items-center gap-2"><div class="h-8 w-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 font-bold text-xs">GL</div><span class="text-sm font-medium">GrowthLabs</span></div></td>
            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Oct 23, 2023</td>
            <td class="px-6 py-4"><span class="px-2.5 py-1 text-[11px] font-bold uppercase rounded-full bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">Flagged</span></td>
            <td class="px-6 py-4"><div class="flex items-center gap-2"><div class="w-24 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden"><div class="bg-[#2463eb] h-full rounded-full" style="width: 45%"></div></div><span class="text-xs font-bold text-[#2463eb]">45%</span><span class="text-xs text-slate-400">(12)</span></div></td>
            <td class="px-6 py-4 text-right"><div class="flex justify-end gap-2"><button class="px-3 py-1 bg-red-500 text-white rounded-lg text-xs font-bold">Moderate</button><button class="p-2 text-slate-400 hover:text-slate-600 transition-colors"><span class="material-symbols-outlined text-xl">more_vert</span></button></div></td>
          </tr>
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-4"><div class="flex flex-col"><span class="text-sm font-bold">Backend Engineer (Go)</span><span class="text-xs text-slate-400">#JB-48255 • Austin, TX</span></div></td>
            <td class="px-6 py-4"><div class="flex items-center gap-2"><div class="h-8 w-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 font-bold text-xs">FI</div><span class="text-sm font-medium">Fintech Inc.</span></div></td>
            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Oct 21, 2023</td>
            <td class="px-6 py-4"><span class="px-2.5 py-1 text-[11px] font-bold uppercase rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">Expired</span></td>
            <td class="px-6 py-4"><div class="flex items-center gap-2"><div class="w-24 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden"><div class="bg-[#2463eb] h-full rounded-full" style="width: 88%"></div></div><span class="text-xs font-bold text-[#2463eb]">88%</span><span class="text-xs text-slate-400">(84)</span></div></td>
            <td class="px-6 py-4 text-right"><div class="flex justify-end gap-2"><button class="p-2 text-[#2463eb] hover:bg-[#2463eb]/5 rounded-lg transition-colors"><span class="material-symbols-outlined text-xl">refresh</span></button><button class="p-2 text-slate-400 hover:text-slate-600 transition-colors"><span class="material-symbols-outlined text-xl">more_vert</span></button></div></td>
          </tr>
          <tr class="bg-[#2463eb]/5 hover:bg-[#2463eb]/10 transition-colors">
            <td class="px-6 py-4 border-l-4 border-[#2463eb]"><div class="flex flex-col"><div class="flex items-center gap-2"><span class="text-sm font-bold">Fullstack Developer</span><span class="material-symbols-outlined text-[#2463eb] text-sm">stars</span></div><span class="text-xs text-slate-400">#JB-48220 • Remote</span></div></td>
            <td class="px-6 py-4"><div class="flex items-center gap-2"><div class="h-8 w-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 font-bold text-xs">SH</div><span class="text-sm font-medium">Startup Hub</span></div></td>
            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Oct 20, 2023</td>
            <td class="px-6 py-4"><span class="px-2.5 py-1 text-[11px] font-bold uppercase rounded-full bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-400">Active</span></td>
            <td class="px-6 py-4"><div class="flex items-center gap-2"><div class="w-24 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden"><div class="bg-[#2463eb] h-full rounded-full" style="width: 95%"></div></div><span class="text-xs font-bold text-[#2463eb]">95%</span><span class="text-xs text-slate-400">(156)</span></div></td>
            <td class="px-6 py-4 text-right"><div class="flex justify-end gap-2"><button class="p-2 text-[#2463eb] transition-colors"><span class="material-symbols-outlined text-xl">star</span></button><button class="p-2 text-slate-400 hover:text-slate-600 transition-colors"><span class="material-symbols-outlined text-xl">more_vert</span></button></div></td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- Pagination -->
    <div class="bg-slate-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-between border-t border-slate-200 dark:border-slate-800">
      <span class="text-xs font-medium text-slate-500">Showing 1 to 10 of 1,284 results</span>
      <div class="flex gap-2">
        <button class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 disabled:opacity-50" disabled><span class="material-symbols-outlined">chevron_left</span></button>
        <button class="px-3 py-1 rounded-lg bg-[#2463eb] text-white text-sm font-bold">1</button>
        <button class="px-3 py-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-sm font-medium">2</button>
        <button class="px-3 py-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-sm font-medium">3</button>
        <button class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400"><span class="material-symbols-outlined">chevron_right</span></button>
      </div>
    </div>
  </div>
</template>
