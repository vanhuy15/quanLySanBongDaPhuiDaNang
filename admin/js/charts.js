/**
 * ADMIN CHARTS MODULE
 * Chart.js Line & Bar charts for Dashboard statistics and Revenue analytics
 */

const commonChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      onClick: () => false,
      onHover: (e) => (e.native.target.style.cursor = "default"),
    },
    tooltip: {
      callbacks: {
        label: function (context) {
          return new Intl.NumberFormat("vi-VN", {
            style: "currency",
            currency: "VND",
          }).format(context.parsed.y);
        },
      },
    },
  },
  scales: {
    x: {
      ticks: {
        maxRotation: 45,
        minRotation: 45,
        autoSkip: false,
        font: { size: 10 },
      },
    },
    y: {
      beginAtZero: true,
      ticks: {
        callback: function (value) {
          return new Intl.NumberFormat("vi-VN").format(value) + " VND";
        },
      },
    },
  },
};

function setupDashboardChart() {
  const canvas = document.getElementById("revenueChart");
  if (!canvas || typeof Chart === "undefined") return;

  const ctx = canvas.getContext("2d");
  const labels = JSON.parse(canvas.dataset.labels || "[]");
  const data = JSON.parse(canvas.dataset.revenue || "[]");

  const gradient = ctx.createLinearGradient(0, 0, 0, 300);
  gradient.addColorStop(0, "rgba(0, 122, 100, 0.25)");
  gradient.addColorStop(1, "rgba(0, 122, 100, 0.0)");

  new Chart(ctx, {
    type: "line",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Doanh thu (VND)",
          data: data,
          backgroundColor: gradient,
          borderColor: "#007a64",
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointRadius: 5,
          pointHoverRadius: 7,
          pointBackgroundColor: "#ffffff",
          pointBorderColor: "#007a64",
          pointBorderWidth: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: "#0f172a",
          padding: 10,
          cornerRadius: 8,
          callbacks: {
            label: function (context) {
              return " " + Number(context.parsed.y).toLocaleString("vi-VN") + " VNĐ";
            },
          },
        },
      },
      scales: {
        x: {
          ticks: {
            maxRotation: 0,
            minRotation: 0,
            autoSkip: false,
            font: { size: 11 },
          },
          grid: { display: false },
        },
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return Number(value).toLocaleString("vi-VN") + " VNĐ";
            },
          },
        },
      },
    },
  });
}

function setupRevenueReportChart() {
  const canvas = document.getElementById("revenueByFieldChart");
  if (!canvas || typeof Chart === "undefined") return;

  const ctx = canvas.getContext("2d");
  const labels = JSON.parse(canvas.dataset.labels || "[]");
  const data = JSON.parse(canvas.dataset.revenue || "[]");

  new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Doanh thu (VND)",
          data: data,
          backgroundColor: "rgba(0, 122, 100, 0.75)",
          borderColor: "#007a64",
          borderWidth: 1.5,
          borderRadius: 8,
          barPercentage: 0.5,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: "#0f172a",
          padding: 10,
          cornerRadius: 8,
          callbacks: {
            label: function (context) {
              return " " + Number(context.parsed.y).toLocaleString("vi-VN") + " VNĐ";
            },
          },
        },
      },
      scales: {
        x: {
          ticks: {
            maxRotation: 0,
            minRotation: 0,
            autoSkip: false,
            font: { size: 11, lineHeight: 1.2 },
            callback: function (value) {
              const label = this.getLabelForValue(value);
              if (label.length < 15) return label;
              const words = label.split(" ");
              let lines = [];
              let currentLine = words[0];
              for (let i = 1; i < words.length; i++) {
                if (currentLine.length + words[i].length < 20) {
                  currentLine += " " + words[i];
                } else {
                  lines.push(currentLine);
                  currentLine = words[i];
                }
              }
              lines.push(currentLine);
              return lines;
            },
          },
        },
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return Number(value).toLocaleString("vi-VN") + " VNĐ";
            },
          },
        },
      },
    },
  });
}
