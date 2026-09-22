<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
  Facebook, Instagram, Heart, ThumbsUp, Smile, Globe,
  MessageCircle, Share2, Eye, TrendingUp, Users,
  RefreshCw, Calendar, Filter, Plus, UserPlus,
  Search, ExternalLink, MoreVertical, Clock,
  ArrowUpRight, ArrowDownRight, Loader2, Sparkles,
  Mail, CheckCircle, XCircle // added Mail icon
} from 'lucide-vue-next';

// ─── Props ──────────────────────────────────────────────────
const props = defineProps({
  data: {
    type: Object,
    default: () => ({
      facebook: { metrics: {}, posts: [], interactions: [] },
      instagram: { metrics: {}, posts: [], interactions: [] },
      emails: { metrics: {}, emails: [], interactions: [] } // added emails
    })
  },
  permissions: { type: Object, default: () => ({}) },
  fbAccount: { type: Object, default: () => ({ connected: false }) },
  fbError: { type: [String, null], default: null },
  convertedExternalIds: { type: Array, default: () => [] },
});

// ─── Live Facebook Page connection ──────────────────────────────────
import { useForm } from '@inertiajs/vue3';
const fbForm = useForm({ page_input: '', access_token: '' });
const connecting = ref(false);
const connectFb = () => {
  connecting.value = true;
  fbForm.post(route('crm.socials.facebook.connect'), {
    preserveScroll: true,
    onFinish: () => { connecting.value = false; },
  });
};
const disconnectFb = () => {
  if (!confirm('Disconnect the Facebook page? The demo feed returns until you reconnect.')) return;
  router.delete(route('crm.socials.facebook.disconnect'), { preserveScroll: true });
};

// ─── Live comments per post (best-effort; needs token permission) ───
import axios from 'axios';
const commentsByPost = ref({});
const commentsLoading = ref({});
const commentsError = ref({});
const viewComments = async (post) => {
  if (!post.live) return;
  const id = post.id;
  if (commentsByPost.value[id]) { delete commentsByPost.value[id]; return; }
  commentsLoading.value[id] = true;
  commentsError.value[id] = null;
  try {
    const { data } = await axios.get(route('crm.socials.facebook.comments'), { params: { post_id: id } });
    commentsByPost.value[id] = data.comments || [];
    if (data.error) commentsError.value[id] = data.error;
  } catch {
    commentsError.value[id] = 'Could not load comments.';
  }
  commentsLoading.value[id] = false;
};

// ─── Convert social items into REAL CRM leads ───────────────────────
const converting = ref(null);
const convertPost = (post) => {
  converting.value = `post-${post.id}`;
  router.post(route('crm.socials.facebook.convert-post'), { post_id: post.id }, {
    preserveScroll: true,
    onFinish: () => { converting.value = null; },
  });
};
const convertComment = (comment, postId) => {
  converting.value = `comment-${comment.id}`;
  router.post(route('crm.socials.facebook.convert-comment'), {
    comment_id: comment.id, name: comment.user, message: comment.comment, post_id: postId,
  }, {
    preserveScroll: true,
    onFinish: () => { converting.value = null; },
  });
};
const isConverted = (id) => (props.convertedExternalIds || []).includes(id);

// ─── State ──────────────────────────────────────────────────
const activePlatform = ref('facebook');
const isLoading = ref(false);
const dateFilter = ref('last30');
const searchQuery = ref('');
const filterTag = ref('all');

// ─── Computed ──────────────────────────────────────────────
const currentData = computed(() => {
  // For emails, we use the 'emails' key instead of 'posts'
  if (activePlatform.value === 'emails') {
    return props.data.emails || { metrics: {}, emails: [], interactions: [] };
  }
  return props.data[activePlatform.value] || { metrics: {}, posts: [], interactions: [] };
});

const filteredItems = computed(() => {
  let items = [];
  if (activePlatform.value === 'emails') {
    items = currentData.value.emails || [];
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase();
      items = items.filter(e =>
        e.subject?.toLowerCase().includes(q) ||
        e.from?.toLowerCase().includes(q) ||
        e.body?.toLowerCase().includes(q)
      );
    }
    if (filterTag.value === 'unread') {
      items = items.filter(e => !e.is_read);
    } else if (filterTag.value === 'read') {
      items = items.filter(e => e.is_read);
    }
  } else {
    items = currentData.value.posts || [];
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase();
      items = items.filter(p => p.caption?.toLowerCase().includes(q) || p.message?.toLowerCase().includes(q));
    }
    // filterTag can be extended for posts (e.g., promo, inquiry) – we keep existing logic
    if (filterTag.value === 'promo') {
      // mock filter – we could check for keywords
    } else if (filterTag.value === 'inquiry') {
      // mock filter
    }
  }
  return items;
});

const formatDate = (date) => {
  return new Date(date).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const totalReactions = (item) => {
  if (item.reactions) {
    return Object.values(item.reactions).reduce((a, b) => a + b, 0);
  }
  return item.likes || 0;
};

const getSentimentBadge = (sentiment) => {
  const map = {
    'Interested': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    'Inquiry': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'Positive': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'Feedback': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  };
  return map[sentiment] || 'bg-slate-100 text-slate-700';
};

// Platform icons and colors
const platformInfo = {
  facebook: { icon: Facebook, color: '#1877F2', label: 'Facebook' },
  instagram: { icon: Instagram, color: '#E4405F', label: 'Instagram' },
  emails: { icon: Mail, color: '#EA4335', label: 'Emails' } // using Gmail red
};
const platformIcon = computed(() => platformInfo[activePlatform.value]?.icon || Facebook);
const platformColor = computed(() => platformInfo[activePlatform.value]?.color || '#1877F2');

const canEdit = computed(() => props.permissions?.socials === 'edit');
const emailFilterTags = ['all', 'unread', 'read'];
const postFilterTags = ['all', 'promo', 'inquiry'];

// ─── Methods ──────────────────────────────────────────────
const syncData = () => {
  isLoading.value = true;
  setTimeout(() => {
    isLoading.value = false;
  }, 1500);
};

const createLead = (item) => {
  // Emails tab (demo): unchanged placeholder.
  const name = item.from || item.user || 'Contact';
  alert(`Lead created from ${activePlatform.value} by ${name}`);
};

const switchPlatform = (platform) => {
  activePlatform.value = platform;
  searchQuery.value = '';
  filterTag.value = 'all';
};
</script>

<template>
  <Head title="Socials Lead Hub" />
  <AuthenticatedLayout>
    <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 dark:from-zinc-950 dark:via-zinc-950 dark:to-indigo-950/30">
      <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6 pb-16">

        <!-- ─── Hero header ──────────────────────────────────── -->
        <div class="animate-fade-up relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
          <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl animate-float" />
          <div class="absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-fuchsia-400/20 blur-3xl animate-float-delayed" />
          <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;" />
          <div class="relative flex flex-wrap items-center gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/30 shadow-lg animate-pop">
              <component :is="platformIcon" class="h-7 w-7" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-100">
                <Sparkles class="h-3.5 w-3.5" /> CRM · Social listening
              </p>
              <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                {{ platformInfo[activePlatform]?.label || 'Social' }} Lead Hub
              </h1>
              <p class="text-sm text-blue-100/90">
                Real‑time engagement metrics and feeds for Monti Textile on {{ platformInfo[activePlatform]?.label || 'social' }}.
              </p>
            </div>
            <div class="flex items-center gap-2">
              <span v-if="canEdit" class="flex items-center gap-1.5 rounded-full bg-emerald-400/90 px-3 py-1.5 text-xs font-black text-emerald-950">
                <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" /> Full access
              </span>
              <span v-else class="flex items-center gap-1.5 rounded-full bg-amber-300/90 px-3 py-1.5 text-xs font-black text-amber-950">
                <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" /> View only
              </span>
              <button
                @click="syncData"
                :disabled="isLoading"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-indigo-700 rounded-2xl text-xs font-black uppercase tracking-wide shadow-lg hover:scale-105 active:scale-95 transition-all disabled:opacity-60"
              >
                <RefreshCw :class="['w-4 h-4', isLoading && 'animate-spin']" />
                {{ isLoading ? 'Syncing...' : 'Sync Data' }}
              </button>
            </div>
          </div>

          <!-- Search + platform tabs + date filter -->
          <div class="relative mt-6 flex flex-col xl:flex-row gap-3">
            <div class="relative flex-1">
              <Search class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search posts, emails, senders..."
                class="w-full rounded-2xl border-0 bg-white/95 py-3 pl-11 pr-4 text-sm font-medium text-gray-900 shadow-lg placeholder:text-gray-400 focus:ring-2 focus:ring-white/70 outline-none transition" />
            </div>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="(info, key) in platformInfo"
                :key="key"
                @click="switchPlatform(key)"
                :class="activePlatform === key ? 'bg-white text-indigo-700 shadow-lg scale-105' : 'bg-white/15 text-white hover:bg-white/25'"
                class="flex items-center gap-1.5 rounded-2xl px-4 py-2.5 text-xs font-black uppercase tracking-wide backdrop-blur transition-all duration-200 active:scale-95">
                <component :is="info.icon" class="w-4 h-4" /> {{ info.label }}
              </button>
              <select v-model="dateFilter" class="rounded-2xl bg-white/15 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white backdrop-blur outline-none hover:bg-white/25 transition cursor-pointer [&>option]:text-gray-900">
                <option value="last7">Last 7 days</option>
                <option value="last30">Last 30 days</option>
                <option value="custom">Custom</option>
              </select>
            </div>
          </div>
        </div>

        <!-- ─── Metrics Cards ──────────────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <!-- For Emails: total, unread, leads generated -->
          <template v-if="activePlatform === 'emails'">
            <div v-for="(m, i) in [
              { label: 'Total Emails', value: currentData.metrics.total || 0, icon: Mail, tint: 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400', bar: 'from-blue-500 to-indigo-500' },
              { label: 'Unread', value: currentData.metrics.unread || 0, icon: Eye, tint: 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400', bar: 'from-amber-500 to-orange-500' },
              { label: 'Leads Generated', value: currentData.metrics.leads_generated || 0, icon: UserPlus, tint: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400', bar: 'from-emerald-500 to-teal-500' },
            ]" :key="m.label" :style="{ animationDelay: `${i * 80}ms` }"
              class="animate-fade-up group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1.5 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300 overflow-hidden">
              <div class="pointer-events-none absolute -top-12 -right-12 h-32 w-32 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
              <div :class="['absolute left-0 top-5 bottom-5 w-1 rounded-r-full bg-gradient-to-b', m.bar]" />
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ m.label }}</p>
                  <p class="text-2xl font-black text-slate-900 dark:text-white mt-1 tracking-tight">{{ m.value }}</p>
                </div>
                <div :class="['p-3 rounded-2xl group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300', m.tint]">
                  <component :is="m.icon" class="w-5 h-5" />
                </div>
              </div>
            </div>
          </template>
          <!-- For FB/IG: visitors, reactions, followers -->
          <template v-else>
            <div class="animate-fade-up group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1.5 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300 overflow-hidden">
              <div class="pointer-events-none absolute -top-12 -right-12 h-32 w-32 rounded-full bg-gradient-to-br from-indigo-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
              <div class="absolute left-0 top-5 bottom-5 w-1 rounded-r-full bg-gradient-to-b from-blue-500 to-indigo-500" />
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Page Visitors / Impressions</p>
                  <p class="text-2xl font-black text-slate-900 dark:text-white mt-1 tracking-tight">{{ currentData.metrics.visitors?.toLocaleString() || 0 }}</p>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-2xl text-[#1877F2] group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                  <Eye class="w-5 h-5" />
                </div>
              </div>
              <div class="flex items-center gap-1 mt-2 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" />
                <ArrowUpRight class="w-3 h-3" />
                {{ currentData.metrics.visitors_trend || '+0%' }} vs last month
              </div>
            </div>
            <div class="animate-fade-up group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1.5 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300 overflow-hidden" style="animation-delay:80ms">
              <div class="pointer-events-none absolute -top-12 -right-12 h-32 w-32 rounded-full bg-gradient-to-br from-amber-400/20 to-rose-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
              <div class="absolute left-0 top-5 bottom-5 w-1 rounded-r-full bg-gradient-to-b from-amber-500 to-rose-500" />
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total Reactions / Engagements</p>
                  <p class="text-2xl font-black text-slate-900 dark:text-white mt-1 tracking-tight">{{ currentData.metrics.reactions?.toLocaleString() || 0 }}</p>
                </div>
                <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-2xl group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                  <ThumbsUp class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                </div>
              </div>
              <div class="flex items-center gap-1 mt-2 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" />
                <ArrowUpRight class="w-3 h-3" />
                {{ currentData.metrics.reactions_trend || '+0%' }} vs last month
              </div>
            </div>
            <div class="animate-fade-up group relative bg-white/80 dark:bg-zinc-900/80 backdrop-blur rounded-3xl p-5 shadow-sm border border-gray-100 dark:border-zinc-800 hover:shadow-2xl hover:shadow-indigo-500/15 hover:-translate-y-1.5 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-300 overflow-hidden" style="animation-delay:160ms">
              <div class="pointer-events-none absolute -top-12 -right-12 h-32 w-32 rounded-full bg-gradient-to-br from-rose-400/20 to-fuchsia-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
              <div class="absolute left-0 top-5 bottom-5 w-1 rounded-r-full bg-gradient-to-b from-rose-500 to-fuchsia-500" />
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Page Followers / Likes</p>
                  <p class="text-2xl font-black text-slate-900 dark:text-white mt-1 tracking-tight">{{ currentData.metrics.followers?.toLocaleString() || 0 }}</p>
                  <p class="text-xs text-slate-400 mt-1 font-medium">+{{ currentData.metrics.new_followers || 0 }} new this week</p>
                </div>
                <div class="p-3 bg-rose-50 dark:bg-rose-900/20 rounded-2xl group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                  <Users class="w-5 h-5 text-rose-500 dark:text-rose-400" />
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- ─── Main Content: Feed + Side Panel ────────── -->
        <div class="flex flex-col lg:flex-row gap-4">

          <!-- Left: Feed Column -->
          <div class="flex-1 min-w-0">
            <div class="animate-fade-up bg-white dark:bg-zinc-900 rounded-3xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden hover:shadow-xl transition-shadow duration-300" style="animation-delay:120ms">
              <!-- Feed header -->
              <div class="p-6 border-b border-gray-100 dark:border-zinc-800 flex flex-wrap items-center justify-between gap-3 bg-gradient-to-r from-indigo-50 to-transparent dark:from-indigo-900/20">
                <div class="flex items-center gap-2">
                  <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white dark:bg-zinc-800 shadow-sm"><component :is="platformIcon" class="w-5 h-5" :style="{ color: platformColor }" /></span>
                  <h2 class="text-[11px] font-black uppercase tracking-widest text-gray-700 dark:text-gray-200">
                    {{ activePlatform === 'emails' ? 'Inbox' : `Monti Textile ${platformInfo[activePlatform]?.label || ''} Feed` }}
                  </h2>
                  <span class="rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-[11px] font-black px-2.5 py-1">{{ filteredItems.length }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <!-- Filter tags (specific to each platform) -->
                  <template v-if="activePlatform === 'emails'">
                    <button
                      v-for="tag in emailFilterTags" :key="tag"
                      @click="filterTag = tag"
                      :class="filterTag === tag ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30 scale-105' : 'bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-zinc-700'"
                      class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wide transition-all active:scale-95">
                      {{ tag }}
                    </button>
                  </template>
                  <!-- FB/IG filter tags -->
                  <template v-else>
                    <button
                      v-for="tag in postFilterTags" :key="tag"
                      @click="filterTag = tag"
                      :class="filterTag === tag ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30 scale-105' : 'bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-zinc-700'"
                      class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wide transition-all active:scale-95">
                      {{ tag === 'promo' ? 'Product' : tag === 'inquiry' ? 'Inquiries' : tag }}
                    </button>
                  </template>
                </div>
              </div>

              <!-- Facebook connection panel -->
              <div v-if="activePlatform === 'facebook'" class="border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-blue-50/70 to-transparent dark:from-blue-900/10 px-5 py-4">
                <div v-if="!fbAccount.connected">
                  <p class="text-xs font-black uppercase tracking-widest text-gray-500 mb-1">Connect a real Facebook page</p>
                  <p class="text-[11px] text-gray-400 mb-3">Paste your page link + a Page access token. Posts below are demo data until you connect — Meta does not allow token-free page feeds.</p>
                  <div class="grid sm:grid-cols-2 gap-2.5">
                    <input v-model="fbForm.page_input" placeholder="https://facebook.com/your-page or Page ID"
                      class="px-4 py-2.5 rounded-xl bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-sm outline-none focus:ring-2 focus:ring-indigo-500" />
                    <input v-model="fbForm.access_token" type="password" placeholder="Page access token (EAAG…)"
                      class="px-4 py-2.5 rounded-xl bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-sm outline-none focus:ring-2 focus:ring-indigo-500" />
                  </div>
                  <p v-if="fbForm.errors.page_input || fbForm.errors.access_token || fbForm.errors.error" class="mt-1.5 text-[11px] font-bold text-rose-500">
                    {{ fbForm.errors.page_input || fbForm.errors.access_token || fbForm.errors.error }}
                  </p>
                  <div class="mt-2.5 flex flex-wrap items-center gap-2">
                    <button @click="connectFb" :disabled="connecting || !fbForm.page_input || !fbForm.access_token"
                      class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-[#1877F2] text-white rounded-xl text-[11px] font-black uppercase tracking-wide hover:brightness-110 active:scale-95 transition disabled:opacity-50">
                      <Facebook class="w-3.5 h-3.5" /> {{ connecting ? 'Connecting…' : 'Connect page' }}
                    </button>
                    <span class="text-[10px] font-bold text-gray-400">Token: Meta Developers → your App → Page access token (pages_read_engagement). Stored encrypted, never shown again.</span>
                  </div>
                </div>
                <div v-else class="flex flex-wrap items-center gap-2.5">
                  <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1877F2] text-white"><Facebook class="h-4 w-4" /></span>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-black truncate">{{ fbAccount.page_name || 'Facebook page' }} <span class="ml-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 px-2 py-0.5 text-[10px] uppercase">Live</span></p>
                    <p class="text-[11px] text-gray-400 truncate">{{ fbAccount.page_url }} · synced {{ fbAccount.last_synced_at ? formatDate(fbAccount.last_synced_at) : 'just now' }}</p>
                  </div>
                  <button @click="disconnectFb" class="px-3 py-2 rounded-xl bg-gray-100 dark:bg-zinc-800 text-[11px] font-black uppercase text-gray-500 hover:text-rose-500 transition">Disconnect</button>
                </div>
                <p v-if="fbError" class="mt-2 text-[11px] font-bold text-rose-500">Facebook error: {{ fbError }} — showing demo posts meanwhile.</p>
              </div>

              <!-- Items (posts or emails) -->
              <div class="divide-y divide-gray-100 dark:divide-zinc-800">
                <!-- Skeleton loading -->
                <div v-if="isLoading" v-for="i in 3" :key="i" class="p-5 animate-pulse">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-slate-200 dark:bg-zinc-700 rounded-2xl"></div>
                    <div class="flex-1">
                      <div class="h-4 bg-slate-200 dark:bg-zinc-700 rounded w-1/3 mb-1"></div>
                      <div class="h-3 bg-slate-200 dark:bg-zinc-700 rounded w-1/4"></div>
                    </div>
                  </div>
                  <div class="h-20 bg-slate-200 dark:bg-zinc-700 rounded-2xl w-full mb-3"></div>
                  <div class="flex gap-2">
                    <div class="h-4 bg-slate-200 dark:bg-zinc-700 rounded w-10"></div>
                    <div class="h-4 bg-slate-200 dark:bg-zinc-700 rounded w-10"></div>
                  </div>
                </div>

                <!-- Emails Feed -->
                <TransitionGroup v-else-if="activePlatform === 'emails' && filteredItems.length > 0" name="card" tag="div" class="divide-y divide-gray-100 dark:divide-zinc-800">
                  <div v-for="(email, i) in filteredItems" :key="email.id" :style="{ transitionDelay: `${Math.min(i * 40, 400)}ms` }" class="group p-5 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors">
                    <div class="flex items-start gap-3">
                      <!-- Unread indicator -->
                      <div class="w-2 h-2 rounded-full mt-2 shrink-0 shadow-lg" :class="email.is_read ? 'bg-slate-300 dark:bg-zinc-600' : 'bg-blue-500 shadow-blue-500/40 animate-pulse'"></div>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                          <div class="min-w-0">
                            <p class="text-sm font-black text-slate-900 dark:text-white truncate tracking-tight group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition-colors">
                              {{ email.subject }}
                            </p>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                              <span class="font-medium">From: {{ email.from }}</span>
                              <span>·</span>
                              <span>{{ formatDate(email.date) }}</span>
                            </div>
                          </div>
                          <span :class="['px-2.5 py-1 rounded-full text-[10px] font-black uppercase ring-1 ring-black/5 shrink-0', getSentimentBadge(email.sentiment)]">
                            {{ email.sentiment }}
                          </span>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300 mt-1 line-clamp-2 leading-relaxed">{{ email.body }}</p>
                      </div>
                    </div>
                    <!-- Action bar -->
                    <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-gray-100 dark:border-zinc-800 mt-3">
                      <button
                        @click="createLead(email)"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:shadow-lg hover:shadow-indigo-500/30 hover:scale-105 active:scale-95 text-white rounded-xl text-[11px] font-black uppercase tracking-wide transition-all"
                      >
                        <UserPlus class="w-3.5 h-3.5" /> Create Lead
                      </button>
                      <span v-if="!email.is_read" class="flex items-center gap-1 text-[10px] font-black uppercase text-amber-700 bg-amber-100 dark:bg-amber-500/15 dark:text-amber-300 px-2.5 py-1 rounded-full"><span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" />Unread</span>
                    </div>
                  </div>
                </TransitionGroup>

                <!-- FB/IG Posts Feed -->
                <TransitionGroup v-else-if="activePlatform !== 'emails' && filteredItems.length > 0" name="card" tag="div" class="divide-y divide-gray-100 dark:divide-zinc-800">
                  <div v-for="(post, i) in filteredItems" :key="post.id" :style="{ transitionDelay: `${Math.min(i * 40, 400)}ms` }" class="group p-5 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors">
                    <!-- Post header -->
                    <div class="flex items-center gap-3 mb-3">
                      <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 flex items-center justify-center text-white font-black text-sm shadow-lg group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                        MT
                      </div>
                      <div class="min-w-0">
                        <p class="text-sm font-black text-slate-900 dark:text-white tracking-tight">Monti Textile</p>
                        <p class="text-xs text-slate-400">{{ formatDate(post.created_time) }}</p>
                      </div>
                      <a :href="post.permalink" target="_blank" class="ml-auto flex h-8 w-8 items-center justify-center rounded-xl bg-gray-100 dark:bg-zinc-800 text-gray-400 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                        <ExternalLink class="w-4 h-4" />
                      </a>
                    </div>

                    <!-- Caption -->
                    <p class="text-sm text-slate-700 dark:text-slate-200 whitespace-pre-line mb-3 leading-relaxed">{{ post.caption || post.message }}</p>

                    <!-- Media preview -->
                    <div v-if="post.media_url" class="rounded-2xl overflow-hidden mb-3 border border-gray-100 dark:border-zinc-800">
                      <img :src="post.media_url" alt="Post media" class="w-full h-auto max-h-80 object-cover group-hover:scale-[1.01] transition-transform duration-500" />
                    </div>

                    <!-- Analytics strip -->
                    <div class="flex items-center gap-4 text-xs font-bold text-slate-500 mb-3">
                      <span class="flex items-center gap-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/20 px-2 py-1 text-blue-600 dark:text-blue-400">
                        <ThumbsUp class="w-3.5 h-3.5" /> {{ totalReactions(post) }}
                      </span>
                      <span class="flex items-center gap-1.5 rounded-lg bg-violet-50 dark:bg-violet-900/20 px-2 py-1 text-violet-600 dark:text-violet-400">
                        <MessageCircle class="w-3.5 h-3.5" /> {{ post.comments_count }}
                      </span>
                      <span class="flex items-center gap-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 px-2 py-1 text-emerald-600 dark:text-emerald-400">
                        <Share2 class="w-3.5 h-3.5" /> {{ post.shares_count }}
                      </span>
                    </div>

                    <!-- Action bar -->
                      <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-gray-100 dark:border-zinc-800">
                        <button
                          v-if="post.live"
                          @click="viewComments(post)"
                          :disabled="commentsLoading[post.id]"
                          class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all active:scale-95 disabled:opacity-60"
                        >
                          <MessageCircle class="w-3.5 h-3.5" /> {{ commentsByPost[post.id] ? 'Hide Comments' : commentsLoading[post.id] ? 'Loading…' : 'View Comments' }}
                        </button>
                        <button
                          v-else
                          @click="viewComments(post)"
                          class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all active:scale-95"
                        >
                          <MessageCircle class="w-3.5 h-3.5" /> View Comments
                        </button>
                        <button
                          v-if="post.live"
                          :disabled="converting === `post-${post.id}` || isConverted(post.id)"
                          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-[11px] font-black uppercase tracking-wide transition-all ml-auto active:scale-95 disabled:opacity-60"
                          :class="isConverted(post.id) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-gradient-to-r from-indigo-600 to-violet-600 hover:shadow-lg hover:shadow-indigo-500/30 hover:scale-105 text-white'"
                          @click="convertPost(post)"
                        >
                          <UserPlus class="w-3.5 h-3.5" /> {{ isConverted(post.id) ? 'Converted ✓' : converting === `post-${post.id}` ? 'Converting…' : 'Create Lead' }}
                        </button>
                        <button
                          v-else
                          class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:shadow-lg hover:shadow-indigo-500/30 hover:scale-105 active:scale-95 text-white rounded-xl text-[11px] font-black uppercase tracking-wide transition-all ml-auto"
                          @click="createLead({ user: 'Commenter', comment: post.caption || post.message })"
                        >
                          <UserPlus class="w-3.5 h-3.5" /> Create Lead
                        </button>
                      </div>
                      <!-- Live comments (expandable, convertible) -->
                      <div v-if="post.live && commentsByPost[post.id]?.length" class="mt-3 space-y-2 border-t border-gray-100 dark:border-zinc-800 pt-3">
                        <div v-for="c in commentsByPost[post.id]" :key="c.id" class="flex items-start gap-2.5 rounded-2xl bg-gray-50 dark:bg-zinc-800/60 p-3">
                          <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-blue-600 to-violet-700 flex items-center justify-center text-[11px] font-black text-white shrink-0">
                            {{ (c.user || '?').charAt(0).toUpperCase() }}
                          </div>
                          <div class="flex-1 min-w-0">
                            <p class="text-xs font-black text-slate-900 dark:text-white">{{ c.user }}</p>
                            <p class="text-xs text-slate-600 dark:text-slate-300 line-clamp-3">{{ c.comment }}</p>
                          </div>
                          <button
                            @click="convertComment(c, post.id)"
                            :disabled="converting === `comment-${c.id}` || isConverted(c.id)"
                            class="shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase transition-all active:scale-95 disabled:opacity-60"
                            :class="isConverted(c.id) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-indigo-600 text-white hover:bg-indigo-700'">
                            {{ isConverted(c.id) ? 'Lead ✓' : converting === `comment-${c.id}` ? '…' : '+ Lead' }}
                          </button>
                        </div>
                      </div>
                      <p v-if="post.live && commentsError[post.id]" class="mt-2 text-[11px] font-bold text-amber-600 dark:text-amber-400">
                        Comments unavailable: {{ commentsError[post.id] }} — the post itself can still convert.
                      </p>
                  </div>
                </TransitionGroup>

                <div v-if="filteredItems.length === 0 && !isLoading" class="flex flex-col items-center justify-center py-16 text-center">
                  <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-indigo-900/30 rounded-full mb-4 animate-bounce-soft">
                    <Search class="h-9 w-9 text-indigo-400" />
                  </div>
                  <p class="text-sm font-black text-gray-700 dark:text-gray-200">No items match your filters</p>
                  <p class="text-xs text-gray-400 mt-1">Try a different search or filter.</p>
                  <button @click="searchQuery=''; filterTag='all'"
                    class="mt-4 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700 transition active:scale-95">Clear filters</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Side Panel -->
          <div class="lg:w-80 xl:w-96 shrink-0">
            <div class="animate-fade-up bg-white dark:bg-zinc-900 rounded-3xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden sticky top-6 hover:shadow-xl transition-shadow duration-300" style="animation-delay:200ms">
              <div class="p-6 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-indigo-50 to-transparent dark:from-indigo-900/20">
                <h3 class="text-[11px] font-black uppercase tracking-widest text-gray-700 dark:text-gray-200 flex items-center gap-2">
                  <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white dark:bg-zinc-800 shadow-sm"><Users class="w-4 h-4" :style="{ color: platformColor }" /></span>
                  {{ activePlatform === 'emails' ? 'Recent Email Activity' : 'Recent Interactions & Leads' }}
                </h3>
              </div>
              <div class="divide-y divide-gray-100 dark:divide-zinc-800 max-h-[600px] overflow-y-auto">
                <!-- For emails, show interactions (conversion history) or fallback -->
                <div v-if="activePlatform === 'emails'">
                  <TransitionGroup v-if="currentData.interactions && currentData.interactions.length > 0" name="card" tag="div" class="divide-y divide-gray-100 dark:divide-zinc-800">
                    <div v-for="(interaction, i) in currentData.interactions" :key="interaction.id" :style="{ transitionDelay: `${Math.min(i * 40, 300)}ms` }" class="group p-4 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors">
                      <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 flex items-center justify-center text-xs font-black text-white shrink-0 shadow-lg group-hover:scale-110 transition-transform">
                          {{ interaction.user.charAt(0) }}
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-black text-slate-900 dark:text-white truncate">{{ interaction.user }}</p>
                          <p class="text-xs text-slate-500 truncate">{{ interaction.comment }}</p>
                          <div class="flex items-center gap-2 mt-1.5">
                            <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black uppercase', getSentimentBadge(interaction.sentiment)]">
                              {{ interaction.sentiment }}
                            </span>
                            <span class="text-[11px] text-slate-400">{{ formatDate(interaction.created_at) }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </TransitionGroup>
                  <div v-else class="flex flex-col items-center py-12 text-center px-6">
                    <div class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-indigo-900/30 rounded-full mb-3 animate-bounce-soft"><Mail class="h-7 w-7 text-indigo-400" /></div>
                    <p class="text-sm font-bold text-slate-400">No recent email conversions.</p>
                  </div>
                </div>
                <!-- For FB/IG, show interactions -->
                <div v-else-if="!(activePlatform === 'facebook' && fbAccount.connected)">
                  <TransitionGroup v-if="currentData.interactions && currentData.interactions.length > 0" name="card" tag="div" class="divide-y divide-gray-100 dark:divide-zinc-800">
                    <div v-for="(interaction, i) in currentData.interactions" :key="interaction.id" :style="{ transitionDelay: `${Math.min(i * 40, 300)}ms` }" class="group p-4 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors">
                      <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 flex items-center justify-center text-xs font-black text-white shrink-0 shadow-lg group-hover:scale-110 transition-transform">
                          {{ interaction.user.charAt(0) }}
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-black text-slate-900 dark:text-white truncate">{{ interaction.user }}</p>
                          <p class="text-xs text-slate-500 truncate">{{ interaction.comment }}</p>
                          <div class="flex items-center gap-2 mt-1.5">
                            <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black uppercase', getSentimentBadge(interaction.sentiment)]">
                              {{ interaction.sentiment }}
                            </span>
                            <span class="text-[11px] text-slate-400">{{ formatDate(interaction.created_at) }}</span>
                          </div>
                          <button
                            @click="createLead(interaction)"
                            class="mt-2 inline-flex items-center gap-1 text-xs font-black uppercase tracking-wide text-indigo-600 dark:text-indigo-400 hover:underline"
                          >
                            <UserPlus class="w-3 h-3" /> Convert to CRM Lead
                          </button>
                        </div>
                      </div>
                    </div>
                  </TransitionGroup>
                  <div v-else class="flex flex-col items-center py-12 text-center px-6">
                    <div class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-indigo-900/30 rounded-full mb-3 animate-bounce-soft"><Users class="h-7 w-7 text-indigo-400" /></div>
                    <p class="text-sm font-bold text-slate-400">No recent interactions.</p>
                  </div>
                </div>
                <div v-else class="flex flex-col items-center py-12 text-center px-6">
                  <div class="p-4 bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/30 rounded-full mb-3"><CheckCircle class="h-7 w-7 text-emerald-500" /></div>
                  <p class="text-sm font-black text-slate-700 dark:text-gray-200">Live page connected</p>
                  <p class="text-xs text-slate-400 mt-1 max-w-[220px]">Convert leads straight from each post's comments — open a post and tap View Comments.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

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
</style>
