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

function createSalesChart(canvas, sales) {
    new Chart(canvas, {
        type: "line",
        data: {
            labels: sales.labels,
            datasets: [
                {
                    label: "Penjualan (Rp)",
                    data: sales.values,
                    yAxisID: "y",
                    borderColor: "#0d9488",
                    backgroundColor: "rgba(13, 148, 136, 0.13)",
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointRadius: 2.5,
                    pointHoverRadius: 5,
                },
                {
                    label: "Jumlah pesanan",
                    data: sales.orders,
                    yAxisID: "y1",
                    borderColor: "#3b82f6",
                    backgroundColor: "#3b82f6",
                    borderDash: [5, 4],
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: "index", intersect: false },
            plugins: {
                legend: { display: true, position: "bottom", labels: { usePointStyle: true, boxWidth: 7, padding: 18 } },
                tooltip: {
                    backgroundColor: "#111827",
                    titleColor: "#f9fafb",
                    bodyColor: "#d1d5db",
                    borderWidth: 0,
                    cornerRadius: 8,
                    padding: 12,
                    callbacks: {
                        label: (context) => context.dataset.yAxisID === "y"
                            ? ` ${context.dataset.label}: Rp ${new Intl.NumberFormat("id-ID").format(context.parsed.y)}`
                            : ` ${context.dataset.label}: ${new Intl.NumberFormat("id-ID").format(context.parsed.y)}`,
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 12 } },
                y: {
                    beginAtZero: true,
                    position: "left",
                    ticks: { callback: (value) => new Intl.NumberFormat("id-ID", { notation: "compact" }).format(value) },
                },
                y1: {
                    beginAtZero: true,
                    position: "right",
                    grid: { drawOnChartArea: false },
                    ticks: { precision: 0 },
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

/** Membaca payload metrik dari Blade dan menggambar ulang grafik dashboard. */
function initializeDashboard() {
    const periodSelect = document.getElementById("dashboard-period");
    const dateInput = document.getElementById("dashboard-date");
    const dateLabel = document.querySelector('label[for="dashboard-date"]');
    const dateField = document.getElementById("dashboard-date-field");

    if (periodSelect && dateInput && !periodSelect.dataset.bound) {
        periodSelect.dataset.bound = "true";
        periodSelect.addEventListener("change", () => {
            const period = periodSelect.value;
            const currentValue = dateInput.value;
            const now = new Date();
            const year = currentValue.match(/^\d{4}/)?.[0] ?? String(now.getFullYear());
            const month = currentValue.match(/^\d{4}-(\d{2})/)?.[1]
                ?? String(now.getMonth() + 1).padStart(2, "0");
            const day = currentValue.match(/^\d{4}-\d{2}-(\d{2})$/)?.[1]
                ?? String(now.getDate()).padStart(2, "0");

            if (dateField) dateField.hidden = period === "7days" || period === "30days";
            dateInput.type = period === "day" ? "date" : period === "month" ? "month" : "number";
            dateInput.value = period === "day"
                ? (currentValue.length === 10 ? currentValue : `${year}-${month}-${day}`)
                : period === "month"
                    ? (currentValue.length >= 7 ? currentValue.slice(0, 7) : `${year}-${month}`)
                    : year;
            dateInput.min = period === "year" ? "2000" : "";
            dateInput.max = period === "year" ? "2100" : "";
            dateInput.placeholder = period === "year" ? "YYYY" : "";
            if (dateLabel) dateLabel.textContent = period === "month" ? "Pilih bulan" : period === "day" ? "Pilih tanggal" : "Pilih tahun";
        });
    }

    const data = getDashboardData();

    if (!data) return;

    const salesCanvas = document.getElementById("sales-chart");
    const statusCanvas = document.getElementById("order-status-chart");

    if (salesCanvas && data.sales) {
        Chart.getChart(salesCanvas)?.destroy();
        createSalesChart(salesCanvas, data.sales);
    }

    if (statusCanvas && data.order_statuses) {
        Chart.getChart(statusCanvas)?.destroy();
        createOrderStatusChart(statusCanvas, data.order_statuses);
    }
}

// Hancurkan instance Chart.js sebelum konten diganti agar tidak terjadi memory leak.
function destroyDashboardCharts() {
    const salesCanvas = document.getElementById("sales-chart");
    const statusCanvas = document.getElementById("order-status-chart");

    if (salesCanvas) Chart.getChart(salesCanvas)?.destroy();
    if (statusCanvas) Chart.getChart(statusCanvas)?.destroy();
}

document.addEventListener("boost:before-swap", destroyDashboardCharts);
document.addEventListener("boost:page-ready", initializeDashboard);

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeDashboard, { once: true });
} else {
    initializeDashboard();
}
