<script setup>
import { ref, onMounted, watch, computed, onBeforeUnmount, toRefs } from 'vue'
import { MagnifyingGlassIcon, XMarkIcon, UserCircleIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import Modal from '../Modal.vue'
import { useReactiveStore } from '../../store/reactives/reactive'
import { useToast } from 'vue-toastification'
import { useMapRouting } from '../../composables/useMapRouting'
import { useGuestTracking } from '../../composables/useGuestTracking'
import { useFacilityMarkers } from '../../composables/useFacilityMarkers'

// Props
const props = defineProps({
  facilities: {
    type: Array,
    default: () => []
  },
  notes: {
    type: Array,
    default: () => []
  },
  polygons: {
    type: Array,
    default: () => []
  }
})

const toast = useToast()
const react = useReactiveStore()
const {
  isSearching,
  showSearchResults,
  showGuestModal,
  isGuestInfoComplete,
  showInstructions,
  isReviewModalOpen,
  isSavingFeedback,
  map,
  searchQuery,
  destinationMarker,
  routePolyline,
  routeInfo,
  privateRoutes,
  guestStep,
  selectedMarker,
  feedback,
  message,
  filteredLocations,
  selectedLocation,
  geoJsonLayers,
  navigationInstructions,
} = toRefs(react)

// Use composables
const routingUtils = useMapRouting()
const tracking = useGuestTracking()
const { addFacilityMarker, displayPolygons, markerTypeIcons, facilityMarkers, polygonLayers } = useFacilityMarkers()

const guestInfo = ref({
  id: null,
  nickname: '',
  role: ''
})
const sessionCheckInterval = ref(null)
const transportMode = ref('walking')
const lastRouteCalculationPosition = ref(null) // Track last position where route was calculated
const navigationInstructionsContainer = ref(null) // Reference to instructions container

const transportModes = [
  { value: 'walking', label: 'Walking', icon: '🚶', speed: 5 },
  { value: 'riding', label: 'Riding', icon: '🚴', speed: 15 },
  { value: 'driving', label: 'Driving', icon: '🚗', speed: 40 }
]

const noteMarkers = ref([])

const guestRoles = [
  { value: 'student', label: 'Student', icon: '🎓' },
  { value: 'faculty', label: 'Faculty', icon: '👨‍🏫' },
  { value: 'visitor', label: 'Visitor', icon: '👤' },
]

const debounce = (func, delay) => {
  let timeoutId
  return (...args) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => func(...args), delay)
  }
}

const logSearchToBackend = async (query) => {
  if (!query.trim() || !guestInfo.value.id) return
  try {
    await axios.post('/search-logs', {
      guest_id: guestInfo.value.id,
      query: query.trim(),
      search_at: new Date().toISOString(),
    })
    console.log('✅ Search logged successfully')
  } catch (error) {
    console.error('Failed to log search:', error)
  }
}

const debouncedLogSearch = debounce(logSearchToBackend, 500)

// Watch for navigation instructions updates and scroll to top
watch(navigationInstructions, () => {
  if (navigationInstructionsContainer.value) {
    // Scroll to top when instructions update
    navigationInstructionsContainer.value.scrollTop = 0
  }
}, { deep: true })

watch(searchQuery, (query) => {
  if (query.length > 0) {
    debouncedLogSearch(query)
  }

  if (query.length > 0) {
    isSearching.value = true
    filteredLocations.value = locations.value.filter(loc =>
      loc.name.toLowerCase().includes(query.toLowerCase()) ||
      loc.department.toLowerCase().includes(query.toLowerCase()) ||
      (loc.category && loc.category.toLowerCase().includes(query.toLowerCase()))
    )
    showSearchResults.value = true
  } else {
    showSearchResults.value = false
    filteredLocations.value = []
  }
  isSearching.value = false
})

const startSessionChecker = () => {
  sessionCheckInterval.value = setInterval(() => {
    const savedGuestInfo = sessionStorage.getItem('guestInfo')
    if (savedGuestInfo) {
      try {
        const parsed = JSON.parse(savedGuestInfo)
        if (parsed.expiresAt && Date.now() > parsed.expiresAt) {
          console.log('⏱️ Session expired, reloading page...')
          clearSessionAndReload()
        }
      } catch (error) {
        console.error('Error checking session:', error)
        clearSessionAndReload()
      }
    }
  }, 60000)
}

const clearSessionAndReload = () => {
  sessionStorage.removeItem('guestInfo')
  window.location.reload()
}

const loadGuestInfoFromSession = () => {
  const savedGuestInfo = sessionStorage.getItem('guestInfo')
  if (savedGuestInfo) {
    try {
      const parsed = JSON.parse(savedGuestInfo)
      if (parsed.expiresAt && Date.now() > parsed.expiresAt) {
        console.log('⏱️ Session expired, clearing stored data')
        sessionStorage.removeItem('guestInfo')
        return false
      }
      guestInfo.value = parsed
      isGuestInfoComplete.value = true
      showGuestModal.value = false
      startSessionChecker()
      return true
    } catch (error) {
      console.error('Error parsing guest info:', error)
      sessionStorage.removeItem('guestInfo')
    }
  }
  return false
}

const locations = computed(() => {
  if (!props.facilities || !Array.isArray(props.facilities)) {
    console.warn('No facilities data')
    return []
  }

  return props.facilities
    .filter(facility => {
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
          marker_id: facility.marker.id,
          name: facility.name || 'Unnamed Facility',
          lng: parseFloat(facility.marker.longitude),
          lat: parseFloat(facility.marker.latitude),
          department: facility.marker?.type || 'Unknown',
          markerType: markerType,
          category: facility.category || 'General',
          description: facility.description || '',
          icon: markerTypeIcons[markerType] || markerTypeIcons.default,
          marker: facility.marker
        }
      } catch (error) {
        console.error('Error processing facility:', facility, error)
        return null
      }
    })
    .filter(location => location !== null)
})

const goToStep2 = () => {
  if (!guestInfo.value.nickname.trim()) {
    toast.error('Please enter your nickname')
    return
  }
  guestStep.value = 2
}

const backToStep1 = () => {
  guestStep.value = 1
}

const saveGuestInfo = async () => {
  if (!guestInfo.value.nickname || !guestInfo.value.role) {
    toast.error('Please complete all steps')
    return
  }

  try {
    const response = await axios.post('/guests', {
      name: guestInfo.value.nickname,
      role: guestInfo.value.role,
    })

    const data = response.data
    guestInfo.value.id = data.id

    const guestData = {
      ...guestInfo.value,
      expiresAt: Date.now() + (24 * 60 * 60 * 1000)
    }

    sessionStorage.setItem('guestInfo', JSON.stringify(guestData))
    isGuestInfoComplete.value = true
    showGuestModal.value = false

    toast.success(`Welcome, ${guestInfo.value.nickname}!`)
    startSessionChecker()

    if (!map.value) {
      await initializeMap()
    } else {
      if (tracking.userLocation.value) {
        tracking.updateUserMarker(guestInfo.value, map.value, handleLocationUpdate)
      } else {
        tracking.startTracking(guestInfo.value, map.value, handleLocationUpdate)
      }
    }
  } catch (error) {
    console.error('Error saving guest info:', error)
    toast.error(error.response?.data?.message || 'Failed to save guest information')
  }
}

const calculateETA = (distanceMeters) => {
  const currentMode = transportModes.find(m => m.value === transportMode.value)
  const speedKmH = currentMode ? currentMode.speed : 5
  const speedMS = speedKmH * 1000 / 3600
  const timeSeconds = distanceMeters / speedMS
  const timeMinutes = Math.ceil(timeSeconds / 60)

  const now = new Date()
  const eta = new Date(now.getTime() + timeSeconds * 1000)

  return {
    minutes: timeMinutes,
    arrivalTime: eta.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
  }
}

const generateInstructions = (route, isPrivatePath) => {
  // Use enhanced instruction generator from composable
  return routingUtils.generateEnhancedInstructions(route, isPrivatePath)
}

const createRoute = async (clickLatLng, location = null) => {
  if (!tracking.userLocation.value) {
    toast.error('Waiting for GPS location...')
    return
  }

  // Clear existing route safely
  if (routePolyline.value && map.value) {
    try {
      if (map.value.hasLayer(routePolyline.value)) {
        map.value.removeLayer(routePolyline.value)
      }
    } catch (e) {
      console.warn('Error removing route polyline:', e)
    }
    routePolyline.value = null
  }
  if (destinationMarker.value && map.value) {
    try {
      if (map.value.hasLayer(destinationMarker.value)) {
        map.value.removeLayer(destinationMarker.value)
      }
    } catch (e) {
      console.warn('Error removing destination marker:', e)
    }
    destinationMarker.value = null
  }
  if (map.value) {
    map.value.closePopup()
  }

  const userPos = { lat: tracking.userLocation.value.lat, lng: tracking.userLocation.value.lng }
  const destPos = { lat: clickLatLng.lat, lng: clickLatLng.lng }

  // Store the position where we're calculating this route from
  lastRouteCalculationPosition.value = { lat: userPos.lat, lng: userPos.lng }

  if (location) {
    selectedLocation.value = location
  }

  console.log('🔍 Calculating optimal route...')

  // Check if user clicked ON a campus path (within 30m)
  if (privateRoutes.value && privateRoutes.value.features) {
    const clickedLineString = routingUtils.findClickedLineString(destPos, privateRoutes.value.features, 30)

    if (clickedLineString) {
      console.log('🎯 User clicked on a campus path! Using hybrid route...')

      const lineCoords = clickedLineString.feature.geometry.coordinates
      const nearestPoint = clickedLineString.point

      // Get the entry point of the LineString
      const lineStart = {
        lat: lineCoords[0][1],
        lng: lineCoords[0][0]
      }

      // Step 1: Public route from user to campus path start
      const publicToLine = await routingUtils.getPublicRoute(userPos, lineStart, transportMode.value)
      let fullRoute = []

      if (publicToLine && publicToLine.length > 0) {
        fullRoute = [...publicToLine]
      } else {
        fullRoute = [userPos, lineStart]
      }

      // Step 2: Follow the campus path to the clicked point
      for (let i = 0; i <= nearestPoint.index; i++) {
        const [lng, lat] = lineCoords[i]
        fullRoute.push({ lat, lng })
      }

      drawRouteOnMap(fullRoute, 'hybrid', true)
      console.log('✅ Hybrid route to campus path completed')
      return
    }
  }

  // If no private routes available, use public route only
  if (!privateRoutes.value || !privateRoutes.value.features || privateRoutes.value.features.length === 0) {
    console.warn('⚠️ No campus paths available, using public route only')
    const publicRoute = await routingUtils.getPublicRoute(userPos, destPos, transportMode.value)
    if (publicRoute) {
      drawRouteOnMap(publicRoute, 'public', false)
    } else {
      drawRouteOnMap([userPos, destPos], 'direct', false)
    }
    return
  }

  // Build campus graph from private routes
  const campusGraph = routingUtils.buildCampusGraph(privateRoutes.value.features)

  if (campusGraph.size === 0) {
    console.warn('⚠️ Campus graph is empty, using public route only')
    const publicRoute = await routingUtils.getPublicRoute(userPos, destPos, transportMode.value)
    if (publicRoute) {
      drawRouteOnMap(publicRoute, 'public', false)
    } else {
      drawRouteOnMap([userPos, destPos], 'direct', false)
    }
    return
  }

  // Calculate all possible route options
  const routeOptions = []

  // Option 1: Public route only
  try {
    const publicRoute = await routingUtils.getPublicRoute(userPos, destPos, transportMode.value)
    if (publicRoute && publicRoute.length > 0) {
      const distance = routingUtils.calculateRouteDistance(publicRoute)
      routeOptions.push({
        type: 'public',
        route: publicRoute,
        distance: distance,
        isPrivate: false
      })
      console.log(`🟢 Public route: ${Math.round(distance)}m`)
    }
  } catch (error) {
    console.error('❌ Public route error:', error)
  }

  // Option 2: Private campus route (if both points are near campus)
  try {
    const startNodeId = routingUtils.findNearestNode(userPos, campusGraph, 100)
    const goalNodeId = routingUtils.findNearestNode(destPos, campusGraph, 100)

    if (startNodeId && goalNodeId) {
      console.log(`🔍 Trying A* from ${startNodeId} to ${goalNodeId}`)
      const privateRoute = routingUtils.findPathAStar(startNodeId, goalNodeId, campusGraph)

      if (privateRoute && privateRoute.length > 0) {
        const distance = routingUtils.calculateRouteDistance(privateRoute)
        routeOptions.push({
          type: 'private',
          route: privateRoute,
          distance: distance,
          isPrivate: true
        })
        console.log(`🔴 Campus route: ${Math.round(distance)}m`)
      }
    }
  } catch (error) {
    console.error('❌ Campus route error:', error)
  }

  // Option 3: Hybrid routes (public to campus entrance + private path)
  try {
    const campusEntrances = routingUtils.findCampusEntrances(campusGraph)

    if (campusEntrances.length > 0) {
      const nearestEntrance = campusEntrances.reduce((nearest, entrance) => {
        const dist = routingUtils.calculateDistance(destPos.lat, destPos.lng, entrance.lat, entrance.lng)
        return dist < nearest.distance ? { entrance, distance: dist } : nearest
      }, { entrance: null, distance: Infinity })

      if (nearestEntrance.entrance && nearestEntrance.distance < 200) {
        const entrancePos = { lat: nearestEntrance.entrance.lat, lng: nearestEntrance.entrance.lng }
        const publicToEntrance = await routingUtils.getPublicRoute(userPos, entrancePos, transportMode.value)

        if (publicToEntrance && publicToEntrance.length > 0) {
          const goalNodeId = routingUtils.findNearestNode(destPos, campusGraph, 100)

          if (goalNodeId) {
            const privatePath = routingUtils.findPathAStar(nearestEntrance.entrance.id, goalNodeId, campusGraph)

            if (privatePath && privatePath.length > 0) {
              const hybridRoute = [...publicToEntrance, ...privatePath]
              const distance = routingUtils.calculateRouteDistance(hybridRoute)
              routeOptions.push({
                type: 'hybrid',
                route: hybridRoute,
                distance: distance,
                isPrivate: true
              })
              console.log(`🔵 Hybrid route: ${Math.round(distance)}m`)
            }
          }
        }
      }
    }
  } catch (error) {
    console.error('❌ Hybrid route error:', error)
  }

  // Select the best route (shortest distance)
  if (routeOptions.length === 0) {
    console.warn('⚠️ All routing failed, using direct line')
    drawRouteOnMap([userPos, destPos], 'direct', false)
    return
  }

  const bestRoute = routeOptions.reduce((best, current) =>
    current.distance < best.distance ? current : best
  )

  console.log(`✅ Selected: ${bestRoute.type.toUpperCase()} route (${Math.round(bestRoute.distance)}m)`)
  drawRouteOnMap(bestRoute.route, bestRoute.type, bestRoute.isPrivate)
}

const drawRouteOnMap = (route, routeType, isPrivatePath) => {
  if (!map.value) {
    console.warn('Cannot draw route: map not initialized')
    return
  }

  // Remove old route layers safely before adding new ones
  if (routePolyline.value) {
    try {
      if (map.value.hasLayer(routePolyline.value)) {
        map.value.removeLayer(routePolyline.value)
      }
    } catch (e) {
      console.warn('Error removing old route polyline:', e)
    }
    routePolyline.value = null
  }

  if (destinationMarker.value) {
    try {
      if (map.value.hasLayer(destinationMarker.value)) {
        map.value.removeLayer(destinationMarker.value)
      }
    } catch (e) {
      console.warn('Error removing old destination marker:', e)
    }
    destinationMarker.value = null
  }

  const routeCoords = route.map(p => [p.lat, p.lng])

  const routeColors = {
    public: '#4CAF50',
    private: '#FF6B6B',
    hybrid: '#2196F3',
    direct: '#9E9E9E'
  }

  routePolyline.value = L.polyline(routeCoords, {
    color: routeColors[routeType] || '#9E9E9E',
    weight: 6,
    opacity: 0.8,
    smoothFactor: 1.0
  }).addTo(map.value)

  const finalDest = route[route.length - 1]
  destinationMarker.value = L.marker([finalDest.lat, finalDest.lng], {
    title: 'Destination',
    icon: L.divIcon({
      className: 'destination-marker',
      html: '<div style="background: #FF5252; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
      iconSize: [24, 24],
      iconAnchor: [12, 12]
    })
  }).addTo(map.value)

  const totalDistance = routingUtils.calculateRouteDistance(route)
  const distanceText = totalDistance > 1000
    ? `${(totalDistance / 1000).toFixed(2)} km`
    : `${Math.round(totalDistance)} m`

  const eta = calculateETA(totalDistance)

  const routeLabels = {
    public: '🟢 Public Roads',
    private: '🔴 Campus Path',
    hybrid: '🔵 Public → Campus',
    direct: '⚪ Direct Line'
  }

  const currentMode = transportModes.find(m => m.value === transportMode.value)
  routeInfo.value = `${routeLabels[routeType]}\n${currentMode.icon} ${currentMode.label}\n📏 ${distanceText}\n⏱️ ${eta.minutes} min\n🕐 Arrive at ${eta.arrivalTime}`

  // Generate updated navigation instructions (use original route array with {lat, lng} objects)
  navigationInstructions.value = generateInstructions(route, isPrivatePath)
  showInstructions.value = true

  console.log(`🧭 Generated ${navigationInstructions.value.length} navigation instructions`)

  // Fit bounds to the new route with safeguards
  try {
    if (routePolyline.value && map.value.hasLayer(routePolyline.value)) {
      map.value.fitBounds(routePolyline.value.getBounds(), { padding: [50, 50] })
    }
  } catch (error) {
    console.warn('Error fitting bounds:', error)
  }

  toast.success(`Route via ${routeLabels[routeType]}`)
}

const deleteNote = async (noteId) => {
  try {
    await axios.delete(`/notes/${noteId}`)
    toast.success('Note deleted successfully!')

    const noteMarkerIndex = noteMarkers.value.findIndex(nm => nm.noteId === noteId)
    if (noteMarkerIndex !== -1 && map.value) {
      const marker = noteMarkers.value[noteMarkerIndex].marker
      if (marker && map.value.hasLayer(marker)) {
        map.value.removeLayer(marker)
      }
      noteMarkers.value.splice(noteMarkerIndex, 1)
    }
  } catch (error) {
    console.error('Error deleting note:', error)
    toast.error('Failed to delete note')
  }
}

const displayNotesOnMap = () => {
  // Clean up existing note markers
  noteMarkers.value.forEach(nm => {
    if (map.value && map.value.hasLayer && map.value.hasLayer(nm.marker)) {
      try {
        map.value.removeLayer(nm.marker)
      } catch (error) {
        console.warn('Error removing note marker:', error)
      }
    }
  })
  noteMarkers.value = []

  if (!props.notes || !Array.isArray(props.notes) || !map.value) return

  props.notes.forEach(note => {
    const facility = locations.value.find(loc => loc.id === note.marker_id)
    if (!facility) return

    const noteMarker = L.marker([facility.lat, facility.lng], {
      icon: L.divIcon({
        className: 'note-marker',
        html: `
          <div class="note-bubble">
            <button class="note-delete-btn" data-note-id="${note.id}" title="Delete note">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            <div class="note-content">${note.content}</div>
            <div class="note-arrow"></div>
          </div>
        `,
        iconSize: [200, 80],
        iconAnchor: [100, 100]
      }),
      zIndexOffset: 1000
    }).addTo(map.value)

    noteMarker.on('add', () => {
      setTimeout(() => {
        const deleteBtn = document.querySelector(`[data-note-id="${note.id}"]`)
        if (deleteBtn) {
          deleteBtn.addEventListener('click', (e) => {
            e.stopPropagation()
            if (confirm('Are you sure you want to delete this note?')) {
              deleteNote(note.id)
            }
          })
        }
      }, 100)
    })

    noteMarkers.value.push({
      noteId: note.id,
      marker: noteMarker
    })
  })

  console.log(`📝 Displayed ${noteMarkers.value.length} notes on map`)
}

watch(() => props.notes, () => {
  if (map.value) {
    displayNotesOnMap()
  }
}, { deep: true })

watch(() => props.polygons, () => {
  if (map.value) {
    displayPolygons(props.polygons, map.value)
  }
}, { deep: true, immediate: false })

const loadPrivateRoutes = async () => {
  try {
    const response = await axios.get('/routes/export/geojson-pub', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    privateRoutes.value = response.data

    if (map.value) {
      refreshPrivateRoutesOnMap()
    }
    console.log('✅ Loaded routes from database:', privateRoutes.value)
  } catch (error) {
    console.error('❌ Error loading routes from database:', error)
    toast.error('Failed to load campus paths')
  }
}

const refreshPrivateRoutesOnMap = () => {
  if (!map.value || !privateRoutes.value || !privateRoutes.value.features) {
    console.warn('Cannot refresh private routes: Map or data not ready')
    return
  }

  // Remove existing route layers
  geoJsonLayers.value.forEach(layer => {
    if (map.value && map.value.hasLayer && map.value.hasLayer(layer)) {
      try {
        map.value.removeLayer(layer)
      } catch (error) {
        console.warn('Error removing route layer:', error)
      }
    }
  })
  geoJsonLayers.value = []

  // Add new route layers with enhanced visibility
  privateRoutes.value.features.forEach((feature) => {
    if (feature.geometry && feature.geometry.type === 'LineString') {
      const layer = L.geoJSON(feature, {
        style: {
          color: '#FF6B6B',
          weight: 6,
          opacity: 0.9,
          dashArray: '10, 5',
          lineCap: 'round',
          lineJoin: 'round'
        }
      }).addTo(map.value)

      layer.on('click', (e) => {
        L.DomEvent.stopPropagation(e)
        createRoute(e.latlng)
      })

      geoJsonLayers.value.push(layer)
    }
  })

  console.log(`✅ Added ${privateRoutes.value.features.length} campus path layers`)
  toast.success('Campus paths updated in real-time!')
}

const handleLocationUpdate = (newLocation) => {
  // Only recalculate if there's an active destination
  if (selectedLocation.value) {
    // Check if we need to recalculate based on distance moved since last calculation
    if (lastRouteCalculationPosition.value) {
      const distanceMoved = routingUtils.calculateDistance(
        lastRouteCalculationPosition.value.lat,
        lastRouteCalculationPosition.value.lng,
        newLocation.lat,
        newLocation.lng
      )

      // Recalculate route only if moved more than 10 meters
      if (distanceMoved > 10) {
        console.log(`🔄 User moved ${Math.round(distanceMoved)}m since last route calculation, updating route...`)
        lastRouteCalculationPosition.value = { lat: newLocation.lat, lng: newLocation.lng }

        // Show a subtle notification about route update
        toast.info(`📍 Updating route from new position...`, { timeout: 2000 })

        // Recalculate route - this will update instructions automatically
        createRoute(L.latLng(selectedLocation.value.lat, selectedLocation.value.lng), selectedLocation.value)
      } else {
        console.log(`📍 User moved ${Math.round(distanceMoved)}m (< 10m threshold, keeping current route)`)
      }
    } else {
      // First location update with active destination, calculate initial route
      console.log(`🎯 First GPS position with active destination, calculating initial route...`)
      lastRouteCalculationPosition.value = { lat: newLocation.lat, lng: newLocation.lng }

      // Calculate route - this will generate initial instructions
      createRoute(L.latLng(selectedLocation.value.lat, selectedLocation.value.lng), selectedLocation.value)
    }
  }
}

const openReviewModal = (location) => {
  selectedMarker.value = location
  feedback.value = ''
  isReviewModalOpen.value = true
}

const initializeMap = async () => {
  try {
    await loadPrivateRoutes()

    // Define base map layers
    const standard = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    })

    const satellite = L.tileLayer(
      'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
      {
        maxZoom: 19,
        attribution: 'Tiles © Esri — Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
      }
    )

    // Satellite with labels overlay (optional enhancement)
    const satelliteLabels = L.tileLayer(
      'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
      {
        maxZoom: 19,
        attribution: ''
      }
    )

    // Initialize map with standard view
    map.value = L.map('map', {
      center: [8.169500, 126.001838],
      zoom: 17,
      layers: [standard],
      zoomControl: true
    })

    // Add layer control with base layers
    const baseMaps = {
      '🗺️ Standard': standard,
      '🛰️ Satellite': satellite,
      '🛰️ Satellite + Labels': L.layerGroup([satellite, satelliteLabels])
    }

    L.control.layers(baseMaps, null, { position: 'topright' }).addTo(map.value)

    map.value.whenReady(() => {
      console.log('✅ Map is fully loaded and ready')

      // Add facility markers
      if (locations.value.length > 0) {
        console.log(`📍 Adding ${locations.value.length} facility markers`)
        locations.value.forEach(location => {
          addFacilityMarker(location, map.value, createRoute, openReviewModal)
        })
      }

      // Display notes
      displayNotesOnMap()

      // Display polygons on map load
      if (props.polygons && props.polygons.length > 0) {
        displayPolygons(props.polygons, map.value)
      }

      // Add campus route layers with enhanced visibility
      if (privateRoutes.value && privateRoutes.value.features) {
        privateRoutes.value.features.forEach((feature) => {
          if (feature.geometry && feature.geometry.type === 'LineString') {
            const layer = L.geoJSON(feature, {
              style: {
                color: '#FF6B6B',
                weight: 6,
                opacity: 0.9,
                dashArray: '10, 5',
                lineCap: 'round',
                lineJoin: 'round'
              }
            }).addTo(map.value)

            layer.on('click', (e) => {
              L.DomEvent.stopPropagation(e)
              createRoute(e.latlng)
            })

            geoJsonLayers.value.push(layer)
          }
        })
      }

      // Map click handler for route creation
      map.value.on('click', (e) => {
        createRoute(e.latlng)
      })

      // Start GPS tracking after map is ready
      setTimeout(() => {
        tracking.startTracking(guestInfo.value, map.value, handleLocationUpdate)
      }, 500)
    })

  } catch (error) {
    console.error('Error initializing map:', error)
    toast.error('Failed to initialize map. Please refresh the page.')
  }
}

const selectSearchResult = (location) => {
  selectedLocation.value = location
  searchQuery.value = location.name
  showSearchResults.value = false
  createRoute(L.latLng(location.lat, location.lng), location)
  console.log(`🧭 Routing to ${location.name}`)
}

const clearSearch = () => {
  searchQuery.value = ''
  selectedLocation.value = null
  showSearchResults.value = false
  lastRouteCalculationPosition.value = null // Reset route calculation tracker

  if (routePolyline.value && map.value) {
    try {
      if (map.value.hasLayer(routePolyline.value)) {
        map.value.removeLayer(routePolyline.value)
      }
    } catch (e) {
      console.warn('Error removing route polyline:', e)
    }
    routePolyline.value = null
  }
  if (destinationMarker.value && map.value) {
    try {
      if (map.value.hasLayer(destinationMarker.value)) {
        map.value.removeLayer(destinationMarker.value)
      }
    } catch (e) {
      console.warn('Error removing destination marker:', e)
    }
    destinationMarker.value = null
  }

  routeInfo.value = null
  showInstructions.value = false
  console.log('🗑️ Route cleared')
}

const saveFeedback = async () => {
  if (!feedback.value.trim()) {
    toast.error('Please enter your feedback')
    return
  }

  if (!guestInfo.value.id) {
    toast.error('Guest information not found')
    return
  }

  isSavingFeedback.value = true

  try {
    const response = await axios.post('/create/feedback', {
      guest_id: guestInfo.value.id,
      marker_id: selectedMarker.value.marker.id,
      message: feedback.value.trim(),
    })
    message.value = response.data.message
    toast.success(message.value || 'Feedback saved successfully!')
    isReviewModalOpen.value = false
    feedback.value = ''
    selectedMarker.value = null
  } catch (error) {
    console.error('Error saving feedback:', error)
    toast.error('Failed to save feedback')
  } finally {
    isSavingFeedback.value = false
  }
}

const hasGuestInfo = loadGuestInfoFromSession()

onMounted(() => {
  console.log('📝 Notes:', props.notes)
  console.log('📐 Polygons:', props.polygons)

  if (hasGuestInfo) {
    initializeMap()
    console.log(`👋 Welcome back, ${guestInfo.value.nickname}!`)
  }

  // Listen for real-time updates
  window.Echo.channel('main-channel')
    .listen('.MainEvent', (e) => {
      if (e.type === "route") {
        loadPrivateRoutes()
        console.log('🔄 Route updated:', e.action)
      }
      if (e.type === "polygon") {
        axios.get('/facilities/polygons').then(response => {
          displayPolygons(response.data, map.value)
        })
        console.log('🔄 Polygon updated:', e.action)
      }
    })
})

onBeforeUnmount(() => {
  // Clean up tracking
  tracking.stopTracking(map.value)

  // Clear session checker
  if (sessionCheckInterval.value) {
    clearInterval(sessionCheckInterval.value)
  }

  // Clean up map layers safely
  if (map.value) {
    try {
      if (routePolyline.value && map.value.hasLayer(routePolyline.value)) {
        map.value.removeLayer(routePolyline.value)
      }
      if (destinationMarker.value && map.value.hasLayer(destinationMarker.value)) {
        map.value.removeLayer(destinationMarker.value)
      }

      geoJsonLayers.value.forEach(layer => {
        if (map.value.hasLayer(layer)) {
          map.value.removeLayer(layer)
        }
      })

      noteMarkers.value.forEach(nm => {
        if (nm.marker && map.value.hasLayer(nm.marker)) {
          map.value.removeLayer(nm.marker)
        }
      })
    } catch (error) {
      console.warn('Error during cleanup:', error)
    }
  }

  console.log('👋 Component unmounted, cleanup complete')
})
</script>

<template>
  <div class="relative w-full h-screen">
    <!-- Map Container -->
    <div
      id="map"
      :class="[
        'w-full h-full transition-all duration-300 z-0',
        !isGuestInfoComplete ? 'blur-sm pointer-events-none' : ''
      ]"
    ></div>

    <!-- Guest Info Modal -->
    <div v-if="showGuestModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[9999] p-4">
      <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-sm mx-auto">
        <!-- Step 1: Nickname -->
        <div v-if="guestStep === 1" class="p-6 sm:p-8">
          <div class="text-center mb-6 sm:mb-8">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Welcome to Campus Navigator</h2>
            <p class="text-xs sm:text-sm text-gray-500">Let's get started with your nickname</p>
          </div>

          <div class="space-y-4">
            <input
              v-model="guestInfo.nickname"
              type="text"
              placeholder="Enter your nickname"
              class="w-full px-4 py-3 text-sm sm:text-base text-gray-900 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
              maxlength="50"
              @keyup.enter="goToStep2"
            />

            <button
              @click="goToStep2"
              :disabled="!guestInfo.nickname.trim()"
              :class="[
                'w-full px-4 py-3 text-sm font-medium rounded-lg transition-all',
                guestInfo.nickname.trim()
                  ? 'bg-blue-600 hover:bg-blue-700 text-white'
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed'
              ]"
            >
              Continue
            </button>
          </div>
        </div>

        <!-- Step 2: Role Selection -->
        <div v-if="guestStep === 2" class="p-6 sm:p-8">
          <div class="text-center mb-6 sm:mb-8">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Select Your Role</h2>
            <p class="text-xs sm:text-sm text-gray-500">How are you visiting our campus?</p>
          </div>

          <div class="space-y-3 mb-6">
            <button
              v-for="role in guestRoles"
              :key="role.value"
              @click="guestInfo.role = role.value"
              :class="[
                'w-full flex items-center gap-3 px-4 py-3 rounded-lg border-2 transition-all',
                guestInfo.role === role.value
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-200 hover:border-gray-300'
              ]"
            >
              <span class="text-xl sm:text-2xl">{{ role.icon }}</span>
              <span class="text-sm font-medium text-gray-900">{{ role.label }}</span>
            </button>
          </div>

          <div class="space-y-2">
            <button
              @click="saveGuestInfo"
              :disabled="!guestInfo.role"
              :class="[
                'w-full px-4 py-3 text-sm font-medium rounded-lg transition-all',
                guestInfo.role
                  ? 'bg-blue-600 hover:bg-blue-700 text-white'
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed'
              ]"
            >
              Start Exploring
            </button>

            <button
              @click="backToStep1"
              class="w-full px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
            >
              Back
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Search Bar -->
    <div
      v-if="isGuestInfoComplete"
      class="absolute top-4 left-4 right-4 sm:left-1/2 sm:right-auto sm:transform sm:-translate-x-1/2 z-10 sm:w-full sm:max-w-md sm:px-4"
    >
      <div class="bg-white rounded-lg shadow-lg">
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon class="h-4 w-4 sm:h-5 sm:w-5 text-gray-400" />
          </div>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search for facilities..."
            class="w-full pl-9 sm:pl-10 pr-10 py-2.5 sm:py-3 border-0 rounded-lg focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
          />
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="absolute inset-y-0 right-0 pr-3 flex items-center"
          >
            <XMarkIcon class="h-4 w-4 sm:h-5 sm:w-5 text-gray-400 hover:text-gray-600" />
          </button>
        </div>

        <div
          v-if="showSearchResults"
          class="mt-1 max-h-48 sm:max-h-60 overflow-y-auto border-t border-gray-200"
        >
          <div v-if="isSearching" class="p-3 sm:p-4 text-center text-gray-500 text-xs sm:text-sm">
            Searching...
          </div>
          <div v-else-if="filteredLocations.length === 0" class="p-3 sm:p-4 text-center text-gray-500 text-xs sm:text-sm">
            No facilities found
          </div>
          <div v-else>
            <button
              v-for="location in filteredLocations"
              :key="location.id"
              @click="selectSearchResult(location)"
              class="w-full text-left px-3 sm:px-4 py-2.5 sm:py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 transition-colors"
            >
              <div class="flex items-center gap-2">
                <span class="text-lg sm:text-xl">{{ location.icon }}</span>
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-xs sm:text-sm text-gray-900 truncate">{{ location.name }}</div>
                  <div class="text-xs text-gray-500 mt-0.5 truncate">{{ location.markerType }} • {{ location.category }}</div>
                </div>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Transport Mode Selector -->
    <div
      v-if="isGuestInfoComplete"
      class="absolute bottom-4 left-4 z-10"
    >
      <div class="bg-white rounded-lg shadow-lg p-1.5 sm:p-2 flex gap-1 sm:gap-2 flex-wrap max-w-[calc(100vw-6rem)] sm:max-w-none">
        <button
          v-for="mode in transportModes"
          :key="mode.value"
          @click="transportMode = mode.value"
          :class="[
            'px-2.5 py-2 sm:px-3 sm:py-2 rounded-md text-xs sm:text-sm font-medium transition-all flex items-center justify-center gap-1 min-w-[44px]',
            transportMode === mode.value
              ? 'bg-blue-600 text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
          :title="mode.label"
        >
          <span class="text-base sm:text-lg">{{ mode.icon }}</span>
          <span class="hidden lg:inline text-xs">{{ mode.label }}</span>
        </button>
      </div>
    </div>

    <!-- User Profile Button -->
    <div
      v-if="isGuestInfoComplete"
      class="absolute top-4 right-4 z-10"
    >
      <div class="flex items-center gap-2 bg-white px-3 py-2 sm:px-4 sm:py-2 rounded-lg shadow-lg">
        <UserCircleIcon class="h-5 w-5 text-blue-600" />
        <div class="text-left hidden sm:block">
          <div class="text-xs font-semibold text-gray-900 truncate max-w-[120px]">{{ guestInfo.nickname }}</div>
          <div class="text-xs text-gray-500">{{ guestInfo.role }}</div>
        </div>
      </div>
    </div>

    <!-- Route Info Panel -->
    <div
      v-if="isGuestInfoComplete && routeInfo"
      class="hidden sm:block absolute top-20 left-4 bg-white rounded-lg shadow-lg w-64 sm:w-72 lg:max-w-xs z-10 border border-gray-200"
    >
      <div class="px-3 py-2 border-b border-gray-200 bg-gray-900 flex justify-between items-center">
        <h3 class="font-semibold text-white text-xs sm:text-sm">Route Info</h3>
        <button
          @click="routeInfo = null"
          class="text-gray-400 hover:text-white transition text-lg leading-none w-5 h-5 flex items-center justify-center sm:hidden"
        >
          ×
        </button>
      </div>
      <div class="px-3 py-2 text-xs text-gray-700 leading-relaxed whitespace-pre-line max-h-32 sm:max-h-40 overflow-y-auto">
        {{ routeInfo }}
      </div>
    </div>

    <!-- Navigation Instructions Panel -->
    <div
      v-if="showInstructions"
      class="absolute left-4 right-4 bottom-20 sm:left-auto sm:right-4 sm:bottom-4 sm:w-72 bg-white rounded-lg shadow-lg z-20 max-h-52 sm:max-h-72 overflow-hidden flex flex-col border border-gray-200"
    >
      <div class="px-3 py-2 border-b border-gray-200 flex justify-between items-center bg-gray-900">
        <h3 class="font-semibold text-white text-xs sm:text-sm">Navigation</h3>
        <button
          @click="showInstructions = false"
          class="text-gray-400 hover:text-white transition text-lg leading-none w-5 h-5 flex items-center justify-center"
        >
          ×
        </button>
      </div>
      <div ref="navigationInstructionsContainer" class="overflow-y-auto">
        <div
          v-for="(instruction, idx) in navigationInstructions"
          :key="idx"
          class="flex items-start gap-2 px-3 py-2 border-b border-gray-100 last:border-b-0 hover:bg-gray-50 transition"
        >
          <span class="text-sm sm:text-base mt-0.5">{{ instruction.icon }}</span>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-gray-900 leading-snug">{{ instruction.text }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ instruction.distance }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Review Modal -->
    <Modal :show="isReviewModalOpen" @close="isReviewModalOpen = false">
      <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-md mx-auto">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <span class="text-xl sm:text-2xl">📝</span>
              <h3 class="text-base sm:text-lg font-bold text-gray-900">Feedback</h3>
            </div>
            <button @click="isReviewModalOpen = false" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <div>
            <label class="block text-xs sm:text-sm font-semibold text-gray-900 mb-2">
              Your Feedback
            </label>
            <textarea
              v-model="feedback"
              placeholder="Write your feedback about this location..."
              rows="4"
              class="w-full px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all resize-none"
              maxlength="500"
            ></textarea>
            <p class="text-xs text-gray-500 mt-1">
              {{ feedback.length }}/500 characters
            </p>
          </div>
        </div>
        <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gray-50 border-t border-gray-200 flex gap-2 sm:gap-3">
          <button
            @click="isReviewModalOpen = false"
            type="button"
            :disabled="isSavingFeedback"
            class="flex-1 px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="saveFeedback"
            type="button"
            :disabled="!feedback.trim() || isSavingFeedback"
            :class="[
              'flex-1 px-3 sm:px-4 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all',
              feedback.trim() && !isSavingFeedback
                ? 'bg-blue-600 hover:bg-blue-700 text-white'
                : 'bg-gray-200 text-gray-400 cursor-not-allowed'
            ]"
          >
            {{ isSavingFeedback ? 'Saving...' : 'Save Feedback' }}
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<style scoped>
#map {
  height: 100vh;
  width: 100%;
  position: relative;
  z-index: 0;
}

.user-marker, .destination-marker, .facility-marker {
  background: none;
  border: none;
}

.facility-label {
  background: white;
  border: 1px solid #3B82F6;
  border-radius: 6px;
  padding: 2px 6px;
  font-size: 11px;
  color: #1E3A8A;
  font-weight: 600;
  box-shadow: 0 1px 4px rgba(0,0,0,0.2);
  white-space: nowrap;
  pointer-events: none;
}

@keyframes pulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.1);
    opacity: 0.8;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.user-location-marker {
  background: none !important;
  border: none !important;
}

.user-popup {
  font-family: system-ui, -apple-system, sans-serif;
  font-weight: 500;
}

.leaflet-interactive {
  stroke-linecap: round;
  stroke-linejoin: round;
}

/* Enhanced visibility for satellite view */
.leaflet-overlay-pane .leaflet-interactive {
  filter: drop-shadow(0 0 2px rgba(0, 0, 0, 0.5));
}

/* Ensure facility markers are visible on all base layers */
.facility-marker {
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.4));
}

/* Enhanced polygon borders for satellite view */
.leaflet-overlay-pane svg path.leaflet-interactive {
  stroke-width: 3;
}

.note-bubble {
  position: relative;
  background: white;
  border: 2px solid #fbbf24;
  border-radius: 8px;
  padding: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  max-width: 200px;
}

.note-content {
  font-size: 12px;
  color: #374151;
  line-height: 1.4;
}

.note-delete-btn {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #ef4444;
  color: white;
  border: 2px solid white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  transition: all 0.2s;
}

.note-delete-btn:hover {
  background: #dc2626;
  transform: scale(1.1);
}

.note-arrow {
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 10px solid transparent;
  border-right: 10px solid transparent;
  border-top: 10px solid #fbbf24;
}

/* Animation for navigation instructions update */
@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.navigation-instruction-enter-active {
  animation: slideInRight 0.3s ease-out;
}
</style>
