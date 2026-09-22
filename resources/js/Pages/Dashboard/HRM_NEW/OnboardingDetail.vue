<template>
    <AuthenticatedLayout>
        <!-- START SCREEN LOADING OVERLAY -->
        <Transition name="fade">
            <div
                v-if="pageLoading"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-100/50 backdrop-blur-md"
                aria-live="polite"
            >
                <div class="flex flex-col items-center gap-3">
                    <div class="w-10 h-10 rounded-full border-4 border-slate-300 border-t-blue-600 animate-spin"></div>
                    <p class="text-sm font-semibold text-slate-600">Loading onboarding...</p>
                </div>
            </div>
        </Transition>

        <div class="flex min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-blue-50">
            <div class="flex-1 p-6 lg:p-8 text-slate-900 font-sans min-w-0 relative">
                <div class="absolute inset-0 bg-grid-slate-100 [mask-image:linear-gradient(0deg,transparent,black,transparent)] pointer-events-none"></div>

                <!-- HEADER -->
                <header class="mb-6 relative">
                    <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-3">
                        <Link :href="route('hrm.onboarding.status.index')" class="hover:text-slate-700 cursor-pointer transition-colors inline-flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Onboarding
                        </Link>
                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-slate-800 font-semibold">{{ onboarding.name }}</span>
                    </nav>

                    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                {{ initials }}
                            </div>
                            <div>
                                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">{{ onboarding.name }}</h1>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <span class="text-xs text-slate-500">{{ onboarding.position }}</span>
                                    <span class="text-[10px] text-slate-300">•</span>
                                    <span class="text-xs text-slate-500">{{ onboarding.department }}</span>
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-semibold border shadow-sm" :class="onboarding.status_badge">
                                        {{ onboarding.status }}
                                    </span>
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-semibold border bg-slate-50 text-slate-600 border-slate-200">
                                        Application: {{ onboarding.application_status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                @click="openModal('details')"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-semibold transition-all duration-200 shadow-sm hover:shadow-md"
                            >
                                <Pencil class="w-3.5 h-3.5" /> Edit Details
                            </button>
                            <button
                                v-if="!alreadyHired"
                                @click="confirmComplete"
                                :disabled="!canComplete"
                                :title="!canComplete ? 'Complete all required items and mandatory activities first.' : ''"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-200 shadow-lg"
                                :class="canComplete
                                    ? 'bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white shadow-emerald-500/25'
                                    : 'bg-slate-200 text-slate-500 cursor-not-allowed'
                                "
                            >
                                <CheckCircle2 class="w-3.5 h-3.5" /> Complete Onboarding
                            </button>
                            <button
                                v-if="!alreadyHired"
                                @click="confirmCreateEmployee"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white rounded-xl text-xs font-bold transition-all duration-200 shadow-lg hover:shadow-xl"
                            >
                                <UserPlus class="w-3.5 h-3.5" /> Create Employee
                            </button>
                            <div
                                v-else
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold"
                            >
                                <CheckCircle2 class="w-3.5 h-3.5" /> Employee Record Created
                            </div>
                        </div>
                    </div>

                    <!-- Missing items warning -->
                    <div v-if="missing.length > 0" class="mt-4 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                        <p class="text-xs font-bold text-amber-800 mb-1">Not yet ready to complete onboarding:</p>
                        <ul class="list-disc list-inside text-[11px] text-amber-700 space-y-0.5">
                            <li v-for="m in missing" :key="m">{{ m }}</li>
                        </ul>
                    </div>
                </header>

                <!-- PROGRESS -->
                <div class="bg-white rounded-xl border border-slate-200/60 shadow-md backdrop-blur-sm p-4 mb-5 relative">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-600">Overall Onboarding Progress</span>
                        <span class="text-sm font-bold text-blue-600">{{ progress }}%</span>
                    </div>
                    <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden shadow-inner">
                        <div class="h-full bg-gradient-to-r from-blue-500 to-emerald-500 rounded-full transition-all duration-700" :style="{ width: progress + '%' }"></div>
                    </div>
                </div>

                <!-- FINAL REVIEW -->
                <div class="bg-white rounded-xl border border-slate-200/60 shadow-md backdrop-blur-sm overflow-hidden mb-5 relative">
                    <div class="px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-transparent flex items-center gap-2">
                        <ClipboardCheck class="w-4 h-4 text-blue-600" />
                        <h2 class="text-sm font-bold text-slate-800">Final Review</h2>
                        <span class="ml-auto text-[10px] font-semibold text-slate-400">{{ onboarding.template || 'Template not set' }}</span>
                    </div>

                    <!-- Ready banner -->
                    <div
                        v-if="readiness.completed"
                        class="mx-4 mt-4 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 flex items-center gap-3"
                    >
                        <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                        <div>
                            <p class="text-xs font-bold text-emerald-800">Onboarding Completed</p>
                            <p class="text-[11px] text-emerald-700">The employee record can now be created. All required items and mandatory activities are done.</p>
                        </div>
                    </div>
                    <div
                        v-else-if="readiness.ready"
                        class="mx-4 mt-4 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 flex items-center gap-3"
                    >
                        <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                        <div>
                            <p class="text-xs font-bold text-emerald-800">Ready for Completion</p>
                            <p class="text-[11px] text-emerald-700">All required items and mandatory activities are done. You can complete the onboarding now.</p>
                        </div>
                    </div>
                    <div
                        v-else
                        class="mx-4 mt-4 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-center gap-3"
                    >
                        <X class="w-5 h-5 text-amber-600 shrink-0" />
                        <div>
                            <p class="text-xs font-bold text-amber-800">Not Yet Ready for Completion</p>
                            <p class="text-[11px] text-amber-700">Complete every required item and mandatory activity to unlock the onboarding completion.</p>
                        </div>
                    </div>

                    <!-- Readiness counts -->
                    <div class="p-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3">
                            <div class="flex items-center justify-between">
                                <FileText class="w-3.5 h-3.5 text-slate-400" />
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Required Items</span>
                            </div>
                            <p class="text-xl font-bold text-slate-800 mt-1.5">{{ readiness.required_items_done }}<span class="text-xs text-slate-400 font-semibold"> / {{ readiness.required_items_total }}</span></p>
                            <p class="text-[10px] text-slate-400 mt-0.5">verified or completed</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3">
                            <div class="flex items-center justify-between">
                                <CalendarDays class="w-3.5 h-3.5 text-slate-400" />
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Mandatory Activities</span>
                            </div>
                            <p class="text-xl font-bold text-slate-800 mt-1.5">{{ readiness.required_activities_done }}<span class="text-xs text-slate-400 font-semibold"> / {{ readiness.required_activities_total }}</span></p>
                            <p class="text-[10px] text-slate-400 mt-0.5">completed</p>
                        </div>
                        <div class="rounded-xl border border-rose-100 bg-rose-50/40 p-3">
                            <div class="flex items-center justify-between">
                                <X class="w-3.5 h-3.5 text-rose-400" />
                                <span class="text-[9px] font-bold text-rose-500 uppercase tracking-wider">Rejected Required Items</span>
                            </div>
                            <p class="text-xl font-bold text-slate-800 mt-1.5">{{ readiness.rejected_items }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">awaiting resubmission</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3">
                            <div class="flex items-center justify-between">
                                <StickyNote class="w-3.5 h-3.5 text-slate-400" />
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Blocked Items</span>
                            </div>
                            <p class="text-xl font-bold text-slate-800 mt-1.5">{{ readiness.blocked_items }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">need unblocking</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 relative">
                    <!-- LEFT COLUMN -->
                    <div class="space-y-5">
                        <!-- PERSONAL INFO -->
                        <div class="bg-white rounded-xl border border-slate-200/60 shadow-md backdrop-blur-sm overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-transparent flex items-center gap-2">
                                <User class="w-4 h-4 text-blue-600" />
                                <h3 class="text-sm font-bold text-slate-800">Personal Information</h3>
                            </div>
                            <div class="p-4 space-y-3">
                                <div v-if="profile?.photo" class="flex justify-center mb-2">
                                    <img :src="profile.photo" alt="Profile" class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-lg" />
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Full Name</span>
                                    <span class="text-xs font-medium text-slate-700">{{ profile?.full_name || onboarding.name }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Email</span>
                                    <span class="text-xs font-medium text-slate-700">{{ profile?.email || onboarding.email }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Phone</span>
                                    <span class="text-xs font-medium text-slate-700">{{ profile?.phone || onboarding.phone }}</span>
                                </div>
                                <div v-if="profile?.birth_date" class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Birth Date</span>
                                    <span class="text-xs font-medium text-slate-700">{{ formatDate(profile.birth_date) }}</span>
                                </div>
                                <div v-if="profile?.gender" class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Gender</span>
                                    <span class="text-xs font-medium text-slate-700">{{ profile.gender }}</span>
                                </div>
                                <div v-if="profile?.civil_status" class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Civil Status</span>
                                    <span class="text-xs font-medium text-slate-700">{{ profile.civil_status }}</span>
                                </div>
                                <div v-if="profile?.address" class="flex justify-between gap-3 border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400 shrink-0">Address</span>
                                    <span class="text-xs font-medium text-slate-700 text-right">{{ profile.address }} {{ profile.zip_code }}</span>
                                </div>
                                <div v-if="profile?.education" class="flex justify-between gap-3 border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400 shrink-0">Education</span>
                                    <span class="text-xs font-medium text-slate-700 text-right">{{ profile.education }}</span>
                                </div>
                                <div v-if="profile?.emergency_contact" class="flex justify-between gap-3 border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400 shrink-0">Emergency</span>
                                    <span class="text-xs font-medium text-slate-700 text-right">
                                        {{ profile.emergency_contact.name }}
                                        <span class="block text-[10px] text-slate-400">{{ profile.emergency_contact.relationship }} · {{ profile.emergency_contact.phone }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- EMPLOYMENT DETAILS -->
                        <div class="bg-white rounded-xl border border-slate-200/60 shadow-md backdrop-blur-sm overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-transparent flex items-center gap-2">
                                <Briefcase class="w-4 h-4 text-blue-600" />
                                <h3 class="text-sm font-bold text-slate-800">Employment Details</h3>
                            </div>
                            <div class="p-4 space-y-3">
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Position</span>
                                    <span class="text-xs font-medium text-slate-700">{{ onboarding.position }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Department</span>
                                    <span class="text-xs font-medium text-slate-700">{{ onboarding.department }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Employment Type</span>
                                    <span class="text-xs font-medium text-slate-700">{{ onboarding.employment_type || '—' }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Work Mode</span>
                                    <span class="text-xs font-medium text-slate-700">{{ onboarding.work_mode || '—' }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Onboarding Template</span>
                                    <span class="text-xs font-medium text-slate-700">{{ onboarding.template || '—' }}</span>
                                </div>
                                <div v-if="onboarding.salary" class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Salary Range</span>
                                    <span class="text-xs font-medium text-slate-700">{{ onboarding.salary }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Start Date</span>
                                    <span class="text-xs font-medium text-slate-700">{{ formatDate(onboarding.start_date) }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Expected Start</span>
                                    <span class="text-xs font-medium text-slate-700">{{ formatDate(onboarding.expected_start_date) }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Expected Completion</span>
                                    <span class="text-xs font-medium text-slate-700">{{ formatDate(onboarding.expected_completion_date) }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Work Location</span>
                                    <span class="text-xs font-medium text-slate-700">{{ onboarding.work_location || '—' }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-[11px] text-slate-400">Work Schedule</span>
                                    <span class="text-xs font-medium text-slate-700">{{ onboarding.work_schedule || '—' }}</span>
                                </div>
                                <div class="flex justify-between pb-2">
                                    <span class="text-[11px] text-slate-400">Assigned Manager</span>
                                    <span class="text-xs font-medium text-slate-700">{{ onboarding.assigned_manager || 'Unassigned' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT 2 COLUMNS -->
                    <div class="xl:col-span-2 space-y-5">
                        <!-- UNIFIED ONBOARDING ITEMS -->
                        <div class="bg-white rounded-xl border border-slate-200/60 shadow-md backdrop-blur-sm overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-transparent flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <ClipboardCheck class="w-4 h-4 text-blue-600" />
                                    <h3 class="text-sm font-bold text-slate-800">Onboarding Items</h3>
                                </div>
                                <span class="text-[10px] text-slate-500 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200/60">Eligible items for this template snapshot</span>
                            </div>
                            <div class="p-4 space-y-6" v-if="hasSnapshot">
                                <div v-for="(items, section) in sections" :key="section">
                                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                                        <span class="w-1 h-3.5 rounded-full" :class="sectionColor(section)"></span>{{ section }}
                                        <span class="text-[9px] font-semibold text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100">{{ items.length }}</span>
                                    </h4>
                                    <div class="space-y-2">
                                        <div
                                            v-for="item in items"
                                            :key="item.id"
                                            class="p-3 rounded-xl border border-slate-100 hover:border-blue-100 hover:bg-blue-50/20 transition-all"
                                        >
                                            <div class="flex flex-col md:flex-row md:items-start gap-3">
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-xs font-medium text-slate-700 flex flex-wrap items-center gap-2">
                                                        {{ item.name }}
                                                        <span :class="item.is_required ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-slate-50 text-slate-400 border-slate-200'" class="px-1.5 py-0.5 rounded text-[9px] font-bold border">
                                                            {{ item.is_required ? 'REQUIRED' : 'OPTIONAL' }}
                                                        </span>
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold border bg-indigo-50 text-indigo-600 border-indigo-200">{{ item.input_type }}</span>
                                                        <span v-if="!item.applicant_visible" class="px-1.5 py-0.5 rounded text-[9px] font-bold border bg-slate-50 text-slate-500 border-slate-200">INTERNAL</span>
                                                        <span :class="['px-2 py-0.5 rounded-full text-[9px] font-bold border', item.status_badge]">
                                                            {{ item.status }}
                                                        </span>
                                                    </div>
                                                    <p v-if="item.description" class="text-[10px] text-slate-400 mt-0.5">{{ item.description }}</p>
                                                    <div class="flex flex-wrap items-center gap-2 mt-1.5 text-[9px] text-slate-400">
                                                        <span>Owner: {{ item.responsible_party }}</span>
                                                        <span v-if="item.due_date">· Due: {{ formatDate(item.due_date) }}</span>
                                                        <span v-if="item.submitted_at">· Submitted: {{ formatDateTime(item.submitted_at) }}</span>
                                                    </div>

                                                    <!-- Submission details for applicant items -->
                                                    <div v-if="!item.is_task && item.latest_submission" class="mt-2 flex flex-wrap items-center gap-2">
                                                        <a
                                                            v-if="item.latest_submission.file_url"
                                                            :href="item.latest_submission.file_url"
                                                            target="_blank"
                                                            class="inline-flex items-center gap-1 text-[10px] font-semibold text-blue-600 hover:text-blue-800 bg-blue-50 px-2 py-1 rounded-lg border border-blue-100"
                                                        >
                                                            <Download class="w-3 h-3" /> {{ item.latest_submission.original_name }}
                                                        </a>
                                                        <span v-if="item.latest_submission.value" class="text-[10px] text-slate-600 bg-slate-50 px-2 py-1 rounded-lg border border-slate-100 max-w-xs truncate">
                                                            {{ displayValue(item.latest_submission.value) }}
                                                        </span>
                                                        <span v-if="item.latest_submission.submitted_at" class="text-[9px] text-slate-400">Submitted {{ formatDateTime(item.latest_submission.submitted_at) }}</span>
                                                    </div>

                                                    <div v-if="item.status === 'Rejected'" class="mt-1.5 text-[10px] font-semibold text-rose-600 bg-rose-50 px-2.5 py-1.5 rounded-lg border border-rose-100">
                                                        Rejected: {{ item.rejection_reason || 'No reason provided.' }} — applicant may resubmit.
                                                    </div>

                                                    <!-- Submission history -->
                                                    <div v-if="item.submissions && item.submissions.length > 1" class="mt-2">
                                                        <button class="text-[9px] font-bold text-slate-400 hover:text-slate-600" @click="toggleHistory(item.id)">
                                                        History ({{ item.submissions.length }})
                                                    </button>
                                                        <div v-if="expandedHistory[item.id]" class="mt-1.5 space-y-1">
                                                            <div v-for="(sub, i) in item.submissions.slice(1)" :key="sub.id" class="flex items-center gap-2 text-[9px] text-slate-500">
                                                                <span class="px-1.5 py-0.5 rounded bg-slate-50 border border-slate-100">{{ sub.status }}</span>
                                                                <span v-if="sub.original_name" class="truncate max-w-[180px]">{{ sub.original_name }}</span>
                                                                <span v-if="sub.value" class="truncate max-w-[180px]">{{ displayValue(sub.value) }}</span>
                                                                <span>{{ formatDateTime(sub.submitted_at) }}</span>
                                                                <span v-if="sub.status === 'Rejected'" class="text-rose-500">{{ sub.rejection_reason }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Actions -->
                                                <div class="flex items-center gap-2 shrink-0">
                                                    <!-- Task / Verification: HR sets status directly -->
                                                    <div v-if="item.is_task">
                                                        <select
                                                            :value="item.status"
                                                            @change="updateTaskStatus(item, $event.target.value)"
                                                            :class="['px-2.5 py-1.5 rounded-lg border text-[10px] font-semibold focus:ring-2 focus:ring-blue-500', itemStatusSelect(item.status)]"
                                                        >
                                                            <option v-for="s in taskStatuses" :key="s" :value="s">{{ s }}</option>
                                                        </select>
                                                    </div>

                                                    <!-- Applicant item: review workflow -->
                                                    <template v-else>
                                                        <button
                                                            v-if="canReview(item)"
                                                            @click="reviewItem(item, 'approve')"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white rounded-lg text-[10px] font-bold transition-all duration-200 shadow-sm"
                                                        >
                                                            <Check class="w-3 h-3" /> Approve
                                                        </button>
                                                        <button
                                                            v-if="canReview(item)"
                                                            @click="openRejectModal(item)"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 rounded-lg text-[10px] font-bold transition-all duration-200"
                                                        >
                                                            <X class="w-3 h-3" /> Reject
                                                        </button>
                                                        <span
                                                            v-if="item.status === 'Verified'"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-lg text-[10px] font-bold"
                                                        >
                                                            <Check class="w-3 h-3" /> Verified
                                                        </span>
                                                        <span
                                                            v-if="!item.latest_submission"
                                                            class="inline-flex items-center px-2.5 py-1.5 text-[10px] text-amber-600 bg-amber-50 border border-amber-100 rounded-lg"
                                                        >
                                                            Awaiting submission
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="p-8 text-center">
                                <p class="text-xs text-slate-400">No onboarding items yet. Edit Details to choose a template (or contact HR).</p>
                            </div>
                        </div>

                        <!-- ACTIVITIES / SCHEDULE -->
                        <div class="bg-white rounded-xl border border-slate-200/60 shadow-md backdrop-blur-sm overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-transparent flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <CalendarDays class="w-4 h-4 text-blue-600" />
                                    <h3 class="text-sm font-bold text-slate-800">Orientation & Training Schedule</h3>
                                </div>
                                <button
                                    @click="openModal('activity')"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-lg text-[10px] font-semibold transition-all duration-200 shadow-sm"
                                >
                                    <Plus class="w-3 h-3" /> Add Activity
                                </button>
                            </div>
                            <div class="p-4">
                                <div v-if="activities.length === 0" class="text-center py-6">
                                    <p class="text-xs text-slate-400">No activities scheduled yet.</p>
                                </div>
                                <div v-else class="space-y-2">
                                    <div
                                        v-for="activity in activities"
                                        :key="activity.id"
                                        class="flex flex-col md:flex-row md:items-center gap-3 p-3 rounded-xl border border-slate-100 hover:border-blue-100 transition-all"
                                    >
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center shrink-0">
                                            <CalendarDays class="w-4 h-4 text-blue-600" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs font-bold text-slate-700 flex flex-wrap items-center gap-2">
                                                {{ activity.title }}
                                                <span v-if="activity.is_required" class="bg-rose-50 text-rose-600 border-rose-200 px-1.5 py-0.5 rounded text-[9px] font-bold border">REQUIRED</span>
                                                <span :class="['px-2 py-0.5 rounded-full text-[9px] font-bold border', activity.status_badge]">{{ activity.status }}</span>
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">
                                                {{ activity.type }}
                                                <template v-if="activity.schedule_date"> · {{ formatDate(activity.schedule_date) }}
                                                    <template v-if="activity.start_time"> · {{ activity.start_time }}<template v-if="activity.end_time"> - {{ activity.end_time }}</template></template>
                                                </template>
                                                <template v-if="activity.method"> · {{ activity.method }}</template>
                                                <template v-if="activity.location"> · {{ activity.location }}</template>
                                                <template v-if="activity.meeting_link"> · <a :href="activity.meeting_link" target="_blank" class="text-blue-600 hover:underline">{{ activity.meeting_link }}</a></template>
                                                <template v-if="activity.facilitator"> · Facilitator: {{ activity.facilitator }}</template>
                                                <template v-if="activity.organizer"> · Organizer: {{ activity.organizer }}</template>
                                                <template v-if="activity.applicant_visible"> · <span class="text-blue-500">Visible to applicant</span></template>
                                            </div>
                                            <p v-if="activity.description" class="text-[10px] text-slate-500 mt-1">{{ activity.description }}</p>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <select
                                                v-if="activity.attendance_status"
                                                :value="activity.attendance_status"
                                                @change="updateAttendance(activity, $event.target.value)"
                                                class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-[10px] font-semibold focus:ring-2 focus:ring-blue-500"
                                            >
                                                <option v-for="s in attendanceStatuses" :key="s" :value="s">{{ s }}</option>
                                            </select>
                                            <select
                                                :value="activity.status"
                                                @change="updateActivity(activity, $event.target.value)"
                                                :class="['px-2.5 py-1.5 rounded-lg border text-[10px] font-semibold focus:ring-2 focus:ring-blue-500', activityStatusSelect(activity.status)]"
                                            >
                                                <option v-for="s in activityStatuses" :key="s" :value="s">{{ s }}</option>
                                            </select>
                                            <button
                                                @click="deleteActivity(activity)"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                            >
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- HR NOTES -->
                        <div class="bg-white rounded-xl border border-slate-200/60 shadow-md backdrop-blur-sm overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-transparent flex items-center gap-2">
                                <StickyNote class="w-4 h-4 text-blue-600" />
                                <h3 class="text-sm font-bold text-slate-800">HR Notes <span class="text-[9px] font-medium text-slate-400">(private — not visible to the applicant)</span></h3>
                            </div>
                            <div class="p-4 space-y-3">
                                <form @submit.prevent="storeNote" class="space-y-2">
                                    <textarea
                                        v-model="noteForm.content"
                                        rows="3"
                                        placeholder="Add an internal note about this new hire..."
                                        class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    ></textarea>
                                    <div class="flex items-center justify-between">
                                        <label class="flex items-center gap-2 text-[10px] text-slate-500 cursor-pointer">
                                            <input v-model="noteForm.is_hr_private" type="checkbox" class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                            Private note (HR only)
                                        </label>
                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-1 px-3.5 py-2 bg-gradient-to-r from-slate-800 to-slate-900 hover:from-slate-700 hover:to-slate-800 text-white rounded-xl text-[10px] font-bold transition-all duration-200 shadow-md"
                                        >
                                            <Plus class="w-3 h-3" /> Add Note
                                        </button>
                                    </div>
                                </form>

                                <div v-if="notes.length === 0" class="text-center py-4">
                                    <p class="text-xs text-slate-400">No notes yet.</p>
                                </div>
                                <div v-else class="space-y-2 max-h-72 overflow-y-auto pr-1">
                                    <div v-for="note in notes" :key="note.id" class="flex gap-3 p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-600 to-slate-700 flex items-center justify-center text-white font-bold text-[10px] shrink-0">
                                            {{ note.user ? note.user.email.charAt(0).toUpperCase() : 'HR' }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[11px] text-slate-600 leading-relaxed">{{ note.content }}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[9px] text-slate-400">{{ note.user?.email || 'HR' }} · {{ formatDateTime(note.created_at) }}</span>
                                                <span v-if="note.is_hr_private" class="text-[9px] font-bold text-violet-500 bg-violet-50 px-1.5 py-0.5 rounded">PRIVATE</span>
                                            </div>
                                        </div>
                                        <button @click="deleteNote(note)" class="p-1 text-slate-300 hover:text-rose-500 transition-colors">
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ MODALS ============ -->
        <div v-if="activeModal" class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModal">
            <div class="bg-white rounded-2xl border border-slate-200/60 w-full max-w-2xl overflow-hidden shadow-2xl">
                <!-- Details Modal -->
                <template v-if="activeModal === 'details'">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50 to-transparent">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Edit Onboarding Details</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Update start date, manager, template (before items exist), location and schedule.</p>
                        </div>
                        <button @click="closeModal" class="p-2 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-600 transition-all">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div v-if="!hasSnapshot" class="bg-blue-50/60 border border-blue-100 rounded-xl p-3">
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Onboarding Template</label>
                            <select v-model="detailsForm.template_id" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm">
                                <option value="">-- Select template --</option>
                                <option v-for="t in templates" :key="t.id" :value="t.id">
                                    {{ t.name }} ({{ t.code }} v{{ t.version }})
                                </option>
                            </select>
                            <p class="text-[10px] text-slate-500 mt-1">Selecting a template snapshots its items. Not shown once items already exist.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Start Date</label>
                                <input v-model="detailsForm.start_date" type="date" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Expected Start Date</label>
                                <input v-model="detailsForm.expected_start_date" type="date" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Expected Completion Date</label>
                                <input v-model="detailsForm.expected_completion_date" type="date" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Assigned Manager</label>
                                <input v-model="detailsForm.assigned_manager" type="text" placeholder="e.g. HR Operations Manager" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Work Location</label>
                                <input v-model="detailsForm.work_location" type="text" placeholder="e.g. Main Plant" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Work Schedule</label>
                                <input v-model="detailsForm.work_schedule" type="text" placeholder="e.g. Monday - Friday, 8:00 AM - 5:00 PM" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                            </div>
                        </div>
                    </div>
                    <div class="p-4 border-t border-slate-100 bg-gradient-to-r from-slate-50 to-transparent flex items-center justify-end gap-3">
                        <button @click="closeModal" class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-sm font-semibold transition-all">Cancel</button>
                        <button @click="saveDetails" :disabled="saving" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white rounded-xl text-sm font-bold transition-all shadow-lg">
                            {{ saving ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </template>

                <!-- Reject Modal -->
                <template v-if="activeModal === 'reject'">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-rose-50 to-transparent">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Reject Submission</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Let the applicant know why this submission was rejected.</p>
                        </div>
                        <button @click="closeModal" class="p-2 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-600 transition-all">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-5">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Rejection Reason</label>
                        <textarea
                            v-model="rejectForm.reason"
                            rows="3"
                            placeholder="e.g. The document is unclear. Please upload a clearer scan."
                            class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 focus:border-transparent shadow-sm"
                        ></textarea>
                    </div>
                    <div class="p-4 border-t border-slate-100 bg-gradient-to-r from-slate-50 to-transparent flex items-center justify-end gap-3">
                        <button @click="closeModal" class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-sm font-semibold transition-all">Cancel</button>
                        <button @click="submitReject" :disabled="saving" class="px-5 py-2.5 bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 text-white rounded-xl text-sm font-bold transition-all shadow-lg">
                            {{ saving ? 'Rejecting...' : 'Reject Submission' }}
                        </button>
                    </div>
                </template>

                <!-- Activity Modal -->
                <template v-if="activeModal === 'activity'">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50 to-transparent">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Schedule Activity</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Add orientation, training or pre-employment activity for this new hire.</p>
                        </div>
                        <button @click="closeModal" class="p-2 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-600 transition-all">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Activity Title</label>
                            <input v-model="activityForm.title" type="text" placeholder="e.g. New Hire Orientation" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Type</label>
                                <select v-model="activityForm.type" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm">
                                    <option v-for="t in activityTypes" :key="t" :value="t">{{ t }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Schedule Date</label>
                                <input v-model="activityForm.schedule_date" type="date" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Start Time</label>
                                <input v-model="activityForm.start_time" type="time" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">End Time</label>
                                <input v-model="activityForm.end_time" type="time" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Method</label>
                                <select v-model="activityForm.method" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm">
                                    <option value="On-site">On-site</option>
                                    <option value="Online">Online</option>
                                    <option value="Hybrid">Hybrid</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Location</label>
                                <input v-model="activityForm.location" type="text" placeholder="e.g. HR Conference Room" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Meeting Link</label>
                                <input v-model="activityForm.meeting_link" type="url" placeholder="https://meet.example.com/..." class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Facilitator</label>
                                <input v-model="activityForm.facilitator" type="text" placeholder="e.g. HR Supervisor" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Organizer</label>
                                <input v-model="activityForm.organizer" type="text" placeholder="e.g. HR Department" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm" />
                            </div>
                        </div>
                        <div class="flex items-center gap-5">
                            <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                                <input v-model="activityForm.is_required" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                Required for completion
                            </label>
                            <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                                <input v-model="activityForm.applicant_visible" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                Visible to applicant
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Description</label>
                            <textarea v-model="activityForm.description" rows="2" placeholder="Optional details about this activity..." class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm"></textarea>
                        </div>
                    </div>
                    <div class="p-4 border-t border-slate-100 bg-gradient-to-r from-slate-50 to-transparent flex items-center justify-end gap-3">
                        <button @click="closeModal" class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-sm font-semibold transition-all">Cancel</button>
                        <button @click="saveActivity" :disabled="saving" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white rounded-xl text-sm font-bold transition-all shadow-lg">
                            {{ saving ? 'Saving...' : 'Schedule Activity' }}
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Swal from 'sweetalert2';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import {
    Pencil, CheckCircle2, UserPlus, User, Briefcase, ClipboardCheck, FileText,
    CalendarDays, StickyNote, Check, X, Plus, Download, Trash2,
} from 'lucide-vue-next';

const props = defineProps({
    onboarding: { type: Object, required: true },
    sections: { type: Object, default: () => ({}) },
    activities: { type: Array, default: () => [] },
    notes: { type: Array, default: () => [] },
    profile: { type: Object, default: null },
    canComplete: { type: Boolean, default: false },
    missing: { type: Array, default: () => [] },
    readiness: { type: Object, default: () => ({}) },
    progress: { type: Number, default: 0 },
    alreadyHired: { type: Boolean, default: false },
    templates: { type: Array, default: () => [] },
});

const page = usePage();
const pageLoading = ref(true);
const saving = ref(false);
const activeModal = ref(null);
const expandedHistory = ref({});

onMounted(() => {
    setTimeout(() => { pageLoading.value = false; }, 150);
});

// Toast flash
watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
}, { immediate: true });

// Watch validation errors
watch(() => page.props.errors, (errors) => {
    if (Object.keys(errors).length > 0) {
        const first = Object.values(errors)[0];
        toast.error(String(first));
    }
}, { deep: true });

const initials = computed(() => {
    const parts = props.onboarding.name.trim().split(/\s+/);
    const first = parts[0] ? parts[0][0] : '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
    return (first + last).toUpperCase();
});

const hasSnapshot = computed(() =>
    Object.keys(props.sections).some((key) => props.sections[key].length > 0)
);

const taskStatuses = ['Pending', 'In Progress', 'Completed', 'Blocked'];
const activityStatuses = ['Draft', 'Scheduled', 'In Progress', 'Completed', 'Cancelled', 'Rescheduled', 'No Show'];
const attendanceStatuses = ['Pending', 'Confirmed', 'Present', 'Absent', 'Late', 'Excused', 'No Show'];
const activityTypes = ['Orientation', 'Training', 'Pre-Employment', 'Team Introduction', 'Equipment Setup', 'Other'];

const sectionColor = (section) =>
    ({
        'Applicant Requirements': 'bg-blue-500',
        'HR Processing': 'bg-violet-500',
        'Company Preparation': 'bg-emerald-500',
    })[section] || 'bg-slate-400';

const toggleHistory = (id) => {
    expandedHistory.value[id] = !expandedHistory.value[id];
};

const canReview = (item) =>
    item.latest_submission && ['Submitted', 'Resubmitted', 'Under Review'].includes(item.status);

const displayValue = (value) => {
    if (value === true) return 'Yes';
    if (value === false) return 'No';
    return String(value);
};

// ============ DETAILS FORM ============
const detailsForm = ref({
    start_date: props.onboarding.start_date || '',
    expected_start_date: props.onboarding.expected_start_date || '',
    expected_completion_date: props.onboarding.expected_completion_date || '',
    assigned_manager: props.onboarding.assigned_manager || '',
    work_location: props.onboarding.work_location || '',
    work_schedule: props.onboarding.work_schedule || '',
    template_id: '',
});

const saveDetails = () => {
    if (detailsForm.value.template_id !== '' && hasSnapshot.value) {
        detailsForm.value.template_id = '';
    }
    saving.value = true;
    router.patch(route('hrm.onboarding.update', props.onboarding.id), detailsForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            toast.success('Onboarding details updated.');
        },
        onError: () => toast.error('Failed to update details.'),
        onFinish: () => { saving.value = false; },
    });
};

// ============ ITEMS ============
const updateTaskStatus = (item, status) => {
    router.patch(route('hrm.onboarding.items.status', { onboarding: props.onboarding.id, item: item.id }), { status }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Task status updated.'),
        onError: () => toast.error('Failed to update task status.'),
    });
};

const itemStatusSelect = (status) =>
    ({
        Pending: 'bg-slate-50 text-slate-500 border-slate-200',
        Submitted: 'bg-blue-50 text-blue-600 border-blue-200',
        Resubmitted: 'bg-indigo-50 text-indigo-600 border-indigo-200',
        'Under Review': 'bg-amber-50 text-amber-700 border-amber-200',
        'In Progress': 'bg-amber-50 text-amber-700 border-amber-200',
        Verified: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        Completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        Rejected: 'bg-rose-50 text-rose-600 border-rose-200',
        Blocked: 'bg-rose-50 text-rose-600 border-rose-200',
    })[status] || 'bg-slate-50 text-slate-500 border-slate-200';

const rejectForm = ref({ item: null, reason: '' });

const openRejectModal = (item) => {
    rejectForm.value = { item, reason: '' };
    activeModal.value = 'reject';
};

const reviewItem = (item, decision) => {
    router.post(route('hrm.onboarding.items.review', { onboarding: props.onboarding.id, item: item.id }), { decision }, {
        preserveScroll: true,
        onSuccess: () => toast.success(decision === 'approve' ? 'Submission verified.' : 'Submission marked as pending.'),
        onError: () => toast.error('Failed to review submission.'),
    });
};

const submitReject = () => {
    if (!rejectForm.value.reason.trim()) {
        toast.error('Please provide a rejection reason.');
        return;
    }
    saving.value = true;
    router.post(
        route('hrm.onboarding.items.review', { onboarding: props.onboarding.id, item: rejectForm.value.item.id }),
        { decision: 'reject', reason: rejectForm.value.reason },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                toast.success('Submission rejected. The applicant can resubmit.');
            },
            onError: () => toast.error('Failed to reject submission.'),
            onFinish: () => { saving.value = false; },
        },
    );
};

// ============ NOTES ============
const noteForm = ref({ content: '', is_hr_private: true });

const storeNote = () => {
    if (!noteForm.value.content.trim()) {
        toast.error('Note content is required.');
        return;
    }
    router.post(route('hrm.onboarding.notes.store', props.onboarding.id), noteForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Note added.');
            noteForm.value.content = '';
        },
        onError: () => toast.error('Failed to add note.'),
    });
};

const deleteNote = (note) => {
    Swal.fire({
        title: 'Delete note?',
        text: 'This note will be permanently removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#e11d48',
        customClass: { popup: 'rounded-[2rem]' },
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('hrm.onboarding.notes.destroy', { onboarding: props.onboarding.id, note: note.id }), {
                preserveScroll: true,
                onSuccess: () => toast.success('Note removed.'),
                onError: () => toast.error('Failed to remove note.'),
            });
        }
    });
};

// ============ ACTIVITIES ============
const activityForm = ref({
    title: '',
    type: 'Orientation',
    schedule_date: '',
    start_time: '',
    end_time: '',
    method: '',
    location: '',
    meeting_link: '',
    facilitator: '',
    organizer: '',
    is_required: false,
    applicant_visible: true,
    description: '',
});

const saveActivity = () => {
    if (!activityForm.value.title.trim()) {
        toast.error('Activity title is required.');
        return;
    }
    saving.value = true;
    router.post(route('hrm.onboarding.activities.store', props.onboarding.id), activityForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            toast.success('Activity scheduled.');
        },
        onError: () => toast.error('Failed to schedule activity.'),
        onFinish: () => { saving.value = false; },
    });
};

const updateActivity = (activity, status) => {
    router.patch(route('hrm.onboarding.activities.update', { onboarding: props.onboarding.id, activity: activity.id }), { status }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Activity updated.'),
        onError: () => toast.error('Failed to update activity.'),
    });
};

const updateAttendance = (activity, attendance_status) => {
    router.patch(route('hrm.onboarding.activities.update', { onboarding: props.onboarding.id, activity: activity.id }), {
        attendance_status,
        status: activity.status,
    }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Attendance updated.'),
        onError: () => toast.error('Failed to update attendance.'),
    });
};

const deleteActivity = (activity) => {
    Swal.fire({
        title: 'Remove activity?',
        text: activity.title + ' will be removed from the schedule.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Remove',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#e11d48',
        customClass: { popup: 'rounded-[2rem]' },
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('hrm.onboarding.activities.destroy', { onboarding: props.onboarding.id, activity: activity.id }), {
                preserveScroll: true,
                onSuccess: () => toast.success('Activity removed.'),
                onError: () => toast.error('Failed to remove activity.'),
            });
        }
    });
};

const activityStatusSelect = (status) =>
    ({
        Draft: 'bg-slate-50 text-slate-500 border-slate-200',
        Scheduled: 'bg-blue-50 text-blue-600 border-blue-200',
        'In Progress': 'bg-amber-50 text-amber-600 border-amber-200',
        Completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        Cancelled: 'bg-rose-50 text-rose-600 border-rose-200',
        Rescheduled: 'bg-violet-50 text-violet-600 border-violet-200',
        'No Show': 'bg-rose-50 text-rose-600 border-rose-200',
    })[status] || 'bg-slate-50 text-slate-500 border-slate-200';

// ============ COMPLETE / CREATE EMPLOYEE ============
const confirmComplete = () => {
    if (!props.canComplete) {
        toast.error('Complete all required items and mandatory activities first.');
        return;
    }
    Swal.fire({
        title: 'Complete Onboarding?',
        text: 'This marks the onboarding as completed. You can then create the employee record.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Complete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#059669',
        customClass: { popup: 'rounded-[2rem]' },
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('hrm.onboarding.complete', props.onboarding.id), {}, {
                preserveScroll: true,
                onSuccess: () => toast.success('Onboarding completed.'),
                onError: () => toast.error('Failed to complete onboarding.'),
            });
        }
    });
};

const confirmCreateEmployee = () => {
    if (!props.canComplete && props.onboarding.status !== 'Completed') {
        toast.error('The onboarding must be completed before creating the employee record.');
        return;
    }
    Swal.fire({
        title: 'Create Employee Record?',
        html: 'This will generate an employee record from the verified applicant data, update the application to <b>Hired</b>, and finalize onboarding.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Yes, Create Employee',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#2563eb',
        customClass: { popup: 'rounded-[2rem]' },
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('hrm.onboarding.create-employee', props.onboarding.id), {}, {
                onSuccess: () => toast.success('Employee record created.'),
                onError: () => toast.error('Failed to create employee record.'),
            });
        }
    });
};

// ============ HELPERS ============
const openModal = (type) => {
    if (type === 'activity') {
        activityForm.value = { title: '', type: 'Orientation', schedule_date: '', start_time: '', end_time: '', method: '', location: '', meeting_link: '', facilitator: '', organizer: '', is_required: false, applicant_visible: true, description: '' };
    }
    if (type === 'details') {
        detailsForm.value = {
            start_date: props.onboarding.start_date || '',
            expected_start_date: props.onboarding.expected_start_date || '',
            expected_completion_date: props.onboarding.expected_completion_date || '',
            assigned_manager: props.onboarding.assigned_manager || '',
            work_location: props.onboarding.work_location || '',
            work_schedule: props.onboarding.work_schedule || '',
            template_id: props.onboarding.template_id || '',
        };
    }
    activeModal.value = type;
};

const closeModal = () => {
    activeModal.value = null;
};

const formatDate = (value) => {
    if (!value) return '—';
    const d = new Date(value);
    if (isNaN(d)) return '—';
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatDateTime = (value) => {
    if (!value) return '—';
    const d = new Date(value);
    if (isNaN(d)) return '—';
    return d.toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
};
</script>

<style scoped>
.bg-grid-slate-100 {
    background-image: radial-gradient(circle, #cbd5e1 1px, transparent 1px);
    background-size: 24px 24px;
}
</style>