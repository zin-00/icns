import { defineStore } from "pinia";
import { ref } from "vue";

export const useReactiveStore = defineStore("reactive", () => {

    const lng = ref(0)
    const lat = ref(0)

    const privateRoutes = ref(null)
    const routeInfo = ref(null)
    const guestStep = ref(1)
    const userLocation = ref(null)
    const map = ref(null)
    const userMarker = ref(null)
    const destinationMarker = ref(null)
    const routePolyline = ref(null)
    const watchId = ref(null)
    const selectedMarker = ref(null)
    const markerNote = ref('')
    const feedback = ref('')
    const message = ref('');
    const dateFrom = ref('');
    const dateTo = ref('');
    const profile = ref('');

    // Boolean
    const isLoading = ref(false)
    const isOpen = ref(false)
    const isEditMode = ref(false)
    const isConfirmationModalOpen = ref(false)
    const isLoaded = ref(false)
    const showInstructions = ref(false)
    const showGuestModal = ref(true)
    const showSearchResults = ref(false)
    const isSearching = ref(false)
    const showNoteModal = ref(false)
    const isSavingNote = ref(false)
    const isSavingFeedback = ref(false)
    const isGuestInfoComplete = ref(false)
    const isReviewModalOpen = ref(false)


    // Selected
    const selected = ref(null)
    const selectedLocation = ref(null)

    // Data Arrays
    const items = ref([])
    const markers = ref([])
    const navigationInstructions = ref([])
    const facilityMarkers = ref([])
    const geoJsonLayers = ref([])
    const filteredLocations = ref([])


    // Pagination
    const paginated = ref({})

    // Form Data Objects
    const data = ref({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    })

    // Filters
    const searchQuery = ref('')
    const statusFilter = ref('all')

    return {
        // Coordinates
        lng,
        lat,

        //
        privateRoutes,
        routeInfo,
        guestStep,
        userLocation,
        map,
        userMarker,
        destinationMarker,
        routePolyline,
        watchId,
        selectedMarker,
        markerNote,
        feedback,
        message,
        dateFrom,
        dateTo,

        // Boolean
        isLoading,
        isOpen,
        isEditMode,
        isConfirmationModalOpen,
        isLoaded,
        showInstructions,
        showGuestModal,
        isSearching,
        showSearchResults,
        showNoteModal,
        isSavingNote,
        isGuestInfoComplete,
        isReviewModalOpen,
        isSavingFeedback,



        // Selected
        selected,
        selectedLocation,

        // Data Arrays
        items,
        markers,
        navigationInstructions,
        facilityMarkers,
        geoJsonLayers,
        filteredLocations,

        // Pagination
        paginated,

        // Data Objects
        data,

        // Filters
        searchQuery,
        statusFilter,
    }
})
