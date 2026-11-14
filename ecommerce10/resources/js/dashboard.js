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

Chart.defaults.font.family = "'Geist', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.color = "#94a3b8";

function getDashboardData() {
    const element = document.getElementById("dashboard-chart-data");

    if (!element) return null;

    try {
        return JSON.parse(element.textContent);
    } catch (error) {
        console.error("Unable to parse dashboard chart data.", error);
        return null;
    }
}

function createOrdersChart(canvas, orders) {
    new Chart(canvas, {
        type: "line",
        data: {
            labels: orders.labels,
            datasets: [
                {
                    label: "Orders",
                    data: orders.orders,
                    borderColor: "#3b82f6",
                    backgroundColor: "rgba(59, 130, 246, 0.14)",
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: "index", intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "#111827",
                    titleColor: "#f9fafb",
                    bodyColor: "#d1d5db",
                    borderWidth: 0,
                    cornerRadius: 8,
                    padding: 12,
                    callbacks: {
                        label: (context) =>
                            `Orders: ${new Intl.NumberFormat("id-ID").format(context.parsed.y)}`,
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { maxRotation: 0 },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        callback: (value) =>
                            new Intl.NumberFormat("id-ID").format(value),
                    },
                },
            },
        },
    });
}

function createOrderStatusChart(canvas, statuses) {
    new Chart(canvas, {
        type: "doughnut",
        data: {
            labels: statuses.map((status) => status.name),
            datasets: [
                {
                    data: statuses.map((status) => status.value),
                    backgroundColor: statuses.map((status) => status.color),
                    borderWidth: 0,
                    hoverOffset: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "66%",
            layout: { padding: 4 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "#111827",
                    titleColor: "#f9fafb",
                    bodyColor: "#f9fafb",
                    borderWidth: 0,
                    cornerRadius: 8,
                    padding: 10,
                    callbacks: {
                        label: (context) =>
                            `${context.label}: ${new Intl.NumberFormat("id-ID").format(context.parsed)}`,
                    },
                },
            },
        },
    });
}

function initializeDashboard() {
    const periodSelect = document.getElementById("dashboard-period");
    const dateInput = document.getElementById("dashboard-date");
    const dateLabel = document.querySelector('label[for="dashboard-date"]');

    if (periodSelect && dateInput && !periodSelect.dataset.bound) {
        periodSelect.dataset.bound = "true";
        periodSelect.addEventListener("change", () => {
            const period = periodSelect.value;
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, "0");
            const day = String(now.getDate()).padStart(2, "0");

            dateInput.type = period === "day" ? "date" : period === "month" ? "month" : "number";
            dateInput.value = period === "day" ? `${year}-${month}-${day}` : period === "month" ? `${year}-${month}` : String(year);
            dateInput.min = period === "year" ? "2000" : "";
            dateInput.max = period === "year" ? "2100" : "";
            dateInput.placeholder = period === "year" ? "YYYY" : "";
            if (dateLabel) dateLabel.textContent = `Select ${period.charAt(0).toUpperCase()}${period.slice(1)}`;
        });
    }

    const data = getDashboardData();

    if (!data) return;

    const ordersCanvas = document.getElementById("orders-chart");
    const statusCanvas = document.getElementById("order-status-chart");

    if (ordersCanvas && data.orders) {
        Chart.getChart(ordersCanvas)?.destroy();
        createOrdersChart(ordersCanvas, data.orders);
    }

    if (statusCanvas && data.order_statuses) {
        Chart.getChart(statusCanvas)?.destroy();
        createOrderStatusChart(statusCanvas, data.order_statuses);
    }
}

function destroyDashboardCharts() {
    const ordersCanvas = document.getElementById("orders-chart");
    const statusCanvas = document.getElementById("order-status-chart");

    if (ordersCanvas) Chart.getChart(ordersCanvas)?.destroy();
    if (statusCanvas) Chart.getChart(statusCanvas)?.destroy();
}

document.addEventListener("boost:before-swap", destroyDashboardCharts);
document.addEventListener("boost:page-ready", initializeDashboard);

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeDashboard, { once: true });
} else {
    initializeDashboard();
}
