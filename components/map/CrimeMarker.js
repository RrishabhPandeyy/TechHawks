function CrimeMarker({ map, position, data, onClick }) {
    try {
        const [marker, setMarker] = React.useState(null);
        const [infoWindow, setInfoWindow] = React.useState(null);

        React.useEffect(() => {
            if (map) {
                const newMarker = createMarker(map, position, {
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        fillColor: getCrimeTypeColor(data.type),
                        fillOpacity: 0.7,
                        strokeWeight: 1,
                        scale: 10
                    },
                    title: data.title
                });

                const content = `
                    <div class="p-4">
                        <h3 class="font-semibold">${data.title}</h3>
                        <p class="text-sm text-gray-600">${data.description}</p>
                        <p class="text-xs text-gray-500 mt-2">${new Date(data.timestamp).toLocaleString()}</p>
                    </div>
                `;

                const newInfoWindow = createInfoWindow(content);

                newMarker.addListener('click', () => {
                    newInfoWindow.open(map, newMarker);
                    onClick?.(data);
                });

                setMarker(newMarker);
                setInfoWindow(newInfoWindow);

                return () => {
                    newMarker.setMap(null);
                    newInfoWindow.close();
                };
            }
        }, [map, position, data, onClick]);

        return null;
    } catch (error) {
        console.error('Crime marker component error:', error);
        reportError(error);
        return null;
    }
}

function getCrimeTypeColor(type) {
    const colors = {
        violence: '#ef4444',
        theft: '#3b82f6',
        cybercrime: '#8b5cf6',
        harassment: '#f59e0b',
        default: '#6b7280'
    };

    return colors[type] || colors.default;
}
