<div class="fi-chart-card" x-data="pembayaranCharts()">
    <div class="fi-chart-card-header">
        <div class="fi-chart-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
        </div>
        <span class="fi-chart-card-title">Pembayaran</span>
    </div>
    <div class="fi-chart-split">
        <div class="fi-chart-half">
            <div class="fi-chart-half-label">Hari</div>
            <div x-ref="hariChart" style="height:220px; min-height:220px; width:100%;"></div>
        </div>
        <div class="fi-chart-divider"></div>
        <div class="fi-chart-half">
            <div class="fi-chart-half-label">Bulan</div>
            <div x-ref="bulanChart" style="height:220px; min-height:220px; width:100%;"></div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('pembayaranCharts', () => ({
        chartHari: null,
        chartBulan: null,

        init() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.renderHari();
                    this.renderBulan();
                });
            });
        },

        renderHari() {
            if (this.$refs.hariChart.hasChildNodes()) return;

            this.chartHari = new ApexCharts(this.$refs.hariChart, {
                series: [{
                    name: 'Jumlah',
                    data: [38, 12, 105]
                }],
                chart: {
                    type: 'bar',
                    height: 220,
                    toolbar: { show: false },
                    foreColor: 'rgba(255,255,255,0.82)',
                    background: 'transparent',
                    animations: { enabled: true }
                },
                colors: ['#6142ac', '#72b6c7', '#f5c153'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        distributed: true,
                        borderRadius: 8,
                        borderRadiusApplication: 'end'
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: ['Menunggu', 'Gagal', 'Sukses'],
                    axisBorder: { color: 'rgba(255,255,255,0.25)' },
                    axisTicks: { color: 'rgba(255,255,255,0.25)' },
                    labels: { style: { colors: 'rgba(255,255,255,0.7)' } }
                },
                yaxis: {
                    labels: { style: { colors: 'rgba(255,255,255,0.7)' } }
                },
                grid: {
                    borderColor: 'rgba(255,255,255,0.08)',
                    strokeDashArray: 4
                },
                tooltip: {
                    theme: 'dark',
                    style: { fontSize: '12px' }
                },
                legend: { show: false },
                theme: { mode: 'dark' }
            });
            this.chartHari.render();
        },

        renderBulan() {
            if (this.$refs.bulanChart.hasChildNodes()) return;

            this.chartBulan = new ApexCharts(this.$refs.bulanChart, {
                series: [{
                    name: 'Akumulasi',
                    data: [420, 3850]
                }],
                chart: {
                    type: 'bar',
                    height: 220,
                    toolbar: { show: false },
                    foreColor: 'rgba(255,255,255,0.82)',
                    background: 'transparent',
                    animations: { enabled: true }
                },
                colors: ['#72b6c7', '#f5c153'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        distributed: true,
                        borderRadius: 8,
                        borderRadiusApplication: 'end'
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: ['Gagal', 'Sukses'],
                    axisBorder: { color: 'rgba(255,255,255,0.25)' },
                    axisTicks: { color: 'rgba(255,255,255,0.25)' },
                    labels: { style: { colors: 'rgba(255,255,255,0.7)' } }
                },
                yaxis: {
                    labels: { style: { colors: 'rgba(255,255,255,0.7)' } }
                },
                grid: {
                    borderColor: 'rgba(255,255,255,0.08)',
                    strokeDashArray: 4
                },
                tooltip: {
                    theme: 'dark',
                    style: { fontSize: '12px' }
                },
                legend: { show: false },
                theme: { mode: 'dark' }
            });
            this.chartBulan.render();
        },

        destroy() {
            if (this.chartHari) {
                this.chartHari.destroy();
                this.chartHari = null;
            }
            if (this.chartBulan) {
                this.chartBulan.destroy();
                this.chartBulan = null;
            }
        }
    }));
</script>
@endscript