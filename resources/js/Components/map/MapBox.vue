<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { MagnifyingGlassIcon, XMarkIcon, MapPinIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import Modal from '../Modal.vue'
import TextInput from '../TextInput.vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet-routing-machine'
import 'leaflet-routing-machine/dist/leaflet-routing-machine.css'

const toast = useToast()

// --- Props ---
const props = defineProps({
  isAdmin: {
    type: Boolean,
    default: false
  },
  markers: {
    type: Array,
    default: () => []
  }
})

// --- Reactive variables ---
const map = ref(null)
const routingControl = ref(null)
const selectedLocation = ref('')
const profile = ref('walking')
const searchQuery = ref('')
const showSearchResults = ref(false)
const isSearching = ref(false)
const showMarkerModal = ref(false)
const showRouteModal = ref(false)
const clickAddModeEnabled = ref(false)
const drawRouteMode = ref(false)
const userLocation = ref(null)
const isSubmitting = ref(false)
const userMarker = ref(null)
const userHeading = ref(0)

// Drawing route variables
const drawnRoute = ref(null)
const routePoints = ref([])
const isDrawing = ref(false)

// New marker form data
const newMarker = ref({
  latitude: '',
  longitude: '',
  label: '',
  type: ''
})

// New route form data
const newRoute = ref({
  start_location: '',
  end_location: '',
  estimated_time: '',
  path_data: []
})

// Marker types
const markerTypes = [
  'Administration',
  'Academic',
  'Student Services',
  'Services',
  'Facility',
  'Other'
]

// Use markers from props
const locations = ref([])
const savedRoutes = ref([])

// Filtered locations based on search
const filteredLocations = ref([])

// Store marker instances
const markerInstances = ref([])

// Fetch markers from backend
const fetchMarkers = async () => {
  try {
    const response = await axios.get('/markers', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    locations.value = response.data.map(marker => ({
      id: marker.id,
      name: marker.label,
      lng: parseFloat(marker.longitude),
      lat: parseFloat(marker.latitude),
      department: marker.type
    }))

    if (map.value) {
      markerInstances.value.forEach(m => m.remove())
      markerInstances.value = []
      locations.value.forEach(location => {
        addMarkerToMap(location)
      })
    }
  } catch (error) {
    console.error('Error fetching markers:', error)
    toast.error('Failed to load markers')
  }
}

// Fetch saved routes from backend
const fetchRoutes = async () => {
  try {
    const response = await axios.get('/routes', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    savedRoutes.value = response.data

    // Display routes on map
    if (map.value) {
      savedRoutes.value.forEach(route => {
        displayRoute(route)
      })
    }
  } catch (error) {
    console.error('Error fetching routes:', error)
  }
}

// Display a saved route on the map
const displayRoute = (route) => {
  if (!route.path_data || route.path_data.length === 0) return

  const pathCoordinates = route.path_data.map(point => [point.lat, point.lng])

  const polyline = L.polyline(pathCoordinates, {
    color: '#3B82F6',
    weight: 4,
    opacity: 0.7
  }).addTo(map.value)

  polyline.bindPopup(`
    <div class="p-2">
      <h3 class="font-bold text-sm">${route.start_location} → ${route.end_location}</h3>
      <p class="text-xs text-gray-600">Est. Time: ${route.estimated_time}</p>
    </div>
  `)
}

// Initialize compass tracking
const startCompassTracking = () => {
  if ('ondeviceorientationabsolute' in window) {
    window.addEventListener('deviceorientationabsolute', handleOrientation, true)
  } else if ('ondeviceorientation' in window) {
    window.addEventListener('deviceorientation', handleOrientation, true)
  }
}

const handleOrientation = (event) => {
  let heading = event.alpha || event.webkitCompassHeading || 0

  if (event.webkitCompassHeading) {
    heading = event.webkitCompassHeading
  } else if (event.alpha) {
    heading = 360 - event.alpha
  }

  userHeading.value = heading

  if (userMarker.value) {
    const markerElement = userMarker.value.getElement()
    if (markerElement) {
      const svg = markerElement.querySelector('svg')
      if (svg) {
        svg.style.transform = `rotate(${heading}deg)`
      }
    }
  }
}

// Update user marker
const updateUserMarker = () => {
  if (!userLocation.value || !map.value) return

  try {
    if (userMarker.value && typeof userMarker.value.setLatLng === 'function') {
      userMarker.value.setLatLng([userLocation.value.lat, userLocation.value.lng])
      const markerElement = userMarker.value.getElement()
      if (markerElement) {
        const svg = markerElement.querySelector('svg')
        if (svg) {
          svg.style.transform = `rotate(${userHeading.value}deg)`
        }
      }
    } else {
      userMarker.value = L.marker([userLocation.value.lat, userLocation.value.lng], {
        title: 'You',
        icon: L.divIcon({
          className: 'user-marker',
          html: `<div style="background: #4CAF50; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="white" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transform: rotate(${userHeading.value}deg); transition: transform 0.3s ease;"><polygon points="12 2 19 21 12 17 5 21 12 2"/></svg></div>`,
          iconSize: [24, 24],
          iconAnchor: [12, 12]
        })
      }).addTo(map.value)

      const popupContent = `<div style="font-size: 13px; color: #1f2937; font-weight: 500;">You</div>`
      userMarker.value.bindPopup(popupContent, { offset: [0, -12], closeButton: false })
    }
  } catch (error) {
    console.error('Error updating user marker:', error)
    userMarker.value = null
  }
}

onMounted(() => {
  // Initialize Leaflet map
  map.value = L.map('map').setView([8.16953, 126.00306], 16)

  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map.value)

  // Get user location
  getUserLocation()
  startCompassTracking()

  // Fetch markers and routes
  fetchMarkers()
  fetchRoutes()

  // Admin: Enable click to add markers
  if (props.isAdmin) {
    map.value.on('click', handleMapClick)
  }
})

// Get user current location
const getUserLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        userLocation.value = {
          lng: position.coords.longitude,
          lat: position.coords.latitude
        }
        updateUserMarker()
      },
      (error) => {
        console.error('Geolocation error:', error)
      }
    )
  }
}

// Add marker to map
const addMarkerToMap = (location) => {
  const marker = L.marker([location.lat, location.lng], {
    icon: L.icon({
      iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34]
    })
  })
    .addTo(map.value)
    .bindPopup(`
      <div class="p-2">
        <h3 class="font-bold text-sm">${location.name}</h3>
        <p class="text-xs text-gray-600">${location.department}</p>
      </div>
    `)

  markerInstances.value.push(marker)

  if (!props.isAdmin) {
    marker.on('click', () => {
      selectedLocation.value = location
      routeToLocation()
    })
  }
}

// Handle map click (Admin only)
const handleMapClick = (e) => {
  if (!props.isAdmin) return

  if (clickAddModeEnabled.value) {
    newMarker.value.longitude = e.latlng.lng.toFixed(6)
    newMarker.value.latitude = e.latlng.lat.toFixed(6)
    showMarkerModal.value = true
  }

  if (drawRouteMode.value && isDrawing.value) {
    addRoutePoint(e.latlng)
  }
}

// Toggle click-add mode
const toggleClickAddMode = () => {
  clickAddModeEnabled.value = !clickAddModeEnabled.value
  if (clickAddModeEnabled.value) {
    drawRouteMode.value = false
    map.value.getContainer().style.cursor = 'crosshair'
    toast.info('Click on the map to add a new location')
  } else {
    map.value.getContainer().style.cursor = ''
  }
}

// Toggle draw route mode
const toggleDrawRouteMode = () => {
  drawRouteMode.value = !drawRouteMode.value
  if (drawRouteMode.value) {
    clickAddModeEnabled.value = false
    startDrawingRoute()
    toast.info('Click on map to draw route path')
  } else {
    cancelDrawingRoute()
  }
}

// Start drawing route
const startDrawingRoute = () => {
  isDrawing.value = true
  routePoints.value = []

  if (drawnRoute.value) {
    drawnRoute.value.remove()
  }

  map.value.getContainer().style.cursor = 'crosshair'
}

// Add point to route
const addRoutePoint = (latlng) => {
  routePoints.value.push(latlng)

  if (drawnRoute.value) {
    drawnRoute.value.remove()
  }

  drawnRoute.value = L.polyline(routePoints.value, {
    color: '#EF4444',
    weight: 4,
    opacity: 0.7,
    dashArray: '10, 10'
  }).addTo(map.value)

  L.circleMarker(latlng, {
    radius: 5,
    fillColor: '#EF4444',
    color: '#fff',
    weight: 2,
    fillOpacity: 1
  }).addTo(map.value)
}

// Finish drawing route
const finishDrawingRoute = () => {
  if (routePoints.value.length < 2) {
    toast.error('Route must have at least 2 points')
    return
  }

  isDrawing.value = false
  drawRouteMode.value = false
  map.value.getContainer().style.cursor = ''

  newRoute.value.path_data = routePoints.value.map(point => ({
    lat: point.lat,
    lng: point.lng
  }))

  showRouteModal.value = true
}

// Cancel drawing route
const cancelDrawingRoute = () => {
  isDrawing.value = false
  drawRouteMode.value = false
  routePoints.value = []

  if (drawnRoute.value) {
    drawnRoute.value.remove()
    drawnRoute.value = null
  }

  map.value.getContainer().style.cursor = ''
}

// Submit new marker
const submit = async () => {
  if (isSubmitting.value) return

  if (!newMarker.value.label || !newMarker.value.type) {
    toast.error('Please fill in all required fields')
    return
  }

  isSubmitting.value = true

  try {
    const response = await axios.post('/markers', {
      latitude: newMarker.value.latitude,
      longitude: newMarker.value.longitude,
      label: newMarker.value.label,
      type: newMarker.value.type
    })

    toast.success('Marker added successfully!')

    const newLoc = {
      id: response.data.id,
      name: newMarker.value.label,
      lng: parseFloat(newMarker.value.longitude),
      lat: parseFloat(newMarker.value.latitude),
      department: newMarker.value.type
    }

    locations.value.push(newLoc)
    addMarkerToMap(newLoc)

    closeMarkerModal()
    clickAddModeEnabled.value = false
    map.value.getContainer().style.cursor = ''

  } catch (error) {
    console.error('Error saving marker:', error)
    toast.error(error.response?.data?.message || 'Failed to save marker')
  } finally {
    isSubmitting.value = false
  }
}

// Submit new route
const submitRoute = async () => {
  if (isSubmitting.value) return

  if (!newRoute.value.start_location || !newRoute.value.end_location || !newRoute.value.estimated_time) {
    toast.error('Please fill in all required fields')
    return
  }

  isSubmitting.value = true

  try {
    const response = await axios.post('/routes', {
      start_location: newRoute.value.start_location,
      end_location: newRoute.value.end_location,
      estimated_time: newRoute.value.estimated_time,
      path_data: newRoute.value.path_data
    })

    toast.success('Route saved successfully!')

    savedRoutes.value.push(response.data)
    displayRoute(response.data)

    closeRouteModal()

  } catch (error) {
    console.error('Error saving route:', error)
    toast.error(error.response?.data?.message || 'Failed to save route')
  } finally {
    isSubmitting.value = false
  }
}

// Close modals
const closeMarkerModal = () => {
  showMarkerModal.value = false
  newMarker.value = { latitude: '', longitude: '', label: '', type: '' }
}

const closeRouteModal = () => {
  showRouteModal.value = false
  newRoute.value = { start_location: '', end_location: '', estimated_time: '', path_data: [] }

  if (drawnRoute.value) {
    drawnRoute.value.remove()
    drawnRoute.value = null
  }

  routePoints.value = []
}

// Watch for search query
watch(searchQuery, (query) => {
  if (query.length > 0) {
    isSearching.value = true
    filteredLocations.value = locations.value.filter(loc =>
      loc.name.toLowerCase().includes(query.toLowerCase()) ||
      loc.department.toLowerCase().includes(query.toLowerCase())
    )
    showSearchResults.value = true
  } else {
    showSearchResults.value = false
    filteredLocations.value = []
  }
  isSearching.value = false
})

// Handle routing
const routeToLocation = () => {
  if (!selectedLocation.value || !userLocation.value) return

  if (routingControl.value) {
    map.value.removeControl(routingControl.value)
  }

  routingControl.value = L.Routing.control({
    waypoints: [
      L.latLng(userLocation.value.lat, userLocation.value.lng),
      L.latLng(selectedLocation.value.lat, selectedLocation.value.lng)
    ],
    routeWhileDragging: false,
    show: false
  }).addTo(map.value)
}

// Handle search result selection
const selectSearchResult = (location) => {
  selectedLocation.value = location
  searchQuery.value = location.name
  showSearchResults.value = false

  map.value.flyTo([location.lat, location.lng], 17)

  if (!props.isAdmin) {
    routeToLocation()
  }
}

// Clear search
const clearSearch = () => {
  searchQuery.value = ''
  selectedLocation.value = ''
  showSearchResults.value = false

  if (routingControl.value) {
    map.value.removeControl(routingControl.value)
    routingControl.value = null
  }
}
</script>

<template>
  <div class="relative w-full h-screen">
    <!-- Map Container -->
    <div id="map" class="w-full h-full"></div>

    <!-- Search Bar -->
    <div class="absolute top-4 left-1/2 transform -translate-x-1/2 z-[1000] w-full max-w-md px-4">
      <div class="bg-white rounded-lg shadow-lg">
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
          </div>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search for rooms, offices, or buildings..."
            class="w-full pl-10 pr-10 py-3 border-0 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
          />
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="absolute inset-y-0 right-0 pr-3 flex items-center"
          >
            <XMarkIcon class="h-5 w-5 text-gray-400 hover:text-gray-600" />
          </button>
        </div>

        <div v-if="showSearchResults" class="mt-1 max-h-60 overflow-y-auto border-t border-gray-200">
          <div v-if="isSearching" class="p-4 text-center text-gray-500 text-sm">Searching...</div>
          <div v-else-if="filteredLocations.length === 0" class="p-4 text-center text-gray-500 text-sm">
            No locations found
          </div>
          <div v-else>
            <button
              v-for="location in filteredLocations"
              :key="location.id"
              @click="selectSearchResult(location)"
              class="w-full text-left px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 transition-colors"
            >
              <div class="font-medium text-sm text-gray-900">{{ location.name }}</div>
              <div class="text-xs text-gray-500 mt-1">{{ location.department }}</div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Admin Controls -->
    <div v-if="isAdmin" class="absolute top-4 right-4 z-[1000] flex gap-2">
      <button
        @click="toggleClickAddMode"
        :class="[
          'flex items-center gap-2 px-4 py-2 rounded-lg shadow-lg font-medium text-sm transition-all',
          clickAddModeEnabled ? 'bg-green-600 text-white' : 'bg-white text-gray-700'
        ]"
      >
        <MapPinIcon class="h-5 w-5" />
        <span class="hidden sm:inline">{{ clickAddModeEnabled ? 'Adding...' : 'Add Location' }}</span>
      </button>

      <button
        @click="toggleDrawRouteMode"
        :class="[
          'flex items-center gap-2 px-4 py-2 rounded-lg shadow-lg font-medium text-sm transition-all',
          drawRouteMode ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'
        ]"
      >
        <PencilIcon class="h-5 w-5" />
        <span class="hidden sm:inline">{{ drawRouteMode ? 'Drawing...' : 'Draw Route' }}</span>
      </button>

      <button
        v-if="isDrawing"
        @click="finishDrawingRoute"
        class="flex items-center gap-2 px-4 py-2 rounded-lg shadow-lg font-medium text-sm bg-green-600 text-white"
      >
        Finish Route
      </button>

      <button
        v-if="isDrawing"
        @click="cancelDrawingRoute"
        class="flex items-center gap-2 px-4 py-2 rounded-lg shadow-lg font-medium text-sm bg-red-600 text-white"
      >
        Cancel
      </button>
    </div>

    <!-- Add Marker Modal -->
    <Modal :show="showMarkerModal" @close="closeMarkerModal">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-auto">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Create New Marker</h3>
        </div>

        <div class="px-6 py-5">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-900 mb-2">Latitude</label>
              <TextInput v-model="newMarker.latitude" type="text" readonly class="w-full" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-900 mb-2">Longitude</label>
              <TextInput v-model="newMarker.longitude" type="text" readonly class="w-full" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-900 mb-2">Location Name</label>
              <TextInput v-model="newMarker.label" type="text" placeholder="e.g., Main Library" class="w-full" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-900 mb-2">Type</label>
              <select v-model="newMarker.type" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <option value="" disabled>Select a type</option>
                <option v-for="type in markerTypes" :key="type" :value="type">{{ type }}</option>
              </select>
            </div>
          </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
          <button @click="closeMarkerModal" class="px-5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md">
            Cancel
          </button>
          <button @click="submit" :disabled="!newMarker.label || !newMarker.type || isSubmitting" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 rounded-md disabled:bg-gray-400">
            {{ isSubmitting ? 'Saving...' : 'Save Marker' }}
          </button>
        </div>
      </div>
    </Modal>

    <!-- Add Route Modal -->
    <Modal :show="showRouteModal" @close="closeRouteModal">
      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl mx-auto">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Save Route</h3>
        </div>

        <div class="px-6 py-5">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-900 mb-2">Start Location</label>
              <TextInput v-model="newRoute.start_location" type="text" placeholder="e.g., Main Gate" class="w-full" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-900 mb-2">End Location</label>
              <TextInput v-model="newRoute.end_location" type="text" placeholder="e.g., Library" class="w-full" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-900 mb-2">Estimated Time</label>
              <TextInput v-model="newRoute.estimated_time" type="text" placeholder="e.g., 5 minutes" class="w-full" />
            </div>

            <div class="text-sm text-gray-600">
              Route points: {{ routePoints.length }}
            </div>
          </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
          <button @click="closeRouteModal" class="px-5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md">
            Cancel
          </button>
          <button @click="submitRoute" :disabled="!newRoute.start_location || !newRoute.end_location || !newRoute.estimated_time || isSubmitting" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 rounded-md disabled:bg-gray-400">
            {{ isSubmitting ? 'Saving...' : 'Save Route' }}
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<style scoped>
.max-h-60::-webkit-scrollbar {
  width: 6px;
}

.max-h-60::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.max-h-60::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}
</style>
