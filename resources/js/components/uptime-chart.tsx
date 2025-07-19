import React from 'react';
import {
    ResponsiveContainer,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Cell
} from 'recharts';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';

interface UptimeData {
    uptime_percentage: number;
    period: string;
    start_date: string;
    end_date: string;
}

interface UptimeChartProps {
    serviceName: string;
    metrics: Record<string, UptimeData>;
    chartData?: Array<{
        timestamp: string;
        uptime: number;
        label: string;
    }>;
    showChart?: boolean;
}

const PERIOD_LABELS: Record<string, string> = {
    '24h': 'Last 24 Hours',
    '7d': 'Last 7 Days',
    '30d': 'Last 30 Days',
    '90d': 'Last 90 Days',
};

const getUptimeColor = (uptime: number): string => {
    if (uptime >= 99.9) return '#22c55e'; // green-500
    if (uptime >= 99.0) return '#eab308'; // yellow-500
    if (uptime >= 95.0) return '#f97316'; // orange-500
    return '#ef4444'; // red-500
};

const getUptimeColorClass = (uptime: number): string => {
    if (uptime >= 99.9) return 'text-green-600 bg-green-50 border-green-200';
    if (uptime >= 99.0) return 'text-yellow-600 bg-yellow-50 border-yellow-200';
    if (uptime >= 95.0) return 'text-orange-600 bg-orange-50 border-orange-200';
    return 'text-red-600 bg-red-50 border-red-200';
};

export function UptimeChart({ serviceName, metrics, chartData, showChart = false }: UptimeChartProps) {
    const periods = ['24h', '7d', '30d', '90d'];

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <span>Uptime Metrics</span>
                    {serviceName && (
                        <Badge variant="outline" className="text-xs">
                            {serviceName}
                        </Badge>
                    )}
                </CardTitle>
                <CardDescription>
                    Service availability over different time periods
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    {periods.map((period) => {
                        const data = metrics[period];
                        if (!data) return null;

                        return (
                            <div key={period} className="text-center">
                                <div className={`inline-flex items-center px-3 py-2 rounded-md border ${getUptimeColorClass(data.uptime_percentage)}`}>
                                    <span className="text-lg font-bold">
                                        {data.uptime_percentage}%
                                    </span>
                                </div>
                                <p className="text-sm text-muted-foreground mt-1">
                                    {PERIOD_LABELS[period]}
                                </p>
                            </div>
                        );
                    })}
                </div>

                {showChart && chartData && chartData.length > 0 && (
                    <>
                        <Separator className="my-4" />
                        <div>
                            <h4 className="text-sm font-medium mb-3">Uptime Trend (Last 7 Days)</h4>
                            <div className="h-32">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={chartData} margin={{ top: 10, right: 10, left: 10, bottom: 10 }}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
                                        <XAxis
                                            dataKey="label"
                                            tick={{ fontSize: 10 }}
                                            angle={-45}
                                            textAnchor="end"
                                            height={40}
                                        />
                                        <YAxis hide />
                                        <Tooltip
                                            formatter={(value: number) => [`${value.toFixed(1)}%`, 'Uptime']}
                                            contentStyle={{
                                                backgroundColor: 'white',
                                                border: '1px solid #e2e8f0',
                                                borderRadius: '8px',
                                                fontSize: '12px'
                                            }}
                                        />
                                        <Bar dataKey="uptime" radius={[2, 2, 0, 0]} fill="#22c55e" />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    </>
                )}

                <div className="mt-4 text-xs text-muted-foreground space-y-1">
                    <p>• Uptime is calculated based on service operational status</p>
                    <p>• Updates every time service status changes</p>
                    <p>• Green: ≥99.9% | Yellow: ≥99.0% | Orange: ≥95.0% | Red: &lt;95.0%</p>
                </div>
            </CardContent>
        </Card>
    );
}

// Component for displaying uptime metrics for multiple services
interface ServiceUptimeListProps {
    services: Array<{
        service_id: number;
        service_name: string;
        uptime_percentage: number;
        status: string;
    }>;
    period?: string;
}

export function ServiceUptimeList({ services, period = '30d' }: ServiceUptimeListProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Service Uptime Overview</CardTitle>
                <CardDescription>
                    Uptime percentage for all services ({PERIOD_LABELS[period]})
                </CardDescription>
            </CardHeader>
            <CardContent>
                {services.length > 0 && (
                    <>
                        {/* Bar Chart */}
                        <div className="h-48 mb-4">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={services} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
                                    <XAxis
                                        dataKey="service_name"
                                        tick={{ fontSize: 11 }}
                                        angle={-45}
                                        textAnchor="end"
                                        height={60}
                                    />
                                    <YAxis
                                        domain={[95, 100]}
                                        tick={{ fontSize: 11 }}
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

                        <Separator className="my-4" />
                    </>
                )}

                <div className="space-y-3">
                    {services.map((service) => (
                        <div key={service.service_id} className="flex items-center justify-between p-3 rounded-lg border">
                            <div className="flex-1">
                                <h4 className="font-medium">{service.service_name}</h4>
                                <p className="text-sm text-muted-foreground">
                                    Current status: {service.status}
                                </p>
                            </div>
                            <div className={`px-3 py-1 rounded-md text-sm font-medium ${getUptimeColorClass(service.uptime_percentage)}`}>
                                {service.uptime_percentage}%
                            </div>
                        </div>
                    ))}
                </div>

                {services.length === 0 && (
                    <div className="text-center py-8 text-muted-foreground">
                        No services found
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

// Simple organization uptime summary
interface OrganizationUptimeSummaryProps {
    averageUptime: number;
    period?: string;
    serviceCount: number;
}

export function OrganizationUptimeSummary({ averageUptime, period = '30d', serviceCount }: OrganizationUptimeSummaryProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Organization Uptime</CardTitle>
                <CardDescription>
                    Average uptime across all services ({PERIOD_LABELS[period]})
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="text-center">
                    <div className={`inline-flex items-center px-6 py-4 rounded-lg text-3xl font-bold ${getUptimeColorClass(averageUptime)}`}>
                        {averageUptime}%
                    </div>
                    <p className="text-muted-foreground mt-2">
                        Across {serviceCount} service{serviceCount !== 1 ? 's' : ''}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
} 