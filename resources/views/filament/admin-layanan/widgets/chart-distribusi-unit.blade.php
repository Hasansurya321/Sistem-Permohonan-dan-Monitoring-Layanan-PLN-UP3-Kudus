<div class="fi-chart-card" x-data="distribusiUnitChart()">
    <div class="fi-chart-card-header">
        <div class="fi-chart-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="3" width="15" height="13"></rect>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                <circle cx="18.5" cy="18.5" r="2.5"></circle>
            </svg>
        </div>
        <span class="fi-chart-card-title">Distribusi Unit</span>
    </div>
    <div class="fi-chart-full">
        <div x-ref="chart" style="height:320px; min-height:320px; width:100%; padding-top:8px;"></div>
    </div>
</div>

@script
<script>
    Alpine.data('distribusiUnitChart', () => ({
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
                series: [{
                    name: 'Jumlah',
                    data: [85, 60, 120, 45]
                }],
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: { show: false },
                    foreColor: 'rgba(255,255,255,0.82)',
                    background: 'transparent',
                    animations: { enabled: true }
                },
                colors: ['#e5c067', '#6043ad', '#82b1c4', '#2350ff'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '48%',
                        distributed: true,
                        borderRadius: 6,
                        borderRadiusApplication: 'end'
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: ['Unit Survey', 'Unit Perencanaan', 'Unit Konstruksi', 'Unit TE'],
                    axisBorder: { show: true, color: 'rgba(255,255,255,0.15)' },
                    axisTicks: { show: true, color: 'rgba(255,255,255,0.15)' },
                    labels: {
                        style: {
                            colors: 'rgba(255,255,255,0.75)',
                            fontSize: '12px',
                            fontWeight: 500
                        }
                    }
                },
                yaxis: {
                    axisBorder: { show: true, color: 'rgba(255,255,255,0.25)' },
                    axisTicks: { show: true, color: 'rgba(255,255,255,0.25)' },
                    labels: {
                        show: true,
                        style: {
                            colors: 'rgba(255,255,255,0.5)',
                            fontSize: '10px'
                        }
                    }
                },
                grid: {
                    borderColor: 'rgba(255,255,255,0.1)',
                    strokeDashArray: 4,
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } }
                },
                tooltip: {
                    theme: 'dark',
                    style: { fontSize: '12px' },
                    y: {
                        formatter: function(val) { return val + ' unit'; }
                    }
                },
                legend: { show: false },
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