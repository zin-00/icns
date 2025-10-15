<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import {
    ArrowLeftIcon,
    PrinterIcon,
    DocumentArrowDownIcon,
    ChartBarIcon,
    ClockIcon,
    CalendarIcon,
    TagIcon
} from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    report: {
        type: Object,
        required: true
    }
});

const isExporting = ref(false);
const activeTab = ref('overview');

// Format date
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatShortDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

// Export to PDF
const exportPDF = () => {
    isExporting.value = true;
    setTimeout(() => {
        window.print();
        isExporting.value = false;
    }, 100);
};

// Export to DOCX
const exportDOCX = async () => {
    isExporting.value = true;

    try {
        const html = generateDocumentHTML();
        const blob = new Blob([html], {
            type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        });

        const link = document.createElement('a');
        const url = window.URL.createObjectURL(blob);
        link.href = url;
        link.download = `${props.report.title.replace(/\s+/g, '-')}-${Date.now()}.docx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Export error:', error);
    } finally {
        isExporting.value = false;
    }
};

// Generate HTML for document export
const generateDocumentHTML = () => {
    const data = props.report.data;

    let html = `
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>${props.report.title}</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; padding: 40px; }
            h1 { color: #1f2937; border-bottom: 3px solid #1f2937; padding-bottom: 10px; margin-bottom: 10px; }
            h2 { color: #1f2937; margin-top: 30px; margin-bottom: 15px; border-left: 4px solid #1f2937; padding-left: 10px; }
            .meta { background-color: #f3f4f6; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; }
            .meta p { margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th { background-color: #f3f4f6; padding: 12px; text-align: left; font-weight: bold; border-bottom: 2px solid #d1d5db; }
            td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
            tr:nth-child(even) { background-color: #f9fafb; }
            .stat-box { display: inline-block; background-color: #f3f4f6; padding: 15px 20px; margin: 10px 20px 10px 0; border-radius: 5px; text-align: center; }
            .stat-value { font-size: 24px; font-weight: bold; color: #1f2937; }
            .stat-label { font-size: 12px; color: #6b7280; text-transform: uppercase; margin-top: 5px; }
        </style>
    </head>
    <body>
        <h1>${props.report.title}</h1>
        <div class="meta">
            <p><strong>Description:</strong> ${props.report.description}</p>
            <p><strong>Type:</strong> ${props.report.type.toUpperCase()}</p>
            ${props.report.period_from ? `<p><strong>Period:</strong> ${formatDate(props.report.period_from)} to ${formatDate(props.report.period_to)}</p>` : ''}
            <p><strong>Generated:</strong> ${formatDate(props.report.created_at)}</p>
        </div>
    `;

    if (props.report.type === 'usage') html += generateUsageReport(data);
    else if (props.report.type === 'feedback') html += generateFeedbackReport(data);
    else if (props.report.type === 'facility') html += generateFacilityReport(data);
    else if (props.report.type === 'search') html += generateSearchReport(data);

    html += '</body></html>';
    return html;
};

const generateUsageReport = (data) => `
    <h2>Usage Statistics</h2>
    <div>
        <div class="stat-box"><div class="stat-value">${data.total_searches}</div><div class="stat-label">Total Searches</div></div>
        <div class="stat-box"><div class="stat-value">${data.unique_searchers}</div><div class="stat-label">Unique Searchers</div></div>
    </div>
    <h2>Top Searches</h2>
    <table><thead><tr><th>Query</th><th>Count</th></tr></thead><tbody>
        ${data.top_searches?.map(s => `<tr><td>${s.query}</td><td>${s.count}</td></tr>`).join('') || '<tr><td colspan="2">No data</td></tr>'}
    </tbody></table>
`;

const generateFeedbackReport = (data) => `
    <h2>Feedback Statistics</h2>
    <div><div class="stat-box"><div class="stat-value">${data.total_feedback}</div><div class="stat-label">Total Feedback</div></div></div>
    <h2>Feedback by Location</h2>
    <table><thead><tr><th>Location</th><th>Count</th></tr></thead><tbody>
        ${data.feedback_by_location?.map(l => `<tr><td>${l.location}</td><td>${l.count}</td></tr>`).join('') || '<tr><td colspan="2">No data</td></tr>'}
    </tbody></table>
`;

const generateFacilityReport = (data) => `
    <h2>Facility Statistics</h2>
    <div><div class="stat-box"><div class="stat-value">${data.total_facilities}</div><div class="stat-label">Total Facilities</div></div></div>
    <h2>Facilities by Category</h2>
    <table><thead><tr><th>Category</th><th>Count</th></tr></thead><tbody>
        ${data.facilities_by_category?.map(c => `<tr><td>${c.category}</td><td>${c.count}</td></tr>`).join('') || '<tr><td colspan="2">No data</td></tr>'}
    </tbody></table>
`;

const generateSearchReport = (data) => `
    <h2>Search Statistics</h2>
    <div>
        <div class="stat-box"><div class="stat-value">${data.total_searches}</div><div class="stat-label">Total Searches</div></div>
        <div class="stat-box"><div class="stat-value">${data.unique_queries}</div><div class="stat-label">Unique Queries</div></div>
    </div>
    <h2>Top 10 Searches</h2>
    <table><thead><tr><th>Query</th><th>Count</th></tr></thead><tbody>
        ${data.top_10_searches?.map(s => `<tr><td>${s.query}</td><td>${s.search_count}</td></tr>`).join('') || '<tr><td colspan="2">No data</td></tr>'}
    </tbody></table>
`;

// Get badge color based on type
const getTypeBadgeColor = (type) => {
    const colors = {
        usage: 'bg-blue-100 text-blue-800',
        feedback: 'bg-green-100 text-green-800',
        facility: 'bg-purple-100 text-purple-800',
        search: 'bg-orange-100 text-orange-800'
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
};

// Calculate percentage for progress bars
const calculatePercentage = (value, total) => {
    return total > 0 ? Math.round((value / total) * 100) : 0;
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <button
                    @click="router.back()"
                    class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 mb-4 transition"
                >
                    <ArrowLeftIcon class="h-4 w-4" />
                    Back to Reports
                </button>

                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900">{{ report.title }}</h1>
                            <span :class="['px-3 py-1 rounded-full text-xs font-semibold uppercase', getTypeBadgeColor(report.type)]">
                                {{ report.type }}
                            </span>
                        </div>
                        <p class="text-gray-600">{{ report.description }}</p>

                        <div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-gray-500">
                            <div class="flex items-center gap-2">
                                <CalendarIcon class="h-4 w-4" />
                                <span>Generated {{ formatShortDate(report.created_at) }}</span>
                            </div>
                            <div v-if="report.period_from" class="flex items-center gap-2">
                                <ClockIcon class="h-4 w-4" />
                                <span>{{ formatShortDate(report.period_from) }} - {{ formatShortDate(report.period_to) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 flex-shrink-0">
                        <button
                            @click="exportPDF"
                            :disabled="isExporting"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <PrinterIcon class="h-4 w-4" />
                            Print PDF
                        </button>
                        <button
                            @click="exportDOCX"
                            :disabled="isExporting"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <DocumentArrowDownIcon class="h-4 w-4" />
                            Export DOCX
                        </button>
                    </div>
                </div>
            </div>

            <!-- Usage Report -->
            <div v-if="report.type === 'usage'" class="space-y-6">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-600">Total Searches</p>
                            <ChartBarIcon class="h-5 w-5 text-blue-600" />
                        </div>
                        <p class="text-3xl font-bold text-gray-900">{{ report.data.total_searches?.toLocaleString() || 0 }}</p>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-600">Unique Searchers</p>
                            <ChartBarIcon class="h-5 w-5 text-green-600" />
                        </div>
                        <p class="text-3xl font-bold text-gray-900">{{ report.data.unique_searchers?.toLocaleString() || 0 }}</p>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-600">Avg. Searches/User</p>
                            <ChartBarIcon class="h-5 w-5 text-purple-600" />
                        </div>
                        <p class="text-3xl font-bold text-gray-900">
                            {{ report.data.unique_searchers > 0 ? (report.data.total_searches / report.data.unique_searchers).toFixed(1) : 0 }}
                        </p>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-600">Top Query Count</p>
                            <ChartBarIcon class="h-5 w-5 text-orange-600" />
                        </div>
                        <p class="text-3xl font-bold text-gray-900">
                            {{ report.data.top_searches?.[0]?.count?.toLocaleString() || 0 }}
                        </p>
                    </div>
                </div>

                <!-- Top Searches -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Top Search Queries</h3>
                        <p class="text-sm text-gray-600 mt-1">Most frequently searched terms</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rank</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Query</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Count</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Percentage</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="(search, index) in report.data.top_searches" :key="index" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-sm font-semibold text-gray-700">
                                            {{ index + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-medium text-gray-900">{{ search.query }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-700">{{ search.count.toLocaleString() }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 bg-gray-200 rounded-full h-2 max-w-xs">
                                                <div
                                                    class="bg-blue-600 h-2 rounded-full transition-all"
                                                    :style="{ width: `${calculatePercentage(search.count, report.data.total_searches)}%` }"
                                                ></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-700 min-w-[3rem] text-right">
                                                {{ calculatePercentage(search.count, report.data.total_searches) }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!report.data.top_searches?.length">
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        No search data available
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Guest Roles -->
                <div v-if="report.data.guest_roles?.length" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">User Roles Distribution</h3>
                        <p class="text-sm text-gray-600 mt-1">Breakdown of searches by user role</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="(role, index) in report.data.guest_roles" :key="index" class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700 capitalize">{{ role.role }}</span>
                                    <span class="text-2xl font-bold text-gray-900">{{ role.count }}</span>
                                </div>
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div
                                        class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full"
                                        :style="{ width: `${calculatePercentage(role.count, report.data.total_searches)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback Report -->
            <div v-if="report.type === 'feedback'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <p class="text-sm font-medium text-gray-600 mb-2">Total Feedback</p>
                        <p class="text-3xl font-bold text-gray-900">{{ report.data.total_feedback?.toLocaleString() || 0 }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- By Location -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">By Location</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <div v-for="(item, index) in report.data.feedback_by_location" :key="index" class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">{{ item.location }}</span>
                                <div class="flex items-center gap-3">
                                    <div class="bg-gray-200 rounded-full h-2 w-32">
                                        <div
                                            class="bg-green-600 h-2 rounded-full"
                                            :style="{ width: `${calculatePercentage(item.count, report.data.total_feedback)}%` }"
                                        ></div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 min-w-[2rem] text-right">{{ item.count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- By Role -->
                    <div v-if="report.data.feedback_by_role?.length" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">By Role</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <div v-for="(item, index) in report.data.feedback_by_role" :key="index" class="flex items-center justify-between">
                                <span class="text-sm text-gray-700 capitalize">{{ item.role }}</span>
                                <div class="flex items-center gap-3">
                                    <div class="bg-gray-200 rounded-full h-2 w-32">
                                        <div
                                            class="bg-green-600 h-2 rounded-full"
                                            :style="{ width: `${calculatePercentage(item.count, report.data.total_feedback)}%` }"
                                        ></div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 min-w-[2rem] text-right">{{ item.count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Facility Report -->
            <div v-if="report.type === 'facility'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <p class="text-sm font-medium text-gray-600 mb-2">Total Facilities</p>
                        <p class="text-3xl font-bold text-gray-900">{{ report.data.total_facilities?.toLocaleString() || 0 }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- By Category -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">By Category</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <div v-for="(item, index) in report.data.facilities_by_category" :key="index" class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">{{ item.category }}</span>
                                <div class="flex items-center gap-3">
                                    <div class="bg-gray-200 rounded-full h-2 w-32">
                                        <div
                                            class="bg-purple-600 h-2 rounded-full"
                                            :style="{ width: `${calculatePercentage(item.count, report.data.total_facilities)}%` }"
                                        ></div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 min-w-[2rem] text-right">{{ item.count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- By Department -->
                    <div v-if="report.data.facilities_by_department?.length" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">By Department</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <div v-for="(item, index) in report.data.facilities_by_department" :key="index" class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">{{ item.department }}</span>
                                <div class="flex items-center gap-3">
                                    <div class="bg-gray-200 rounded-full h-2 w-32">
                                        <div
                                            class="bg-purple-600 h-2 rounded-full"
                                            :style="{ width: `${calculatePercentage(item.count, report.data.total_facilities)}%` }"
                                        ></div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 min-w-[2rem] text-right">{{ item.count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Report -->
            <div v-if="report.type === 'search'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <p class="text-sm font-medium text-gray-600 mb-2">Total Searches</p>
                        <p class="text-3xl font-bold text-gray-900">{{ report.data.total_searches?.toLocaleString() || 0 }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <p class="text-sm font-medium text-gray-600 mb-2">Unique Queries</p>
                        <p class="text-3xl font-bold text-gray-900">{{ report.data.unique_queries?.toLocaleString() || 0 }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top 10 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Top 10 Searches</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <div v-for="(item, index) in report.data.top_10_searches" :key="index" class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-xs font-semibold text-orange-700">
                                    {{ index + 1 }}
                                </span>
                                <span class="flex-1 text-sm text-gray-700">{{ item.query }}</span>
                                <span class="text-sm font-semibold text-gray-900">{{ item.search_count }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom 10 -->
                    <div v-if="report.data.bottom_10_searches?.length" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Bottom 10 Searches</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <div v-for="(item, index) in report.data.bottom_10_searches" :key="index" class="flex items-center gap-3">
                                <span class="flex-1 text-sm text-gray-700">{{ item.query }}</span>
                                <span class="text-sm font-semibold text-gray-900">{{ item.search_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@media print {
    button {
        display: none !important;
    }

    .no-print {
        display: none !important;
    }

    body {
        background: white;
    }

    .shadow-sm {
        box-shadow: none;
    }

    .border {
        border: 1px solid #e5e7eb;
    }
}
</style>
