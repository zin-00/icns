<script setup>
import { onMounted, ref } from 'vue'

let map
let activeRouteLayer = null

const startPoint = ref(null)
const endPoint = ref(null)

// OSRM server base URL
const osrmBaseUrl = 'http://127.0.0.1:5000'

// Function to request and draw OSRM route
const drawOSRMRoute = async (start, end) => {
  try {
    const coordinates = `${start.lng},${start.lat};${end.lng},${end.lat}`
    const url = `${osrmBaseUrl}/route/v1/driving/${coordinates}?overview=full&geometries=geojson`

    const response = await fetch(url)
    const data = await response.json()

    if (data.code === 'Ok' && data.routes.length > 0) {
      const routeCoords = data.routes[0].geometry.coordinates.map(([lng, lat]) => [lat, lng])

      // Remove previous route
      if (activeRouteLayer) map.removeLayer(activeRouteLayer)

      // Draw route
      activeRouteLayer = L.polyline(routeCoords, {
        color: 'blue',
        weight: 5,
        opacity: 0.8
      }).addTo(map)

      map.fitBounds(activeRouteLayer.getBounds())
    } else {
      console.warn('⚠️ No route data available')
    }
  } catch (error) {
    console.error('🚨 OSRM routing failed:', error)
  }
}

onMounted(() => {
  // Initialize map
  map = L.map('map').setView([14.675996, 121.04375], 14)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  // Click to set start/end points
  map.on('click', (e) => {
    if (!startPoint.value) {
      startPoint.value = e.latlng
      L.marker(e.latlng, { title: 'Start Point' }).addTo(map)
    } else if (!endPoint.value) {
      endPoint.value = e.latlng
      L.marker(e.latlng, { title: 'Destination' }).addTo(map)
      drawOSRMRoute(startPoint.value, endPoint.value)
    } else {
      // Reset for next route
      startPoint.value = e.latlng
      endPoint.value = null
      if (activeRouteLayer) map.removeLayer(activeRouteLayer)
      map.eachLayer((layer) => {
        if (layer instanceof L.Marker) map.removeLayer(layer)
      })
      L.marker(e.latlng, { title: 'Start Point' }).addTo(map)
    }
  })
})
</script>

<template>
  <div id="map" class="w-full h-[100vh]"></div>
</template>

<style>
#map {
  width: 100%;
  height: 100vh;
}
</style>
