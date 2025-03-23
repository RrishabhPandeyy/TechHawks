document.addEventListener('DOMContentLoaded', () => {
  // Get crime data from hidden div
  const crimeDataElement = document.getElementById('crimeData');
  const crimeTypeDataElement = document.getElementById('crimeTypeData');
  const crimeStatusDataElement = document.getElementById('crimeStatusData');
  const crimeTimeDataElement = document.getElementById('crimeTimeData');
  
  let crimeData = [];
  let crimeTypeData = {};
  let crimeStatusData = {};
  let crimeTimeData = {};
  
  if (crimeDataElement) {
    try {
      crimeData = JSON.parse(crimeDataElement.getAttribute('data-crime'));
    } catch (e) {
      console.error('Error parsing crime data:', e);
    }
  }
  
  if (crimeTypeDataElement) {
    try {
      crimeTypeData = JSON.parse(crimeTypeDataElement.getAttribute('data-crime-type'));
    } catch (e) {
      console.error('Error parsing crime type data:', e);
    }
  }
  
  if (crimeStatusDataElement) {
    try {
      crimeStatusData = JSON.parse(crimeStatusDataElement.getAttribute('data-crime-status'));
    } catch (e) {
      console.error('Error parsing crime status data:', e);
    }
  }
  
  if (crimeTimeDataElement) {
    try {
      crimeTimeData = JSON.parse(crimeTimeDataElement.getAttribute('data-crime-time'));
    } catch (e) {
      console.error('Error parsing crime time data:', e);
    }
  }
  
  // Initialize map
  const mapElement = document.getElementById('crimeMap');
  
  if (mapElement) {
    // Initialize map
    const map = new google.maps.Map(mapElement, {
      center: { lat: 20.5937, lng: 78.9629 }, // Center of India
      zoom: 5,
      mapTypeControl: false
    });
    
    // Add markers for crime data
    const markers = [];
    const infoWindow = new google.maps.InfoWindow();
    
    crimeData.forEach(crime => {
      // Skip if no location data
      if (!crime.lat || !crime.lng) return;
      
      // Get crime type color
      const color = getCrimeTypeColor(crime.type);
      
      // Create marker
      const marker = new google.maps.Marker({
        position: { lat: Number.parseFloat(crime.lat), lng: Number.parseFloat(crime.lng) },
        map: map,
        title: crime.type,
        icon: {
          path: google.maps.SymbolPath.CIRCLE,
          fillColor: color,
          fillOpacity: 0.7,
          strokeWeight: 1,
          strokeColor: '#ffffff',
          scale: 10
        }
      });
      
      // Add click event listener
      marker.addListener('click', () => {
        // Format date
        const date = new Date(crime.created_at);
        const formattedDate = date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
        
        // Set info window content
        infoWindow.setContent(`
          <div style="padding: 8px; max-width: 200px;">
            <h3 style="margin: 0 0 8px; font-weight: bold; text-transform: capitalize;">${crime.type}</h3>
            <p style="margin: 0; font-size: 12px;">${formattedDate}</p>
          </div>
        `);
        
        // Open info window
        infoWindow.open(map, marker);
        
        // Show crime details card
        showCrimeDetails(crime);
      });
      
      markers.push(marker);
    });
    
    // Fit map to markers
    if (markers.length > 0) {
      const bounds = new google.maps.LatLngBounds();
      markers.forEach(marker => bounds.extend(marker.getPosition()));
      map.fitBounds(bounds);
      
      // Don't zoom in too far
      google.maps.event.addListenerOnce(map, 'idle', () => {
        if (map.getZoom() > 15) {
          map.setZoom(15);

