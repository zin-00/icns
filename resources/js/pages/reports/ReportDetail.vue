<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import {
    ArrowLeftIcon,
    PrinterIcon,
    DocumentArrowDownIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    report: {
        type: Object,
        required: true
    }
});

const isExporting = ref(false);

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

// Export to PDF
const exportPDF = () => {
    isExporting.value = true;
    window.print();
    setTimeout(() => {
        isExporting.value = false;
    }, 1000);
};

// Export to DOCX
const exportDOCX = async () => {
    isExporting.value = true;

    try {
        const html = generateDocumentHTML();

        // Create blob from HTML
        const blob = new Blob([html], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' });

        // Create download link
        const link = document.createElement('a');
        const url = window.URL.createObjectURL(blob);
        link.href = url;
        link.download = `${props.report.title.replace(/\s+/g, '-')}.docx`;
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
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                padding: 40px;
            }
            h1 {
                color: #1f2937;
                border-bottom: 3px solid #1f2937;
                padding-bottom: 10px;
                margin-bottom: 10px;
            }
            h2 {
                color: #1f2937;
                margin-top: 30px;
                margin-bottom: 15px;
                border-left: 4px solid #1f2937;
                padding-left: 10px;
            }
            .meta {
                background-color: #f3f4f6;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
                font-size: 14px;
            }
            .meta p {
                margin: 5px 0;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th {
                background-color: #f3f4f6;
                padding: 12px;
                text-align: left;
                font-weight: bold;
                border-bottom: 2px solid #d1d5db;
            }
            td {
                padding: 10px 12px;
                border-bottom: 1px solid #e5e7eb;
            }
            tr:nth-child(even) {
                background-color: #f9fafb;
            }
            .stat-box {
                display: inline-block;
                background-color: #f3f4f6;
                padding: 15px 20px;
                margin: 10px 20px 10px 0;
                border-radius: 5px;
                text-align: center;
            }
            .stat-value {
                font-size: 24px;
                font-weight: bold;
                color: #1f2937;
            }
            .stat-label {
                font-size: 12px;
                color: #6b7280;
                text-transform: uppercase;
                margin-top: 5px;
            }
            .badge {
                display: inline-block;
                padding: 5px 10px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: bold;
                background-color: #e5e7eb;
                color: #1f2937;
            }
            .page-break {
                page-break-after: always;
            }
        </style>
    </head>
    <body>
        <h1>${props.report.title}</h1>

        <div class="meta">
            <p><strong>Description:</strong> ${props.report.description}</p>
            <p><strong>Report Type:</strong> <span class="badge">${props.report.type}</span></p>
            ${props.report.period_from ? `<p><strong>Period:</strong> ${formatDate(props.report.period_from)} to ${formatDate(props.report.period_to)}</p>` : ''}
            <p><strong>Generated:</strong> ${formatDate(props.report.created_at)}</p>
        </div>
    `;

    // Report type specific content
    if (props.report.type === 'usage') {
        html += generateUsageReport(data);
    } else if (props.report.type === 'feedback') {
        html += generateFeedbackReport(data);
    } else if (props.report.type === 'facility') {
        html += generateFacilityReport(data);
    } else if (props.report.type === 'search') {
        html += generateSearchReport(data);
    }

    html += '</body></html>';
    return html;
};

const generateUsageReport = (data) => {
    let html = `
        <h2>Usage Statistics</h2>
        <div>
            <div class="stat-box">
                <div class="stat-value">${data.total_searches}</div>
                <div class="stat-label">Total Searches</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">${data.unique_searchers}</div>
                <div class="stat-label">Unique Searchers</div>
            </div>
        </div>

        <h2>Top Searches</h2>
        <table>
            <thead>
                <tr>
                    <th>Query</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                ${data.top_searches?.map(s => `
                    <tr>
                        <td>${s.query}</td>
                        <td>${s.count}</td>
                    </tr>
                `).join('') || '<tr><td colspan="2">No data</td></tr>'}
            </tbody>
        </table>

        <h2>Guest Roles</h2>
        <table>
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                ${data.guest_roles?.map(r => `
                    <tr>
                        <td>${r.role}</td>
                        <td>${r.count}</td>
                    </tr>
                `).join('') || '<tr><td colspan="2">No data</td></tr>'}
            </tbody>
        </table>
    `;
    return html;
};

const generateFeedbackReport = (data) => {
    let html = `
        <h2>Feedback Statistics</h2>
        <div>
            <div class="stat-box">
                <div class="stat-value">${data.total_feedback}</div>
                <div class="stat-label">Total Feedback</div>
            </div>
        </div>

        <h2>Feedback by Location</h2>
        <table>
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                ${data.feedback_by_location?.map(l => `
                    <tr>
                        <td>${l.location}</td>
                        <td>${l.count}</td>
                    </tr>
                `).join('') || '<tr><td colspan="2">No data</td></tr>'}
            </tbody>
        </table>

        <h2>Feedback by Role</h2>
        <table>
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                ${data.feedback_by_role?.map(r => `
                    <tr>
                        <td>${r.role}</td>
                        <td>${r.count}</td>
                    </tr>
                `).join('') || '<tr><td colspan="2">No data</td></tr>'}
            </tbody>
        </table>

        ${data.sample_feedback && data.sample_feedback.length > 0 ? `
        <div class="page-break"></div>
        <h2>Sample Feedback</h2>
        <table>
            <thead>
                <tr>
                    <th>Guest</th>
                    <th>Location</th>
                    <th>Message</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                ${data.sample_feedback.map(f => `
                    <tr>
                        <td>${f.guest}</td>
                        <td>${f.location}</td>
                        <td>${f.message}</td>
                        <td>${f.date}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
        ` : ''}
    `;
    return html;
};

const generateFacilityReport = (data) => {
    let html = `
        <h2>Facility Statistics</h2>
        <div>
            <div class="stat-box">
                <div class="stat-value">${data.total_facilities}</div>
                <div class="stat-label">Total Facilities</div>
            </div>
        </div>

        <h2>Facilities by Category</h2>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                ${data.facilities_by_category?.map(c => `
                    <tr>
                        <td>${c.category}</td>
                        <td>${c.count}</td>
                    </tr>
                `).join('') || '<tr><td colspan="2">No data</td></tr>'}
            </tbody>
        </table>

        <h2>Facilities by Department</h2>
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                ${data.facilities_by_department?.map(d => `
                    <tr>
                        <td>${d.department}</td>
                        <td>${d.count}</td>
                    </tr>
                `).join('') || '<tr><td colspan="2">No data</td></tr>'}
            </tbody>
        </table>

        <h2>Facilities by Status</h2>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                ${data.facilities_by_status?.map(s => `
                    <tr>
                        <td>${s.status}</td>
                        <td>${s.count}</td>
                    </tr>
                `).join('') || '<tr><td colspan="2">No data</td></tr>'}
            </tbody>
        </table>

        ${data.facility_details && data.facility_details.length > 0 ? `
        <div class="page-break"></div>
        <h2>Facility Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                ${data.facility_details.map(f => `
                    <tr>
                        <td>${f.name}</td>
                        <td>${f.category}</td>
                        <td>${f.department}</td>
                        <td>${f.status}</td>
                        <td>${f.location}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
        ` : ''}
    `;
    return html;
};

const generateSearchReport = (data) => {
    let html = `
        <h2>Search Statistics</h2>
        <div>
            <div class="stat-box">
                <div class="stat-value">${data.total_searches}</div>
                <div class="stat-label">Total Searches</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">${data.unique_queries}</div>
                <div class="stat-label">Unique Queries</div>
            </div>
        </div>

        <h2>Top 10 Searches</h2>
        <table>
            <thead>
                <tr>
                    <th>Query</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                ${data.top_10_searches?.map(s => `
                    <tr>
                        <td>${s.query}</td>
                        <td>${s.search_count}</td>
                    </tr>
                `).join('') || '<tr><td colspan="2">No data</td></tr>'}
            </tbody>
        </table>

        <h2>Bottom 10 Searches</h2>
        <table>
            <thead>
                <tr>
                    <th>Query</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                ${data.bottom_10_searches?.map(s => `
                    <tr>
                        <td>${s.query}</td>
                        <td>${s.search_count}</td>
                    </tr>
                `).join('') || '<tr><td colspan="2">No data</td></tr>'}
            </tbody>
        </table>
    `;
    return html;
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <button
                        @click="router.back()"
                        class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 mb-3"
                    >
                        <ArrowLeftIcon class="h-4 w-4" />
                        Back
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900">{{ report.title }}</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ report.description }}</p>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="exportPDF"
                        :disabled="isExporting"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition disabled:opacity-50"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        PDF
                    </button>
                    <button
                        @click="exportDOCX"
                        :disabled="isExporting"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition disabled:opacity-50"
                    >
                        <DocumentArrowDownIcon class="h-4 w-4" />
                        DOCX
                    </button>
                </div>
            </div>

            <!-- Metadata -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-gray-600 uppercase font-semibold mb-1">Type</p>
                        <p class="text-sm font-medium text-gray-900 capitalize">{{ report.type }}</p>
                    </div>
                    <div v-if="report.period_from">
                        <p class="text-xs text-gray-600 uppercase font-semibold mb-1">Period From</p>
                        <p class="text-sm font-medium text-gray-900">{{ formatDate(report.period_from) }}</p>
                    </div>
                    <div v-if="report.period_to">
                        <p class="text-xs text-gray-600 uppercase font-semibold mb-1">Period To</p>
                        <p class="text-sm font-medium text-gray-900">{{ formatDate(report.period_to) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 uppercase font-semibold mb-1">Generated</p>
                        <p class="text-sm font-medium text-gray-900">{{ formatDate(report.created_at) }}</p>
                    </div>
                </div>
            </div>

            <!-- Report Content -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div v-if="report.type === 'usage'" class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Usage Statistics</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Total Searches</p>
                                <p class="text-3xl font-bold text-gray-900">{{ report.data.total_searches }}</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Unique Searchers</p>
                                <p class="text-3xl font-bold text-gray-900">{{ report.data.unique_searchers }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Top Searches</h4>
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-900">Query</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-900">Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="search in report.data.top_searches" :key="search.query">
                                    <td class="px-4 py-2 text-gray-700">{{ search.query }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ search.count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="report.type === 'feedback'" class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Feedback Statistics</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600">Total Feedback</p>
                            <p class="text-3xl font-bold text-gray-900">{{ report.data.total_feedback }}</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Feedback by Location</h4>
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-900">Location</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-900">Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="(location, idx) in report.data.feedback_by_location" :key="idx">
                                    <td class="px-4 py-2 text-gray-700">{{ location.location }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ location.count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="report.type === 'facility'" class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Facility Statistics</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600">Total Facilities</p>
                            <p class="text-3xl font-bold text-gray-900">{{ report.data.total_facilities }}</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Facilities by Category</h4>
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-900">Category</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-900">Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="(cat, idx) in report.data.facilities_by_category" :key="idx">
                                    <td class="px-4 py-2 text-gray-700">{{ cat.category }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ cat.count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="report.type === 'search'" class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Search Statistics</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Total Searches</p>
                                <p class="text-3xl font-bold text-gray-900">{{ report.data.total_searches }}</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Unique Queries</p>
                                <p class="text-3xl font-bold text-gray-900">{{ report.data.unique_queries }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Top 10 Searches</h4>
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-900">Query</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-900">Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="search in report.data.top_10_searches" :key="search.query">
                                    <td class="px-4 py-2 text-gray-700">{{ search.query }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ search.search_count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }

    body {
        background: white;
    }

    .bg-gray-50 {
        background-color: #f9fafb;
    }
}
</style>
