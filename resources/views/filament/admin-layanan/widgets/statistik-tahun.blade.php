<div class="fi-chart-card fi-chart-card-wide" x-data="statistikTahun()">
    <div class="fi-chart-card-header" style="justify-content:center;">
        <span class="fi-chart-card-title">Statistik Tahun 2026</span>
    </div>
    <div class="fi-chart-full">
        <div x-ref="chart" style="height:340px; min-height:340px; width:100%;"></div>
    </div>
</div>

@script
<script>
    Alpine.data('statistikTahun', () => ({
        chart: null,

        init() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.renderChart();
                });
            });
        },

        renderChart() {
            if (this.$refs.chart.hasChildNodes()) return;

            this.chart = new ApexCharts(this.$refs.chart, {
                series: [
                    {
                        name: 'Sukses',
                        data: [12000, 14000, 13000, 15500, 14800, 16200, 15800, 17000, 16500, 18000, 17500, 19000]
                    },
                    {
                        name: 'Gagal',
                        data: [7000, 8000, 6000, 7500, 8200, 6800, 7200, 8500, 7800, 9000, 8300, 9500]
                    }
                ],
                chart: {
                    type: 'bar',
                    height: 340,
                    parentHeightOffset: 0,
                    toolbar: { show: false },
                    foreColor: 'rgba(255,255,255,0.82)',
                    background: 'transparent',
                    animations: { enabled: true }
                },
                colors: ['#0d5eff', '#ff3a3a'],
                fill: { opacity: 0.92 },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '38%',
                        borderRadius: 5,
                        borderRadiusApplication: 'end'
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                    axisBorder: { show: true, color: 'rgba(255,255,255,0.15)' },
                    axisTicks: { show: true, color: 'rgba(255,255,255,0.15)' },
                    labels: {
                        style: {
                            colors: 'rgba(255,255,255,0.7)',
                            fontSize: '12px',
                            fontWeight: 500
                        }
                    }
                },
                yaxis: {
                    axisBorder: { show: true, color: 'rgba(255,255,255,0.22)' },
                    axisTicks: { show: true, color: 'rgba(255,255,255,0.18)' },
                    labels: {
                        style: {
                            colors: 'rgba(255,255,255,0.65)',
                            fontSize: '11px'
                        },
                        formatter: function(val) {
                            if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                            return val;
                        }
                    }
                },
                grid: {
                    borderColor: 'rgba(255,255,255,0.14)',
                    strokeDashArray: 5,
                    padding: { top: 35, right: 120, left: 15, bottom: 10 },
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } }
                },
                tooltip: {
                    theme: 'dark',
                    style: { fontSize: '12px' },
                    y: {
                        formatter: function(val) { return val.toLocaleString('id-ID'); }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    offsetX: -90,
                    offsetY: 12,
                    itemMargin: { horizontal: 18 },
                    labels: { colors: 'rgba(255,255,255,0.88)' },
                    markers: { radius: 12 }
                },
                theme: { mode: 'dark' }
            });
            this.chart.render();
        },

        destroy() {
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        }
    }));
</script>
@endscript