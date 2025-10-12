<script setup>
import { useReactiveStore } from '../../store/reactives/reactive.js'
import { onMounted, toRefs, watch, onBeforeUnmount, ref, computed } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  facilities: {
    type: Array,
    default: () => []
  }
})

const reactive = useReactiveStore()
const {
       // coordinates
        lat, lng,

        // Boolean
        isLoaded,
        showInstructions,

        // Data Arrays
        navigationInstructions,

        //
        privateRoutes,
        routeInfo,

        //
        step
     } = toRefs(reactive)

let map = null
let userMarker = null
let destinationMarker = null
let routePolyline = null
let watchId = null
let geoJsonLayers = []
// const isLoaded = ref(false)
// const privateRoutes = ref(null)
// const routeInfo = ref(null)
// const navigationInstructions = ref([])
// const showInstructions = ref(false)

// --- Define marker icons for different facility types ---
const markerTypeIcons = {
  building: '🏢',
  classroom: '🏫',
  library: '📚',
  cafeteria: '🍽️',
  sports: '⚽',
  administration: '🏛️',
  parking: '🅿️',
  dormitory: '🏠',
  laboratory: '🔬',
  medical: '🏥',
  default: '📍'
}

// --- Start tracking user ---
const startTracking = () => {
  if (!navigator.geolocation) {
    console.error('Geolocation not supported.')
    return
  }
  watchId = navigator.geolocation.watchPosition(
    (pos) => {
      lat.value = pos.coords.latitude
      lng.value = pos.coords.longitude
    },
    (err) => console.error('Error tracking location:', err.message),
    { enableHighAccuracy: true }
  )
}

const stopTracking = () => {
  if (watchId !== null) navigator.geolocation.clearWatch(watchId)
}

// --- Calculate distance between two points ---
const calculateDistance = (lat1, lng1, lat2, lng2) => {
  const R = 6371e3
  const φ1 = (lat1 * Math.PI) / 180
  const φ2 = (lat2 * Math.PI) / 180
  const Δφ = ((lat2 - lat1) * Math.PI) / 180
  const Δλ = ((lng2 - lng1) * Math.PI) / 180

  const a =
    Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
    Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

  return R * c
}

// --- Find nearest point on specific LineString ---
const getNearestPointOnLine = (point, coordinates) => {
  let nearestIndex = 0
  let minDist = Infinity

  coordinates.forEach((coord, idx) => {
    const dist = calculateDistance(point.lat, point.lng, coord[1], coord[0])
    if (dist < minDist) {
      minDist = dist
      nearestIndex = idx
    }
  })

  return { index: nearestIndex, coord: coordinates[nearestIndex], distance: minDist }
}

// --- Find which LineString was clicked ---
const findClickedLineString = (clickPoint, features, threshold = 30) => {
  let closestFeature = null
  let minDist = Infinity

  features.forEach((feature) => {
    if (feature.geometry.type === 'LineString') {
      const coords = feature.geometry.coordinates
      coords.forEach((coord) => {
        const dist = calculateDistance(clickPoint.lat, clickPoint.lng, coord[1], coord[0])
        if (dist < minDist) {
          minDist = dist
          closestFeature = feature
        }
      })
    }
  })

  return minDist <= threshold ? closestFeature : null
}

// --- Generate Navigation Instructions ---
const generateInstructions = (route, isPrivatePath) => {
  const instructions = []

  // Starting instruction
  instructions.push({
    icon: '🚶',
    text: 'Start from your location',
    distance: '0 m'
  })

  // Calculate segment distances
  let cumulativeDistance = 0
  for (let i = 0; i < route.length - 1; i++) {
    const segmentDist = calculateDistance(
      route[i][0], route[i][1],
      route[i + 1][0], route[i + 1][1]
    )
    cumulativeDistance += segmentDist

    // Add instruction every ~50 meters or at key points
    if (i === Math.floor(route.length * 0.3)) {
      instructions.push({
        icon: '➡️',
        text: isPrivatePath ? 'Continue on public road' : 'Continue straight',
        distance: `${Math.round(cumulativeDistance)} m`
      })
    }

    if (i === Math.floor(route.length * 0.6) && isPrivatePath) {
      instructions.push({
        icon: '🏫',
        text: 'Enter private campus path',
        distance: `${Math.round(cumulativeDistance)} m`
      })
    }
  }

  // Destination instruction
  instructions.push({
    icon: '🎯',
    text: 'You have arrived at your destination',
    distance: `${Math.round(cumulativeDistance)} m`
  })

  return instructions
}

// --- Calculate ETA ---
const calculateETA = (distanceMeters) => {
  const walkingSpeedKmH = 5 // Average walking speed
  const walkingSpeedMS = walkingSpeedKmH * 1000 / 3600
  const timeSeconds = distanceMeters / walkingSpeedMS
  const timeMinutes = Math.ceil(timeSeconds / 60)

  const now = new Date()
  const eta = new Date(now.getTime() + timeSeconds * 1000)

  return {
    minutes: timeMinutes,
    arrivalTime: eta.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
  }
}

const getPublicRoute = async (start, end) => {
  try {
    const url = `https://router.project-osrm.org/route/v1/driving/${start.lng},${start.lat};${end.lng},${end.lat}?overview=full&geometries=geojson`
    const response = await fetch(url)
    const data = await response.json()

    if (data.code === 'Ok' && data.routes.length > 0) {
      const coords = data.routes[0].geometry.coordinates
      return coords.map(([lng, lat]) => ({ lat, lng }))
    }
  } catch (err) {
    console.error('OSRM routing error:', err)
  }
  return null
}

// --- Add Facility Marker to Map ---
const addFacilityMarker = (location) => {
  if (!map) return

  const marker = L.marker([location.lat, location.lng], {
    title: location.name,
    icon: L.divIcon({
      className: 'facility-marker',
      html: `
        <div style="
          background: white;
          border: 2px solid #3B82F6;
          border-radius: 50%;
          width: 40px;
          height: 40px;
          display: flex;
          align-items: center;
          justify-content: center;
          box-shadow: 0 2px 8px rgba(0,0,0,0.3);
          font-size: 16px;
        ">
          ${location.icon}
        </div>
      `,
      iconSize: [40, 40],
      iconAnchor: [20, 20]
    })
  }).addTo(map)

  // Add popup with facility information
  marker.bindPopup(`
    <div class="p-2 min-w-40">
      <div class="flex items-center gap-2 mb-2">
        <span class="text-lg">${location.icon}</span>
        <h3 class="font-bold text-gray-800">${location.name}</h3>
      </div>
      <p class="text-sm text-gray-600 mb-1"><strong>Type:</strong> ${location.department}</p>
      <p class="text-sm text-gray-600 mb-1"><strong>Category:</strong> ${location.category}</p>
      ${location.description ? `<p class="text-sm text-gray-600 mt-2">${location.description}</p>` : ''}
      <button onclick="this.parentElement.parentElement.parentElement._source._map.panTo([${location.lat}, ${location.lng}]);"
              class="mt-2 w-full bg-blue-500 text-white py-1 px-3 rounded text-sm hover:bg-blue-600 transition">
        Route to here
      </button>
    </div>
  `)

  return marker
}

// --- Main Routing Function ---
const createRoute = async (clickLatLng) => {
  if (!lat.value || !lng.value) {
    alert('⚠️ Waiting for GPS location...')
    return
  }

  if (!privateRoutes.value || !privateRoutes.value.features) {
    alert('⚠️ No route data available.')
    return
  }

  // Clear previous route
  if (routePolyline) map.removeLayer(routePolyline)
  if (destinationMarker) map.removeLayer(destinationMarker)

  const userPos = { lat: lat.value, lng: lng.value }

  // Check if user clicked on a private LineString
  const clickedFeature = findClickedLineString(
    clickLatLng,
    privateRoutes.value.features
  )

  if (clickedFeature) {
    // USER CLICKED ON A LINESTRING - Route using that specific path
    console.log('🔴 Clicked on LineString - routing via private path')

    const lineCoords = clickedFeature.geometry.coordinates
    const nearestToClick = getNearestPointOnLine(clickLatLng, lineCoords)

    // START of the LineString (first point)
    const lineStart = {
      lat: lineCoords[0][1],
      lng: lineCoords[0][0]
    }

    // Destination point (where user clicked on the line)
    const destPoint = {
      lat: lineCoords[nearestToClick.index][1],
      lng: lineCoords[nearestToClick.index][0]
    }

    let fullRoute = []

    // Step 1: Public route from user (A) to START of LineString
    const publicSegment = await getPublicRoute(userPos, lineStart)
    if (publicSegment) {
      publicSegment.forEach(p => fullRoute.push([p.lat, p.lng]))
    } else {
      fullRoute.push([userPos.lat, userPos.lng])
      fullRoute.push([lineStart.lat, lineStart.lng])
    }

    // Step 2: Follow the private LineString from START to clicked point
    for (let i = 0; i <= nearestToClick.index; i++) {
      const [lng, lat] = lineCoords[i]
      fullRoute.push([lat, lng])
    }

    // Step 3: Final destination marker at clicked point
    fullRoute.push([clickLatLng.lat, clickLatLng.lng])

    // Draw route
    routePolyline = L.polyline(fullRoute, {
      color: '#2196F3',
      weight: 6,
      opacity: 0.8
    }).addTo(map)

    destinationMarker = L.marker([clickLatLng.lat, clickLatLng.lng], {
      title: 'Destination',
      icon: L.divIcon({
        className: 'destination-marker',
        html: '<div style="background: #FF5252; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
        iconSize: [24, 24],
        iconAnchor: [12, 12]
      })
    }).addTo(map)

    // Calculate distance
    let totalDistance = 0
    for (let i = 0; i < fullRoute.length - 1; i++) {
      totalDistance += calculateDistance(
        fullRoute[i][0], fullRoute[i][1],
        fullRoute[i + 1][0], fullRoute[i + 1][1]
      )
    }

    const distanceText = totalDistance > 1000
      ? `${(totalDistance / 1000).toFixed(2)} km`
      : `${Math.round(totalDistance)} m`

    const eta = calculateETA(totalDistance)
    routeInfo.value = `🟢 Public → 🔴 Private\n📏 ${distanceText}\n⏱️ ${eta.minutes} min\n🕐 Arrive at ${eta.arrivalTime}`

    navigationInstructions.value = generateInstructions(fullRoute, true)
    showInstructions.value = true

    map.fitBounds(routePolyline.getBounds(), { padding: [50, 50] })

  } else {
    // USER CLICKED ON REGULAR MAP - Use public roads only
    console.log('🟢 Clicked on map - public roads only')

    const destPos = { lat: clickLatLng.lat, lng: clickLatLng.lng }
    const publicRoute = await getPublicRoute(userPos, destPos)

    let fullRoute = []
    if (publicRoute) {
      publicRoute.forEach(p => fullRoute.push([p.lat, p.lng]))
    } else {
      fullRoute.push([userPos.lat, userPos.lng])
      fullRoute.push([destPos.lat, destPos.lng])
    }

    routePolyline = L.polyline(fullRoute, {
      color: '#4CAF50',
      weight: 6,
      opacity: 0.8
    }).addTo(map)

    destinationMarker = L.marker([clickLatLng.lat, clickLatLng.lng], {
      title: 'Destination',
      icon: L.divIcon({
        className: 'destination-marker',
        html: '<div style="background: #FF5252; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
        iconSize: [24, 24],
        iconAnchor: [12, 12]
      })
    }).addTo(map)

    let totalDistance = 0
    for (let i = 0; i < fullRoute.length - 1; i++) {
      totalDistance += calculateDistance(
        fullRoute[i][0], fullRoute[i][1],
        fullRoute[i + 1][0], fullRoute[i + 1][1]
      )
    }

    const distanceText = totalDistance > 1000
      ? `${(totalDistance / 1000).toFixed(2)} km`
      : `${Math.round(totalDistance)} m`

    const eta = calculateETA(totalDistance)
    routeInfo.value = `🟢 Public Roads\n📏 ${distanceText}\n⏱️ ${eta.minutes} min\n🕐 Arrive at ${eta.arrivalTime}`

    navigationInstructions.value = generateInstructions(fullRoute, false)
    showInstructions.value = true

    map.fitBounds(routePolyline.getBounds(), { padding: [50, 50] })
  }
}

// --- Load GeoJSON ---
const loadPrivateRoutes = async () => {
  try {
    const res = await fetch('/path.json')
    if (!res.ok) throw new Error('Failed to load path.json')
    privateRoutes.value = await res.json()
    console.log('✅ Loaded path.json:', privateRoutes.value)
    isLoaded.value = true
  } catch (err) {
    console.error('❌ Error loading path.json:', err)
  }
}

// --- Process facilities data ---
const locations = computed(() => {
  if (!props.facilities || !Array.isArray(props.facilities)) {
    console.warn('No facilities data or invalid format')
    return []
  }

  return props.facilities.filter(facility => {
    const hasValidMarker = facility &&
      facility.marker &&
      facility.marker.longitude !== null &&
      facility.marker.latitude !== null &&
      !isNaN(parseFloat(facility.marker.longitude)) &&
      !isNaN(parseFloat(facility.marker.latitude))

    if (!hasValidMarker) {
      console.warn('Skipping facility with invalid marker:', facility)
    }

    return hasValidMarker
  })
  .map(facility => {
    try {
      const markerType = facility.marker?.type?.toLowerCase() || 'default'
      return {
        id: facility.id,
        name: facility.name || 'Unnamed Facility',
        lng: parseFloat(facility.marker.longitude),
        lat: parseFloat(facility.marker.latitude),
        department: facility.marker?.type || 'Unknown',
        markerType: markerType,
        category: facility.category || 'General',
        description: facility.description || '',
        icon: markerTypeIcons[markerType] || markerTypeIcons.default
      }
    } catch (err) {
      console.error('Error processing facility', facility, err)
      return null
    }
  }).filter(location => location !== null)
})

// --- Initialize Map ---
const initMap = async () => {
  const standard = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  })

  const satellite = L.tileLayer(
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    { maxZoom: 19, attribution: 'Tiles © Esri' }
  )

  map = L.map('map', {
    center: [lat.value || 8.169, lng.value || 126.003],
    zoom: 17,
    layers: [standard]
  })

  L.control.layers({ '🗺️ Standard': standard, '🛰️ Satellite': satellite }).addTo(map)

  // User marker
  userMarker = L.marker([lat.value || 0, lng.value || 0], {
    title: 'You',
    icon: L.divIcon({
      className: 'user-marker',
      html: '<div style="background: #4CAF50; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
      iconSize: [20, 20],
      iconAnchor: [10, 10]
    })
  }).addTo(map)

  // Add facilities markers
  if (locations.value.length > 0) {
    console.log(`📍 Adding ${locations.value.length} facility markers to map`)
    locations.value.forEach(location => {
      addFacilityMarker(location)
    })
  } else {
    console.warn('No valid locations to display on map')
  }

  // Load and display private routes
  if (privateRoutes.value) {
    privateRoutes.value.features.forEach((feature, idx) => {
      if (feature.geometry.type === 'LineString') {
        const layer = L.geoJSON(feature, {
          style: {
            color: '#FF6B6B',
            weight: 5,
            opacity: 0.7,
            dashArray: '8, 4'
          }
        }).addTo(map)

        // Make lines clickable
        layer.on('click', (e) => {
          L.DomEvent.stopPropagation(e)
          createRoute(e.latlng)
        })

        geoJsonLayers.push(layer)
      }
    })

    console.log(`✅ Loaded ${privateRoutes.value.features.length} LineStrings`)
  }

  // Click on map (not on LineString)
  map.on('click', (e) => {
    createRoute(e.latlng)
  })
}

watch([lat, lng], ([newLat, newLng]) => {
  if (map && userMarker) {
    userMarker.setLatLng([newLat, newLng])
  }
})

onMounted(async () => {
  await loadPrivateRoutes()
  if (isLoaded.value) {
    await initMap()
    startTracking()
  }
})

onBeforeUnmount(() => stopTracking())
</script>

<template>
  <div class="w-full h-screen relative">
    <div v-if="!isLoaded" class="absolute inset-0 flex items-center justify-center bg-white/70 z-50">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
        <span class="text-gray-700 font-medium">Loading campus routes...</span>
      </div>
    </div>
    <div id="map" class="w-full h-full"></div>

    <!-- Instructions overlay -->
    <div class="absolute top-4 left-4 bg-white/95 p-4 rounded-lg shadow-lg max-w-xs ">
      <h3 class="font-bold text-gray-800 mb-2">🗺️ Smart Router</h3>
      <ul class="text-sm text-gray-600 space-y-1">
        <li>🟢 <strong>You</strong> = Point A</li>
        <li>🔵 <strong>Blue markers</strong> = Facilities</li>
        <li>🔴 <strong>Dashed lines</strong> = Private paths</li>
        <li>👆 <strong>Click red line</strong> = Route via that path</li>
        <li>👆 <strong>Click map</strong> = Public roads only</li>
      </ul>
      <div v-if="routeInfo" class="mt-3 pt-3 border-t border-gray-200 text-xs font-medium text-blue-600 whitespace-pre-line">
        {{ routeInfo }}
      </div>
    </div>

    <!-- Navigation Instructions Panel -->
    <div v-if="showInstructions" class="absolute bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-80 bg-white rounded-lg shadow-xl z-30 max-h-96 overflow-hidden flex flex-col">
      <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-blue-50">
        <h3 class="font-bold text-gray-800">📍 Navigation</h3>
        <button @click="showInstructions = false" class="text-gray-500 hover:text-gray-700 text-xl leading-none">
          ×
        </button>
      </div>
      <div class="overflow-y-auto p-4 space-y-3">
        <div v-for="(instruction, idx) in navigationInstructions" :key="idx"
             class="flex items-start gap-3 p-2 rounded hover:bg-gray-50 transition">
          <span class="text-2xl">{{ instruction.icon }}</span>
          <div class="flex-1">
            <p class="text-sm font-medium text-gray-800">{{ instruction.text }}</p>
            <p class="text-xs text-gray-500">{{ instruction.distance }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
#map {
  height: 100vh;
  width: 100%;
}

.user-marker, .destination-marker, .facility-marker {
  background: none;
  border: none;
}

@media (max-width: 768px) {
  .absolute.top-4.left-4 {
    max-width: calc(100vw - 2rem);
    font-size: 0.875rem;
  }
}
</style>
