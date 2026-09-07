<template>
    <Head :title="`Conversation with ${supplier?.business_name}`" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
            <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 pb-16">

                <!-- Hero header -->
                <div class="animate-fade-up relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
                    <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl animate-float" />
                    <div class="absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-fuchsia-400/20 blur-3xl animate-float-delayed" />
                    <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                    <div class="relative flex flex-wrap items-center gap-4">
                        <Link :href="route('eco.suppliers')" class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/30 shadow-lg hover:bg-white/25 transition active:scale-95">
                            <ArrowLeft class="h-5 w-5" />
                        </Link>
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/30 shadow-lg animate-pop">
                            <MessagesSquare class="h-7 w-7" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                                <Sparkles class="h-3.5 w-3.5" /> ECO · Supplier Chat
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight truncate">
                                {{ supplier?.business_name }}
                            </h1>
                            <p class="text-sm text-blue-100/90 truncate">{{ supplier?.representative_name }} · {{ supplier?.email }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-white/25 backdrop-blur flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-300 animate-pulse" /> {{ supplier?.status || 'Active' }}
                            </span>
                            <button @click="checkSupplierCredit" class="flex items-center gap-1.5 rounded-full bg-amber-300/90 px-3 py-1.5 text-xs font-black text-amber-950 hover:bg-amber-200 transition active:scale-95">
                                <Shield class="h-3.5 w-3.5" /> Credit Check
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <!-- Main conversation area -->
                    <div class="animate-fade-up lg:col-span-2 group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 transition-all duration-300 overflow-hidden flex flex-col h-[calc(100vh-250px)]" style="animation-delay:80ms">
                        <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                        <div class="p-5 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-indigo-50 to-transparent dark:from-indigo-900/20 flex items-center gap-2">
                            <MessagesSquare class="h-5 w-5 text-indigo-600" />
                            <h2 class="text-sm font-black uppercase tracking-widest">Conversation</h2>
                            <span class="ml-auto rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-[11px] font-black px-2.5 py-1">{{ messages.length }} messages</span>
                        </div>
                        <!-- Messages -->
                        <div ref="messagesContainer" class="relative flex-1 overflow-y-auto p-6 space-y-4">
                            <TransitionGroup name="card" tag="div" class="space-y-4">
                                <div v-for="msg in messages" :key="msg.id" class="flex" :class="msg.sender_type === 'supplier' ? 'justify-start' : 'justify-end'">
                                    <div :class="msg.sender_type === 'supplier'
                                        ? 'bg-gray-100 dark:bg-zinc-800 text-gray-800 dark:text-gray-200'
                                        : 'bg-gradient-to-br from-indigo-600 to-violet-700 text-white shadow-lg shadow-indigo-500/25'"
                                        class="max-w-[70%] rounded-2xl px-4 py-2.5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all">
                                        <p class="text-sm font-medium">{{ msg.message }}</p>
                                        <div v-if="msg.meeting_data" class="mt-2 text-xs border-t border-white/20 pt-1.5 flex items-center gap-1">
                                            <Calendar class="h-3 w-3 shrink-0" />
                                            Meeting: {{ msg.meeting_data.type }} at {{ msg.meeting_data.location }} on {{ formatDateTime(msg.meeting_data.scheduled_at) }}
                                        </div>
                                        <div v-if="msg.attachment" class="mt-1">
                                            <a :href="msg.attachment" target="_blank" class="text-xs underline flex items-center gap-1">
                                                <Paperclip class="h-3 w-3" /> Attachment
                                            </a>
                                        </div>
                                        <p class="text-[10px] opacity-70 mt-1 text-right">{{ formatTime(msg.created_at) }}</p>
                                    </div>
                                </div>
                            </TransitionGroup>
                            <div v-if="isTyping" class="flex justify-start">
                                <div class="bg-gray-100 dark:bg-zinc-800 rounded-2xl px-4 py-2.5 text-gray-500 dark:text-gray-400 text-sm flex items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse" />
                                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse" style="animation-delay:150ms" />
                                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse" style="animation-delay:300ms" />
                                    Supplier is typing...
                                </div>
                            </div>
                        </div>

                        <!-- Message input -->
                        <div class="border-t border-gray-100 dark:border-zinc-800 p-4 bg-white/60 dark:bg-zinc-900/60">
                            <form @submit.prevent="sendMessage" class="flex gap-3">
                                <input v-model="newMessage" type="text" placeholder="Type your message..."
                                    class="flex-1 rounded-2xl border-0 bg-gray-100 dark:bg-zinc-800 px-4 py-3 text-sm font-medium text-gray-900 dark:text-white placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-500 outline-none transition" />
                                <button type="button" @click="triggerFileUpload" class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gray-100 dark:bg-zinc-800 text-gray-500 hover:bg-indigo-100 hover:text-indigo-600 dark:hover:bg-indigo-900/30 transition active:scale-95">
                                    <Paperclip class="h-5 w-5" />
                                </button>
                                <input ref="fileInput" type="file" class="hidden" @change="uploadAttachment" />
                                <button type="submit" :disabled="sending" class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 text-white shadow-lg shadow-indigo-500/25 hover:scale-105 active:scale-95 transition disabled:opacity-50">
                                    <Send v-if="!sending" class="h-5 w-5" />
                                    <Loader2 v-else class="h-5 w-5 animate-spin" />
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar actions -->
                    <div class="space-y-5">
                        <!-- Set Meeting Card -->
                        <div class="animate-fade-up group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300 p-5 overflow-hidden" style="animation-delay:160ms">
                            <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                            <div class="absolute left-0 top-5 bottom-5 w-1 bg-gradient-to-b from-indigo-500 to-fuchsia-500 rounded-r-full scale-y-0 group-hover:scale-y-100 origin-center transition-transform duration-300" />
                            <h3 class="relative text-sm font-black uppercase tracking-widest mb-4 flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-900/20"><Calendar class="h-4 w-4 text-indigo-600" /></span> Schedule Meeting
                            </h3>
                            <form @submit.prevent="scheduleMeeting" class="relative">
                                <div class="space-y-3">
                                    <input v-model="meetingData.scheduled_at" type="datetime-local" required
                                        class="w-full rounded-2xl border-0 bg-gray-100 dark:bg-zinc-800 p-2.5 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none" />
                                    <input v-model="meetingData.location" type="text" placeholder="Location (e.g., Zoom, Office)" required
                                        class="w-full rounded-2xl border-0 bg-gray-100 dark:bg-zinc-800 p-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-500 outline-none" />
                                    <select v-model="meetingData.type" required class="w-full rounded-2xl border-0 bg-gray-100 dark:bg-zinc-800 p-2.5 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
                                        <option value="onsite">On-site</option>
                                        <option value="video">Video Call</option>
                                        <option value="phone">Phone Call</option>
                                    </select>
                                    <button type="submit" :disabled="scheduling" class="w-full py-2.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-2xl font-bold text-sm hover:bg-indigo-600 hover:text-white transition active:scale-95 disabled:opacity-50">
                                        {{ scheduling ? 'Scheduling...' : 'Send Meeting Invite' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Request Quotation / Purchase Order Card -->
                        <div class="animate-fade-up group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300 p-5 overflow-hidden" style="animation-delay:240ms">
                            <div class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-cyan-400/20 to-blue-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
                            <div class="absolute left-0 top-5 bottom-5 w-1 bg-gradient-to-b from-cyan-500 to-blue-500 rounded-r-full scale-y-0 group-hover:scale-y-100 origin-center transition-transform duration-300" />
                            <h3 class="relative text-sm font-black uppercase tracking-widest mb-4 flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/20"><FileText class="h-4 w-4 text-blue-600" /></span> Request Quotation
                            </h3>
                            <button @click="openRequestModal" class="relative w-full py-2.5 bg-gradient-to-br from-indigo-600 to-violet-700 text-white rounded-2xl font-bold text-sm shadow-lg shadow-indigo-500/25 hover:scale-[1.02] active:scale-95 transition">
                                Request Quotation / PO
                            </button>
                        </div>

                        <!-- Previous requests -->
                        <div v-if="requests.length > 0" class="animate-fade-up bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl border border-gray-100 dark:border-zinc-800 shadow-sm p-5 hover:shadow-xl transition-shadow duration-300" style="animation-delay:320ms">
                            <h3 class="text-sm font-black uppercase tracking-widest mb-3">Sent Requests · {{ requests.length }}</h3>
                            <TransitionGroup name="card" tag="div" class="space-y-2">
                                <div v-for="req in requests" :key="req.id" class="group p-3 bg-gray-50 dark:bg-zinc-800/60 rounded-2xl border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                                    <div class="flex justify-between items-center">
                                        <span class="font-mono text-xs font-bold text-gray-900 dark:text-white">{{ req.request_number }}</span>
                                        <span :class="req.status === 'accepted' ? 'bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30' : req.status === 'rejected' ? 'bg-rose-100 text-rose-700 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-500/30' : 'bg-amber-100 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30'" class="text-[9px] font-black uppercase px-2.5 py-1 rounded-full ring-1 flex items-center gap-1">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" /> {{ req.status }}
                                        </span>
                                    </div>
                                    <p class="text-sm font-black mt-1 text-gray-900 dark:text-white">₱{{ formatPrice(req.grand_total) }}</p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ req.items_count }} items</p>
                                </div>
                            </TransitionGroup>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quotation / Purchase Request Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showRequestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showRequestModal = false">
                    <div class="bg-white dark:bg-zinc-900 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col animate-pop">
                        <div class="relative overflow-hidden px-6 py-5 bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 text-white flex justify-between items-center">
                            <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/10 blur-2xl animate-float" />
                            <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
                            <h3 class="relative font-black text-lg">Request Quotation / Purchase Order</h3>
                            <button @click="showRequestModal = false" class="relative p-1.5 bg-white/15 hover:bg-white/25 rounded-xl transition"><X class="h-5 w-5" /></button>
                        </div>
                        <div class="p-6 overflow-y-auto">
                            <form @submit.prevent="submitRequest" class="space-y-4">
                                <div v-for="(item, idx) in requestItems" :key="idx" class="border border-gray-100 dark:border-zinc-800 p-4 rounded-2xl space-y-2 bg-gray-50/50 dark:bg-zinc-800/40">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-sm text-gray-900 dark:text-white">Item {{ idx+1 }}</span>
                                        <button type="button" @click="removeItem(idx)" v-if="requestItems.length > 1" class="text-xs font-bold text-rose-600 hover:underline">Remove</button>
                                    </div>
                                    <input v-model="item.material_name" type="text" placeholder="Material/Product name" required class="w-full rounded-xl border-0 bg-white dark:bg-zinc-800 p-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-500 outline-none" />
                                    <input v-model.number="item.quantity" type="number" placeholder="Quantity" required class="w-full rounded-xl border-0 bg-white dark:bg-zinc-800 p-2.5 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none" />
                                    <input v-model="item.unit" type="text" placeholder="Unit (e.g., kg, rolls)" required class="w-full rounded-xl border-0 bg-white dark:bg-zinc-800 p-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-500 outline-none" />
                                    <input v-model.number="item.unit_price" type="number" step="0.01" placeholder="Target unit price (optional)" class="w-full rounded-xl border-0 bg-white dark:bg-zinc-800 p-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-500 outline-none" />
                                    <textarea v-model="item.specs" placeholder="Specifications / requirements" rows="2" class="w-full rounded-xl border-0 bg-white dark:bg-zinc-800 p-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                                </div>
                                <button type="button" @click="addItem" class="text-sm text-indigo-600 dark:text-indigo-400 font-bold hover:underline">+ Add another item</button>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                    <div><label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Required Delivery Date</label><input v-model="requestForm.delivery_date" type="date" required class="w-full rounded-xl border-0 bg-gray-100 dark:bg-zinc-800 p-2.5 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none" /></div>
                                    <div><label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Payment Terms</label><input v-model="requestForm.payment_terms" required class="w-full rounded-xl border-0 bg-gray-100 dark:bg-zinc-800 p-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="e.g., Net 30" /></div>
                                    <div class="col-span-1 sm:col-span-2"><label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Notes / Remarks</label><textarea v-model="requestForm.notes" rows="2" class="w-full rounded-xl border-0 bg-gray-100 dark:bg-zinc-800 p-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-500 outline-none"></textarea></div>
                                </div>

                                <button type="submit" :disabled="submitting" class="w-full py-3 bg-gradient-to-br from-indigo-600 to-violet-700 text-white rounded-2xl font-bold shadow-lg shadow-indigo-500/25 hover:scale-[1.01] active:scale-95 transition disabled:opacity-50">
                                    {{ submitting ? 'Sending...' : 'Send Request to Supplier' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, nextTick, onMounted, watch } from 'vue';
import { ArrowLeft, Shield, Calendar, Paperclip, Send, Loader2, FileText, X, Sparkles, MessagesSquare } from 'lucide-vue-next';

const props = defineProps({
    supplier: {
        type: Object,
        required: true
    },
    messages: {
        type: Array,
        default: () => []
    },
    requests: {
        type: Array,
        default: () => []
    }
});

const messagesContainer = ref(null);
const newMessage = ref('');
const sending = ref(false);
const scheduling = ref(false);
const isTyping = ref(false);
const showRequestModal = ref(false);
const submitting = ref(false);
const fileInput = ref(null);

// Meeting form
const meetingData = ref({
    scheduled_at: '',
    location: '',
    type: 'video'
});

// Request form (quotation/purchase order)
const requestItems = ref([{ material_name: '', quantity: 1, unit: '', unit_price: 0, specs: '' }]);
const requestForm = ref({
    delivery_date: '',
    payment_terms: '',
    notes: ''
});

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;
    sending.value = true;
    try {
        await router.post(route('eco.supplier.message', props.supplier.id), { message: newMessage.value });
        newMessage.value = '';
        scrollToBottom();
    } finally {
        sending.value = false;
    }
};

const triggerFileUpload = () => {
    fileInput.value.click();
};

const uploadAttachment = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('attachment', file);
    formData.append('message', 'Sent an attachment');
    await router.post(route('eco.supplier.message', props.supplier.id), formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    });
    fileInput.value.value = '';
    scrollToBottom();
};

const scheduleMeeting = async () => {
    scheduling.value = true;
    await router.post(route('eco.supplier.meeting', props.supplier.id), meetingData.value);
    meetingData.value = { scheduled_at: '', location: '', type: 'video' };
    scheduling.value = false;
    scrollToBottom();
};

const checkSupplierCredit = async () => {
    const res = await axios.get(route('eco.supplier.credit-check', props.supplier.id));
    alert(`Credit Status: ${res.data.is_good_payer ? 'Good Payer' : 'High Risk'}\nOutstanding Balance: ₱${res.data.outstanding}`);
};

const openRequestModal = () => {
    showRequestModal.value = true;
};

const addItem = () => {
    requestItems.value.push({ material_name: '', quantity: 1, unit: '', unit_price: 0, specs: '' });
};

const removeItem = (idx) => {
    requestItems.value.splice(idx, 1);
};

const submitRequest = async () => {
    // Validate each item
    for (let item of requestItems.value) {
        if (!item.material_name || !item.quantity || !item.unit) {
            alert('Please fill all required fields for each item.');
            return;
        }
    }
    if (!requestForm.value.delivery_date || !requestForm.value.payment_terms) {
        alert('Please fill delivery date and payment terms.');
        return;
    }
    submitting.value = true;
    await router.post(route('eco.supplier.request', props.supplier.id), {
        items: requestItems.value,
        delivery_date: requestForm.value.delivery_date,
        payment_terms: requestForm.value.payment_terms,
        notes: requestForm.value.notes
    });
    submitting.value = false;
    showRequestModal.value = false;
    // reset form
    requestItems.value = [{ material_name: '', quantity: 1, unit: '', unit_price: 0, specs: '' }];
    requestForm.value = { delivery_date: '', payment_terms: '', notes: '' };
    scrollToBottom();
};

const formatPrice = (val) => Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const formatDateTime = (date) => new Date(date).toLocaleString('en-PH');
const formatTime = (date) => new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

watch(() => props.messages, () => {
    scrollToBottom();
}, { deep: true });

onMounted(() => {
    scrollToBottom();
});
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
.card-enter-active { transition: opacity 0.45s ease, transform 0.45s cubic-bezier(0.22,1,0.36,1); }
.card-enter-from { opacity: 0; transform: translateY(18px) scale(0.98); }
.card-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; position: absolute; }
.card-leave-to { opacity: 0; transform: scale(0.96); }
.card-move { transition: transform 0.4s ease; }
.modal-enter-active, .modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-active > div, .modal-leave-active > div { transition: transform 0.3s cubic-bezier(0.22,1,0.36,1), opacity 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from > div, .modal-leave-to > div { opacity: 0; transform: scale(0.95) translateY(10px); }
</style>
