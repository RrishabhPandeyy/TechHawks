function SOSButton({ onTrigger }) {
    try {
        const [loading, setLoading] = React.useState(false);
        const [listening, setListening] = React.useState(false);
        const recognition = React.useRef(null);

        React.useEffect(() => {
            if ('webkitSpeechRecognition' in window) {
                recognition.current = new webkitSpeechRecognition();
                recognition.current.continuous = true;
                recognition.current.interimResults = true;

                recognition.current.onresult = (event) => {
                    const transcript = Array.from(event.results)
                        .map(result => result[0].transcript)
                        .join('');

                    if (transcript.toLowerCase().includes('police help me')) {
                        handleEmergencySOS();
                    }
                };
            }

            return () => {
                if (recognition.current) {
                    recognition.current.stop();
                }
            };
        }, []);

        const toggleVoiceRecognition = () => {
            if (listening) {
                recognition.current?.stop();
            } else {
                recognition.current?.start();
            }
            setListening(!listening);
        };

        const handleEmergencySOS = async () => {
            setLoading(true);
            try {
                const location = await getUserLocation();
                await triggerSOS({
                    ...location,
                    type: 'emergency',
                    timestamp: new Date().toISOString()
                });

                // Start recording
                if (navigator.mediaDevices) {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: true,
                        audio: true
                    });
                    // Handle stream (would be implemented in backend)
                    console.log('Started emergency recording');
                }

                // Play siren sound
                const audio = new Audio('/assets/siren.mp3');
                audio.loop = true;
                audio.play();

                onTrigger?.();
            } catch (error) {
                console.error('Emergency SOS failed:', error);
                alert('Failed to trigger emergency SOS');
            } finally {
                setLoading(false);
            }
        };

        const handleRegularSOS = async () => {
            setLoading(true);
            try {
                const location = await getUserLocation();
                await triggerSOS({
                    ...location,
                    type: 'regular',
                    timestamp: new Date().toISOString()
                });
                onTrigger?.();
            } catch (error) {
                console.error('SOS trigger failed:', error);
                alert('Failed to trigger SOS');
            } finally {
                setLoading(false);
            }
        };

        return (
            <div data-name="sos-buttons" className="fixed bottom-4 right-4 space-y-4">
                <button
                    onClick={handleEmergencySOS}
                    className="sos-button bg-red-600 hover:bg-red-700"
                    disabled={loading}
                >
                    <i className="fas fa-exclamation-triangle text-2xl"></i>
                </button>

                <button
                    onClick={handleRegularSOS}
                    className="sos-button bg-yellow-500 hover:bg-yellow-600"
                    disabled={loading}
                >
                    <i className="fas fa-phone-alt text-2xl"></i>
                </button>

                <button
                    onClick={toggleVoiceRecognition}
                    className={`sos-button ${listening ? 'bg-green-600' : 'bg-gray-600'}`}
                >
                    <i className={`fas fa-microphone${listening ? '-slash' : ''} text-2xl`}></i>
                </button>
            </div>
        );
    } catch (error) {
        console.error('SOS button component error:', error);
        reportError(error);
        return null;
    }
}
