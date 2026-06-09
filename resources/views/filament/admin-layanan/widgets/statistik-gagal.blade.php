<div class="fi-chart-card" x-data="statistikGagal()">
    <div class="fi-chart-card-header">
        <div class="fi-chart-card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
        </div>
        <span class="fi-chart-card-title">Statistik Gagal Bulan Ini</span>
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
                        <span class="fi-progress-value">18</span>
                    </div>
                    <div class="fi-progress-bar">
                        <div class="fi-progress-fill" style="width:40%; background:#1d6dff;"></div>
                    </div>
                </div>
                <div class="fi-progress-item">
                    <div class="fi-progress-label">
                        <span>Lupa Akun</span>
                        <span class="fi-progress-value">9</span>
                    </div>
                    <div class="fi-progress-bar">
                        <div class="fi-progress-fill" style="width:20%; background:#f5c153;"></div>
                    </div>
                </div>
            </div>
            <div class="fi-hybrid-group">
                <div class="fi-hybrid-group-title">Permohonan Layanan</div>
                <div class="fi-progress-item">
                    <div class="fi-progress-label">
                        <span>Tambah Daya</span>
                        <span class="fi-progress-value">12</span>
                    </div>
                    <div class="fi-progress-bar">
                        <div class="fi-progress-fill" style="width:27%; background:#8d5cff;"></div>
                    </div>
                </div>
                <div class="fi-progress-item">
                    <div class="fi-progress-label">
                        <span>Pasang Baru</span>
                        <span class="fi-progress-value">7</span>
                    </div>
                    <div class="fi-progress-bar">
                        <div class="fi-progress-fill" style="width:16%; background:#ba6cff;"></div>
                    </div>
                </div>
            </div>
            <div class="fi-hybrid-group">
                <div class="fi-hybrid-group-title">Pembayaran</div>
                <div class="fi-progress-item">
                    <div class="fi-progress-label">
                        <span>Gagal</span>
                        <span class="fi-progress-value">35</span>
                    </div>
                    <div class="fi-progress-bar">
                        <div class="fi-progress-fill" style="width:78%; background:#ff3434;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('statistikGagal', () => ({
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
                series: [46],
                chart: {
                    type: 'radialBar',
                    height: 180,
                    width: 180,
                    toolbar: { show: false },
                    background: 'transparent'
                },
                colors: ['#ff3434'],
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
                        gradientToColors: ['#ff6b6b'],
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