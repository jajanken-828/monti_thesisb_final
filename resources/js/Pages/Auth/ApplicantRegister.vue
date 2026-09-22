<script setup lang="ts">
import { onMounted, ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const props = defineProps<{
    jobPostings?: Array<{ id: number; title: string }>;
}>();

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone_number: '',
    password: '',
    password_confirmation: '',
    position_applied: '',
    job_posting_id: '',
    street_address: '',
    city: '',
    state_province: '',
    postal_zip_code: '',
});

const isLoaded = ref(false);
const showPassword = ref(false);

onMounted(() => {
    isLoaded.value = true;
});

const pickPosting = (e: Event) => {
    const id = (e.target as HTMLSelectElement).value;
    form.job_posting_id = id;
    const found = (props.jobPostings || []).find((p) => String(p.id) === String(id));
    if (found) form.position_applied = found.title;
};

const submit = () => {
    form.post(route('applicant.register.store'), {
        onSuccess: () => {
            toast.success('Account created! Opening your application tracker.');
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors)[0] || 'Registration failed. Please review the form.';
            toast.error(errorMsg);
        },
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout
        title="Applicant Registration"
        highlight="Register"
        subtitle="Create your applicant account to file applications and track every hiring stage."
        badge="Applicant Portal"
        accent="sky"
    >
        <Head title="Monti Textile - Applicant Registration" />

        <form @submit.prevent="submit" class="space-y-5">
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <InputLabel for="first_name" value="First Name" class="text-white/90" />
                    <TextInput id="first_name" type="text" class="auth-input mt-1 block w-full"
                        v-model="form.first_name" required autofocus autocomplete="given-name" placeholder="Juan" />
                    <InputError class="mt-1 text-red-300" :message="form.errors.first_name" />
                </div>
                <div>
                    <InputLabel for="last_name" value="Last Name" class="text-white/90" />
                    <TextInput id="last_name" type="text" class="auth-input mt-1 block w-full"
                        v-model="form.last_name" required autocomplete="family-name" placeholder="Dela Cruz" />
                    <InputError class="mt-1 text-red-300" :message="form.errors.last_name" />
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <InputLabel for="email" value="Email Address" class="text-white/90" />
                    <TextInput id="email" type="email" class="auth-input mt-1 block w-full"
                        v-model="form.email" required autocomplete="username" placeholder="you@example.com" />
                    <InputError class="mt-1 text-red-300" :message="form.errors.email" />
                </div>
                <div>
                    <InputLabel for="phone_number" value="Mobile Number" class="text-white/90" />
                    <TextInput id="phone_number" type="text" class="auth-input mt-1 block w-full"
                        v-model="form.phone_number" required autocomplete="tel" placeholder="+63 9XX XXX XXXX" />
                    <InputError class="mt-1 text-red-300" :message="form.errors.phone_number" />
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <InputLabel for="password" value="Password" class="text-white/90" />
                    <TextInput id="password" :type="showPassword ? 'text' : 'password'" class="auth-input mt-1 block w-full"
                        v-model="form.password" required autocomplete="new-password" placeholder="••••••••" />
                    <InputError class="mt-1 text-red-300" :message="form.errors.password" />
                </div>
                <div>
                    <InputLabel for="password_confirmation" value="Confirm Password" class="text-white/90" />
                    <TextInput id="password_confirmation" :type="showPassword ? 'text' : 'password'" class="auth-input mt-1 block w-full"
                        v-model="form.password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
                </div>
            </div>

            <div>
                <InputLabel for="job_posting_id" value="Position (optional)" class="text-white/90" />
                <select id="job_posting_id" @change="pickPosting"
                    class="auth-input mt-1 block w-full bg-white/10 border border-white/20 rounded-lg text-sm text-white">
                    <option value="" class="bg-slate-800">General Application</option>
                    <option v-for="p in (jobPostings || [])" :key="p.id" :value="p.id" class="bg-slate-800">{{ p.title }}</option>
                </select>
                <InputError class="mt-1 text-red-300" :message="form.errors.job_posting_id" />
            </div>

            <div>
                <InputLabel for="street_address" value="Street Address" class="text-white/90" />
                <TextInput id="street_address" type="text" class="auth-input mt-1 block w-full"
                    v-model="form.street_address" required autocomplete="street-address" placeholder="House / Street / Barangay" />
                <InputError class="mt-1 text-red-300" :message="form.errors.street_address" />
            </div>

            <div class="grid sm:grid-cols-3 gap-5">
                <div>
                    <InputLabel for="city" value="City" class="text-white/90" />
                    <TextInput id="city" type="text" class="auth-input mt-1 block w-full"
                        v-model="form.city" required placeholder="Imus" />
                    <InputError class="mt-1 text-red-300" :message="form.errors.city" />
                </div>
                <div>
                    <InputLabel for="state_province" value="Province" class="text-white/90" />
                    <TextInput id="state_province" type="text" class="auth-input mt-1 block w-full"
                        v-model="form.state_province" required placeholder="Cavite" />
                    <InputError class="mt-1 text-red-300" :message="form.errors.state_province" />
                </div>
                <div>
                    <InputLabel for="postal_zip_code" value="ZIP Code" class="text-white/90" />
                    <TextInput id="postal_zip_code" type="text" class="auth-input mt-1 block w-full"
                        v-model="form.postal_zip_code" required placeholder="4103" />
                    <InputError class="mt-1 text-red-300" :message="form.errors.postal_zip_code" />
                </div>
            </div>

            <p class="text-xs text-slate-400">
                Already applied via the public form with this email? Registering claims that
                application into your new portal account.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-6 pt-6 border-t border-white/10 mt-6">
                <div class="text-sm font-medium text-slate-300">
                    Have an account?
                    <Link :href="route('applicant.login')"
                        class="text-white hover:text-blue-300 font-semibold ml-1.5 transition-colors">
                        Sign In
                    </Link>
                </div>

                <PrimaryButton
                    class="auth-btn w-full px-5 py-2.5 text-xs font-bold text-white sm:w-auto"
                    :class="{ 'opacity-60 cursor-wait': form.processing }" :disabled="form.processing">
                    <span v-if="form.processing">Creating...</span>
                    <span v-else>Create Account</span>
                </PrimaryButton>
            </div>
        </form>
    </AuthLayout>
</template>
