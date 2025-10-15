<script setup>
import GuestMap from '../../Components/map/GuestMap.vue';
import { Head } from '@inertiajs/vue3';
import GuestMapV1 from '../../Components/map/GuestMapV1.vue';

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
      <GuestMapV1 :facilities="facilities" :notes="notes" />
       <!-- <LeafletMap :facilities="facilities" /> -->

       <!-- <PathFinder/> -->
    </div>
  </div>
</template>
