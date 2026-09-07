<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    MapPin, ShieldCheck, Loader2, Globe, Navigation,
    Crosshair, Radio, Layers, Save, AlertCircle, CheckCircle2,
    Database, Activity, Clock, History, X, Archive
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

// Leaflet imports
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    savedLocation: Object,
    locationHistory: { type: Array, default: () => [] }
});

// Helper function to safely convert to number
const toNum = (val) => val ? Number(val) : null;

// State
const coords = ref({ 
    latitude: toNum(props.savedLocation?.latitude) || 14.5995, 
    longitude: toNum(props.savedLocation?.longitude) || 120.9842, 
    accuracy: null 
});

// For the Status Bar - Ensuring these are Numbers to prevent .toFixed() errors
const lastSavedInfo = ref({
    lat: toNum(props.savedLocation?.latitude),
    lng: toNum(props.savedLocation?.longitude),
    time: props.savedLocation?.created_at ? new Date(props.savedLocation.created_at).toLocaleTimeString() : null
});

const rangeRadius = ref(toNum(props.savedLocation?.range_radius) || 500);
const isSyncing = ref(false);
const isSaving = ref(false);
const statusMsg = ref(null);
const errorMsg = ref(null);
const showArchiveModal = ref(false);
const placeName = ref(null);
const resolvingPlace = ref(false);
const resolvedNames = ref({}); // { logId: placeName } for old archives
const placeCache = new Map();

const shortName = (display_name) => display_name ? display_name.split(',').slice(0, 3).join(',').trim() : null;

const reverseGeocode = async (lat, lng) => {
    const key = `${Number(lat).toFixed(5)},${Number(lng).toFixed(5)}`;
    if (placeCache.has(key)) return placeCache.get(key);
    try {
        const res = await axios.get('https://nominatim.openstreetmap.org/reverse', {
            params: { lat, lon: lng, format: 'json' },
            headers: { 'Accept-Language': 'en' },
        });
        const name = shortName(res.data?.display_name);
        placeCache.set(key, name);
        return name;
    } catch {
        placeCache.set(key, null);
        return null;
    }
};

const openArchiveConfirm = async () => {
    errorMsg.value = null;
    showArchiveModal.value = true;
    // Resolve the human-readable place for the confirm modal
    resolvingPlace.value = true;
    placeName.value = null;
    placeName.value = await reverseGeocode(coords.value.latitude, coords.value.longitude);
    resolvingPlace.value = false;
};

// Local archive log (seeded from server, prepended on every save)
const history = ref([...(props.locationHistory ?? [])]);

let map = null;
let marker = null;
let circle = null;

const initMap = () => {
    const streetView = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
    const satelliteView = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20 });

    map = L.map('map', {
        center: [coords.value.latitude, coords.value.longitude],
        zoom: 15,
        layers: [streetView]
    });

    L.control.layers({ "Default": streetView, "Satellite": satelliteView }).addTo(map);

    marker = L.marker([coords.value.latitude, coords.value.longitude]).addTo(map);
    circle = L.circle([coords.value.latitude, coords.value.longitude], {
        color: '#3b82f6', fillColor: '#3b82f6', fillOpacity: 0.15, radius: rangeRadius.value
    }).addTo(map);
};

const updateMap = () => {
    if (map && marker && circle) {
        const pos = [coords.value.latitude, coords.value.longitude];
        marker.setLatLng(pos);
        circle.setLatLng(pos);
        circle.setRadius(rangeRadius.value);
        map.panTo(pos);
    }
};

watch([() => coords.value.latitude, () => coords.value.longitude, rangeRadius], updateMap);
onMounted(async () => {
    initMap();
    // Backfill place names for old archives saved before place_name existed
    // (Nominatim policy: max 1 req/sec — capped to avoid hammering the API)
    let resolved = 0;
    for (const log of history.value) {
        if (resolved >= 15) break;
        if (!log.place_name && log.id && !resolvedNames.value[log.id]) {
            const name = await reverseGeocode(log.latitude, log.longitude);
            if (name) resolvedNames.value[log.id] = name;
            resolved++;
            await new Promise(r => setTimeout(r, 1100));
        }
    }
});

const saveToDatabase = async () => {
    showArchiveModal.value = false;
    isSaving.value = true;
    errorMsg.value = null;
    statusMsg.value = null;

    const payload = {
        latitude: coords.value.latitude,
        longitude: coords.value.longitude,
        range_radius: parseInt(rangeRadius.value),
        label: 'CEO Manual Log',
        place_name: placeName.value,
    };

    try {
        const response = await axios.post(route('ceo.location.store'), payload);
        statusMsg.value = response.data.message;

        // Update the Live Status Bar with fresh Numbers
        lastSavedInfo.value = {
            lat: Number(payload.latitude),
            lng: Number(payload.longitude),
            time: new Date().toLocaleTimeString()
        };

        // Prepend to the archive log instantly (no reload needed)
        if (response.data?.data) {
            history.value.unshift(response.data.data);
        }

        Swal.fire({
            title: 'Archived!',
            html: `<b>${placeName.value || 'Location'}</b><br>(${Number(payload.latitude).toFixed(6)}, ${Number(payload.longitude).toFixed(6)}) with ${payload.range_radius}m radius was successfully recorded in the database.`,
            icon: 'success',
            confirmButtonColor: '#4f46e5',
            confirmButtonText: 'Great',
            background: '#fff',
            customClass: {
                popup: 'rounded-[2rem] shadow-2xl',
                title: 'font-black tracking-tight text-slate-900',
                confirmButton: 'rounded-xl px-6 py-2.5 font-bold',
            }
        });
    } catch (err) {
        errorMsg.value = err.response?.data?.message || "Failed to save to database.";
        Swal.fire({
            title: 'Archive Failed',
            text: errorMsg.value,
            icon: 'error',
            confirmButtonColor: '#e11d48',
            confirmButtonText: 'Retry',
            background: '#fff',
            customClass: {
                popup: 'rounded-[2rem] shadow-2xl',
                title: 'font-black tracking-tight text-slate-900',
                confirmButton: 'rounded-xl px-6 py-2.5 font-bold',
            }
        });
    } finally {
        isSaving.value = false;
    }
};

const syncGps = () => {
    isSyncing.value = true;
    navigator.geolocation.getCurrentPosition((pos) => {
        coords.value.latitude = pos.coords.latitude;
        coords.value.longitude = pos.coords.longitude;
        coords.value.accuracy = pos.coords.accuracy ? pos.coords.accuracy.toFixed(2) : 'N/A';
        map.setZoom(18);
        isSyncing.value = false;
    }, () => {
        errorMsg.value = "GPS Signal Denied.";
        isSyncing.value = false;
    }, { enableHighAccuracy: true });
};
</script>

<template>
    <Head title="Strategic Geolocation" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
            <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 pb-16">

                <!-- Hero header -->
                <div class="animate-fade-up relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
                    <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div class="absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-fuchsia-400/20 blur-3xl animate-float-delayed" />
                    <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                    <div class="relative flex flex-wrap items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/30 shadow-lg animate-pop">
                            <MapPin class="h-7 w-7" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                                <Globe class="h-3.5 w-3.5" /> CEO · Strategic Geolocation
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Strategic Geolocation</h1>
                            <p class="text-sm text-blue-100/90">Pin the headquarters coordinates and archiving radius.</p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-white/25 backdrop-blur">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-300 animate-pulse" /> {{ rangeRadius }}m radius
                            </span>
                            <span v-if="typeof lastSavedInfo.lat === 'number'" class="rounded-full bg-emerald-400/90 px-3 py-1.5 text-xs font-black text-emerald-950">Archived</span>
                            <span v-else class="rounded-full bg-amber-300/90 px-3 py-1.5 text-xs font-black text-amber-950">No archive yet</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">

                    <!-- Controls -->
                    <div class="animate-fade-up lg:col-span-1" style="animation-delay: 80ms">
                        <div class="group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur p-6 sm:p-8 rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300 overflow-hidden">
                            <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                            <div class="relative flex items-center gap-3 mb-6">
                                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 text-white shadow-lg">
                                    <Crosshair class="w-5 h-5" />
                                </span>
                                <div>
                                    <h2 class="text-base font-black tracking-tight text-slate-900 dark:text-white leading-tight">Controls</h2>
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Manual log</p>
                                </div>
                            </div>

                            <div class="relative space-y-4 mb-6">
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 block mb-1.5">Latitude</label>
                                    <input v-model.number="coords.latitude" type="number" step="any" class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800/80 border border-gray-100 dark:border-zinc-700/60 rounded-2xl shadow-inner text-sm font-bold text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500/50 transition" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 block mb-1.5">Longitude</label>
                                    <input v-model.number="coords.longitude" type="number" step="any" class="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-800/80 border border-gray-100 dark:border-zinc-700/60 rounded-2xl shadow-inner text-sm font-bold text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500/50 transition" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 block mb-1.5">Range Radius (M)</label>
                                    <input v-model.number="rangeRadius" type="number" class="w-full px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/50 rounded-2xl shadow-inner text-sm font-black text-indigo-600 dark:text-indigo-300 outline-none focus:ring-2 focus:ring-indigo-500/50 transition" />
                                </div>
                            </div>

                            <Transition name="modal">
                                <div v-if="errorMsg" class="relative mb-4 p-4 bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-300 rounded-2xl text-xs font-bold border border-red-100 dark:border-red-500/20 flex items-center gap-2">
                                    <AlertCircle class="w-4 h-4 shrink-0" /> {{ errorMsg }}
                                </div>
                            </Transition>
                            <Transition name="modal">
                                <div v-if="statusMsg" class="relative mb-4 p-4 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 rounded-2xl text-xs font-bold border border-emerald-100 dark:border-emerald-500/20 flex items-center gap-2">
                                    <CheckCircle2 class="w-4 h-4 shrink-0" /> {{ statusMsg }}
                                </div>
                            </Transition>

                            <div class="relative flex flex-col gap-3">
                                <!-- <button @click="syncGps" :disabled="isSyncing" class="w-full py-5 bg-slate-900 text-white rounded-2xl shadow-lg flex items-center justify-center gap-3 text-[10px] tracking-widest hover:bg-black transition-all">
                                    <Loader2 v-if="isSyncing" class="w-4 h-4 animate-spin" />
                                    <Navigation v-else class="w-4 h-4" /> 1. SYNC GPS
                                </button> -->

                                <button @click="openArchiveConfirm" :disabled="isSaving" class="w-full py-4 bg-gradient-to-r from-blue-700 via-indigo-700 to-violet-800 text-white rounded-2xl shadow-xl shadow-indigo-500/20 flex items-center justify-center gap-2 text-xs font-black uppercase tracking-widest hover:shadow-2xl hover:-translate-y-0.5 active:scale-95 transition-all disabled:opacity-50">
                                    <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                                    <Save v-else class="w-4 h-4" /> Archive Data
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Map -->
                    <div class="animate-fade-up lg:col-span-3" style="animation-delay: 140ms">
                        <div class="group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur p-2 rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 transition-all duration-300 h-[720px] overflow-hidden">
                            <div id="map" class="w-full h-full rounded-[1.4rem] z-0"></div>

                            <div class="absolute top-6 left-6 z-[1000] pointer-events-none">
                                <div class="bg-slate-900/80 backdrop-blur-md text-white px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest flex items-center gap-2 ring-1 ring-white/20">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Secure Live Feed
                                </div>
                            </div>

                            <div class="absolute bottom-6 left-6 right-6 z-[1000]">
                                <div class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border border-gray-100 dark:border-zinc-800 rounded-3xl p-4 shadow-2xl flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex flex-wrap items-center gap-6">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/20">
                                                <Database class="w-4 h-4 text-blue-500" />
                                            </span>
                                            <div>
                                                <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Database Status</p>
                                                <p class="text-xs font-black text-slate-900 dark:text-white">Active / Archiving</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 border-l border-gray-100 dark:border-zinc-800 pl-6">
                                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
                                                <Activity class="w-4 h-4 text-emerald-500" />
                                            </span>
                                            <div v-if="typeof lastSavedInfo.lat === 'number'">
                                                <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Last Saved Coordinates</p>
                                                <p class="text-xs font-black text-slate-900 dark:text-white">
                                                    {{ lastSavedInfo.lat.toFixed(4) }}, {{ lastSavedInfo.lng.toFixed(4) }}
                                                </p>
                                            </div>
                                            <div v-else>
                                                <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Last Saved Coordinates</p>
                                                <p class="text-xs font-bold text-gray-400 italic">No Archive Data</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 bg-slate-50 dark:bg-zinc-800/60 px-4 py-2 rounded-2xl border border-gray-100 dark:border-zinc-700/60">
                                        <Clock class="w-3.5 h-3.5 text-indigo-400" />
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-300">
                                            Sync: {{ lastSavedInfo.time ? lastSavedInfo.time : 'Waiting' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Archive history / logs -->
                <div class="animate-fade-up" style="animation-delay: 200ms">
                    <div class="group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 transition-all duration-300 overflow-hidden">
                        <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                        <div class="relative p-6 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-indigo-50 to-transparent dark:from-indigo-900/20 flex flex-wrap items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 text-white shadow-lg">
                                <History class="w-5 h-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <h2 class="text-base font-black tracking-tight text-slate-900 dark:text-white leading-tight">Archive Logs</h2>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Every location ever pinned</p>
                            </div>
                            <span class="rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-3 py-1.5 text-xs font-black">{{ history.length }} record{{ history.length !== 1 ? 's' : '' }}</span>
                        </div>
                        <div v-if="history.length === 0" class="relative p-12 text-center">
                            <Archive class="mx-auto h-10 w-10 text-gray-200 dark:text-zinc-700 animate-bounce-soft" />
                            <p class="mt-3 text-sm font-black text-gray-500 dark:text-gray-400">No archived locations yet</p>
                            <p class="text-xs text-gray-400 mt-1">Pin a location above and click Archive Data — it will be logged here.</p>
                        </div>
                        <TransitionGroup v-else name="card" tag="div" class="relative divide-y divide-gray-100 dark:divide-zinc-800">
                            <div v-for="(log, i) in history" :key="log.id ?? i" :style="{ transitionDelay: `${Math.min(i * 40, 400)}ms` }"
                                class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 sm:px-6 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 text-white shadow-md text-xs font-black">{{ String(i + 1).padStart(2, '0') }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-black text-gray-900 dark:text-white truncate flex items-center gap-1.5"><MapPin class="h-3.5 w-3.5 shrink-0 text-indigo-500" /> {{ log.place_name || resolvedNames[log.id] || log.label || 'Manual Log' }}</p>
                                    <p class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ Number(log.latitude).toFixed(6) }}, {{ Number(log.longitude).toFixed(6) }}</p>
                                </div>
                                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 text-[11px] font-black">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" /> {{ log.range_radius }}m
                                </span>
                                <span class="text-[11px] font-bold text-gray-400 whitespace-nowrap">{{ log.created_at ? new Date(log.created_at).toLocaleString() : '' }}</span>
                            </div>
                        </TransitionGroup>
                    </div>
                </div>
            </div>
        </div>

        <!-- Archive confirmation modal -->
        <Transition name="modal">
            <div v-if="showArchiveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showArchiveModal = false">
                <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-violet-800 p-5 flex items-center gap-3 relative overflow-hidden">
                        <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/15 blur-2xl" />
                        <div class="relative flex h-10 w-10 items-center justify-center rounded-2xl bg-white/20 ring-1 ring-white/30">
                            <Archive class="w-5 h-5 text-white" />
                        </div>
                        <div class="relative min-w-0 flex-1">
                            <h3 class="text-white font-black">Confirm Archive</h3>
                            <p class="text-xs text-blue-100">This will be recorded in the database</p>
                        </div>
                        <button @click="showArchiveModal = false" class="relative flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-white hover:bg-white/35 hover:rotate-90 transition-all">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 p-4 space-y-2 text-sm">
                            <p class="flex justify-between gap-3"><span class="text-gray-500 font-bold">Latitude</span><span class="font-mono font-black text-gray-900 dark:text-white">{{ Number(coords.latitude).toFixed(6) }}</span></p>
                            <p class="flex justify-between gap-3"><span class="text-gray-500 font-bold">Longitude</span><span class="font-mono font-black text-gray-900 dark:text-white">{{ Number(coords.longitude).toFixed(6) }}</span></p>
                            <p class="flex justify-between gap-3"><span class="text-gray-500 font-bold">Radius</span><span class="font-black text-indigo-600 dark:text-indigo-300">{{ rangeRadius }} meters</span></p>
                            <div class="flex justify-between gap-3 pt-2 border-t border-indigo-100 dark:border-indigo-800">
                                <span class="text-gray-500 font-bold flex items-center gap-1.5 shrink-0"><MapPin class="h-3.5 w-3.5" /> Place</span>
                                <span v-if="resolvingPlace" class="text-xs font-bold text-gray-400 flex items-center gap-1.5"><Loader2 class="h-3.5 w-3.5 animate-spin" /> Locating…</span>
                                <span v-else class="text-xs font-black text-gray-900 dark:text-white text-right">{{ placeName || 'Unknown location' }}</span>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button @click="showArchiveModal = false" class="flex-1 py-2.5 rounded-xl bg-gray-100 dark:bg-zinc-800 text-gray-500 font-bold text-sm hover:bg-gray-200 active:scale-95 transition">Cancel</button>
                            <button @click="saveToDatabase" :disabled="isSaving" class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 rounded-xl bg-gradient-to-r from-blue-700 via-indigo-700 to-violet-800 text-white font-black text-sm shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-60">
                                <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                                <Save v-else class="w-4 h-4" /> {{ isSaving ? 'Archiving…' : 'Confirm' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<style scoped>
#map { background: #0f172a; }
:deep(.leaflet-control-layers) { border-radius: 1.5rem !important; border: none !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); padding: 8px; font-weight: 900; font-size: 9px; text-transform: uppercase; }

@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
@keyframes pop { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
@keyframes bounceSoft { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
.animate-fade-up { animation: fadeUp 0.6s cubic-bezier(0.22,1,0.36,1) both; }
.animate-float { animation: float 7s ease-in-out infinite; }
.animate-float-delayed { animation: float 8s ease-in-out 1.2s infinite; }
.animate-pop { animation: pop 0.5s cubic-bezier(0.22,1,0.36,1) both; }
.animate-bounce-soft { animation: bounceSoft 2.4s ease-in-out infinite; }
.card-enter-active { transition: opacity 0.45s ease, transform 0.45s cubic-bezier(0.22,1,0.36,1); }
.card-enter-from { opacity: 0; transform: translateY(18px) scale(0.98); }
.card-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; position: absolute; }
.card-leave-to { opacity: 0; transform: scale(0.96); }
.card-move { transition: transform 0.4s ease; }
.modal-enter-active { transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.22,1,0.36,1); }
.modal-enter-from { opacity: 0; transform: translateY(14px) scale(0.98); }
.modal-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.modal-leave-to { opacity: 0; transform: scale(0.97); }
</style>
