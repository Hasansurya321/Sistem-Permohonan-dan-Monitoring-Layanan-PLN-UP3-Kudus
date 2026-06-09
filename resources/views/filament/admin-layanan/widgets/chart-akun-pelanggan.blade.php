<div class="fi-chart-card" x-data="akunPelangganCharts()">
    {{-- Header --}}
    <div class="fi-chart-card-header">
        <div class="fi-chart-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <span class="fi-chart-card-title">Akun Pelanggan</span>
    </div>

    {{-- Chart Split: HARI | BULAN --}}
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
    Alpine.data('akunPelangganCharts', () => ({
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
                    data: [120, 45]
                }],
                chart: {
                    type: 'bar',
                    height: 220,
                    toolbar: { show: false },
                    foreColor: 'rgba(255,255,255,0.82)',
                    background: 'transparent',
                    animations: { enabled: true }
                },
                colors: ['#59b8c9', '#f5c153'],
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
                    categories: ['Permintaan Akun', 'Lupa Password'],
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
                    data: [2850, 1320]
                }],
                chart: {
                    type: 'bar',
                    height: 220,
                    toolbar: { show: false },
                    foreColor: 'rgba(255,255,255,0.82)',
                    background: 'transparent',
                    animations: { enabled: true }
                },
                colors: ['#59b8c9', '#f5c153'],
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
                    categories: ['Permintaan Akun', 'Lupa Password'],
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