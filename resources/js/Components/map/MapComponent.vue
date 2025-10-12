<script setup>
import { ref, onMounted } from 'vue';
import { LMap, LTileLayer, LMarker, LPopup, LPolyline } from '@vue-leaflet/vue-leaflet';
import axios from 'axios';

const mapCenter = ref([8.9212, 125.4333]);
const zoom = ref(17);

const facilities = ref([
  { name: 'Library', position: [8.9220, 125.4335] },
  { name: 'Gymnasium', position: [8.9200, 125.4320] },
  { name: 'Cafeteria', position: [8.9215, 125.4340] }
]);

const routeCoordinates = ref([]);

const fetchWalkingRoute = async (start, end) => {
  const apiKey = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjU4NDk5NTA5YzYyOTQxN2M5NTg1Zjc4MzIzMTgyNzFkIiwiaCI6Im11cm11cjY0In0=';
  try {
    const response = await axios.post(
      'https://api.openrouteservice.org/v2/directions/foot-walking/geojson',
      {
        coordinates: [
          [start[1], start[0]],
          [end[1], end[0]]
        ]
      },
      {
        headers: {
          Authorization: apiKey,
          'Content-Type': 'application/json'
        }
      }
    );

    routeCoordinates.value = response.data.features[0].geometry.coordinates.map(c => [c[1], c[0]]);
  } catch (err) {
    console.error('Error fetching route:', err.response?.data || err);
  }
};


onMounted(() => {
  const start = [8.9220, 125.4335]; // Library
  const end = [8.9200, 125.4320];   // Gym
  fetchWalkingRoute(start, end);
});
</script>

<template>
  <div class="w-full h-[500px]">
    <LMap :zoom="zoom" :center="mapCenter" style="height: 100%; width: 100%;">
      <LTileLayer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
      />

      <LMarker
        v-for="facility in facilities"
        :key="facility.name"
        :lat-lng="facility.position"
      >
        <LPopup>{{ facility.name }}</LPopup>
      </LMarker>

      <LPolyline
        v-if="routeCoordinates.length"
        :lat-lngs="routeCoordinates"
        color="blue"
        :weight="4"
      />
    </LMap>
  </div>
</template>
