function MapView({ center, zoom, markers, onMarkerClick, showPoliceStations }) {
    try {
        const mapRef = React.useRef(null);
        const [map, setMap] = React.useState(null);
        const [policeStations, setPoliceStations] = React.useState([]);

        React.useEffect(() => {
            if (!mapRef.current) {
                const newMap = initializeMap('map', { center, zoom });
                setMap(newMap);
            }
        }, [center, zoom]);

        React.useEffect(() => {
            if (map && showPoliceStations) {
                const fetchPoliceStations = async () => {
                    try {
                        const stations = await getPoliceStations(center);
                        setPoliceStations(stations);
                    } catch (error) {
                        console.error('Failed to fetch police stations:', error);
                    }
                };

                fetchPoliceStations();
            }
        }, [map, center, showPoliceStations]);

        return (
            <div data-name="map-container" className="map-container">
                <div id="map" style={{ width: '100%', height: '100%' }}>
                    {markers?.map((marker, index) => (
                        <CrimeMarker
                            key={index}
                            map={map}
                            position={marker.position}
                            data={marker.data}
                            onClick={() => onMarkerClick(marker)}
                        />
                    ))}

                    {showPoliceStations && policeStations.map((station, index) => (
                        <PoliceStation
                            key={index}
                            map={map}
                            position={station.position}
                            data={station}
                        />
                    ))}
                </div>
            </div>
        );
    } catch (error) {
        console.error('Map view component error:', error);
        reportError(error);
        return null;
    }
}
