<template>
    <Head title="Business Profile" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
            <div class="p-4 sm:p-6 max-w-4xl mx-auto space-y-6 pb-16">

                <!-- Hero header -->
                <div class="animate-fade-up relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
                    <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div class="absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-fuchsia-400/20 blur-3xl animate-float-delayed" />
                    <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                    <div class="relative flex flex-wrap items-center gap-4">
                    <div class="relative flex-shrink-0">
                        <div class="animate-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 text-2xl font-black ring-1 ring-white/30 shadow-lg backdrop-blur overflow-hidden">
                            <img v-if="displayLogo" :src="displayLogo" alt="Company logo" class="h-full w-full object-cover" @error="logoBroken = true" />
                            <span v-else>{{ (client?.company_name ?? 'C').charAt(0).toUpperCase() }}</span>
                        </div>
                        <button type="button" @click="triggerLogoPicker" title="Upload company logo"
                            class="absolute -bottom-1.5 -right-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-white text-indigo-700 shadow-lg ring-2 ring-indigo-200 hover:scale-110 active:scale-95 transition">
                            <Camera class="h-3.5 w-3.5" />
                        </button>
                    </div>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                                <Building2 class="h-3.5 w-3.5" /> Account Management · Business Profile
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight truncate">{{ client?.company_name || 'Company Profile' }}</h1>
                            <p class="text-sm text-blue-100/90">Update your business info and pin your exact delivery location on the map.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-white/25 backdrop-blur flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-300 animate-pulse" /> {{ client?.email || 'Verified' }}
                            </span>
                            <button @click="refresh" title="Refresh"
                                class="flex h-9 w-9 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25 backdrop-blur hover:bg-white/30 hover:rotate-180 transition-all duration-500 active:scale-95">
                                <RefreshCw class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <!-- stat strip -->
                    <div class="relative mt-6 grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div class="animate-fade-up rounded-2xl bg-white/12 ring-1 ring-white/20 backdrop-blur p-4 hover:bg-white/20 hover:scale-[1.02] transition-all" style="animation-delay:80ms">
                            <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-blue-100"><Briefcase class="h-3.5 w-3.5" /> Business type</p>
                            <p class="mt-1 text-sm font-black truncate">{{ client?.business_type || '—' }}</p>
                        </div>
                        <div class="animate-fade-up rounded-2xl bg-white/12 ring-1 ring-white/20 backdrop-blur p-4 hover:bg-white/20 hover:scale-[1.02] transition-all" style="animation-delay:160ms">
                            <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-blue-100"><MapPin class="h-3.5 w-3.5" /> Location pin</p>
                            <p class="mt-1 text-sm font-black flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full animate-pulse" :class="form.latitude && form.longitude ? 'bg-emerald-300' : 'bg-amber-300'" /> {{ form.latitude && form.longitude ? 'Pinned' : 'Not set' }}</p>
                        </div>
                        <div class="animate-fade-up rounded-2xl bg-white/12 ring-1 ring-white/20 backdrop-blur p-4 hover:bg-white/20 hover:scale-[1.02] transition-all col-span-2 sm:col-span-1" style="animation-delay:240ms">
                            <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-blue-100"><Navigation class="h-3.5 w-3.5" /> Contact</p>
                            <p class="mt-1 text-sm font-black truncate">{{ client?.contact_person || '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Profile Form -->
                <div class="animate-fade-up group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 transition-all duration-300 overflow-hidden" style="animation-delay:150ms">
                    <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                    <form @submit.prevent="submit" class="relative p-6 sm:p-8 space-y-8">

                        <!-- Company Details -->
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-200 mb-4 flex items-center gap-2">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/20"><Briefcase class="h-4 w-4 text-blue-600" /></span> Company Details
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="animate-fade-up" style="animation-delay:50ms">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Company Name *</label>
                                    <input v-model="form.company_name" type="text" required
                                        class="w-full px-4 py-3 rounded-2xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm font-medium transition">
                                </div>
                                <div class="animate-fade-up" style="animation-delay:100ms">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Business Type *</label>
                                    <input v-model="form.business_type" type="text" required
                                        class="w-full px-4 py-3 rounded-2xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm font-medium transition">
                                </div>
                                <div class="animate-fade-up" style="animation-delay:150ms">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">TIN Number</label>
                                    <input v-model="form.tin_number" type="text"
                                        class="w-full px-4 py-3 rounded-2xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm font-medium transition">
                                </div>
                                <div class="animate-fade-up" style="animation-delay:200ms">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Contact Person *</label>
                                    <input v-model="form.contact_person" type="text" required
                                        class="w-full px-4 py-3 rounded-2xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm font-medium transition">
                                </div>
                                <div class="animate-fade-up" style="animation-delay:250ms">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Phone Number *</label>
                                    <input v-model="form.phone" type="tel" required
                                        class="w-full px-4 py-3 rounded-2xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm font-medium transition">
                                </div>
                                <div class="animate-fade-up" style="animation-delay:300ms">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Email (read-only)</label>
                                    <input :value="client?.email" type="email" disabled
                                        class="w-full px-4 py-3 rounded-2xl bg-gray-100 dark:bg-zinc-800/50 border border-transparent text-sm text-gray-500 cursor-not-allowed">
                                </div>
                                <div class="animate-fade-up md:col-span-2" style="animation-delay:350ms">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Company Logo</label>
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 rounded-2xl border border-dashed border-indigo-200 dark:border-indigo-900 bg-indigo-50/50 dark:bg-indigo-900/10 p-4">
                                        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white dark:bg-zinc-800 shadow ring-1 ring-indigo-100 dark:ring-indigo-900">
                                            <img v-if="displayLogo" :src="displayLogo" alt="Company logo preview" class="h-full w-full object-cover" @error="logoBroken = true" />
                                            <ImagePlus v-else class="h-8 w-8 text-indigo-300" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" @click="triggerLogoPicker"
                                                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white shadow-lg shadow-indigo-500/25 hover:bg-indigo-700 hover:scale-105 active:scale-95 transition-all">
                                                    <Camera class="h-4 w-4" /> {{ client?.logo || logoPreview ? 'Change logo' : 'Upload logo' }}
                                                </button>
                                                <button v-if="logoPreview" type="button" @click="saveLogo" :disabled="savingLogo"
                                                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:scale-105 active:scale-95 transition-all disabled:opacity-60">
                                                    <Loader2 v-if="savingLogo" class="h-4 w-4 animate-spin" />
                                                    <CheckCircle2 v-else class="h-4 w-4" /> {{ savingLogo ? 'Saving…' : 'Save logo' }}
                                                </button>
                                                <button v-if="logoPreview" type="button" @click="cancelLogoSelection"
                                                    class="rounded-xl bg-gray-100 dark:bg-zinc-800 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-gray-500 hover:bg-gray-200 active:scale-95 transition">
                                                    Cancel
                                                </button>
                                                <button v-if="!logoPreview && client?.logo" type="button" @click="removeCurrentLogo"
                                                    class="inline-flex items-center gap-1.5 rounded-xl bg-rose-50 dark:bg-rose-900/20 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-rose-600 hover:bg-rose-100 active:scale-95 transition">
                                                    <Trash2 class="h-4 w-4" /> Remove
                                                </button>
                                            </div>
                                            <p class="mt-2 text-[11px] text-gray-400 font-medium">PNG, JPG, WEBP or SVG · max 5MB. {{ logoPreview ? 'Preview ready — click Save logo to store it in the database.' : 'Shown across MontiERP wherever your company appears.' }}</p>
                                        </div>
                                    </div>
                                    <input ref="logoInput" type="file" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" @change="handleLogoChange" class="hidden" />
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Address -->
                        <div class="rounded-3xl border border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-indigo-50/60 to-transparent dark:from-indigo-900/10 p-5">
                            <h3 class="text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-200 mb-4 flex items-center gap-2">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 dark:bg-violet-900/20"><MapPin class="h-4 w-4 text-violet-600" /></span> Delivery Address
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Street Address *</label>
                                    <textarea v-model="form.company_address" rows="2" required
                                        class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition resize-none"></textarea>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">City</label>
                                        <input v-model="form.city" type="text"
                                            class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Province</label>
                                        <input v-model="form.province" type="text"
                                            class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Postal Code</label>
                                        <input v-model="form.postal_code" type="text"
                                            class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pinpoint Location Map -->
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-200 mb-2 flex items-center gap-2">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-900/20"><Navigation class="h-4 w-4 text-indigo-600" /></span> Pinpoint Your Location
                            </h3>
                            <p class="text-xs text-gray-400 mb-4 font-medium">
                                Click anywhere on the map (or drag the pin) to set your exact delivery location.
                                MontiTextiles uses this for route calculation.
                            </p>

                            <!-- Address search bar -->
                            <div class="flex flex-col sm:flex-row gap-3 mb-3">
                                <div class="relative flex-1">
                                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <input
                                        v-model="addressSearch"
                                        type="text"
                                        placeholder="Search address to jump to location..."
                                        class="w-full pl-11 pr-4 py-3 rounded-2xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition"
                                        @keydown.enter.prevent="searchAddress"
                                    />
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" @click="searchAddress" :disabled="isSearching"
                                        class="px-4 py-3 rounded-2xl bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 text-sm font-black text-gray-600 dark:text-gray-300 transition disabled:opacity-50 flex items-center gap-2 active:scale-95">
                                        <Loader2 v-if="isSearching" class="h-4 w-4 animate-spin" />
                                        <Search v-else class="h-4 w-4" />
                                    </button>
                                    <button type="button" @click="useCurrentLocation" :disabled="isGettingLocation"
                                        class="px-4 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:shadow-lg hover:shadow-indigo-500/30 hover:scale-105 active:scale-95 text-sm font-black text-white transition-all disabled:opacity-50 flex items-center gap-2 whitespace-nowrap shadow-lg shadow-indigo-500/25">
                                        <Loader2 v-if="isGettingLocation" class="h-4 w-4 animate-spin" />
                                        <Crosshair v-else class="h-4 w-4" />
                                        My Location
                                    </button>
                                </div>
                            </div>

                            <!-- The Map -->
                            <div class="relative rounded-3xl overflow-hidden border border-gray-200 dark:border-zinc-700 shadow-inner ring-1 ring-indigo-100 dark:ring-indigo-900" style="height:400px;">
                                <div id="client-map" class="w-full h-full z-0"></div>

                                <!-- No pin notice overlay -->
                                <div v-if="!form.latitude || !form.longitude"
                                    class="absolute bottom-4 left-1/2 -translate-x-1/2 z-[1000] pointer-events-none">
                                    <div class="bg-gradient-to-r from-amber-600 to-orange-600 backdrop-blur-sm text-white px-4 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg animate-bounce-soft">
                                        <AlertTriangle class="h-3.5 w-3.5" />
                                        Tap the map to drop your location pin
                                    </div>
                                </div>

                                <!-- Live coordinate badge -->
                                <div v-if="form.latitude && form.longitude"
                                    class="absolute bottom-4 left-4 z-[1000] pointer-events-none">
                                    <div class="bg-slate-900/85 backdrop-blur-sm text-white px-4 py-2.5 rounded-2xl text-[10px] font-black tracking-widest flex items-center gap-2 shadow-lg">
                                        <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></div>
                                        {{ Number(form.latitude).toFixed(6) }}, {{ Number(form.longitude).toFixed(6) }}
                                    </div>
                                </div>

                                <!-- Map type badge -->
                                <div class="absolute top-4 left-4 z-[1000] pointer-events-none">
                                    <div class="bg-slate-900/80 backdrop-blur-md text-white px-3 py-1.5 rounded-xl text-[9px] tracking-widest font-black uppercase flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-pulse"></div>
                                        Interactive Pin Map
                                    </div>
                                </div>
                            </div>

                            <!-- Coordinate inputs (kept for manual override) -->
                            <div class="mt-3 grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Latitude</label>
                                    <input v-model.number="form.latitude" type="number" step="any"
                                        class="w-full px-4 py-3 rounded-2xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm font-mono transition"
                                        @change="syncMapToForm">
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Longitude</label>
                                    <input v-model.number="form.longitude" type="number" step="any"
                                        class="w-full px-4 py-3 rounded-2xl bg-gray-50 dark:bg-zinc-800 border border-transparent focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 outline-none text-sm font-mono transition"
                                        @change="syncMapToForm">
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1 font-medium">
                                These coordinates will appear as a pin on the Logistics Routes map for delivery planning.
                            </p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-100 dark:border-zinc-800">
                            <button type="submit" :disabled="form.processing"
                                class="px-8 py-3.5 bg-gradient-to-r from-blue-700 via-indigo-700 to-violet-800 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-indigo-500/25 hover:shadow-2xl hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                                Update Profile
                            </button>
                            <button type="button" @click="resetForm"
                                class="px-8 py-3.5 bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-gray-300 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-zinc-700 active:scale-95 transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Toast -->
                <Transition name="toast">
                    <div v-if="toast.show"
                        class="fixed bottom-8 right-8 z-[9999] px-6 py-3 rounded-2xl shadow-xl text-white font-bold text-sm flex items-center gap-2 ring-1 ring-white/20"
                        :class="toast.type === 'success' ? 'bg-gradient-to-r from-emerald-600 to-teal-600' : 'bg-gradient-to-r from-rose-600 to-red-600'">
                        <CheckCircle2 v-if="toast.type === 'success'" class="h-4 w-4" />
                        <X v-else class="h-4 w-4" />
                        {{ toast.message }}
                    </div>
                </Transition>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import {
    Building2, RefreshCw, Briefcase, MapPin, Navigation,
    Loader2, Search, Crosshair, AlertTriangle, CheckCircle2, X,
    Camera, ImagePlus, Trash2
} from 'lucide-vue-next';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';

// ── Props & state ─────────────────────────────────────────────────────────
const page   = usePage();
const client = ref(page.props.client ?? page.props.auth?.client);

const form = useForm({
    company_name:    client.value?.company_name    || '',
    business_type:   client.value?.business_type   || '',
    tin_number:      client.value?.tin_number      || '',
    contact_person:  client.value?.contact_person  || '',
    phone:           client.value?.phone           || '',
    company_address: client.value?.company_address || '',
    city:            client.value?.city            || '',
    province:        client.value?.province        || '',
    postal_code:     client.value?.postal_code     || '',
    latitude:        client.value?.latitude  ? Number(client.value.latitude)  : null,
    longitude:       client.value?.longitude ? Number(client.value.longitude) : null,
    logo: null,
});

// ── Company logo ─────────────────────────────────────────────────────────
const logoInput   = ref(null);
const logoPreview = ref(null);
const savingLogo  = ref(false);
const logoBroken  = ref(false);

const displayLogo = computed(() => {
    if (logoPreview.value) return logoPreview.value;
    if (logoBroken.value) return null;
    return client.value?.logo?.logo_url || null;
});

const triggerLogoPicker = () => logoInput.value?.click();

const handleLogoChange = (e) => {
    const file = e.target.files[0] || null;
    form.logo = file;
    logoBroken.value = false;
    logoPreview.value = file ? URL.createObjectURL(file) : null;
};

const cancelLogoSelection = () => {
    form.logo = null;
    logoPreview.value = null;
    if (logoInput.value) logoInput.value.value = '';
};

const removeCurrentLogo = () => {
    if (!client.value?.logo) return;
    router.delete(route('client.profile.logo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            cancelLogoSelection();
            showToast('success', 'Company logo removed.');
        },
        onError: (errors) => showToast('error', Object.values(errors)[0] || 'Failed to remove logo.'),
    });
};

const saveLogo = () => {
    if (!form.logo || savingLogo.value) return;
    savingLogo.value = true;
    router.post(route('client.profile.logo.store'), { logo: form.logo }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            cancelLogoSelection();
            showToast('success', 'Company logo saved to database.');
        },
        onError: (errors) => showToast('error', Object.values(errors)[0] || 'Failed to save logo.'),
        onFinish: () => { savingLogo.value = false; },
    });
};

// Keep local client copy in sync after Inertia reloads (e.g. new logo saved)
watch(() => page.props.client, (val) => { if (val) { client.value = val; logoBroken.value = false; } });

const toast             = ref({ show: false, type: 'success', message: '' });
const addressSearch     = ref('');
const isSearching       = ref(false);
const isGettingLocation = ref(false);

// ── Leaflet ────────────────────────────────────────────────────────────────
let clientMap    = null;
let clientMarker = null;

const buildPinIcon = () => L.divIcon({
    className: '',
    html: `<div style="
        background:#6366f1;color:white;border-radius:50%;
        width:36px;height:36px;
        display:flex;align-items:center;justify-content:center;
        border:4px solid white;box-shadow:0 4px 14px rgba(99,102,241,0.55);
        font-size:16px;cursor:pointer;">📍</div>
    <div style="
        position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);
        width:0;height:0;border-left:7px solid transparent;
        border-right:7px solid transparent;border-top:10px solid #6366f1;"></div>`,
    iconSize:   [36, 44],
    iconAnchor: [18, 44],
});

const placeMarker = (lat, lng) => {
    if (clientMarker) {
        clientMarker.setLatLng([lat, lng]);
    } else {
        clientMarker = L.marker([lat, lng], {
            icon:      buildPinIcon(),
            draggable: true,
        }).addTo(clientMap);

        clientMarker.on('dragend', (e) => {
            const pos        = e.target.getLatLng();
            form.latitude    = parseFloat(pos.lat.toFixed(8));
            form.longitude   = parseFloat(pos.lng.toFixed(8));
        });
    }
    form.latitude  = parseFloat(lat.toFixed(8));
    form.longitude = parseFloat(lng.toFixed(8));
};

const initClientMap = () => {
    const startLat = form.latitude  || 14.5995;
    const startLng = form.longitude || 120.9842;
    const startZoom = form.latitude ? 16 : 12;

    const street    = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
    const satellite = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { maxZoom: 20 }
    );

    clientMap = L.map('client-map', {
        center: [startLat, startLng],
        zoom:   startZoom,
        layers: [street],
    });
    L.control.layers({ 'Street': street, 'Satellite': satellite }).addTo(clientMap);

    // If the client already has a saved location, show it
    if (form.latitude && form.longitude) {
        placeMarker(form.latitude, form.longitude);
    }

    // Click-to-pin
    clientMap.on('click', (e) => {
        placeMarker(e.latlng.lat, e.latlng.lng);
        clientMap.panTo([e.latlng.lat, e.latlng.lng]);
    });
};

// Keep map marker in sync when form coords are manually typed
const syncMapToForm = () => {
    if (!clientMap || !form.latitude || !form.longitude) return;
    placeMarker(form.latitude, form.longitude);
    clientMap.panTo([form.latitude, form.longitude]);
};

// ── Address Search (Nominatim) ─────────────────────────────────────────────
const searchAddress = async () => {
    if (!addressSearch.value.trim()) return;
    isSearching.value = true;
    try {
        const res = await axios.get('https://nominatim.openstreetmap.org/search', {
            params: { q: addressSearch.value, format: 'json', limit: 1, countrycodes: 'ph' },
            headers: { 'Accept-Language': 'en' },
        });
        if (res.data.length) {
            const { lat, lon, display_name } = res.data[0];
            placeMarker(parseFloat(lat), parseFloat(lon));
            clientMap.setView([parseFloat(lat), parseFloat(lon)], 17);
            showToast('success', `Found: ${display_name.split(',').slice(0, 3).join(',')}`);
        } else {
            showToast('error', 'Address not found. Try a more specific search.');
        }
    } catch {
        showToast('error', 'Search failed. Please try again.');
    } finally {
        isSearching.value = false;
    }
};

// ── GPS Current Location ───────────────────────────────────────────────────
const useCurrentLocation = () => {
    if (!navigator.geolocation) {
        showToast('error', 'Geolocation not supported by your browser.');
        return;
    }
    isGettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            placeMarker(pos.coords.latitude, pos.coords.longitude);
            clientMap.setView([pos.coords.latitude, pos.coords.longitude], 17);
            showToast('success', 'GPS location captured.');
            isGettingLocation.value = false;
        },
        () => {
            showToast('error', 'Unable to retrieve GPS location. Please allow location access.');
            isGettingLocation.value = false;
        },
        { enableHighAccuracy: true }
    );
};

// ── Helpers ────────────────────────────────────────────────────────────────
const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3500);
};

const submit = () => {
    form.patch(route('client.profile.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            cancelLogoSelection();
            showToast('success', 'Profile updated successfully.');
        },
        onError: (errors) => showToast('error', Object.values(errors)[0] || 'Update failed.'),
    });
};

const resetForm = () => {
    form.company_name    = client.value?.company_name    || '';
    form.business_type   = client.value?.business_type   || '';
    form.tin_number      = client.value?.tin_number      || '';
    form.contact_person  = client.value?.contact_person  || '';
    form.phone           = client.value?.phone           || '';
    form.company_address = client.value?.company_address || '';
    form.city            = client.value?.city            || '';
    form.province        = client.value?.province        || '';
    form.postal_code     = client.value?.postal_code     || '';
    form.latitude        = client.value?.latitude  ? Number(client.value.latitude)  : null;
    form.longitude       = client.value?.longitude ? Number(client.value.longitude) : null;
    cancelLogoSelection();
    syncMapToForm();
};

const refresh = () => router.reload({ only: ['client'] });

onMounted(() => nextTick(() => initClientMap()));
</script>

<style scoped>
@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
@keyframes pop { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
@keyframes bounceSoft { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
.animate-fade-up { animation: fadeUp 0.6s cubic-bezier(0.22,1,0.36,1) both; }
.animate-float { animation: float 7s ease-in-out infinite; }
.animate-float-delayed { animation: float 8s ease-in-out 1.2s infinite; }
.animate-pop { animation: pop 0.5s cubic-bezier(0.22,1,0.36,1) both; }
.animate-bounce-soft { animation: bounceSoft 2.4s ease-in-out infinite; }
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(20px); }

:deep(.leaflet-control-layers) {
    border-radius: 1rem !important;
    border: none !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
    padding: 6px 10px;
    font-weight: 900;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
:deep(.leaflet-popup-content-wrapper) {
    border-radius: 1rem !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
:deep(.leaflet-popup-tip) { display: none; }
</style>
