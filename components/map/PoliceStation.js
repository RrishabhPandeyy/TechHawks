function PoliceStation({ map, position, data }) {
    try {
        const [marker, setMarker] = React.useState(null);
        const [infoWindow, setInfoWindow] = React.useState(null);

        React.useEffect(() => {
            if (map) {
                const newMarker = createMarker(map, position, {
                    icon: {
                        url: 'https://maps.google.com/mapfiles/ms/icons/police.png',
                        scaledSize: new google.maps.Size(32, 32)
                    },
                    title: data.name
                });

                const content = `
                    <div class="p-4">
                        <h3 class="font-semibold">${data.name}</h3>
                        <p class="text-sm text-gray-600">${data.address}</p>
                        <p class="text-sm text-gray-600">Phone: ${data.phone}</p>
                        <button
                            class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                            onclick="window.location.href='tel:${data.phone}'"
                        >
                            Call Now
                        </button>
                    </div>
                `;

                const newInfoWindow = createInfoWindow(content);

                newMarker.addListener('click', () => {
                    newInfoWindow.open(map, newMarker);
                });

                setMarker(newMarker);
                setInfoWindow(newInfoWindow);

                return () => {
                    newMarker.setMap(null);
                    newInfoWindow.close();
                };
            }
        }, [map, position, data]);

        return null;
    } catch (error) {
        console.error('Police station component error:', error);
        reportError(error);
        return null;
    }
}
