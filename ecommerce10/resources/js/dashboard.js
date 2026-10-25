import {
    ArcElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from "chart.js";

Chart.register(
    ArcElement,
    CategoryScale,
    DoughnutController,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
);

Chart.defaults.font.family =
    "'Geist', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.color = "#94a3b8";

const fullMonths = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];

function formatCurrency(value) {
    return `$${new Intl.NumberFormat("id-ID").format(Math.round(value))}`;
}

function getChartData() {
    const data = document.getElementById("dashboard-chart-data");

    if (!data) {
        console.log("No data found.");
        return null;
    }

    try {
        console.log("Data parsed successfully.");
        return JSON.parse(data.textContent);
    } catch {
        console.log("Invalid data format.");
        return null;
    }
}

function areaGradient(topColor, bottomColor) {
    return function (context) {
        const { ctx, chartArea } = context.chart;

        if (!chartArea) return bottomColor;

        const gradient = ctx.createLinearGradient(
            0,
            chartArea.top,
            0,
            chartArea.bottom,
        );

        gradient.addColorStop(0, topColor);
        gradient.addColorStop(1, bottomColor);

        return gradient;
    };
}

function createOrdersChart(canvas, orders) {
    new Chart(canvas, {
        type: "line",
        data: {
            labels: orders.labels,
            datasets: [
                {
                    label: "Income",
                    data: orders.income,
                    borderColor: "#22c55e",
                    backgroundColor: areaGradient(
                        "rgba(34, 197, 94, 0.20)",
                        "rgba(34, 197, 94, 0)",
                    ),
                    fill: true,
                    tension: 0.45,
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "#22c55e",
                    pointHoverBorderColor: "#ffffff",
                    pointHoverBorderWidth: 2,
                    hitRadius: 24,
                },
                {
                    label: "Expense",
                    data: orders.expense,
                    borderColor: "#8b5cf6",
                    backgroundColor: areaGradient(
                        "rgba(139, 92, 246, 0.16)",
                        "rgba(139, 92, 246, 0)",
                    ),
                    fill: true,
                    tension: 0.45,
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "#8b5cf6",
                    pointHoverBorderColor: "#ffffff",
                    pointHoverBorderWidth: 2,
                    hitRadius: 24,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: "nearest",
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: "#111827",
                    titleColor: "#f9fafb",
                    bodyColor: "#d1d5db",
                    borderWidth: 0,
                    cornerRadius: 8,
                    padding: 12,
                    displayColors: false,
                    titleFont: {
                        size: 15,
                        weight: "bold",
                    },
                    bodyFont: {
                        size: 11,
                        weight: "normal",
                    },
                    callbacks: {
                        title: function (items) {
                            return formatCurrency(items[0].parsed.y);
                        },
                        label: function (item) {
                            return `${fullMonths[item.dataIndex]} 6`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    border: {
                        display: false,
                    },
                    ticks: {
                        color: "#94a3b8",
                        font: {
                            size: 11,
                        },
                        maxRotation: 0,
                    },
                },
                y: {
                    display: false,
                    beginAtZero: true,
                    grace: "20%",
                },
            },
        },
    });
}

function createOperatingSystemChart(canvas, operatingSystems) {
    new Chart(canvas, {
        type: "doughnut",
        data: {
            labels: operatingSystems.map(function (item) {
                return item.name;
            }),
            datasets: [
                {
                    data: operatingSystems.map(function (item) {
                        return item.value;
                    }),
                    backgroundColor: operatingSystems.map(function (item) {
                        return item.color;
                    }),
                    borderWidth: 0,
                    hoverOffset: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "66%",
            layout: {
                padding: 4,
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: "#111827",
                    titleColor: "#f9fafb",
                    bodyColor: "#f9fafb",
                    borderWidth: 0,
                    cornerRadius: 8,
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        title: function () {
                            return "";
                        },
                        label: function (context) {
                            return `${context.label} (${context.parsed}%)`;
                        },
                    },
                },
            },
        },
    });
}

document.addEventListener("DOMContentLoaded", function () {
    console.log("DOMContentLoaded event fired.");
    const data = getChartData();

    if (!data) return;

    const ordersCanvas = document.getElementById("orders-chart");
    const operatingSystemCanvas = document.getElementById(
        "operating-system-chart",
    );

    if (ordersCanvas && data.orders) {
        createOrdersChart(ordersCanvas, data.orders);
    }

    if (operatingSystemCanvas && data.operating_systems) {
        createOperatingSystemChart(
            operatingSystemCanvas,
            data.operating_systems,
        );
    }
});
