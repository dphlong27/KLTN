<script setup>
</script>

<template>
  <!-- Header Actions -->
  <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
      <h1 class="text-3xl font-black tracking-tight">Skill Inventory</h1>
      <p class="text-slate-500 mt-1">Configure and manage skills for advanced AI talent matching and project staffing.</p>
    </div>
    <div class="flex gap-3">
      <button class="flex items-center gap-2 px-4 h-11 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50">
        <span class="material-symbols-outlined">file_download</span> Export
      </button>
      <button class="flex items-center gap-2 px-5 h-11 bg-[#2463eb] text-white rounded-xl text-sm font-bold shadow-lg shadow-[#2463eb]/20 hover:bg-[#2463eb]/90 transition-all">
        <span class="material-symbols-outlined">add</span> Add New Skill
      </button>
    </div>
  </div>

  <!-- Summary Stats -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <p class="text-slate-500 text-sm font-medium">Total Tracked Skills</p>
      <div class="flex items-center justify-between mt-2">
        <p class="text-3xl font-bold">1,284</p>
        <span class="text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 rounded text-xs font-bold">+12 this month</span>
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <p class="text-slate-500 text-sm font-medium">Active Categories</p>
      <div class="flex items-center justify-between mt-2">
        <p class="text-3xl font-bold">12</p>
        <span class="text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded text-xs font-bold">Standardized</span>
      </div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <p class="text-slate-500 text-sm font-medium">Matching Accuracy</p>
      <div class="flex items-center justify-between mt-2">
        <p class="text-3xl font-bold">94.2%</p>
        <span class="text-[#2463eb] bg-[#2463eb]/10 px-2 py-1 rounded text-xs font-bold">AI Optimized</span>
      </div>
    </div>
  </div>

  <!-- Skill Table -->
  <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col">
    <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <button class="px-4 py-2 text-sm font-bold bg-[#2463eb]/10 text-[#2463eb] rounded-lg">All Skills</button>
        <button class="px-4 py-2 text-sm font-medium text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg">Technical</button>
        <button class="px-4 py-2 text-sm font-medium text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg">Soft Skills</button>
        <button class="px-4 py-2 text-sm font-medium text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg">Management</button>
      </div>
      <div class="flex items-center gap-3">
        <select class="bg-slate-100 dark:bg-slate-800 border-none rounded-lg text-sm text-slate-600 dark:text-slate-400 h-9 pl-3 pr-8">
          <option>Usage: High to Low</option>
          <option>Alphabetical</option>
          <option>Recently Added</option>
        </select>
        <button class="size-9 flex items-center justify-center bg-slate-100 dark:bg-slate-800 rounded-lg text-slate-500">
          <span class="material-symbols-outlined text-[20px]">filter_list</span>
        </button>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
          <tr>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Skill Name</th>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Category</th>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tags</th>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Usage Freq.</th>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">AI Score</th>
            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-4"><div class="flex items-center gap-3"><div class="size-8 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center font-bold text-xs">JS</div><span class="font-bold">React.js</span></div></td>
            <td class="px-6 py-4"><span class="px-2.5 py-1 bg-blue-50 dark:bg-blue-500/10 text-blue-600 text-xs font-bold rounded">Technical</span></td>
            <td class="px-6 py-4"><div class="flex gap-1"><span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] rounded font-medium">Frontend</span><span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] rounded font-medium">Web</span></div></td>
            <td class="px-6 py-4"><div class="flex flex-col gap-1.5 w-24"><div class="flex justify-between text-[10px] font-bold"><span>82%</span><span class="text-slate-400">High</span></div><div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full"><div class="h-full bg-[#2463eb] rounded-full" style="width: 82%"></div></div></div></td>
            <td class="px-6 py-4 text-sm font-semibold text-emerald-500">9.8/10</td>
            <td class="px-6 py-4 text-right"><button class="text-slate-400 hover:text-[#2463eb]"><span class="material-symbols-outlined text-[20px]">edit</span></button><button class="text-slate-400 hover:text-red-500 ml-3"><span class="material-symbols-outlined text-[20px]">delete</span></button></td>
          </tr>
          <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-4"><div class="flex items-center gap-3"><div class="size-8 rounded bg-orange-100 dark:bg-orange-900/30 text-orange-600 flex items-center justify-center font-bold text-xs">EQ</div><span class="font-bold">Conflict Resolution</span></div></td>
            <td class="px-6 py-4"><span class="px-2.5 py-1 bg-orange-50 dark:bg-orange-500/10 text-orange-600 text-xs font-bold rounded">Soft Skills</span></td>
            <td class="px-6 py-4"><div class="flex gap-1"><span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] rounded font-medium">Management</span><span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] rounded font-medium">EQ</span></div></td>
            <td class="px-6 py-4"><div class="flex flex-col gap-1.5 w-24"><div class="flex justify-between text-[10px] font-bold"><span>45%</span><span class="text-slate-400">Med</span></div><div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full"><div class="h-full bg-[#2463eb] rounded-full" style="width: 45%"></div></div></div></td>
            <td class="px-6 py-4 text-sm font-semibold text-emerald-500">8.4/10</td>
            <td class="px-6 py-4 text-right"><button class="text-slate-400 hover:text-[#2463eb]"><span class="material-symbols-outlined text-[20px]">edit</span></button><button class="text-slate-400 hover:text-red-500 ml-3"><span class="material-symbols-outlined text-[20px]">delete</span></button></td>
          </tr>
          <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-4"><div class="flex items-center gap-3"><div class="size-8 rounded bg-purple-100 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center font-bold text-xs">AI</div><span class="font-bold">TensorFlow</span></div></td>
            <td class="px-6 py-4"><span class="px-2.5 py-1 bg-blue-50 dark:bg-blue-500/10 text-blue-600 text-xs font-bold rounded">Technical</span></td>
            <td class="px-6 py-4"><div class="flex gap-1"><span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] rounded font-medium">ML</span><span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] rounded font-medium">Python</span></div></td>
            <td class="px-6 py-4"><div class="flex flex-col gap-1.5 w-24"><div class="flex justify-between text-[10px] font-bold"><span>68%</span><span class="text-slate-400">High</span></div><div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full"><div class="h-full bg-[#2463eb] rounded-full" style="width: 68%"></div></div></div></td>
            <td class="px-6 py-4 text-sm font-semibold text-emerald-500">9.2/10</td>
            <td class="px-6 py-4 text-right"><button class="text-slate-400 hover:text-[#2463eb]"><span class="material-symbols-outlined text-[20px]">edit</span></button><button class="text-slate-400 hover:text-red-500 ml-3"><span class="material-symbols-outlined text-[20px]">delete</span></button></td>
          </tr>
          <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-6 py-4"><div class="flex items-center gap-3"><div class="size-8 rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center font-bold text-xs">UX</div><span class="font-bold">Figma Mastery</span></div></td>
            <td class="px-6 py-4"><span class="px-2.5 py-1 bg-blue-50 dark:bg-blue-500/10 text-blue-600 text-xs font-bold rounded">Technical</span></td>
            <td class="px-6 py-4"><div class="flex gap-1"><span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] rounded font-medium">Design</span><span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] rounded font-medium">UX/UI</span></div></td>
            <td class="px-6 py-4"><div class="flex flex-col gap-1.5 w-24"><div class="flex justify-between text-[10px] font-bold"><span>94%</span><span class="text-slate-400">Very High</span></div><div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full"><div class="h-full bg-[#2463eb] rounded-full" style="width: 94%"></div></div></div></td>
            <td class="px-6 py-4 text-sm font-semibold text-emerald-500">9.9/10</td>
            <td class="px-6 py-4 text-right"><button class="text-slate-400 hover:text-[#2463eb]"><span class="material-symbols-outlined text-[20px]">edit</span></button><button class="text-slate-400 hover:text-red-500 ml-3"><span class="material-symbols-outlined text-[20px]">delete</span></button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
      <p class="text-sm text-slate-500 font-medium">Showing 1-10 of 1,284 skills</p>
      <div class="flex items-center gap-2">
        <button class="size-8 flex items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400"><span class="material-symbols-outlined text-sm">chevron_left</span></button>
        <button class="size-8 flex items-center justify-center rounded-lg bg-[#2463eb] text-white font-bold text-xs">1</button>
        <button class="size-8 flex items-center justify-center rounded-lg text-slate-500 font-bold text-xs hover:bg-slate-50">2</button>
        <button class="size-8 flex items-center justify-center rounded-lg text-slate-500 font-bold text-xs hover:bg-slate-50">3</button>
        <span class="text-slate-400 text-xs">...</span>
        <button class="size-8 flex items-center justify-center rounded-lg text-slate-500 font-bold text-xs hover:bg-slate-50">128</button>
        <button class="size-8 flex items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400"><span class="material-symbols-outlined text-sm">chevron_right</span></button>
      </div>
    </div>
  </div>

  <!-- AI Insights -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="p-6 bg-slate-900 text-white rounded-xl flex items-center justify-between overflow-hidden relative">
      <div class="relative z-10 flex flex-col gap-2">
        <div class="flex items-center gap-2 text-[#2463eb]">
          <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
          <span class="text-xs font-bold uppercase tracking-widest">AI Suggestion</span>
        </div>
        <h3 class="text-xl font-bold">New skill trend detected: "Prompt Engineering"</h3>
        <p class="text-slate-400 text-sm max-w-sm">34% of recent job descriptions in your sector now require this. Consider adding it to your technical skill set.</p>
        <button class="mt-2 text-[#2463eb] text-sm font-bold flex items-center gap-1">Add to inventory <span class="material-symbols-outlined text-[16px]">arrow_forward</span></button>
      </div>
      <div class="absolute right-0 top-0 h-full w-1/3 opacity-20 bg-gradient-to-l from-[#2463eb] to-transparent pointer-events-none"></div>
    </div>
    <div class="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-between">
      <div class="flex flex-col gap-1">
        <h3 class="text-lg font-bold">Skill Coverage Gaps</h3>
        <p class="text-slate-500 text-sm">Your organization is currently low on "Cybersecurity" experts.</p>
        <div class="flex items-center gap-4 mt-3">
          <div class="flex -space-x-2">
            <div class="size-7 rounded-full border-2 border-white dark:border-slate-900 bg-slate-200"></div>
            <div class="size-7 rounded-full border-2 border-white dark:border-slate-900 bg-slate-300"></div>
            <div class="size-7 rounded-full border-2 border-white dark:border-slate-900 bg-slate-400"></div>
          </div>
          <span class="text-xs font-bold text-slate-400">0 matching employees</span>
        </div>
      </div>
      <button class="p-3 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-500">
        <span class="material-symbols-outlined">analytics</span>
      </button>
    </div>
  </div>
</template>
