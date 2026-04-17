<script setup>
import EmployerHrManagementPanel from '@/components/Employer/EmployerHrManagementPanel.vue'
</script>

<template>
  <div class="mx-auto max-w-6xl space-y-8">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Quản lý nhân sự HR</h1>
        <p class="mt-1 text-sm leading-7 text-slate-500 dark:text-slate-400">
          Tách riêng module quản lý HR nội bộ để employer theo dõi thành viên, vai trò và quyền thao tác rõ ràng hơn.
        </p>
      </div>
    </div>

    <EmployerHrManagementPanel />
  </div>
</template>
