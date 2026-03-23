import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  // Guest pages
  {
    path: '/',
    name: 'Landing',
    component: () => import('@/components/Guest/LandingPage.vue'),
    meta: { layout: 'guest' }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/components/Guest/RegisterPage.vue'),
    meta: { layout: 'auth' }
  },
  {
    path: '/auth',
    name: 'Auth',
    component: () => import('@/components/Guest/CandidateAuthPage.vue'),
    meta: { layout: 'guest' }
  },
  {
    path: '/employer/auth',
    name: 'EmployerAuth',
    component: () => import('@/components/Guest/CandidateAuthPage.vue'),
    meta: { layout: 'guest' }
  },
  {
    path: '/employer/register',
    name: 'EmployerRegister',
    component: () => import('@/components/Employer/EmployerRegisterPage.vue'),
    meta: { layout: 'auth' }
  },
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: () => import('@/components/Admin/AdminLoginPage.vue'),
    meta: { layout: 'auth' }
  },
  {
    path: '/jobs',
    name: 'JobSearch',
    component: () => import('@/components/Guest/JobSearchPage.vue'),
    meta: { layout: 'guest' }
  },
  {
    path: '/jobs/:id',
    name: 'JobDetail',
    component: () => import('@/components/Guest/JobDetailPage.vue'),
    meta: { layout: 'guest' }
  },
  {
    path: '/ai-career',
    name: 'AICareer',
    component: () => import('@/components/Guest/AICareerPage.vue'),
    meta: { layout: 'guest' }
  },
  // Dashboard pages (Job Seeker)
  {
    path: '/dashboard',
    name: 'SeekerDashboard',
    component: () => import('@/components/Dashboard/SeekerDashboardPage.vue'),
    meta: { layout: 'dashboard' }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('@/components/Dashboard/ProfilePage.vue'),
    meta: { layout: 'dashboard' }
  },
  {
    path: '/my-cv',
    name: 'MyCv',
    component: () => import('@/components/Dashboard/MyCvPage.vue'),
    meta: { layout: 'dashboard' }
  },
  {
    path: '/applications',
    name: 'Applications',
    component: () => import('@/components/Dashboard/ApplicationsPage.vue'),
    meta: { layout: 'dashboard' }
  },
  {
    path: '/saved-jobs',
    name: 'SavedJobs',
    component: () => import('@/components/Dashboard/SavedJobsPage.vue'),
    meta: { layout: 'dashboard' }
  },
  {
    path: '/matched-jobs',
    name: 'MatchedJobs',
    component: () => import('@/components/Dashboard/MatchedJobsPage.vue'),
    meta: { layout: 'dashboard' }
  },
    // Employer pages
  {
    path: '/employer',
    name: 'EmployerDashboard',
    component: () => import('@/components/Employer/EmployerDashboardPage.vue'),
    meta: { layout: 'employer' }
  },
  {
    path: '/employer/jobs',
    name: 'EmployerJobs',
    component: () => import('@/components/Employer/EmployerJobsPage.vue'),
    meta: { layout: 'employer' }
  },
  {
    path: '/employer/candidates',
    name: 'EmployerCandidates',
    component: () => import('@/components/Employer/EmployerCandidatesPage.vue'),
    meta: { layout: 'employer' }
  },
  {
    path: '/employer/interviews',
    name: 'EmployerInterviews',
    component: () => import('@/components/Employer/EmployerInterviewsPage.vue'),
    meta: { layout: 'employer' }
  },
  {
    path: '/employer/company',
    name: 'EmployerCompany',
    component: () => import('@/components/Employer/EmployerCompanyPage.vue'),
    meta: { layout: 'employer' }
  },
  // Admin pages
  {
    path: '/admin',
    name: 'AdminDashboard',
    component: () => import('@/components/Admin/AdminDashboardPage.vue'),
    meta: { layout: 'admin', requiresAdmin: true }
  },
  {
    path: '/admin/users',
    name: 'UserManagement',
    component: () => import('@/components/Admin/UserManagementPage.vue'),
    meta: { layout: 'admin', requiresAdmin: true }
  },
  {
    path: '/admin/companies',
    name: 'CompanyManagement',
    component: () => import('@/components/Admin/CompanyManagementPage.vue'),
    meta: { layout: 'admin', requiresAdmin: true }
  },
  {
    path: '/admin/industries',
    name: 'IndustryManagement',
    component: () => import('@/components/Admin/IndustryManagementPage.vue'),
    meta: { layout: 'admin', requiresAdmin: true }
  },
  {
    path: '/admin/skills',
    name: 'SkillManagement',
    component: () => import('@/components/Admin/SkillManagementPage.vue'),
    meta: { layout: 'admin', requiresAdmin: true }
  },
  {
    path: '/admin/jobs',
    name: 'JobPostingsManagement',
    component: () => import('@/components/Admin/JobPostingsManagementPage.vue'),
    meta: { layout: 'admin', requiresAdmin: true }
  },
  {
    path: '/admin/stats',
    name: 'StatsManagement',
    component: () => import('@/components/Admin/StatsManagementPage.vue'),
    meta: { layout: 'admin', requiresAdmin: true }
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  }
})

router.beforeEach((to) => {
  if (!to.meta?.requiresAdmin) {
    return true
  }

  const storedAdmin = localStorage.getItem('admin')
  const accessToken = localStorage.getItem('access_token') || localStorage.getItem('token')

  if (!storedAdmin || !accessToken) {
    return '/admin/login'
  }

  try {
    const admin = JSON.parse(storedAdmin)
    const adminRole = Number(admin?.vai_tro ?? admin?.role_id ?? admin?.role ?? -1)

    if (adminRole !== 2) {
      localStorage.removeItem('admin')
      localStorage.removeItem('access_token')
      localStorage.removeItem('token')
      return '/admin/login'
    }
  } catch (_) {
    localStorage.removeItem('admin')
    localStorage.removeItem('access_token')
    localStorage.removeItem('token')
    return '/admin/login'
  }

  return true
})

export default router
