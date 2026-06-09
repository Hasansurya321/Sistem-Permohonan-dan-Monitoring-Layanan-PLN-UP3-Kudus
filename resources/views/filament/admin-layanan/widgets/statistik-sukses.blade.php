<div class="fi-chart-card" x-data="statistikSukses()">
    <div class="fi-chart-card-header">
        <div class="fi-chart-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
            </svg>
        </div>
        <span class="fi-chart-card-title">Statistik Sukses Bulan Ini</span>
    </div>
    <div class="fi-hybrid-body">
        <div class="fi-hybrid-radial">
            <div x-ref="radialChart" style="height:180px; width:180px; margin:0 auto;"></div>
        </div>
        <div class="fi-hybrid-list">
            <div class="fi-hybrid-group">
                <div class="fi-hybrid-group-title">Akun Pelanggan</div>
                <div class="fi-progress-item">
                    <div class="fi-progress-label">
                        <span>Permintaan Akun</span>
                        <span class="fi-progress-value">120</span>
                    </div>
                    <div class="fi-progress-bar">
                        <div class="fi-progress-fill" style="width:85%; background:#1d6dff;"></div>
                    </div>
                </div>
                <div class="fi-progress-item">
                    <div class="fi-progress-label">
                        <span>Lupa Akun</span>
                        <span class="fi-progress-value">45</span>
                    </div>
                    <div class="fi-progress-bar">
                        <div class="fi-progress-fill" style="width:32%; background:#f5c153;"></div>
                    </div>
                </div>
            </div>
            <div class="fi-hybrid-group">
                <div class="fi-hybrid-group-title">Permohonan Layanan</div>
                <div class="fi-progress-item">
                    <div class="fi-progress-label">
                        <span>Tambah Daya</span>
                        <span class="fi-progress-value">85</span>
                    </div>
                    <div class="fi-progress-bar">
                        <div class="fi-progress-fill" style="width:60%; background:#8d5cff;"></div>
                    </div>
                </div>
                <div class="fi-progress-item">
                    <div class="fi-progress-label">
                        <span>Pasang Baru</span>
                        <span class="fi-progress-value">52</span>
                    </div>
                    <div class="fi-progress-bar">
                        <div class="fi-progress-fill" style="width:37%; background:#ba6cff;"></div>
                    </div>
                </div>
            </div>
            <div class="fi-hybrid-group">
                <div class="fi-hybrid-group-title">Pembayaran</div>
                <div class="fi-progress-item">
                    <div class="fi-progress-label">
                        <span>Sukses</span>
                        <span class="fi-progress-value">280</span>
                    </div>
                    <div class="fi-progress-bar">
                        <div class="fi-progress-fill" style="width:100%; background:#33d6ff;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('statistikSukses', () => ({
        chart: null,

        init() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.renderRadial();
                });
            });
        },

        renderRadial() {
            if (this.$refs.radialChart.hasChildNodes()) return;

            this.chart = new ApexCharts(this.$refs.radialChart, {
                series: [76],
                chart: {
                    type: 'radialBar',
                    height: 180,
                    width: 180,
                    toolbar: { show: false },
                    background: 'transparent'
                },
                colors: ['#0d5eff'],
                plotOptions: {
                    radialBar: {
                        hollow: {
                            size: '65%'
                        },
                        track: {
                            background: 'rgba(255,255,255,0.08)',
                            strokeWidth: '100%'
                        },
                        dataLabels: {
                            show: true,
                            name: { show: false },
                            value: {
                                show: true,
                                fontSize: '32px',
                                fontWeight: 700,
                                color: '#ffffff',
                                offsetY: 4,
                                formatter: function(val) { return val + '%'; }
                            }
                        }
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'dark',
                        type: 'horizontal',
                        gradientToColors: ['#3d8bff'],
                        stops: [0, 100]
                    }
                },
                stroke: {
                    lineCap: 'round'
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