import { ref } from 'vue'
import L from 'leaflet'
import axios from 'axios'
import { useToast } from 'vue-toastification'

export function useMapRoutes(map, isAdmin) {
  const toast = useToast()

  const savedRoutes = ref([])
  const routePolylines = ref([])
  const drawnRoute = ref(null)
  const routePoints = ref([])
  const temporaryMarkers = ref([])
  const isDrawing = ref(false)
  const drawRouteMode = ref(false)
  const editRouteMode = ref(false)
  const selectedRoute = ref(null)
  const editMarkers = ref([])

  // Fetch routes from backend
  const fetchRoutes = async () => {
    try {
      const response = await axios.get('/routes', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })

      const newRoutes = response.data
      const currentRouteIds = new Set(newRoutes.map(route => route.id))

      // Remove outdated routes
      const routesToRemove = routePolylines.value.filter(routePoly => !currentRouteIds.has(routePoly.id))
      routesToRemove.forEach(routePoly => {
        if (routePoly.polyline && routePoly.polyline.remove) {
          routePoly.polyline.remove()
        }
        const index = routePolylines.value.indexOf(routePoly)
        if (index > -1) {
          routePolylines.value.splice(index, 1)
        }
      })

      // Update existing and add new routes
      newRoutes.forEach(newRoute => {
        if (!newRoute.path_data || newRoute.path_data.length === 0) {
          console.warn('Route has no path data:', newRoute)
          return
        }

        const existingRoute = routePolylines.value.find(r => r.id === newRoute.id)

        if (existingRoute) {
          const hasChanged = JSON.stringify(existingRoute.data.path_data) !== JSON.stringify(newRoute.path_data)
          if (hasChanged) {
            updateRouteDisplay(existingRoute, newRoute)
          }
        } else {
          displayRoute(newRoute)
        }
      })

      savedRoutes.value = newRoutes
      console.log(`🔄 Routes updated: ${newRoutes.length} total`)

    } catch (error) {
      console.error('Error fetching routes:', error)
      toast.error('Failed to load routes')
    }
  }

  // Display a route on the map
  const displayRoute = (route) => {
    if (!route.path_data || route.path_data.length === 0) return

    const existingRoute = routePolylines.value.find(r => r.id === route.id)
    if (existingRoute) {
      console.log('Route already exists, skipping duplicate:', route.id)
      return
    }

    try {
      const pathCoordinates = route.path_data.map(point => [point.lat, point.lng])

      const polyline = L.polyline(pathCoordinates, {
        color: '#3B82F6',
        weight: 4,
        opacity: 0.7,
        routeId: route.id
      }).addTo(map.value)

      const popupContent = createRoutePopup(route)
      polyline.bindPopup(popupContent)

      routePolylines.value.push({ id: route.id, polyline, data: route })
    } catch (error) {
      console.error('Error displaying route:', error, route)
    }
  }

  // Update existing route display
  const updateRouteDisplay = (existingRoute, newRoute) => {
    const pathCoordinates = newRoute.path_data.map(point => [point.lat, point.lng])
    existingRoute.polyline.setLatLngs(pathCoordinates)
    existingRoute.polyline.setPopupContent(createRoutePopup(newRoute))
    existingRoute.data = newRoute
  }

  // Create route popup content
  const createRoutePopup = (route) => {
    const startPoint = `${parseFloat(route.start_lat).toFixed(4)}, ${parseFloat(route.start_lng).toFixed(4)}`
    const endPoint = `${parseFloat(route.end_lat).toFixed(4)}, ${parseFloat(route.end_lng).toFixed(4)}`

    return `
      <div class="p-3">
        <h3 class="font-bold text-sm mb-2">Route ${route.id}</h3>
        <p class="text-xs text-gray-600 mb-1">Start: ${startPoint}</p>
        <p class="text-xs text-gray-600 mb-2">End: ${endPoint}</p>
        <p class="text-xs text-gray-600 mb-3">Est. Time: ${route.estimated_time}</p>
        ${isAdmin ? `
          <div class="flex gap-2">
            <button onclick="window.editRoute(${route.id})" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
              Edit
            </button>
            <button onclick="window.deleteRoute(${route.id})" class="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">
              Delete
            </button>
          </div>
        ` : ''}
      </div>
    `
  }

  // Start drawing route
  const startDrawingRoute = () => {
    isDrawing.value = true
    routePoints.value = []
    temporaryMarkers.value = []

    if (drawnRoute.value) {
      drawnRoute.value.remove()
    }

    map.value.getContainer().style.cursor = 'crosshair'
  }

  // Add point to route
  const addRoutePoint = (latlng) => {
    routePoints.value.push(latlng)

    if (routePoints.value.length === 1) {
      toast.success('Starting point set!')
    }

    if (drawnRoute.value) {
      drawnRoute.value.remove()
    }

    drawnRoute.value = L.polyline(routePoints.value, {
      color: '#EF4444',
      weight: 4,
      opacity: 0.7,
      dashArray: '10, 10'
    }).addTo(map.value)

    const circleMarker = L.circleMarker(latlng, {
      radius: 5,
      fillColor: '#EF4444',
      color: '#fff',
      weight: 2,
      fillOpacity: 1
    }).addTo(map.value)

    temporaryMarkers.value.push(circleMarker)
  }

  // Cancel drawing
  const cancelDrawing = () => {
    isDrawing.value = false
    drawRouteMode.value = false
    routePoints.value = []

    temporaryMarkers.value.forEach(marker => {
      if (marker && marker.remove) marker.remove()
    })
    temporaryMarkers.value = []

    if (drawnRoute.value) {
      drawnRoute.value.remove()
      drawnRoute.value = null
    }

    map.value.getContainer().style.cursor = ''
  }

  // Cleanup routes
  const cleanupRoutes = () => {
    routePolylines.value.forEach(route => {
      if (route.polyline && typeof route.polyline.remove === 'function') {
        try {
          route.polyline.remove()
        } catch (error) {
          console.warn('Error removing polyline:', error)
        }
      }
    })
    routePolylines.value = []

    editMarkers.value.forEach(marker => {
      if (marker && typeof marker.remove === 'function') {
        try {
          marker.remove()
        } catch (error) {
          console.warn('Error removing edit marker:', error)
        }
      }
    })
    editMarkers.value = []

    cancelDrawing()
  }

  return {
    savedRoutes,
    routePolylines,
    drawnRoute,
    routePoints,
    temporaryMarkers,
    isDrawing,
    drawRouteMode,
    editRouteMode,
    selectedRoute,
    editMarkers,
    fetchRoutes,
    displayRoute,
    startDrawingRoute,
    addRoutePoint,
    cancelDrawing,
    cleanupRoutes
  }
}
