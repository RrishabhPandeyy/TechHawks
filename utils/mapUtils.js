const DEFAULT_CENTER = { lat: 20.5937, lng: 78.9629 }; // India's center
const DEFAULT_ZOOM = 5;

function initializeMap(elementId, options = {}) {
    try {
        const mapOptions = {
            center: options.center || DEFAULT_CENTER,
            zoom: options.zoom || DEFAULT_ZOOM,
            styles: [
                {
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{ visibility: "off" }]
                }
            ],
            ...options
        };

        const map = new google.maps.Map(
            document.getElementById(elementId),
            mapOptions
        );

        return map;
    } catch (error) {
        console.error('Map initialization failed:', error);
        throw error;
    }
}

function createMarker(map, position, options = {}) {
    try {
        const marker = new google.maps.Marker({
            position,
            map,
            icon: options.icon,
            title: options.title,
            ...options
        });

        if (options.onClick) {
            marker.addListener('click', () => options.onClick(marker));
        }

        return marker;
    } catch (error) {
        console.error('Marker creation failed:', error);
        throw error;
    }
}

function createHeatmap(map, data) {
    try {
        return new google.maps.visualization.HeatmapLayer({
            data: data.map(point => new google.maps.LatLng(point.lat, point.lng)),
            map: map
        });
    } catch (error) {
        console.error('Heatmap creation failed:', error);
        throw error;
    }
}

function calculateRoute(origin, destination) {
    try {
        const directionsService = new google.maps.DirectionsService();
        const directionsRenderer = new google.maps.DirectionsRenderer();

        return new Promise((resolve, reject) => {
            directionsService.route(
                {
                    origin: origin,
                    destination: destination,
                    travelMode: google.maps.TravelMode.DRIVING
                },
                (result, status) => {
                    if (status === google.maps.DirectionsStatus.OK) {
                        resolve({ renderer: directionsRenderer, result });
                    } else {
                        reject(new Error(`Failed to calculate route: ${status}`));
                    }
                }
            );
        });
    } catch (error) {
        console.error('Route calculation failed:', error);
        throw error;
    }
}

function getUserLocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Geolocation is not supported by your browser'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            position => {
                resolve({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                });
            },
            error => {
                console.error('Geolocation error:', error);
                reject(error);
            }
        );
    });
}

function createInfoWindow(content) {
    try {
        return new google.maps.InfoWindow({
            content: content,
            maxWidth: 300
        });
    } catch (error) {
        console.error('Info window creation failed:', error);
        throw error;
    }
}

function drawCircle(map, center, radius, options = {}) {
    try {
        return new google.maps.Circle({
            map: map,
            center: center,
            radius: radius,
            fillColor: options.fillColor || '#FF0000',
            fillOpacity: options.fillOpacity || 0.35,
            strokeColor: options.strokeColor || '#FF0000',
            strokeOpacity: options.strokeOpacity || 0.8,
            strokeWeight: options.strokeWeight || 2
        });
    } catch (error) {
        console.error('Circle drawing failed:', error);
        throw error;
    }
}
