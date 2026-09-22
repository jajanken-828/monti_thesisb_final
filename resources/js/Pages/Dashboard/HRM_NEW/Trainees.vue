<template>
    <AuthenticatedLayout>
        <div
            class="flex min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-blue-50"
        >
            <Hrsidebar />

            <div class="flex-1 p-8 text-slate-900 font-sans min-w-0 relative">
                <!-- Subtle Background Pattern -->
                <div
                    class="absolute inset-0 bg-grid-slate-100 [mask-image:linear-gradient(0deg,transparent,black,transparent)] pointer-events-none"
                ></div>

                <!-- Professional Header -->
                <header class="mb-8 relative">
                    <div
                        class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6"
                    >
                        <div>
                            <nav
                                class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-3"
                            >
                                <span
                                    class="hover:text-slate-700 cursor-pointer transition-colors"
                                    >Dashboard</span
                                >
                                <svg
                                    class="w-3 h-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                                <span class="text-slate-800 font-semibold"
                                    >Employee Lifecycle</span
                                >
                            </nav>
                            <h1
                                class="text-4xl font-bold tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent"
                            >
                                Trainees
                            </h1>
                            <p class="text-sm text-slate-500 mt-1.5">
                                Manage interns, apprentices, and trainees.
                                Monitor progress, evaluate performance, and
                                convert to employees.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                @click="openModal('create')"
                                class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white rounded-xl text-sm font-bold transition-all duration-200 shadow-lg hover:shadow-xl hover:shadow-blue-500/25 hover:-translate-y-0.5"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                Add Trainee
                            </button>
                        </div>
                    </div>
                </header>

                <!-- KPI METRICS ROW - Floating Cards -->
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8"
                >
                    <div
                        v-for="m in metrics"
                        :key="m.label"
                        class="group bg-white rounded-2xl border border-slate-200/60 p-5 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 hover:border-blue-200/60 shadow-md backdrop-blur-sm"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span
                                class="text-xs font-semibold text-slate-500 uppercase tracking-wider"
                                >{{ m.label }}</span
                            >
                            <div
                                class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center group-hover:from-blue-100 group-hover:to-blue-200 transition-all duration-300 shadow-inner"
                            >
                                <component :is="m.icon" class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-slate-900">
                            {{ m.value }}
                        </div>
                        <div
                            v-if="m.subtext"
                            class="text-xs text-slate-400 mt-1"
                        >
                            {{ m.subtext }}
                        </div>
                    </div>
                </div>

                <!-- TRAINEE CARDS GRID -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-8"
                >
                    <div
                        v-for="t in filteredTrainees"
                        :key="t.id"
                        class="bg-white rounded-2xl border border-slate-200/60 shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 backdrop-blur-sm overflow-hidden group"
                    >
                        <div
                            class="p-5 border-b border-slate-100 bg-gradient-to-r from-slate-50/50 to-transparent"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-12 h-12 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-bold text-lg shadow-lg transition-all duration-300 group-hover:scale-110 shadow-amber-500/20"
                                    >
                                        {{ t.avatar }}
                                    </div>
                                    <div>
                                        <h3
                                            class="text-sm font-bold text-slate-900"
                                        >
                                            {{ t.name }}
                                        </h3>
                                        <span class="text-[10px] text-slate-500"
                                            >{{ t.school }} ·
                                            {{ t.course }}</span
                                        >
                                    </div>
                                </div>
                                <span
                                    :class="[
                                        'inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-semibold border shadow-sm',
                                        t.status === 'Active'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                            : 'bg-slate-50 text-slate-500 border-slate-200',
                                    ]"
                                    >{{ t.status }}</span
                                >
                            </div>
                        </div>

                        <div class="p-5 space-y-3">
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div
                                    class="p-2.5 bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-100 rounded-xl shadow-sm"
                                >
                                    <span class="text-slate-400 block"
                                        >Department</span
                                    >
                                    <span
                                        class="font-semibold text-slate-800"
                                        >{{ t.department }}</span
                                    >
                                </div>
                                <div
                                    class="p-2.5 bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-100 rounded-xl shadow-sm"
                                >
                                    <span class="text-slate-400 block"
                                        >Mentor</span
                                    >
                                    <span
                                        class="font-semibold text-slate-800"
                                        >{{ t.mentor }}</span
                                    >
                                </div>
                                <div
                                    class="p-2.5 bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-100 rounded-xl shadow-sm"
                                >
                                    <span class="text-slate-400 block"
                                        >Start</span
                                    >
                                    <span
                                        class="font-semibold text-slate-800"
                                        >{{ t.start_date }}</span
                                    >
                                </div>
                                <div
                                    class="p-2.5 bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-100 rounded-xl shadow-sm"
                                >
                                    <span class="text-slate-400 block"
                                        >End</span
                                    >
                                    <span
                                        class="font-semibold text-slate-800"
                                        >{{ t.end_date }}</span
                                    >
                                </div>
                            </div>
                            <div class="border-t border-slate-100 pt-3">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-slate-500"
                                        >Evaluation</span
                                    >
                                    <span class="font-semibold text-slate-800"
                                        >{{ t.evaluation_score }}/5</span
                                    >
                                </div>
                                <div class="flex gap-0.5">
                                    <Star
                                        v-for="i in 5"
                                        :key="i"
                                        :class="
                                            i <= t.evaluation_score
                                                ? 'text-amber-400 fill-amber-400 drop-shadow-sm'
                                                : 'text-slate-200'
                                        "
                                        class="h-4 w-4"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            class="px-5 py-3 border-t border-slate-100 bg-gradient-to-r from-slate-50/50 to-transparent flex items-center gap-2"
                        >
                            <button
                                @click="openModal('edit', t)"
                                class="flex-1 px-3 py-2 bg-white border border-slate-200 hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 hover:text-blue-700 hover:border-blue-200 text-slate-700 rounded-xl text-[11px] font-semibold transition-all duration-200 shadow-sm hover:shadow-md flex items-center justify-center gap-1"
                            >
                                <Pencil class="w-4 h-4" /> Edit
                            </button>
                            <button
                                v-if="t.status === 'Active'"
                                @click="convertToEmployee(t)"
                                class="flex-1 px-3 py-2 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white rounded-xl text-[11px] font-semibold transition-all duration-200 shadow-lg hover:shadow-xl hover:shadow-emerald-500/25 flex items-center justify-center gap-1"
                            >
                                <RefreshCw class="w-4 h-4" /> Convert
                            </button>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="filteredTrainees.length === 0"
                        class="col-span-full"
                    >
                        <div
                            class="text-center py-16 bg-white rounded-2xl border border-slate-200/60 shadow-lg backdrop-blur-sm"
                        >
                            <div
                                class="w-20 h-20 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center mx-auto mb-4 shadow-inner"
                            >
                                <GraduationCap class="w-6 h-6" />
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 mb-2">
                                No Trainees Found
                            </h3>
                            <p class="text-sm text-slate-500 mb-6">
                                Add your first trainee to start managing
                                internships.
                            </p>
                            <button
                                @click="openModal('create')"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white rounded-xl text-sm font-bold transition-all duration-200 shadow-lg hover:shadow-xl hover:shadow-blue-500/25"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                Add Trainee
                            </button>
                        </div>
                    </div>
                </div>

                <!-- MODAL -->
                <div
                    v-if="activeModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                >
                    <div
                        class="bg-white rounded-2xl border border-slate-200/60 w-full max-w-2xl overflow-hidden shadow-2xl"
                    >
                        <div
                            class="p-6 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50 to-transparent"
                        >
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">
                                    Trainee Details
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Configure trainee information and internship
                                    details.
                                </p>
                            </div>
                            <button
                                @click="closeModal"
                                class="p-2 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-600 transition-all duration-200 hover:rotate-90 transform"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-slate-600 mb-1.5"
                                        >Name</label
                                    ><input
                                        v-model="form.name"
                                        type="text"
                                        class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-slate-600 mb-1.5"
                                        >School</label
                                    ><input
                                        v-model="form.school"
                                        type="text"
                                        class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-slate-600 mb-1.5"
                                        >Course</label
                                    ><input
                                        v-model="form.course"
                                        type="text"
                                        class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-slate-600 mb-1.5"
                                        >Department</label
                                    ><input
                                        v-model="form.department"
                                        type="text"
                                        class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-slate-600 mb-1.5"
                                        >Mentor</label
                                    ><input
                                        v-model="form.mentor"
                                        type="text"
                                        class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-slate-600 mb-1.5"
                                        >Internship Duration</label
                                    ><input
                                        v-model="form.duration"
                                        type="text"
                                        class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            class="p-4 border-t border-slate-100 bg-gradient-to-r from-slate-50 to-transparent flex items-center justify-end gap-3"
                        >
                            <button
                                @click="closeModal"
                                class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-sm font-semibold transition-all duration-200 shadow-sm hover:shadow-md"
                            >
                                Cancel
                            </button>
                            <button
                                @click="submitForm"
                                class="px-5 py-2.5 bg-gradient-to-r from-slate-800 to-slate-900 hover:from-slate-700 hover:to-slate-800 text-white rounded-xl text-sm font-bold transition-all duration-200 shadow-lg hover:shadow-xl hover:shadow-slate-500/25 hover:-translate-y-0.5"
                            >
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { GraduationCap, CheckCircle2, RefreshCw, Star, Pencil } from 'lucide-vue-next';

const activeModal = ref(null);
const trainees = ref([
    {
        id: 1,
        name: "Kim Santos",
        avatar: "KS",
        school: "University of Manila",
        course: "BS Computer Science",
        department: "Engineering",
        mentor: "Sarah Connor",
        start_date: "2026-06-01",
        end_date: "2026-09-01",
        status: "Active",
        evaluation_score: 4,
    },
    {
        id: 2,
        name: "Lia Tan",
        avatar: "LT",
        school: "Ateneo de Manila",
        course: "BS Information Systems",
        department: "HR",
        mentor: "Emily Parker",
        start_date: "2026-05-15",
        end_date: "2026-08-15",
        status: "Active",
        evaluation_score: 3,
    },
    {
        id: 3,
        name: "Marco Reyes",
        avatar: "MR",
        school: "DLSU",
        course: "BS Marketing",
        department: "Sales",
        mentor: "Vikram Patel",
        start_date: "2026-04-01",
        end_date: "2026-07-01",
        status: "Completed",
        evaluation_score: 5,
    },
]);
const form = ref({
    name: "",
    school: "",
    course: "",
    department: "",
    mentor: "",
    duration: "",
});
const metrics = computed(() => {
    const active = trainees.value.filter((t) => t.status === "Active").length;
    const completed = trainees.value.filter(
        (t) => t.status === "Completed",
    ).length;
    return [
        {
            label: "Active Trainees",
            value: active,
            icon: GraduationCap,
            subtext: "Currently training",
        },
        {
            label: "Completed",
            value: completed,
            icon: CheckCircle2,
            subtext: "Finished internship",
        },
        {
            label: "Conversion Rate",
            value: "67%",
            icon: RefreshCw,
            subtext: "Converted to employees",
        },
        {
            label: "Avg Rating",
            value: "4.0",
            icon: Star,
            subtext: "Evaluation score",
        },
    ];
});
const filteredTrainees = computed(() => trainees.value);
const openModal = (type, data = null) => {
    activeModal.value = type;
    if (data) form.value = { ...data };
};
const closeModal = () => {
    activeModal.value = null;
};
const submitForm = () => {
    closeModal();
};
const convertToEmployee = (t) => {
    t.status = "Converted";
    alert(t.name + " has been converted to a regular employee.");
};
</script>

<style scoped>
.bg-grid-slate-100 {
    background-image: radial-gradient(circle, #cbd5e1 1px, transparent 1px);
    background-size: 24px 24px;
}
.bg-clip-text {
    -webkit-background-clip: text;
    background-clip: text;
}
.backdrop-blur-sm {
    backdrop-filter: blur(8px);
}
</style>
