# Location Picker with OpenStreetMap Integration

## Overview

The LocationPicker component integrates OpenStreetMap (via Leaflet) to allow users to select pickup and drop-off locations visually on a map instead of typing addresses.

## Features

✅ Interactive map with OpenStreetMap tiles
✅ Click anywhere on the map to select a location
✅ Search for locations by address/place name
✅ Automatic reverse geocoding (converts coordinates to readable addresses)
✅ Visual marker placement
✅ Mobile-responsive design
✅ Easy integration with Vue 3 forms

## Installation

Already installed! The following was added:

```bash
npm install leaflet
```

## Usage

### Basic Example

```vue
<template>
  <LocationPicker 
    id="pickup_location"
    label="Pickup Location"
    placeholder="Click to select pickup location on map"
    v-model="pickupLocation"
  />
</template>

<script>
import { ref } from 'vue'
import LocationPicker from '@/components/LocationPicker.vue'

export default {
  components: { LocationPicker },
  setup() {
    const pickupLocation = ref({
      address: '',
      lat: null,
      lng: null
    })

    return { pickupLocation }
  }
}
</script>
```

### Props

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `id` | String | Yes | - | Unique identifier for the input |
| `label` | String | Yes | - | Label text displayed above the input |
| `placeholder` | String | No | "Click to select location on map" | Placeholder text |
| `modelValue` | Object | No | `{ address: '', lat: null, lng: null }` | v-model binding |

### Emits

- `update:modelValue`: Emitted when a location is selected

### Data Structure

The component returns an object with:

```javascript
{
  address: "123 Main St, Manila, Philippines",  // Human-readable address
  lat: 14.5995,                                 // Latitude
  lng: 120.9842                                 // Longitude
}
```

## How It Works

1. **User clicks "Select on Map" button** → Opens a modal with an interactive map
2. **User can search** → Type an address in the search box to find locations quickly
3. **User clicks on map** → Places a marker at the clicked location
4. **Reverse geocoding** → Automatically converts coordinates to a readable address
5. **User confirms** → Location data is saved and modal closes

## Map Features

### Search Functionality
- Type any address, landmark, or place name
- Uses OpenStreetMap Nominatim API for geocoding
- Press Enter or click search button

### Map Interaction
- **Click** to place a marker
- **Drag** to pan around
- **Scroll** to zoom in/out
- Previous marker is automatically removed when clicking a new location

### Default Center
The map defaults to Manila, Philippines (coordinates: 14.5995, 120.9842)

## Customization

### Change Default Map Center

Edit `LocationPicker.vue`:

```javascript
// Change this to your preferred default location
const defaultCenter = { lat: 14.5995, lng: 120.9842 }
```

### Styling

The component uses scoped CSS. You can override styles by:

1. Using CSS variables
2. Adding custom classes
3. Modifying the component directly

## API Usage

The component uses two OpenStreetMap APIs:

1. **Nominatim Geocoding** (for search)
   - Endpoint: `https://nominatim.openstreetmap.org/search`
   - Free, no API key required

2. **Nominatim Reverse Geocoding** (coordinates → address)
   - Endpoint: `https://nominatim.openstreetmap.org/reverse`
   - Free, no API key required

### Usage Limits

OpenStreetMap Nominatim has usage limits:
- Max 1 request per second
- For production, consider:
  - Setting up your own Nominatim instance
  - Using a paid geocoding service (Google Maps, Mapbox)
  - Implementing request throttling

## Integration Points

Currently integrated in:
- ✅ `BrowseVehicles.vue` - User booking form

Can be easily added to:
- `MyBookings.vue` - Edit booking locations
- `BookingManagement.vue` - Admin edit bookings
- Any other form requiring location input

## Troubleshooting

### Map not displaying
- Check browser console for errors
- Ensure Leaflet CSS is imported
- Verify internet connection (map tiles load from OpenStreetMap servers)

### Search not working
- Check network tab for API errors
- Nominatim may rate-limit requests
- Try a more specific search query

### Markers not showing
- This is usually due to icon path issues
- The component includes a fix for Vite/Webpack bundling
- Icons load from CDN: `cdnjs.cloudflare.com`

## Browser Support

Works on all modern browsers:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance

- Map loads on-demand (only when modal opens)
- Map instance is destroyed when modal closes
- Minimal bundle size increase (~45KB gzipped for Leaflet)

## Future Enhancements

Potential improvements:
- [ ] Add route visualization between pickup/dropoff
- [ ] Calculate distance and estimated travel time
- [ ] Save favorite locations
- [ ] Current location detection (geolocation API)
- [ ] Multiple marker support
- [ ] Custom map themes
- [ ] Offline map tiles

## Credits

- **OpenStreetMap**: Map data © OpenStreetMap contributors
- **Leaflet**: Interactive map library
- **Nominatim**: Geocoding service by OpenStreetMap
