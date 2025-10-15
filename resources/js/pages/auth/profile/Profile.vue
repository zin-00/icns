<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    user: Object,
    status: String
});

const editingProfile = ref(false);
const editingPassword = ref(false);

const profileForm = useForm({
    name: props.user.name,
    email: props.user.email
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: ''
});

const updateProfile = () => {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            editingProfile.value = false;
        }
    });
};

const updatePassword = () => {
    passwordForm.patch(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            editingPassword.value = false;
            passwordForm.reset();
        },
        onError: () => {
            passwordForm.reset('password', 'password_confirmation');
        }
    });
};

const cancelEdit = () => {
    editingProfile.value = false;
    profileForm.reset();
    profileForm.clearErrors();
};

const cancelPasswordEdit = () => {
    editingPassword.value = false;
    passwordForm.reset();
    passwordForm.clearErrors();
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Success Message -->
                <div v-if="status" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ status }}
                </div>

                <!-- Profile Information -->
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Profile Information</h3>
                            <button
                                v-if="!editingProfile"
                                @click="editingProfile = true"
                                class="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Edit
                            </button>
                        </div>

                        <form v-if="editingProfile" @submit.prevent="updateProfile" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input
                                    v-model="profileForm.name"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-900"
                                    required
                                />
                                <p v-if="profileForm.errors.name" class="mt-1 text-sm text-red-600">
                                    {{ profileForm.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input
                                    v-model="profileForm.email"
                                    type="email"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-900"
                                    required
                                />
                                <p v-if="profileForm.errors.email" class="mt-1 text-sm text-red-600">
                                    {{ profileForm.errors.email }}
                                </p>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button
                                    type="submit"
                                    :disabled="profileForm.processing"
                                    class="px-4 py-2 bg-gray-900 text-white rounded-md hover:bg-gray-800 disabled:opacity-50"
                                >
                                    Save
                                </button>
                                <button
                                    type="button"
                                    @click="cancelEdit"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>

                        <div v-else class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Name</label>
                                <p class="mt-1 text-gray-900">{{ user.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Email</label>
                                <p class="mt-1 text-gray-900">{{ user.email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Change Password</h3>
                            <button
                                v-if="!editingPassword"
                                @click="editingPassword = true"
                                class="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Change
                            </button>
                        </div>

                        <form v-if="editingPassword" @submit.prevent="updatePassword" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                <input
                                    v-model="passwordForm.current_password"
                                    type="password"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-900"
                                    required
                                />
                                <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">
                                    {{ passwordForm.errors.current_password }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input
                                    v-model="passwordForm.password"
                                    type="password"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-900"
                                    required
                                />
                                <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">
                                    {{ passwordForm.errors.password }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                <input
                                    v-model="passwordForm.password_confirmation"
                                    type="password"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-900"
                                    required
                                />
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button
                                    type="submit"
                                    :disabled="passwordForm.processing"
                                    class="px-4 py-2 bg-gray-900 text-white rounded-md hover:bg-gray-800 disabled:opacity-50"
                                >
                                    Update Password
                                </button>
                                <button
                                    type="button"
                                    @click="cancelPasswordEdit"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>

                        <p v-else class="text-sm text-gray-600">
                            Click "Change" to update your password
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
