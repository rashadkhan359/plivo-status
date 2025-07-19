import React from 'react';
import { 
    ResponsiveContainer, 
    LineChart, 
    Line, 
    XAxis, 
    YAxis, 
    CartesianGrid, 
    Tooltip, 
    BarChart, 
    Bar,
    Cell 
} from 'recharts';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface UptimeData {
    service_id: number;
    service_name: string;
    uptime_percentage: number;
    period: string;
}

interface ChartDataPoint {
    date: string;
    uptime: number;
    timestamp: string;
}

interface PublicUptimeChartProps {
    services: UptimeData[];
    chartData: Record<number, ChartDataPoint[]>;
}

const getUptimeColor = (uptime: number): string => {
    if (uptime >= 99.9) return '#22c55e'; // green-500
    if (uptime >= 99.0) return '#eab308'; // yellow-500
    if (uptime >= 95.0) return '#f97316'; // orange-500
    return '#ef4444'; // red-500
};

const getUptimeStatus = (uptime: number): { label: string; color: string } => {
    if (uptime >= 99.9) return { label: 'Excellent', color: 'bg-green-100 text-green-800 border-green-200' };
    if (uptime >= 99.0) return { label: 'Good', color: 'bg-yellow-100 text-yellow-800 border-yellow-200' };
    if (uptime >= 95.0) return { label: 'Fair', color: 'bg-orange-100 text-orange-800 border-orange-200' };
    return { label: 'Poor', color: 'bg-red-100 text-red-800 border-red-200' };
};

const CustomTooltip = ({ active, payload, label }: any) => {
    if (active && payload && payload.length) {
        return (
            <div className="bg-white p-3 border rounded-lg shadow-lg">
                <p className="font-medium">{`Date: ${label}`}</p>
                <p className="text-green-600">
                    {`Uptime: ${payload[0].value.toFixed(1)}%`}
                </p>
            </div>
        );
    }
    return null;
};

export function PublicUptimeChart({ services, chartData }: PublicUptimeChartProps) {
    if (!services.length) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Service Uptime</CardTitle>
                    <CardDescription>No services to display</CardDescription>
                </CardHeader>
            </Card>
        );
    }

    return (
        <div className="space-y-6">
            {/* Overall Uptime Summary */}
            <Card>
                <CardHeader>
                    <CardTitle>Service Uptime Overview</CardTitle>
                    <CardDescription>
                        Service availability over the last 90 days
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        {/* Uptime Bar Chart */}
                        <div className="h-64">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={services} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
                                    <XAxis 
                                        dataKey="service_name" 
                                        tick={{ fontSize: 12 }}
                                        angle={-45}
                                        textAnchor="end"
                                        height={80}
                                    />
                                    <YAxis 
                                        domain={[95, 100]}
                                        tick={{ fontSize: 12 }}
                                        label={{ value: 'Uptime %', angle: -90, position: 'insideLeft' }}
                                    />
                                    <Tooltip 
                                        formatter={(value: number) => [`${value.toFixed(1)}%`, 'Uptime']}
                                        labelFormatter={(label) => `Service: ${label}`}
                                        contentStyle={{
                                            backgroundColor: 'white',
                                            border: '1px solid #e2e8f0',
                                            borderRadius: '8px'
                                        }}
                                    />
                                    <Bar dataKey="uptime_percentage" radius={[4, 4, 0, 0]}>
                                        {services.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={getUptimeColor(entry.uptime_percentage)} />
                                        ))}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Service Status List */}
                        <div className="grid gap-3">
                            {services.map((service) => {
                                const status = getUptimeStatus(service.uptime_percentage);
                                return (
                                    <div key={service.service_id} className="flex items-center justify-between p-3 rounded-lg border bg-gray-50 dark:bg-accent dark:border-accent">
                                        <div>
                                            <h4 className="font-medium text-gray-900 dark:text-gray-100">{service.service_name}</h4>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">Last 90 days</p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <Badge className={`${status.color} border`}>
                                                {status.label}
                                            </Badge>
                                            <span className="text-lg font-bold" style={{ color: getUptimeColor(service.uptime_percentage) }}>
                                                {service.uptime_percentage}%
                                            </span>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Individual Service Trend Charts */}
            {services.slice(0, 3).map((service) => {
                const serviceChart = chartData[service.service_id];
                if (!serviceChart || serviceChart.length === 0) return null;

                return (
                    <Card key={service.service_id}>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                <span>{service.service_name} - 30 Day Trend</span>
                                <Badge variant="outline" style={{ color: getUptimeColor(service.uptime_percentage) }}>
                                    {service.uptime_percentage}% uptime
                                </Badge>
                            </CardTitle>
                            <CardDescription>
                                Daily uptime percentage over the last 30 days
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="h-48">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={serviceChart} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
                                        <XAxis 
                                            dataKey="date" 
                                            tick={{ fontSize: 11 }}
                                        />
                                        <YAxis 
                                            domain={[90, 100]}
                                            tick={{ fontSize: 11 }}
                                            label={{ value: 'Uptime %', angle: -90, position: 'insideLeft' }}
                                        />
                                        <Tooltip content={<CustomTooltip />} />
                                        <Line 
                                            type="monotone" 
                                            dataKey="uptime" 
                                            stroke={getUptimeColor(service.uptime_percentage)}
                                            strokeWidth={2}
                                            dot={{ fill: getUptimeColor(service.uptime_percentage), strokeWidth: 2, r: 4 }}
                                            activeDot={{ r: 6, stroke: getUptimeColor(service.uptime_percentage), strokeWidth: 2 }}
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>
                );
            })}

            {/* Legend */}
            <Card>
                <CardContent className="pt-6">
                    <div className="text-center text-sm text-gray-600 space-y-2">
                        <p className="font-medium">Uptime Rating Guide</p>
                        <div className="flex justify-center gap-6 flex-wrap">
                            <div className="flex items-center gap-2">
                                <div className="w-3 h-3 rounded bg-green-500"></div>
                                <span>Excellent (≥99.9%)</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="w-3 h-3 rounded bg-yellow-500"></div>
                                <span>Good (≥99.0%)</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="w-3 h-3 rounded bg-orange-500"></div>
                                <span>Fair (≥95.0%)</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="w-3 h-3 rounded bg-red-500"></div>
                                <span>Poor (&lt;95.0%)</span>
                            </div>
                        </div>
                        <p className="text-xs text-gray-500 mt-2">
                            Uptime is calculated based on service operational status. Charts show the last 30 days, summary shows 90 days.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
} 