<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Camera, X, User as UserIcon } from 'lucide-vue-next';

defineProps<{
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    photo: null as File | null,
});

const photoInput = ref<HTMLInputElement | null>(null);
const photoPreview = ref<string | null>(null);

const currentPhotoUrl = computed(() =>
    user.profile_photo_path ? `/storage/${user.profile_photo_path}` : null
);
const displayPhoto = computed(() => photoPreview.value || currentPhotoUrl.value);

const pickPhoto = () => photoInput.value?.click();
const onPhoto = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] || null;
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) {
        form.setError('photo', 'Photo must not exceed 10MB.');
        return;
    }
    form.clearErrors('photo');
    form.photo = file;
    photoPreview.value = URL.createObjectURL(file);
};
const clearPhoto = () => {
    form.photo = null;
    photoPreview.value = null;
    form.clearErrors('photo');
    if (photoInput.value) photoInput.value.value = '';
};

const submit = () => {
    // PHP can't parse multipart on PATCH — spoof the method like USERS/app.vue.
    form.transform((data) => ({ ...data, _method: 'patch' })).post(route('profile.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearPhoto();
            form.transform((data) => data);
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="flex items-center gap-2 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                <UserIcon class="h-4 w-4 text-indigo-500" /> Profile information
            </h2>

            <p class="mt-1 text-[11px] text-slate-400">
                Update your photo, name and email address.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-4 space-y-4">
            <div class="flex items-center gap-4 rounded-2xl border border-slate-100 bg-slate-50/60 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-slate-100 text-xl font-black text-slate-400 ring-1 ring-slate-200 dark:bg-zinc-800 dark:text-zinc-500 dark:ring-zinc-700">
                    <img v-if="displayPhoto" :src="displayPhoto" alt="Profile photo" class="h-full w-full object-cover" />
                    <span v-else>{{ (form.name || '?').charAt(0).toUpperCase() }}</span>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="pickPhoto"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-[11px] font-black uppercase tracking-wide text-white shadow hover:bg-indigo-700 active:scale-95">
                            <Camera class="h-3.5 w-3.5" /> {{ displayPhoto ? 'Change photo' : 'Upload photo' }}
                        </button>
                        <button v-if="form.photo" type="button" @click="clearPhoto"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-gray-100 px-3.5 py-2 text-[11px] font-black uppercase tracking-wide text-gray-500 hover:bg-gray-200 active:scale-95 dark:bg-zinc-800 dark:text-gray-300">
                            <X class="h-3.5 w-3.5" /> Reset
                        </button>
                    </div>
                    <p class="mt-1.5 text-[11px] text-gray-400">JPG, PNG, GIF or WEBP · max 10MB.</p>
                </div>
                <input ref="photoInput" type="file" accept="image/*" @change="onPhoto" class="hidden" />
            </div>
            <InputError :message="form.errors.photo" />

            <div>
                <label for="name" class="mb-1 block text-[10px] font-black uppercase tracking-widest text-slate-400">Name</label>

                <input
                    id="name"
                    type="text"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold text-slate-800 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <div>
                <label for="email" class="mb-1 block text-[10px] font-black uppercase tracking-widest text-slate-400">Email</label>

                <input
                    id="email"
                    type="email"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold text-slate-800 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800 dark:text-zinc-200">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 dark:text-zinc-400 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600 dark:text-green-400"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-3 border-t border-gray-100 pt-4 dark:border-zinc-800">
                <button type="submit" :disabled="form.processing"
                    class="flex-1 rounded-xl bg-indigo-600 py-2.5 text-sm font-black text-white shadow hover:bg-indigo-700 disabled:opacity-50 active:scale-95">
                    {{ form.processing ? 'Saving…' : 'Save changes' }}
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="shrink-0 text-xs font-black uppercase text-emerald-600 dark:text-emerald-400"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
