<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Camera, X } from 'lucide-vue-next';

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
            <h2 class="text-lg font-medium text-gray-900 dark:text-zinc-100">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">
                Update your photo, name and email address.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div class="flex items-center gap-4">
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
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
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

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600 dark:text-zinc-400"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
