import { Chart } from "chart.js/auto";

function cssVar(name, fallback) {
    const value = getComputedStyle(document.documentElement)
        .getPropertyValue(name)
        .trim();

    return value || fallback;
}

function readChartPayload() {
    const element = document.getElementById("earnings-chart-data");

    if (!element) {
        return null;
    }

    try {
        return JSON.parse(element.textContent);
    } catch {
        return null;
    }
}

function destroyIfExists(chart) {
    if (chart) {
        chart.destroy();
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const payload = readChartPayload();

    if (!payload || !payload.has_data) {
        return;
    }

    const primary = cssVar("--color-primary", "#2596be");
    const primaryShade = cssVar("--color-primary-shade-1", "#1e7898");
    const success = cssVar("--color-success", "#047857");
    const textMuted = cssVar("--color-text-muted", "#475569");
    const border = cssVar("--color-border", "#e2e8f0");

    const baseOptions = {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
            mode: "index",
            intersect: false,
        },
        plugins: {
            legend: {
                labels: {
                    color: textMuted,
                },
            },
        },
        scales: {
            x: {
                ticks: { color: textMuted },
                grid: { color: border },
            },
            y: {
                ticks: { color: textMuted },
                grid: { color: border },
                beginAtZero: true,
            },
        },
    };

    const payoutCanvas = document.getElementById("earnings-payout-chart");
    if (payoutCanvas) {
        destroyIfExists(payoutCanvas.chart);
        payoutCanvas.chart = new Chart(payoutCanvas, {
            type: "line",
            data: {
                labels: payload.labels,
                datasets: [
                    {
                        label: "Completed (invoiced)",
                        data: payload.daily.completed_revenue,
                        borderColor: primary,
                        backgroundColor: `${primary}33`,
                        tension: 0.25,
                        fill: false,
                    },
                    {
                        label: "Paid (collected)",
                        data: payload.daily.paid_revenue,
                        borderColor: success,
                        backgroundColor: `${success}33`,
                        tension: 0.25,
                        fill: false,
                    },
                ],
            },
            options: {
                ...baseOptions,
                plugins: {
                    ...baseOptions.plugins,
                    title: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const value = context.parsed.y ?? 0;

                                return `${context.dataset.label}: ₱${Number(value).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                            },
                        },
                    },
                },
                scales: {
                    ...baseOptions.scales,
                    y: {
                        ...baseOptions.scales.y,
                        ticks: {
                            ...baseOptions.scales.y.ticks,
                            callback(value) {
                                return `₱${Number(value).toLocaleString("en-PH")}`;
                            },
                        },
                    },
                },
            },
        });
    }

    const tripsCanvas = document.getElementById("earnings-trips-chart");
    if (tripsCanvas) {
        destroyIfExists(tripsCanvas.chart);
        tripsCanvas.chart = new Chart(tripsCanvas, {
            type: "bar",
            data: {
                labels: payload.labels,
                datasets: [
                    {
                        label: "Completed trips",
                        data: payload.daily.completed_trips,
                        backgroundColor: primaryShade,
                        borderColor: primaryShade,
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                ...baseOptions,
                plugins: {
                    ...baseOptions.plugins,
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const value = context.parsed.y ?? 0;

                                return `${context.dataset.label}: ${value}`;
                            },
                        },
                    },
                },
                scales: {
                    ...baseOptions.scales,
                    y: {
                        ...baseOptions.scales.y,
                        ticks: {
                            ...baseOptions.scales.y.ticks,
                            stepSize: 1,
                            precision: 0,
                        },
                    },
                },
            },
        });
    }
});
