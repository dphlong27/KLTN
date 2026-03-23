<script setup>
</script>

<template>
  <!-- Welcome Section -->
  <div class="flex items-end justify-between">
    <div>
      <h2 class="text-3xl font-extrabold tracking-tight">Dashboard Overview</h2>
      <p class="text-slate-500 dark:text-slate-400 mt-1">Real-time platform performance and growth metrics.</p>
    </div>
    <div class="flex gap-2">
      <div class="flex bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-1">
        <button class="px-3 py-1.5 text-sm font-medium rounded-md bg-slate-100 dark:bg-slate-800">12 Months</button>
        <button class="px-3 py-1.5 text-sm font-medium rounded-md text-slate-500">30 Days</button>
        <button class="px-3 py-1.5 text-sm font-medium rounded-md text-slate-500">7 Days</button>
      </div>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex justify-between items-start mb-4">
        <div class="size-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
          <span class="material-symbols-outlined">person_add</span>
        </div>
        <span class="text-emerald-500 text-sm font-bold flex items-center bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded-full">
          <span class="material-symbols-outlined text-sm">trending_up</span> 5.2%
        </span>
      </div>
      <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Total Users</p>
      <h3 class="text-2xl font-bold mt-1">12,840</h3>
      <div class="mt-4 h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
        <div class="h-full bg-blue-600 rounded-full" style="width: 70%"></div>
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex justify-between items-start mb-4">
        <div class="size-10 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600">
          <span class="material-symbols-outlined">work</span>
        </div>
        <span class="text-emerald-500 text-sm font-bold flex items-center bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded-full">
          <span class="material-symbols-outlined text-sm">trending_up</span> 3.1%
        </span>
      </div>
      <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Active Jobs</p>
      <h3 class="text-2xl font-bold mt-1">1,240</h3>
      <div class="mt-4 h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
        <div class="h-full bg-purple-600 rounded-full" style="width: 45%"></div>
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex justify-between items-start mb-4">
        <div class="size-10 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
          <span class="material-symbols-outlined">description</span>
        </div>
        <span class="text-emerald-500 text-sm font-bold flex items-center bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded-full">
          <span class="material-symbols-outlined text-sm">trending_up</span> 8.4%
        </span>
      </div>
      <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Applications</p>
      <h3 class="text-2xl font-bold mt-1">45,200</h3>
      <div class="mt-4 h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
        <div class="h-full bg-amber-600 rounded-full" style="width: 85%"></div>
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex justify-between items-start mb-4">
        <div class="size-10 rounded-lg bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center text-rose-600">
          <span class="material-symbols-outlined">show_chart</span>
        </div>
        <span class="text-emerald-500 text-sm font-bold flex items-center bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded-full">
          <span class="material-symbols-outlined text-sm">trending_up</span> 1.2%
        </span>
      </div>
      <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Avg. Growth</p>
      <h3 class="text-2xl font-bold mt-1">12.5%</h3>
      <div class="mt-4 h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
        <div class="h-full bg-rose-600 rounded-full" style="width: 62%"></div>
      </div>
    </div>
  </div>

  <!-- Charts Section -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col">
      <div class="flex items-center justify-between mb-6">
        <h4 class="font-bold text-lg">Platform Growth</h4>
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2"><span class="size-2.5 rounded-full bg-[#2463eb]"></span><span class="text-xs text-slate-500">Users</span></div>
          <div class="flex items-center gap-2"><span class="size-2.5 rounded-full bg-slate-300 dark:bg-slate-700"></span><span class="text-xs text-slate-500">Applications</span></div>
        </div>
      </div>
      <div class="flex-1 min-h-[300px] relative">
        <div class="absolute inset-0 flex items-end justify-between px-2 gap-4">
          <div class="w-full flex flex-col items-center gap-2"><div class="w-full bg-[#2463eb]/20 rounded-t-lg hover:bg-[#2463eb]/40 transition-all" style="height: 40%"></div><span class="text-[10px] font-medium text-slate-400">Jan</span></div>
          <div class="w-full flex flex-col items-center gap-2"><div class="w-full bg-[#2463eb]/20 rounded-t-lg hover:bg-[#2463eb]/40 transition-all" style="height: 55%"></div><span class="text-[10px] font-medium text-slate-400">Feb</span></div>
          <div class="w-full flex flex-col items-center gap-2"><div class="w-full bg-[#2463eb]/20 rounded-t-lg hover:bg-[#2463eb]/40 transition-all" style="height: 48%"></div><span class="text-[10px] font-medium text-slate-400">Mar</span></div>
          <div class="w-full flex flex-col items-center gap-2"><div class="w-full bg-[#2463eb] rounded-t-lg hover:bg-[#2463eb]/90 transition-all" style="height: 75%"></div><span class="text-[10px] font-bold text-[#2463eb]">Apr</span></div>
          <div class="w-full flex flex-col items-center gap-2"><div class="w-full bg-[#2463eb]/20 rounded-t-lg hover:bg-[#2463eb]/40 transition-all" style="height: 65%"></div><span class="text-[10px] font-medium text-slate-400">May</span></div>
          <div class="w-full flex flex-col items-center gap-2"><div class="w-full bg-[#2463eb]/20 rounded-t-lg hover:bg-[#2463eb]/40 transition-all" style="height: 85%"></div><span class="text-[10px] font-medium text-slate-400">Jun</span></div>
          <div class="w-full flex flex-col items-center gap-2"><div class="w-full bg-[#2463eb]/20 rounded-t-lg hover:bg-[#2463eb]/40 transition-all" style="height: 90%"></div><span class="text-[10px] font-medium text-slate-400">Jul</span></div>
        </div>
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <h4 class="font-bold text-lg mb-6">Top Popular Skills</h4>
      <div class="space-y-6">
        <div class="space-y-2">
          <div class="flex justify-between text-sm"><span class="font-medium">React / Next.js</span><span class="text-slate-500 font-semibold">82%</span></div>
          <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden"><div class="h-full bg-[#2463eb] rounded-full" style="width: 82%"></div></div>
        </div>
        <div class="space-y-2">
          <div class="flex justify-between text-sm"><span class="font-medium">Python &amp; AI</span><span class="text-slate-500 font-semibold">74%</span></div>
          <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden"><div class="h-full bg-emerald-500 rounded-full" style="width: 74%"></div></div>
        </div>
        <div class="space-y-2">
          <div class="flex justify-between text-sm"><span class="font-medium">Cloud Architecture</span><span class="text-slate-500 font-semibold">61%</span></div>
          <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden"><div class="h-full bg-amber-500 rounded-full" style="width: 61%"></div></div>
        </div>
        <div class="space-y-2">
          <div class="flex justify-between text-sm"><span class="font-medium">UX Design</span><span class="text-slate-500 font-semibold">45%</span></div>
          <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden"><div class="h-full bg-rose-500 rounded-full" style="width: 45%"></div></div>
        </div>
      </div>
    </div>
  </div>
</template>
