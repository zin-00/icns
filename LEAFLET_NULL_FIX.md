# Fix: Leaflet Map Null Reference Error

## Problem
```
Uncaught TypeError: Cannot read properties of null (reading 'latLngToLayerPoint')
Uncaught TypeError: Cannot read properties of null (reading '_latLngToNewLayerPoint')
```

These errors occurred when Leaflet tried to update markers or layers on a map instance that was null or destroyed.

## Root Cause
- Markers, polygons, and layers were being added/updated when the map was null
- Insufficient cleanup when component unmounted
- Missing null checks before removing layers from map
- Async operations trying to access destroyed map instance

## Fixes Applied

### 1. **useGuestTracking.js** - GPS Tracking Composable

#### Updated `updateAccuracyCircle()`
- Added null checks for map and location
- Added `map.hasLayer()` check before removing layers
- Wrapped removal in try-catch blocks
- Set reference to null after removal

#### Updated `updateUserMarker()`
- Added `map.hasLayer()` check before updating marker
- Prevents updates on destroyed markers

#### Updated `startTracking()`
- Added map null check at start
- Check if map still exists during GPS updates
- Stop tracking if map is destroyed mid-update

#### Updated `stopTracking()`
- Added `map.hasLayer()` checks before removing layers
- Remove both accuracy circle AND user marker
- Wrapped removals in try-catch blocks
- Properly null out references

### 2. **useFacilityMarkers.js** - Markers & Polygons Composable

#### Updated `addFacilityMarker()`
- Added `map.addLayer` check before adding marker
- Wrapped in try-catch block
- Return null on error

#### Updated `displayPolygons()`
- Added `map.hasLayer()` check before removing layers
- Check `map.addLayer` exists before adding
- Only add popup/tooltip if map exists
- Wrapped removals in try-catch blocks

#### Added `cleanup()` function
- Remove all facility markers safely
- Remove all polygon layers safely
- Proper hasLayer checks
- Try-catch error handling
- Exported for use in components

### 3. **GuestMapV1.vue** - Main Component

#### Updated `displayPolygons()`
- Same improvements as composable version
- Added hasLayer checks
- Try-catch blocks for removals
- Only add to map if map exists

#### Enhanced `onBeforeUnmount()` lifecycle
```javascript
onBeforeUnmount(() => {
  // Stop GPS tracking first
  stopTracking()

  // Remove ALL markers and layers
  // - Facility markers
  // - Note markers
  // - Polygon layers
  // - GeoJSON layers
  // - Route polylines
  // - Destination markers

  // Destroy map instance
  map.value.remove()
  map.value = null

  // Clear intervals
  clearInterval(sessionCheckInterval.value)
})
```

## Key Improvements

### ✅ Null Safety
- All map operations check if map exists
- All layer removals check `hasLayer()` first
- Proper null checks on location data

### ✅ Error Handling
- Try-catch blocks around all layer operations
- Graceful degradation on errors
- Helpful console warnings for debugging

### ✅ Proper Cleanup
- Comprehensive unmount cleanup
- Remove all layers before destroying map
- Clear all timers and intervals
- Null out all references

### ✅ Async Safety
- Check map exists in GPS callbacks
- Stop tracking if map destroyed
- No updates to destroyed map instances

## Testing Checklist

- [x] Map loads without errors
- [x] Markers display correctly
- [x] Polygons render properly
- [x] GPS tracking works
- [x] Component unmounts cleanly
- [x] No console errors on navigation
- [x] No errors when switching pages
- [x] Real-time updates work
- [x] Dragging user marker works
- [x] Route calculations complete

## Prevention

To prevent similar issues in the future:

1. **Always check map exists** before operations
2. **Use hasLayer()** before removing layers
3. **Wrap in try-catch** for critical operations
4. **Proper cleanup** in onBeforeUnmount
5. **Null checks** in async callbacks
6. **Test navigation** between pages

## Files Modified

- ✅ `resources/js/composables/useGuestTracking.js`
- ✅ `resources/js/composables/useFacilityMarkers.js`
- ✅ `resources/js/Components/map/GuestMapV1.vue`

## Result

✅ **No more Leaflet null reference errors**
✅ **Proper cleanup on component unmount**
✅ **Graceful error handling**
✅ **Stable map operations**
