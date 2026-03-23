<script setup>
</script>

<template>
  <!-- Header with Filters -->
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div class="flex flex-col gap-1">
      <h1 class="text-2xl font-bold">Analytics Overview</h1>
      <p class="text-slate-500 dark:text-slate-400">Detailed insights into your platform's health and growth.</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
      <div class="flex bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-1">
        <button class="px-4 py-1.5 text-sm font-medium bg-[#2463eb] text-white rounded-md shadow-sm">30 Days</button>
        <button class="px-4 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-[#2463eb] transition-colors">90 Days</button>
        <button class="px-4 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-[#2463eb] transition-colors">1 Year</button>
      </div>
      <button class="flex items-center gap-2 px-4 py-2 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50">
        <span class="material-symbols-outlined text-[18px]">calendar_today</span> Custom Range
      </button>
      <button class="flex items-center gap-2 px-4 py-2 bg-[#2463eb] text-white rounded-lg text-sm font-bold shadow-sm hover:bg-[#2463eb]/90 transition-all">
        <span class="material-symbols-outlined text-[18px]">download</span> Export PDF
      </button>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col gap-2">
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Seekers</span>
        <span class="text-emerald-500 flex items-center text-xs font-bold"><span class="material-symbols-outlined text-[14px]">trending_up</span> 12%</span>
      </div>
      <p class="text-3xl font-bold">24,580</p>
      <div class="mt-4 h-1 w-full bg-slate-100 dark:bg-slate-700 rounded-full"><div class="h-full bg-[#2463eb] rounded-full" style="width: 65%"></div></div>
    </div>
    <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col gap-2">
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Employers</span>
        <span class="text-emerald-500 flex items-center text-xs font-bold"><span class="material-symbols-outlined text-[14px]">trending_up</span> 8.4%</span>
      </div>
      <p class="text-3xl font-bold">1,240</p>
      <div class="mt-4 h-1 w-full bg-slate-100 dark:bg-slate-700 rounded-full"><div class="h-full bg-[#2463eb] rounded-full" style="width: 42%"></div></div>
    </div>
    <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col gap-2">
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Applications</span>
        <span class="text-red-500 flex items-center text-xs font-bold"><span class="material-symbols-outlined text-[14px]">trending_down</span> 2%</span>
      </div>
      <p class="text-3xl font-bold">18,290</p>
      <div class="mt-4 h-1 w-full bg-slate-100 dark:bg-slate-700 rounded-full"><div class="h-full bg-[#2463eb] rounded-full" style="width: 80%"></div></div>
    </div>
    <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col gap-2">
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Conversion Rate</span>
        <span class="text-emerald-500 flex items-center text-xs font-bold"><span class="material-symbols-outlined text-[14px]">trending_up</span> 5.1%</span>
      </div>
      <p class="text-3xl font-bold">14.2%</p>
      <div class="mt-4 h-1 w-full bg-slate-100 dark:bg-slate-700 rounded-full"><div class="h-full bg-[#2463eb] rounded-full" style="width: 30%"></div></div>
    </div>
  </div>

  <!-- Charts Section -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- User Growth Chart -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col gap-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-[#2463eb]">person_add</span> User Growth</h3>
        <div class="flex gap-4">
          <div class="flex items-center gap-2"><span class="size-3 rounded-full bg-[#2463eb]"></span><span class="text-xs text-slate-500">Seekers</span></div>
          <div class="flex items-center gap-2"><span class="size-3 rounded-full bg-slate-300 dark:bg-slate-600"></span><span class="text-xs text-slate-500">Employers</span></div>
        </div>
      </div>
      <div class="h-64 w-full flex items-end justify-between px-2 pt-4 relative">
        <div class="absolute inset-x-0 top-1/4 border-t border-slate-100 dark:border-slate-700"></div>
        <div class="absolute inset-x-0 top-2/4 border-t border-slate-100 dark:border-slate-700"></div>
        <div class="absolute inset-x-0 top-3/4 border-t border-slate-100 dark:border-slate-700"></div>
        <div class="flex flex-col gap-1 items-center flex-1"><div class="w-4 bg-[#2463eb] rounded-t-sm" style="height: 40%"></div><div class="w-4 bg-slate-300 dark:bg-slate-600 rounded-t-sm" style="height: 15%"></div><span class="text-[10px] text-slate-400 mt-2">Mon</span></div>
        <div class="flex flex-col gap-1 items-center flex-1"><div class="w-4 bg-[#2463eb] rounded-t-sm" style="height: 55%"></div><div class="w-4 bg-slate-300 dark:bg-slate-600 rounded-t-sm" style="height: 10%"></div><span class="text-[10px] text-slate-400 mt-2">Tue</span></div>
        <div class="flex flex-col gap-1 items-center flex-1"><div class="w-4 bg-[#2463eb] rounded-t-sm" style="height: 45%"></div><div class="w-4 bg-slate-300 dark:bg-slate-600 rounded-t-sm" style="height: 20%"></div><span class="text-[10px] text-slate-400 mt-2">Wed</span></div>
        <div class="flex flex-col gap-1 items-center flex-1"><div class="w-4 bg-[#2463eb] rounded-t-sm" style="height: 70%"></div><div class="w-4 bg-slate-300 dark:bg-slate-600 rounded-t-sm" style="height: 25%"></div><span class="text-[10px] text-slate-400 mt-2">Thu</span></div>
        <div class="flex flex-col gap-1 items-center flex-1"><div class="w-4 bg-[#2463eb] rounded-t-sm" style="height: 60%"></div><div class="w-4 bg-slate-300 dark:bg-slate-600 rounded-t-sm" style="height: 15%"></div><span class="text-[10px] text-slate-400 mt-2">Fri</span></div>
        <div class="flex flex-col gap-1 items-center flex-1"><div class="w-4 bg-[#2463eb] rounded-t-sm" style="height: 85%"></div><div class="w-4 bg-slate-300 dark:bg-slate-600 rounded-t-sm" style="height: 30%"></div><span class="text-[10px] text-slate-400 mt-2">Sat</span></div>
        <div class="flex flex-col gap-1 items-center flex-1"><div class="w-4 bg-[#2463eb] rounded-t-sm" style="height: 95%"></div><div class="w-4 bg-slate-300 dark:bg-slate-600 rounded-t-sm" style="height: 12%"></div><span class="text-[10px] text-slate-400 mt-2">Sun</span></div>
      </div>
    </div>
    <!-- Application Trends -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col gap-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-[#2463eb]">timeline</span> Application Trends</h3>
        <select class="text-xs bg-slate-50 dark:bg-slate-900 border-none rounded-md py-1 px-3">
          <option>Daily</option><option>Weekly</option><option>Monthly</option>
        </select>
      </div>
      <div class="h-64 w-full relative pt-4 overflow-hidden">
        <svg class="w-full h-full" preserveAspectRatio="none" viewBox="0 0 400 200">
          <defs><linearGradient id="grad" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" style="stop-color:#2463eb;stop-opacity:0.2" /><stop offset="100%" style="stop-color:#2463eb;stop-opacity:0" /></linearGradient></defs>
          <path d="M0,180 Q50,150 100,160 T200,80 T300,100 T400,40 L400,200 L0,200 Z" fill="url(#grad)" />
          <path d="M0,180 Q50,150 100,160 T200,80 T300,100 T400,40" fill="none" stroke="#2463eb" stroke-width="3" />
          <circle cx="200" cy="80" fill="#2463eb" r="4" />
        </svg>
      </div>
      <div class="flex justify-between text-[10px] text-slate-400 px-2">
        <span>Sept 15</span><span>Sept 22</span><span>Sept 29</span><span>Oct 06</span><span>Oct 13</span>
      </div>
    </div>

    <!-- Top Hiring Industries -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col gap-6">
      <h3 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-[#2463eb]">work_outline</span> Top Hiring Industries</h3>
      <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-2"><div class="flex items-center justify-between text-sm"><span class="font-medium">Software Development</span><span class="font-bold">42%</span></div><div class="h-2 w-full bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden"><div class="h-full bg-[#2463eb] rounded-full" style="width: 42%"></div></div></div>
        <div class="flex flex-col gap-2"><div class="flex items-center justify-between text-sm"><span class="font-medium">Healthcare</span><span class="font-bold">28%</span></div><div class="h-2 w-full bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden"><div class="h-full bg-[#2463eb] rounded-full" style="width: 28%"></div></div></div>
        <div class="flex flex-col gap-2"><div class="flex items-center justify-between text-sm"><span class="font-medium">E-commerce</span><span class="font-bold">18%</span></div><div class="h-2 w-full bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden"><div class="h-full bg-[#2463eb] rounded-full" style="width: 18%"></div></div></div>
        <div class="flex flex-col gap-2"><div class="flex items-center justify-between text-sm"><span class="font-medium">Fintech</span><span class="font-bold">12%</span></div><div class="h-2 w-full bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden"><div class="h-full bg-[#2463eb] rounded-full" style="width: 12%"></div></div></div>
      </div>
    </div>

    <!-- Regional Hiring Density -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col gap-4">
      <h3 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-[#2463eb]">location_on</span> Regional Hiring Density</h3>
      <div class="relative h-64 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-900">
        <div class="absolute top-[20%] left-[30%]"><div class="size-4 bg-[#2463eb] rounded-full animate-ping opacity-75"></div><div class="size-3 bg-[#2463eb] rounded-full absolute inset-0.5"></div></div>
        <div class="absolute top-[45%] left-[60%]"><div class="size-4 bg-[#2463eb] rounded-full animate-ping opacity-75"></div><div class="size-3 bg-[#2463eb] rounded-full absolute inset-0.5"></div></div>
        <div class="absolute top-[30%] left-[80%]"><div class="size-4 bg-[#2463eb] rounded-full animate-ping opacity-75"></div><div class="size-3 bg-[#2463eb] rounded-full absolute inset-0.5"></div></div>
        <div class="absolute bottom-4 left-4 right-4 bg-white/80 dark:bg-slate-800/80 backdrop-blur p-3 rounded-lg border border-white/20">
          <p class="text-xs font-bold uppercase">Hottest Region</p>
          <p class="text-lg font-bold text-[#2463eb]">San Francisco, USA</p>
          <p class="text-xs text-slate-500">1,200+ new listings today</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Reports Table -->
  <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
      <h3 class="font-bold">Generated Reports</h3>
      <button class="text-sm font-bold text-[#2463eb] hover:underline">View All</button>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">
            <th class="px-6 py-4">Report Name</th><th class="px-6 py-4">Date Range</th><th class="px-6 py-4">Status</th><th class="px-6 py-4">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
            <td class="px-6 py-4 flex items-center gap-3"><span class="material-symbols-outlined text-slate-400">picture_as_pdf</span><div><span class="text-sm font-bold">Monthly_Growth_Oct23.pdf</span><br/><span class="text-xs text-slate-500">Generated 2 hours ago</span></div></td>
            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Oct 01 - Oct 31, 2023</td>
            <td class="px-6 py-4"><span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-bold text-green-700 dark:text-green-400">Completed</span></td>
            <td class="px-6 py-4"><button class="text-slate-400 hover:text-[#2463eb]"><span class="material-symbols-outlined">download</span></button></td>
          </tr>
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
            <td class="px-6 py-4 flex items-center gap-3"><span class="material-symbols-outlined text-slate-400">table_view</span><div><span class="text-sm font-bold">Employer_Retention_Q3.csv</span><br/><span class="text-xs text-slate-500">Generated 1 day ago</span></div></td>
            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">Jul 01 - Sept 30, 2023</td>
            <td class="px-6 py-4"><span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-bold text-green-700 dark:text-green-400">Completed</span></td>
            <td class="px-6 py-4"><button class="text-slate-400 hover:text-[#2463eb]"><span class="material-symbols-outlined">download</span></button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
