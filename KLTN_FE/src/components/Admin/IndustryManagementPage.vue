<script setup>
</script>

<template>
  <!-- Page Header -->
  <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
    <div class="flex flex-col gap-2">
      <h1 class="text-3xl font-extrabold tracking-tight">Industry Management</h1>
      <p class="text-slate-500 dark:text-slate-400 text-base max-w-2xl">Create, categorize, and control industry sectors for the platform's job ecosystem and advanced filters.</p>
    </div>
    <button class="flex items-center gap-2 rounded-xl h-11 px-6 bg-[#2463eb] text-white text-sm font-bold shadow-lg shadow-[#2463eb]/20 hover:bg-[#2463eb]/90 transition-all">
      <span class="material-symbols-outlined text-sm">add</span> Add New Industry
    </button>
  </div>

  <!-- Stats Overview -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex items-center justify-between">
        <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold uppercase tracking-wider">Total Sectors</p>
        <span class="material-symbols-outlined text-[#2463eb] bg-[#2463eb]/10 p-2 rounded-lg">category</span>
      </div>
      <p class="text-3xl font-bold">42</p>
      <div class="flex items-center gap-1">
        <span class="text-emerald-500 text-sm font-bold">+2.4%</span>
        <span class="text-slate-400 text-xs">from last month</span>
      </div>
    </div>
    <div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex items-center justify-between">
        <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold uppercase tracking-wider">Active Status</p>
        <span class="material-symbols-outlined text-[#2463eb] bg-[#2463eb]/10 p-2 rounded-lg">check_circle</span>
      </div>
      <p class="text-3xl font-bold">38</p>
      <div class="flex items-center gap-1">
        <span class="text-emerald-500 text-sm font-bold">+1.2%</span>
        <span class="text-slate-400 text-xs">currently enabled</span>
      </div>
    </div>
    <div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex items-center justify-between">
        <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold uppercase tracking-wider">Avg. Listings</p>
        <span class="material-symbols-outlined text-[#2463eb] bg-[#2463eb]/10 p-2 rounded-lg">work</span>
      </div>
      <p class="text-3xl font-bold">156</p>
      <div class="flex items-center gap-1">
        <span class="text-rose-500 text-sm font-bold">-3.1%</span>
        <span class="text-slate-400 text-xs">jobs per industry</span>
      </div>
    </div>
  </div>

  <!-- Industry Table -->
  <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
    <div class="flex border-b border-slate-200 dark:border-slate-800 px-6 overflow-x-auto">
      <a class="flex items-center h-14 border-b-2 border-[#2463eb] text-[#2463eb] font-bold text-sm px-4 whitespace-nowrap" href="#">All Industries</a>
      <a class="flex items-center h-14 border-b-2 border-transparent text-slate-500 dark:text-slate-400 font-medium text-sm px-4 hover:text-slate-700 dark:hover:text-slate-200 whitespace-nowrap" href="#">Active (38)</a>
      <a class="flex items-center h-14 border-b-2 border-transparent text-slate-500 dark:text-slate-400 font-medium text-sm px-4 hover:text-slate-700 dark:hover:text-slate-200 whitespace-nowrap" href="#">Inactive (4)</a>
      <a class="flex items-center h-14 border-b-2 border-transparent text-slate-500 dark:text-slate-400 font-medium text-sm px-4 hover:text-slate-700 dark:hover:text-slate-200 whitespace-nowrap" href="#">Archived</a>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-slate-50 dark:bg-slate-800/50">
            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 w-[40%]">Industry Name</th>
            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Related Jobs</th>
            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Visibility</th>
            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-5">
              <div class="flex items-center gap-3">
                <div class="size-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center"><span class="material-symbols-outlined text-[20px]">laptop_mac</span></div>
                <div><p class="font-semibold">Information Technology</p><p class="text-slate-500 dark:text-slate-400 text-xs">Software, SaaS, Hardware</p></div>
              </div>
            </td>
            <td class="px-6 py-5">
              <div class="flex items-center gap-2"><span class="font-medium">1,240</span><span class="text-emerald-500 text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 px-1.5 py-0.5 rounded">High Demand</span></div>
            </td>
            <td class="px-6 py-5"><span class="px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 text-xs font-bold rounded">Active</span></td>
            <td class="px-6 py-5 text-right">
              <div class="flex justify-end gap-2">
                <button class="p-2 text-slate-400 hover:text-[#2463eb] rounded-lg transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                <button class="p-2 text-slate-400 hover:text-rose-500 rounded-lg transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
              </div>
            </td>
          </tr>
          <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-5">
              <div class="flex items-center gap-3">
                <div class="size-10 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 flex items-center justify-center"><span class="material-symbols-outlined text-[20px]">medical_services</span></div>
                <div><p class="font-semibold">Healthcare</p><p class="text-slate-500 dark:text-slate-400 text-xs">Pharma, Clinical, Medical Device</p></div>
              </div>
            </td>
            <td class="px-6 py-5"><span class="font-medium">850</span></td>
            <td class="px-6 py-5"><span class="px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 text-xs font-bold rounded">Active</span></td>
            <td class="px-6 py-5 text-right">
              <div class="flex justify-end gap-2">
                <button class="p-2 text-slate-400 hover:text-[#2463eb] rounded-lg transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                <button class="p-2 text-slate-400 hover:text-rose-500 rounded-lg transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
              </div>
            </td>
          </tr>
          <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-5">
              <div class="flex items-center gap-3">
                <div class="size-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center"><span class="material-symbols-outlined text-[20px]">payments</span></div>
                <div><p class="font-semibold">Finance</p><p class="text-slate-500 dark:text-slate-400 text-xs">Banking, Fintech, Insurance</p></div>
              </div>
            </td>
            <td class="px-6 py-5"><span class="font-medium">620</span></td>
            <td class="px-6 py-5"><span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-400 text-xs font-bold rounded">Inactive</span></td>
            <td class="px-6 py-5 text-right">
              <div class="flex justify-end gap-2">
                <button class="p-2 text-slate-400 hover:text-[#2463eb] rounded-lg transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                <button class="p-2 text-slate-400 hover:text-rose-500 rounded-lg transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
              </div>
            </td>
          </tr>
          <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-5">
              <div class="flex items-center gap-3">
                <div class="size-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center"><span class="material-symbols-outlined text-[20px]">school</span></div>
                <div><p class="font-semibold">Education</p><p class="text-slate-500 dark:text-slate-400 text-xs">K-12, EdTech, Higher Ed</p></div>
              </div>
            </td>
            <td class="px-6 py-5"><span class="font-medium">430</span></td>
            <td class="px-6 py-5"><span class="px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 text-xs font-bold rounded">Active</span></td>
            <td class="px-6 py-5 text-right">
              <div class="flex justify-end gap-2">
                <button class="p-2 text-slate-400 hover:text-[#2463eb] rounded-lg transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                <button class="p-2 text-slate-400 hover:text-rose-500 rounded-lg transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
              </div>
            </td>
          </tr>
          <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-5">
              <div class="flex items-center gap-3">
                <div class="size-10 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 flex items-center justify-center"><span class="material-symbols-outlined text-[20px]">precision_manufacturing</span></div>
                <div><p class="font-semibold">Manufacturing</p><p class="text-slate-500 dark:text-slate-400 text-xs">Automotive, Supply Chain, R&amp;D</p></div>
              </div>
            </td>
            <td class="px-6 py-5"><span class="font-medium">310</span></td>
            <td class="px-6 py-5"><span class="px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 text-xs font-bold rounded">Active</span></td>
            <td class="px-6 py-5 text-right">
              <div class="flex justify-end gap-2">
                <button class="p-2 text-slate-400 hover:text-[#2463eb] rounded-lg transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                <button class="p-2 text-slate-400 hover:text-rose-500 rounded-lg transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- Pagination -->
    <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 dark:border-slate-800">
      <p class="text-sm text-slate-500 dark:text-slate-400">Showing 1 to 5 of 42 industries</p>
      <div class="flex items-center gap-2">
        <button class="p-2 rounded-lg border border-slate-200 dark:border-slate-700 disabled:opacity-50" disabled><span class="material-symbols-outlined text-[18px]">chevron_left</span></button>
        <button class="px-3 py-1 rounded-lg bg-[#2463eb] text-white text-sm font-bold">1</button>
        <button class="px-3 py-1 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 text-sm font-medium">2</button>
        <button class="px-3 py-1 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 text-sm font-medium">3</button>
        <span class="text-slate-400">...</span>
        <button class="px-3 py-1 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 text-sm font-medium">9</button>
        <button class="p-2 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800"><span class="material-symbols-outlined text-[18px]">chevron_right</span></button>
      </div>
    </div>
  </div>

  <!-- Bulk Import -->
  <div class="mt-8 flex items-center justify-center p-8 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl bg-white/40 dark:bg-slate-900/40">
    <div class="text-center">
      <p class="font-semibold mb-2">Need to import bulk data?</p>
      <p class="text-slate-400 text-sm mb-4">Upload a CSV or XLSX file to add multiple industries at once.</p>
      <button class="px-4 py-2 border-2 border-[#2463eb] text-[#2463eb] hover:bg-[#2463eb] hover:text-white rounded-xl text-sm font-bold transition-all inline-flex items-center gap-2">
        <span class="material-symbols-outlined text-sm">upload_file</span> Bulk Import
      </button>
    </div>
  </div>
</template>
