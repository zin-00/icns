import { ref } from 'vue'
import L from 'leaflet'

export function useFacilityMarkers() {
  const facilityMarkers = ref([])
  const polygonLayers = ref([])

  // Marker type icons mapping
  const markerTypeIcons = {
    'academic': '🏫',
    'library': '📚',
    'laboratory': '🔬',
    'office': '🏢',
    'cafeteria': '🍽️',
    'sports': '⚽',
    'dormitory': '🏠',
    'parking': '🅿️',
    'medical': '🏥',
    'building': '🏢',
    'classroom': '🏫',
    'administration': '🏛️',
    'default': '📍'
  }

  // Add facility marker to map
  const addFacilityMarker = (location, map, createRoute, openReviewModal) => {
    if (!map || !map.addLayer) {
      console.warn('Cannot add marker: map not ready')
      return
    }

    try {
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

    const popupContent = `
      <div class="p-2 min-w-[230px] font-inter">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-xl">${location.icon}</span>
          <h3 class="font-semibold text-gray-800 m-0 text-sm">${location.name}</h3>
        </div>
        <p class="text-xs text-gray-600 my-1"><strong>Type:</strong> ${location.department}</p>
        <p class="text-xs text-gray-600 my-1"><strong>Category:</strong> ${location.category}</p>
        ${location.description ? `<p class="text-xs text-gray-600 mt-2">${location.description}</p>` : ''}
        <div class="flex justify-between items-center gap-1.5 mt-2.5">
          <button id="route-btn-${location.id}" class="flex-1 bg-blue-500 text-white px-0 py-1.5 rounded-md text-xs border-none cursor-pointer flex items-center justify-center gap-1 hover:bg-blue-600">
            🧭 Route
          </button>
          <button id="review-btn-${location.id}" class="flex-1 bg-amber-500 text-white px-0 py-1.5 rounded-md text-xs border-none cursor-pointer flex items-center justify-center gap-1 hover:bg-amber-600">
            <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' class='w-4 h-4'>
              <path stroke-linecap='round' stroke-linejoin='round' d='M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z' />
            </svg>
            Review
          </button>
        </div>
      </div>
    `

    marker.bindPopup(popupContent)

    marker.on('popupopen', () => {
      const routeBtn = document.getElementById(`route-btn-${location.id}`)
      const reviewBtn = document.getElementById(`review-btn-${location.id}`)

      if (routeBtn) {
        routeBtn.addEventListener('click', () => {
          createRoute(L.latLng(location.lat, location.lng), location)
          marker.closePopup()
        })
      }

      if (reviewBtn) {
        reviewBtn.addEventListener('click', () => {
          openReviewModal(location)
          marker.closePopup()
        })
      }
    })

      facilityMarkers.value.push(marker)
      return marker
    } catch (error) {
      console.error('Error adding facility marker:', error)
      return null
    }
  }

  // Cleanup function to remove all markers and polygons
  const cleanup = (map) => {
    if (!map) return

    // Remove facility markers
    facilityMarkers.value.forEach(marker => {
      if (map.hasLayer && map.hasLayer(marker)) {
        try {
          map.removeLayer(marker)
        } catch (error) {
          console.warn('Error removing facility marker:', error)
        }
      }
    })
    facilityMarkers.value = []

    // Remove polygons
    polygonLayers.value.forEach(layer => {
      if (map.hasLayer && map.hasLayer(layer)) {
        try {
          map.removeLayer(layer)
        } catch (error) {
          console.warn('Error removing polygon layer:', error)
        }
      }
    })
    polygonLayers.value = []
  }

  // Display polygons on map
  const displayPolygons = (polygons, map) => {
    if (!map || !polygons || !Array.isArray(polygons)) {
      console.warn('Cannot display polygons: map or data not ready')
      return
    }

    // Clear existing polygon layers
    polygonLayers.value.forEach(layer => {
      if (map && map.hasLayer && map.hasLayer(layer)) {
        try {
          map.removeLayer(layer)
        } catch (error) {
          console.warn('Error removing polygon layer:', error)
        }
      }
    })
    polygonLayers.value = []

    polygons.forEach(polygon => {
      if (!polygon.coordinates || !Array.isArray(polygon.coordinates)) {
        console.warn('Invalid polygon coordinates:', polygon)
        return
      }

      try {
        // Convert coordinates to Leaflet format [[lat, lng], [lat, lng], ...]
        const latlngs = polygon.coordinates.map(coord => [coord.lat, coord.lng])

        const polygonLayer = L.polygon(latlngs, {
          color: polygon.color || '#3B82F6',
          fillColor: polygon.fill_color || '#3B82F6',
          fillOpacity: polygon.fill_opacity || 0.3,
          weight: 3,
          opacity: 0.9,
          lineCap: 'round',
          lineJoin: 'round'
        })

        // Only add to map if map still exists
        if (map && map.addLayer) {
          polygonLayer.addTo(map)
          polygonLayers.value.push(polygonLayer)
        }
      } catch (error) {
        console.error('Error creating polygon:', polygon, error)
      }
    })

    console.log(`📐 Displayed ${polygonLayers.value.length} polygons on map`)
  }

  return {
    facilityMarkers,
    polygonLayers,
    markerTypeIcons,
    addFacilityMarker,
    displayPolygons,
    cleanup
  }
}
