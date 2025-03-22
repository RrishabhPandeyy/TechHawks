function CrimeAnalytics() {
    try {
        const [statistics, setStatistics] = React.useState(null);
        const [filters, setFilters] = React.useState({
            timeRange: 'week',
            region: 'all'
        });
        const [loading, setLoading] = React.useState(true);

        React.useEffect(() => {
            const fetchStatistics = async () => {
                try {
                    const response = await getCrimeStatistics(filters);
                    setStatistics(response);
                } catch (error) {
                    console.error('Failed to fetch crime statistics:', error);
                } finally {
                    setLoading(false);
                }
            };

            fetchStatistics();
        }, [filters]);

        if (loading) {
            return <div>Loading analytics...</div>;
        }

        return (
            <div data-name="crime-analytics" className="space-y-6">
                <div className="flex gap-4 mb-6">
                    <select
                        value={filters.timeRange}
                        onChange={(e) => setFilters(prev => ({ ...prev, timeRange: e.target.value }))}
                        className="p-2 border rounded"
                    >
                        <option value="week">Last Week</option>
                        <option value="month">Last Month</option>
                        <option value="year">Last Year</option>
                    </select>

                    <select
                        value={filters.region}
                        onChange={(e) => setFilters(prev => ({ ...prev, region: e.target.value }))}
                        className="p-2 border rounded"
                    >
                        <option value="all">All Regions</option>
                        <option value="north">North</option>
                        <option value="south">South</option>
                        <option value="east">East</option>
                        <option value="west">West</option>
                    </select>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="stat-card">
                        <h3 className="text-lg font-semibold">Total Cases</h3>
                        <p className="text-3xl font-bold text-blue-600">
                            {statistics?.totalCases || 0}
                        </p>
                    </div>

                    <div className="stat-card">
                        <h3 className="text-lg font-semibold">Resolved Cases</h3>
                        <p className="text-3xl font-bold text-green-600">
                            {statistics?.resolvedCases || 0}
                        </p>
                    </div>

                    <div className="stat-card">
                        <h3 className="text-lg font-semibold">Active Cases</h3>
                        <p className="text-3xl font-bold text-yellow-600">
                            {statistics?.activeCases || 0}
                        </p>
                    </div>

                    <div className="stat-card">
                        <h3 className="text-lg font-semibold">Emergency Calls</h3>
                        <p className="text-3xl font-bold text-red-600">
                            {statistics?.emergencyCalls || 0}
                        </p>
                    </div>
                </div>

                <div className="analytics-container">
                    <h3 className="text-lg font-semibold mb-4">Crime Types Distribution</h3>
                    <div className="space-y-2">
                        {statistics?.crimeTypes?.map(type => (
                            <div key={type.name} className="flex items-center">
                                <div className="w-32">{type.name}</div>
                                <div className="flex-1 h-4 bg-gray-200 rounded">
                                    <div
                                        className="h-full bg-blue-600 rounded"
                                        style={{ width: `${type.percentage}%` }}
                                    ></div>
                                </div>
                                <div className="w-16 text-right">{type.percentage}%</div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="analytics-container">
                    <h3 className="text-lg font-semibold mb-4">Hotspot Areas</h3>
                    <div className="space-y-4">
                        {statistics?.hotspots?.map(hotspot => (
                            <div key={hotspot.id} className="flex items-center">
                                <div className={`hotspot-indicator hotspot-${hotspot.level}`}></div>
                                <div>
                                    <h4 className="font-medium">{hotspot.area}</h4>
                                    <p className="text-sm text-gray-600">
                                        {hotspot.incidents} incidents in last {filters.timeRange}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    } catch (error) {
        console.error('Crime analytics component error:', error);
        reportError(error);
        return null;
    }
}
