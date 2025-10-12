<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MapComponent from '../../Components/map/MapComponent.vue';
import MapBox from '../../Components/map/MapBox.vue';
</script>
<template>
    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- <MapComponent /> -->
                    <MapBox :isAdmin="true" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
