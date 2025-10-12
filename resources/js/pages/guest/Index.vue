<script setup>
import { onMounted } from 'vue';
import GuestMap from '../../Components/map/GuestMap.vue';
import { Head } from '@inertiajs/vue3';
import LeafletMap from '../../Components/map/LeafletMap.vue';

const props = defineProps({
  facilities: {
    type: Array,
    default: () => []
  },
  notes: {
    type: Array,
    default: () => []
  }
});


</script>

<template>
  <div>
    <div class="w-full h-screen overflow-hidden">
      <GuestMap :facilities="facilities" :notes="notes" />
       <!-- <LeafletMap :facilities="facilities" /> -->
    </div>
  </div>
</template>
