<script setup>
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import { ref, computed, watch } from 'vue';
import {
    XMarkIcon,
    ArrowPathIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    EyeIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    MagnifyingGlassIcon,
    BuildingOffice2Icon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import Modal from '../../Components/Modal.vue';
import TextInput from '../../Components/TextInput.vue';
import { useToast } from 'vue-toastification';
import axios from 'axios';

const props = defineProps({
    markers: {
        type: Array,
        default: () => []
    },
    facilities: {
        type: Array,
        default: () => []
    },
    pagination: {
        type: Object,
        default: () => ({ current_page: 1, last_page: 1, total: 0, per_page: 10, from: 1, to: 1 })
    }
});

const toast = useToast();

const searchQuery = ref('');
const statusFilter = ref('all');
const isOpen = ref(false);
const isConfirmationModalOpen = ref(false);

const data = ref({
    name: '',
    category: '',
    department: '',
    description: '',
    hours: '',
    status: 'active',
    marker_id: null
});

const isEditMode = ref(false);
const selectedFacility = ref(null);
const viewModalOpen = ref(false);
const viewedFacility = ref(null);
const isSaving = ref(false);
const isDeleting = ref(false);

// Optimized filtered facilities
const filteredFacilities = computed(() => {
    let filtered = props.facilities;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(facility => {
            const name = facility.name?.toLowerCase() || '';
            const category = facility.category?.toLowerCase() || '';
            const department = facility.department?.toLowerCase() || '';
            return name.includes(query) || category.includes(query) || department.includes(query);
        });
    }

    if (statusFilter.value && statusFilter.value !== 'all') {
        const filterStatus = statusFilter.value.toLowerCase();
        filtered = filtered.filter(facility =>
            facility.status?.toLowerCase() === filterStatus
        );
    }

    return filtered;
});

// Check if any filters are active
const hasActiveFilters = computed(() => {
    return searchQuery.value || statusFilter.value !== 'all';
});

const loading = ref(false);

const refreshData = () => {
    loading.value = true;
    router.reload({
        preserveState: true,
        onFinish: () => { loading.value = false; }
    });
};

const openAddModal = () => {
    selectedFacility.value = null;
    isEditMode.value = false;
    data.value = {
        name: '',
        category: '',
        department: '',
        description: '',
        hours: '',
        status: 'active',
        marker_id: null
    };
    isOpen.value = true;
};

const handleEdit = (facility) => {
    selectedFacility.value = facility;
    isEditMode.value = true;
    data.value = {
        name: facility.name,
        category: facility.category,
        department: facility.department,
        description: facility.description,
        hours: facility.hours,
        status: facility.status,
        marker_id: facility.marker_id || null
    };
    isOpen.value = true;
};

const handleDelete = (facility) => {
    selectedFacility.value = facility;
    isConfirmationModalOpen.value = true;
};

const confirmDelete = async () => {
    if (!selectedFacility.value || isDeleting.value) return;

    isDeleting.value = true;

    try {
        const response = await axios.delete(`/facilities/${selectedFacility.value.id}/destroy`);
        isConfirmationModalOpen.value = false;
        selectedFacility.value = null;
        toast.success(response.data.message || 'Facility deleted successfully!');
        refreshData();
    } catch (error) {
        console.error('Delete error:', error.response?.data);
        toast.error(error.response?.data?.message || 'Failed to delete facility.');
    } finally {
        isDeleting.value = false;
    }
};

const cancelDelete = () => {
    if (isDeleting.value) return;
    isConfirmationModalOpen.value = false;
    selectedFacility.value = null;
};

const handleView = (facility) => {
    viewedFacility.value = facility;
    viewModalOpen.value = true;
};

const closeViewModal = () => {
    viewModalOpen.value = false;
    viewedFacility.value = null;
};

const handlePageChange = (page) => {
    router.get('/facilities', { page }, {
        preserveState: true,
        preserveScroll: true
    });
};

const saveFacility = async () => {
    if (isSaving.value) return;

    isSaving.value = true;

    try {
        if (isEditMode.value && selectedFacility.value) {
            await axios.put(`/facilities/${selectedFacility.value.id}`, data.value);
            toast.success('Facility updated successfully!');
        } else {
            await axios.post('/facilities', data.value);
            toast.success('Facility created successfully!');
        }

        isOpen.value = false;
        refreshData();
    } catch (error) {
        console.error('Save errors:', error.response?.data);

        if (error.response?.data?.errors) {
            const errors = error.response.data.errors;
            const firstError = Object.values(errors)[0][0];
            toast.error(firstError);
        } else {
            toast.error(error.response?.data?.message || `Failed to ${isEditMode.value ? 'update' : 'create'} facility.`);
        }
    } finally {
        isSaving.value = false;
    }
};

const closeModal = () => {
    if (isSaving.value) return;
    isOpen.value = false;
    selectedFacility.value = null;
    isEditMode.value = false;
};

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
};

const pageNumbers = computed(() => {
    const current = props.pagination.current_page;
    const last = props.pagination.last_page;
    const delta = 2;
    const range = [];
    const rangeWithDots = [];

    for (let i = 1; i <= last; i++) {
        if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
            range.push(i);
        }
    }

    let prev = 0;
    for (let i of range) {
        if (prev) {
            if (i - prev === 2) {
                rangeWithDots.push(prev + 1);
            } else if (i - prev !== 1) {
                rangeWithDots.push('...');
            }
        }
        rangeWithDots.push(i);
        prev = i;
    }

    return rangeWithDots;
});
</script>

<template>
<AuthenticatedLayout>
    <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Facilities</h2>
                <p class="mt-1 text-sm text-gray-600">Manage campus facilities and locations</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <span class="text-sm text-gray-700 font-medium">
                    {{ filteredFacilities.length }} {{ filteredFacilities.length === 1 ? 'facility' : 'facilities' }}
                </span>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-col sm:flex-row gap-3 mb-6">
            <!-- Search -->
            <div class="relative flex-1 max-w-md">
                <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search facilities, category, or department..."
                    class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-lg text-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                />
                <button
                    v-if="searchQuery"
                    @click="searchQuery = ''"
                    type="button"
                    class="absolute right-3 top-2.5 text-gray-500 hover:text-gray-700 transition-colors"
                >
                    <XMarkIcon class="w-4 h-4" />
                </button>
            </div>

            <!-- Status Filter -->
            <select
                v-model="statusFilter"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
            >
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <!-- Clear Filters Button -->
            <button
                v-if="hasActiveFilters"
                @click="clearFilters"
                class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors"
            >
                Clear Filters
            </button>

            <!-- Action Buttons -->
            <div class="flex gap-2 ml-auto">
                <button
                    @click="refreshData"
                    :disabled="loading"
                    type="button"
                    class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition disabled:opacity-50"
                >
                    <ArrowPathIcon :class="['h-4 w-4', loading ? 'animate-spin' : '']" />
                </button>
                <button
                    @click="openAddModal"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition"
                >
                    <PlusIcon class="h-4 w-4" />
                    <span>Add</span>
                </button>
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <!-- Loading State -->
            <div v-if="loading" class="text-center py-12">
                <ArrowPathIcon class="h-6 w-6 animate-spin mx-auto text-gray-400 mb-2" />
                <p class="text-sm text-gray-600">Loading...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredFacilities.length === 0" class="text-center py-12">
                <BuildingOffice2Icon class="h-8 w-8 mx-auto text-gray-300 mb-2" />
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No facilities found</h3>
                <p class="text-sm text-gray-600">Try adjusting your filters or create a new facility</p>
            </div>

            <!-- Table -->
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Hours</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="facility in filteredFacilities"
                            :key="facility.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ facility.name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ facility.category }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ facility.department }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ facility.marker ? facility.marker.label : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ facility.hours || 'N/A' }}</td>

                            <td class="px-6 py-4">
                                <span
                                    :class="[
                                        'inline-flex px-3 py-1 text-xs font-semibold rounded-full',
                                        facility.status === 'active'
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'bg-gray-200 text-gray-700'
                                    ]"
                                >
                                    {{ facility.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-1">
                                    <button
                                        @click.stop="handleView(facility)"
                                        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition"
                                        type="button"
                                        title="View details"
                                    >
                                        <EyeIcon class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click.stop="handleEdit(facility)"
                                        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition"
                                        type="button"
                                        title="Edit facility"
                                    >
                                        <PencilIcon class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click.stop="handleDelete(facility)"
                                        class="p-2 text-gray-600 hover:text-red-600 hover:bg-gray-100 rounded-lg transition"
                                        type="button"
                                        title="Delete facility"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-sm text-gray-600">
                        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }}
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            @click="handlePageChange(pagination.current_page - 1)"
                            :disabled="pagination.current_page === 1"
                            type="button"
                            class="p-2 text-gray-600 hover:text-gray-900 hover:bg-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            <ChevronLeftIcon class="w-4 h-4" />
                        </button>

                        <div class="flex gap-1">
                            <button
                                v-for="page in pageNumbers"
                                :key="page"
                                @click="typeof page === 'number' && handlePageChange(page)"
                                :disabled="typeof page !== 'number'"
                                type="button"
                                :class="[
                                    'px-3 py-1 text-sm rounded-lg transition',
                                    page === pagination.current_page
                                        ? 'bg-gray-900 text-white font-medium'
                                        : 'text-gray-600 hover:text-gray-900 hover:bg-white',
                                    typeof page !== 'number' ? 'cursor-default hover:bg-transparent' : ''
                                ]"
                            >
                                {{ page }}
                            </button>
                        </div>

                        <button
                            @click="handlePageChange(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            type="button"
                            class="p-2 text-gray-600 hover:text-gray-900 hover:bg-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            <ChevronRightIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <Transition name="modal-fade">
        <div v-if="viewModalOpen" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click="closeViewModal">
            <Transition name="modal-scale">
                <div v-if="viewModalOpen" class="bg-white rounded-lg shadow-lg w-full max-w-xl max-h-[75vh] overflow-hidden" @click.stop>
                    <!-- Modal Header -->
                    <div class="p-5 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">{{ viewedFacility?.name }}</h3>
                            <button @click="closeViewModal" type="button" class="text-gray-600 hover:text-gray-900 transition-colors">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-5 overflow-y-auto max-h-[calc(75vh-140px)] space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-xs font-semibold text-gray-900 mb-1 uppercase tracking-wide">Category</h4>
                                <p class="text-sm text-gray-700">{{ viewedFacility?.category }}</p>
                            </div>
                            <div>
                                <h4 class="text-xs font-semibold text-gray-900 mb-1 uppercase tracking-wide">Department</h4>
                                <p class="text-sm text-gray-700">{{ viewedFacility?.department }}</p>
                            </div>
                            <div>
                                <h4 class="text-xs font-semibold text-gray-900 mb-1 uppercase tracking-wide">Hours</h4>
                                <p class="text-sm text-gray-700">{{ viewedFacility?.hours || 'N/A' }}</p>
                            </div>
                            <div>
                                <h4 class="text-xs font-semibold text-gray-900 mb-1 uppercase tracking-wide">Status</h4>
                                <span
                                    :class="[
                                        'inline-flex px-3 py-1 text-xs font-semibold rounded-full',
                                        viewedFacility?.status === 'active'
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'bg-gray-200 text-gray-700'
                                    ]"
                                >
                                    {{ viewedFacility?.status }}
                                </span>
                            </div>
                        </div>

                        <div v-if="viewedFacility?.marker">
                            <h4 class="text-xs font-semibold text-gray-900 mb-2 uppercase tracking-wide">Location</h4>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="font-medium text-gray-900 text-sm">{{ viewedFacility.marker.label }}</p>
                                <p class="text-xs text-gray-600 mt-1">{{ viewedFacility.marker.type }}</p>
                                <p class="text-xs text-gray-600 mt-2">Lat: {{ viewedFacility.marker.latitude }}, Lng: {{ viewedFacility.marker.longitude }}</p>
                            </div>
                        </div>

                        <div v-if="viewedFacility?.description">
                            <h4 class="text-xs font-semibold text-gray-900 mb-2 uppercase tracking-wide">Description</h4>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ viewedFacility.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-5 border-t border-gray-200">
                        <button
                            @click="closeViewModal"
                            class="w-full px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>

    <!-- Delete Confirmation Modal -->
    <Transition name="modal-fade">
        <div v-if="isConfirmationModalOpen" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click="cancelDelete">
            <Transition name="modal-scale">
                <div v-if="isConfirmationModalOpen" class="bg-white rounded-lg shadow-lg w-full max-w-md" @click.stop>
                    <div class="p-6">
                        <!-- Warning Icon -->
                        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                            <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
                        </div>

                        <!-- Title -->
                        <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Facility</h3>

                        <!-- Message -->
                        <p class="text-sm text-gray-600 text-center mb-4">
                            Are you sure you want to delete <span class="font-semibold text-gray-900">{{ selectedFacility?.name }}</span>?
                        </p>

                        <!-- Details -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Category:</span>
                                <span class="font-medium text-gray-900">{{ selectedFacility?.category }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Department:</span>
                                <span class="font-medium text-gray-900">{{ selectedFacility?.department }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span
                                    :class="[
                                        'inline-flex px-2 py-0.5 text-xs font-semibold rounded',
                                        selectedFacility?.status === 'active'
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'bg-gray-200 text-gray-700'
                                    ]"
                                >
                                    {{ selectedFacility?.status }}
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3">
                            <button
                                @click="cancelDelete"
                                :disabled="isDeleting"
                                type="button"
                                class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmDelete"
                                :disabled="isDeleting"
                                type="button"
                                class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <ArrowPathIcon v-if="isDeleting" class="w-4 h-4 animate-spin" />
                                <span>{{ isDeleting ? 'Deleting...' : 'Delete' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>

    <!-- Add/Edit Modal -->
    <Transition name="modal-fade">
        <div v-if="isOpen" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click="closeModal">
            <Transition name="modal-scale">
                <div v-if="isOpen" class="bg-white rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-hidden" @click.stop>
                    <!-- Modal Header -->
                    <div class="p-5 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ isEditMode ? 'Edit Facility' : 'Add Facility' }}
                            </h3>
                            <button @click="closeModal" :disabled="isSaving" type="button" class="text-gray-600 hover:text-gray-900 transition-colors disabled:opacity-50">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="saveFacility" class="p-5 overflow-y-auto max-h-[calc(90vh-140px)]">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-900 mb-1">Name</label>
                                <TextInput
                                    v-model="data.name"
                                    type="text"
                                    placeholder="Facility name"
                                    required
                                    class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-900 mb-1">Category</label>
                                <TextInput
                                    v-model="data.category"
                                    type="text"
                                    placeholder="Category"
                                    required
                                    class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-900 mb-1">Department</label>
                                <TextInput
                                    v-model="data.department"
                                    type="text"
                                    placeholder="Department"
                                    required
                                    class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-900 mb-1">Hours</label>
                                <TextInput
                                    v-model="data.hours"
                                    type="text"
                                    placeholder="9:00 AM - 5:00 PM"
                                    class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-900 mb-1">Status</label>
                                <select
                                    v-model="data.status"
                                    class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-900 mb-1">Marker</label>
                                <select
                                    v-model="data.marker_id"
                                    class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                >
                                    <option :value="null">Select a marker</option>
                                    <option
                                        v-for="marker in markers"
                                        :key="marker.id"
                                        :value="marker.id"
                                    >
                                        {{ marker.label }} - {{ marker.type }}
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-900 mb-1">Description</label>
                                <textarea
                                    v-model="data.description"
                                    rows="4"
                                    placeholder="Facility description"
                                    class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 resize-none"
                                ></textarea>
                            </div>
                        </div>
                    </form>

                    <!-- Modal Footer -->
                    <div class="p-5 border-t border-gray-200 flex gap-3 justify-end">
                        <button
                            @click="closeModal"
                            :disabled="isSaving"
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Cancel
                        </button>
                        <button
                            @click="saveFacility"
                            :disabled="isSaving"
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <ArrowPathIcon v-if="isSaving" class="h-4 w-4 animate-spin" />
                            <span>{{ isSaving ? (isEditMode ? 'Updating...' : 'Creating...') : (isEditMode ? 'Update' : 'Create') }}</span>
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</AuthenticatedLayout>
</template>

<style scoped>
/* Modal fade transition */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

/* Modal scale transition */
.modal-scale-enter-active,
.modal-scale-leave-active {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
}

.modal-scale-enter-from,
.modal-scale-leave-to {
    transform: scale(0.95) translateY(-20px);
    opacity: 0;
}

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, box-shadow;
    transition-duration: 150ms;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
