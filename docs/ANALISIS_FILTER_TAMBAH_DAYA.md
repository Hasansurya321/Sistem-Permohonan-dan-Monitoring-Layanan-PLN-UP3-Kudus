# ANALISIS MENDALAM: IMPLEMENTASI FILTER PADA HALAMAN TAMBAH DAYA

> **Dokumen ini merupakan hasil investigasi teknis untuk replikasi filter yang identik pada halaman Pembayaran.**
> _Tanggal: 30 Mei 2026_
> _Basis Kode: Filament Panel Admin Layanan (Tambah Daya) vs Pelanggan Blade (Pembayaran)_

---

## ⚠️ KONTEKS PENTING

**Halaman Tambah Daya** dibangun di atas **Filament Panel Admin Layanan** dengan arsitektur Livewire.
**Halaman Pembayaran** saat ini adalah **Blade tradisional Laravel** (non-Filament, non-Livewire) yang mewarisi `layouts.pelanggan`.

Keduanya berada di **panel/user yang berbeda**:

- Tambah Daya → Panel `admin-layanan` (Filament \ AdminLayanan)
- Pembayaran → Route `pelanggan` (Blade \ pelanggan)

---

## 1. STRUKTUR KOMPONEN FILTER

### 1.1. Jenis Komponen

**BUKAN** komponen Filament bawaan (bukan `Tabs`, `Filters`, `SegmentedButtons`, `StatsOverview`, dll).
**Menggunakan** segmented tabs **kustom** (custom HTML + CSS + Livewire).

Tidak ada komponen Filament yang digunakan sebagai filter. Semua dibangun dari:

- `<button>` HTML
- `wire:click` Livewire
- CSS kustom di theme.css

### 1.2. File yang Bertanggung Jawab

| Layer              | File                                                                 | Peran                                                           |
| ------------------ | -------------------------------------------------------------------- | --------------------------------------------------------------- |
| **PHP Page Class** | `app/Filament/AdminLayanan/Pages/TambahDaya.php`                     | Logika filter via `activeTab`, `setTab()`, `getFilteredQuery()` |
| **Blade View**     | `resources/views/filament/admin-layanan/pages/tambah-daya.blade.php` | HTML struktur segmented tabs + loading states                   |
| **CSS**            | `resources/css/filament/admin-layanan/theme.css`                     | Semua styling (baris 795-916)                                   |
| **CSS Global**     | `resources/css/app.css`                                              | Tidak ada kontribusi untuk filter ini                           |
| **CSS Lain**       | `patch-v3.css`, `resources/css/app.css`                              | Tidak ada kontribusi                                            |

### 1.3. Struktur Komponen (Hierarki)

```
<x-filament-panels::page>
  └── div.tambah-daya-container
       ├── div.td-status-tabs          ← FILTER GROUP (Segmented Tabs)
       │    ├── button.td-tab.td-tab-active   [Menunggu]
       │    ├── button.td-tab                 [Pending]
       │    └── button.td-tab                 [Selesai]
       └── div.td-table-container
            ├── div.td-loading-overlay   (hidden, tampil saat loading)
            │    └── div.td-loading-spinner
            └── {{ $this->table }}       (Filament Table)
```

---

## 2. DIMENSI DAN UKURAN

### 2.1. Filter Container (`.td-status-tabs`)

| Properti          | Nilai                                       | CSS                                                                        |
| ----------------- | ------------------------------------------- | -------------------------------------------------------------------------- |
| **Layout**        | CSS Grid                                    | `display: grid; grid-template-columns: repeat(3, 1fr)`                     |
| **Lebar**         | 100% dari parent (full width)               | `width: 100%`                                                              |
| **Background**    | `#093c5d`                                   | `background: #093c5d`                                                      |
| **Border Radius** | 12px                                        | `border-radius: 12px`                                                      |
| **Padding**       | 8px (all sides)                             | `padding: 8px`                                                             |
| **Gap antar tab** | 4px                                         | `gap: 4px`                                                                 |
| **Margin Bottom** | 20px (terhadap tabel)                       | `margin-bottom: 20px`                                                      |
| **Margin Top**    | 8px (terhadap KPI Card atau konten di atas) | BERSIFAT KONTEXSTUAL dari `.tambah-daya-container` yaitu `margin-top: 8px` |
| **Box Sizing**    | border-box                                  | `box-sizing: border-box`                                                   |

### 2.2. Individual Tab Button (`.td-tab`)

| Properti               | Nilai                      | CSS                                     |
| ---------------------- | -------------------------- | --------------------------------------- |
| **Tinggi**             | Auto (ditentukan padding)  | Tidak ada `height` tetap                |
| **Padding Vertical**   | 10px                       | `padding: 10px 16px`                    |
| **Padding Horizontal** | 16px                       | `padding: 10px 16px`                    |
| **Border Radius**      | 8px                        | `border-radius: 8px`                    |
| **Font Size**          | 14px                       | `font-size: 14px`                       |
| **Font Weight**        | 500 (normal), 600 (active) | `font-weight: 500` → `font-weight: 600` |
| **Font Family**        | inherit (dari sistem)      | `font-family: inherit`                  |
| **Line Height**        | 1.2                        | `line-height: 1.2`                      |
| **Width**              | 100% dari grid cell        | `width: 100%`                           |
| **Text Align**         | Center                     | `text-align: center`                    |
| **Transisi**           | `all 0.2s ease`            | `transition: all 0.2s ease`             |

### 2.3. Spacing Summary (Layout Flow)

```
KPI Cards / Konten Sebelumnya (dari PermohonanLayanan/induk)
  ↓ margin-top dari .tambah-daya-container = 8px
td-status-tabs (Segmented Filter)
  ↓ margin-bottom = 20px
td-table-container (Tabel)
```

### 2.4. Ukuran Aktual (perhitungan)

```
Container .td-status-tabs:
  ┌► Tinggi total ≈ 8px (padding) + 4px (gap antar tab internal)
     + {10px padding top + 14px font * 1.2 line-height + 10px padding bottom = ~37px per tab}
     + 8px (padding bottom) = ~53px per baris
  └► Lebar = 100% dari content area

Catatan: Karena display:grid, tinggi ditentukan oleh konten tertinggi di grid cell.
Tinggi aktual ~ 53-57px tergantung font rendering.
```

---

## 3. POSISI DAN LAYOUT

### 3.1. Layout Diagram

```
┌──────────────────────────────────────────────────────────────┐
│  KPI Cards (dari PermohonanLayanan)                          │
│  ┌──────────────┐  ┌──────────────┐                          │
│  │  Tambah Daya │  │ Pasang Baru  │                          │
│  └──────────────┘  └──────────────┘                          │
│                                                              │
│  ≈ 8px (margin-top .tambah-daya-container)                   │
│                                                              │
│  ┌──────────────────────────────────────────────────────────┐│
│  │  ┌──────────┬──────────┬──────────┐  ← .td-status-tabs  ││
│  │  │ MENUNGGU │ PENDING  │ SELESAI  │  (grid 3 kolom)      ││
│  │  └──────────┴──────────┴──────────┘                     ││
│  └──────────────────────────────────────────────────────────┘│
│                                                              │
│  ≈ 20px (margin-bottom .td-status-tabs)                      │
│                                                              │
│  ┌──────────────────────────────────────────────────────────┐│
│  │  T A B E L   (td-table-container)                        ││
│  │                                                          ││
│  │  No | Nama | NIK | Daya | Status | ...                  ││
│  │  ─────────────────────────────────────────────────────   ││
│  │  1  | A     | ... | ...  | ...    | ...                 ││
│  └──────────────────────────────────────────────────────────┘│
└──────────────────────────────────────────────────────────────┘
```

### 3.2. Alignment & Layout Detail

| Aspek                          | Nilai                                                                       |
| ------------------------------ | --------------------------------------------------------------------------- |
| **Display**                    | `grid` (bukan flex)                                                         |
| **Grid Template**              | `repeat(3, 1fr)` — 3 kolom sama lebar                                       |
| **Alignment Horizontal**       | Center (text-align: center)                                                 |
| **Alignment Vertical**         | Stretch (default grid)                                                      |
| **Jumlah Kolom Desktop**       | 3 (full width)                                                              |
| **Jumlah Kolom Tablet**        | 3 (tetap, karena tidak ada media query untuk filter)                        |
| **Jumlah Kolom Mobile**        | 3 (tetap, tidak ada perubahan di media query)                               |
| **Posisi terhadap Search Bar** | Tidak ada search bar di halaman ini (search di tabel via Filament built-in) |

### 3.3. Posisi terhadap Elemen Sekitar

**Pada PermohonanLayanan (halaman induk):**

```
x-filament-panels::page
  → slot header (default page header)
  → div.akun-kpi-grid (KPI Cards link ke Tambah Daya & Pasang Baru)
  → (ketika klik link, navigasi ke TambahDaya/PasangBaru)
```

**Pada TambahDaya:**

```
x-filament-panels::page
  → div.tambah-daya-container
    → td-status-tabs (FILTER)
    → td-table-container (TABLE)
```

**Catatan:** Tidak ada hero section, tidak ada search bar, tidak ada widget di halaman Tambah Daya. Hanya filter + tabel.

---

## 4. STYLING VISUAL

### 4.1. Warna dan Style

| Properti                 | Nilai Normal  | Nilai Hover                 | Nilai Active (`td-tab-active`) |
| ------------------------ | ------------- | --------------------------- | ------------------------------ |
| **Background Color**     | `transparent` | `rgba(255, 255, 255, 0.08)` | `#1fa8c9`                      |
| **Text Color**           | `#ffffff`     | `#ffffff` (inherited)       | `#ffffff`                      |
| **Border**               | `none`        | `none`                      | `none`                         |
| **Font Weight**          | `500`         | `500`                       | `600`                          |
| **Border Radius**        | `8px`         | `8px`                       | `8px`                          |
| **Outline**              | `none`        | `none`                      | `none`                         |
| **Background Container** | `#093c5d`     | —                           | —                              |

### 4.2. Typography

| Properti                 | Nilai                                         |
| ------------------------ | --------------------------------------------- |
| **Font Family**          | `inherit` (mengikuti sistem/default aplikasi) |
| **Font Size**            | `14px`                                        |
| **Font Weight (normal)** | `500`                                         |
| **Font Weight (active)** | `600`                                         |
| **Line Height**          | `1.2`                                         |
| **Color**                | `#ffffff`                                     |
| **Text Align**           | `center`                                      |

### 4.3. Shadow

| Element                         | Shadow                                      |
| ------------------------------- | ------------------------------------------- |
| **Container (.td-status-tabs)** | Tidak ada shadow                            |
| **Tabel (.td-table-container)** | `box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06)` |

### 4.4. Tailwind Class Mapping (Jika Diimplementasikan Ulang)

```css
/* Jika dikonversi ke Tailwind, ekuivalen dengan: */

/* Container */
.td-status-tabs → bg-[#093c5d] rounded-xl p-2 gap-[4px] mb-5 grid grid-cols-3 w-full

/* Tab Individual */
.td-tab → bg-transparent text-white border-none rounded-lg px-4 py-[10px]
          text-sm font-medium cursor-pointer transition-all duration-200
          outline-none font-inherit leading-tight text-center w-full

/* Tab Hover */
.td-tab:hover → hover:bg-white/10

/* Tab Active */
.td-tab-active → bg-[#1fa8c9] text-white font-semibold rounded-lg

/* Tab Disabled (loading) */
.td-tab:disabled → opacity-60 cursor-not-allowed
```

---

## 5. RESPONSIVITAS

### 5.1. Perilaku Responsif

| Viewport                 | Perilaku                                |
| ------------------------ | --------------------------------------- |
| **Desktop (>1280px)**    | 3 tombol dalam grid 1 baris, full width |
| **Laptop (1024-1279px)** | 3 tombol dalam grid 1 baris, full width |
| **Tablet (768-1023px)**  | 3 tombol dalam grid 1 baris, full width |
| **Mobile (<768px)**      | 3 tombol dalam grid 1 baris, full width |

### 5.2. Analisis Kritis Responsif

**TIDAK ADA media query khusus** untuk filter di theme.css. Media query yang ada (line 919-939) hanya mencakup:

- `.fi-topbar` height
- Brand image/teks
- `.fi-content` padding

**IMPLIKASI:**

- Filter akan tetap 3 kolom dalam 1 baris di semua ukuran layar
- Pada mobile 360px, 3 tombol dengan padding 16px + gap 4px + padding container 8px
    - Ruang tersedia per tombol: (360 - 16) / 3 ≈ 115px
    - Teks "Menunggu" (≈60px) masih muat
    - Teks "Pending" (≈45px) masih muat
    - Teks "Selesai" (≈50px) masih muat
- Risiko pada viewport <320px: teks mungkin overflow

### 5.3. Responsif Flow

```
Semua ukuran layar:
  [MENUNGGU] [PENDING] [SELESAI]    ← 3 kolom grid, tetap 1 baris
```

**Tidak ada perilaku:**

- Wrap ke bawah (kecuali grid force-break di grid template)
- Menjadi dropdown
- Menjadi scroll horizontal
- Collapse/menghilang

---

## 6. INTERAKSI DAN BEHAVIOUR

### 6.1. Mekanisme Filter

| Aspek                | Detail                                                                    |
| -------------------- | ------------------------------------------------------------------------- |
| **Framework**        | Livewire 3 (built-in Filament)                                            |
| **Event**            | `wire:click="setTab('menunggu')"` — memanggil method PHP langsung         |
| **Property Binding** | `$activeTab` — reactive Livewire property                                 |
| **Debounce**         | TIDAK ADA — langsung update saat diklik                                   |
| **Polling**          | TIDAK ADA                                                                 |
| **wire:navigate**    | TIDAK ADA (ini bukan navigasi halaman, melainkan interaksi dalam halaman) |
| **Delay**            | TIDAK ADA — segera setelah klik, Livewire mengirim request AJAX           |

### 6.2. Loading States

| Aspek                            | Detail                                                                                                          |
| -------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| **Button disabled saat loading** | Ya, `wire:loading.attr="disabled"` dan `wire:target="setTab"`                                                   |
| **Loading text**                 | Teks tombol berubah menjadi "Memuat..." selama loading via `wire:loading` / `wire:loading.remove`               |
| **Table overlay**                | Overlay putih semi-transparan + spinner muncul di atas tabel                                                    |
| **Table opacity**                | Tabel menjadi 50% opacity saat loading via `wire:loading.class="td-table-loading"`                              |
| **Pointer events**               | Diblokir di tabel saat loading                                                                                  |
| **Target scope**                 | Semua loading states terikat ke `wire:target="setTab"`, artinya loading akan muncul untuk perubahan tab MANAPUN |

### 6.3. Alur Update Data Setelah Filter

```
1. User klik tombol "Pending"
   ↓
2. Browser disable tombol + show "Memuat..."
   ↓
3. Livewire kirim AJAX request ke server
   ↓
4. Server panggil setTab('pending')
   ↓
5. $this->activeTab = 'pending'
   ↓
6. Livewire re-render komponen
   ↓
7. Blade re-render: class td-tab-active pindah
   ↓
8. {{ $this->table }} di-render ulang dengan query dari getFilteredQuery()
   ↓
9. Browser update DOM + loading states hilang
   ↓
10. Tabel menampilkan data sesuai tab
```

### 6.4. Method Mapping

```php
public string $activeTab = 'menunggu';  // default tab

#[On('tab-changed')]
public function setTab(string $tab): void
{
    $this->activeTab = $tab;
}

// Di getFilteredQuery():
return match ($this->activeTab) {
    'menunggu' => $query->waitingForAdmin(),    // Filter: waiting for admin
    'pending'  => $query->pendingRevision(),     // Filter: pending revision
    'selesai'  => $query->adminSuccess()         // Filter: completed (success + failed)
              ->orWhere(...adminFailed()),
};
```

---

## 7. CSS YANG MENGONTROL FILTER

### 7.1. Semua Selector CSS yang Mempengaruhi Filter

**Source: `resources/css/filament/admin-layanan/theme.css`**

```css
/* ─── TAMBAH DAYA — SEGMENTED NAVIGATION TABS ─── (baris 794-916) */

/* Container utama */
.tambah-daya-container {
    /* baris 796-799 */
    margin-top: 8px;
    width: 100%;
}

/* Filter group — grid 3 kolom */
.td-status-tabs {
    /* baris 800-810 */
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    width: 100%;
    background: #093c5d;
    border-radius: 12px;
    padding: 8px;
    gap: 4px;
    margin-bottom: 20px;
    box-sizing: border-box;
}

/* Tab button — base style */
.td-tab {
    /* baris 812-828 */
    background: transparent;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    outline: none;
    font-family: inherit;
    line-height: 1.2;
    text-align: center;
    width: 100%;
    box-sizing: border-box;
}

/* Tab hover */
.td-tab:hover {
    /* baris 830-832 */
    background: rgba(255, 255, 255, 0.08);
}

/* Tab active state */
.td-tab-active {
    /* baris 834-839 */
    background: #1fa8c9 !important;
    color: #ffffff;
    border-radius: 8px;
    font-weight: 600;
}

/* Loading states */
.td-tab:disabled {
    /* baris 868-871 */
    opacity: 0.6;
    cursor: not-allowed;
}

.td-tab-loading {
    /* baris 873-877 */
    font-size: 13px;
    font-style: italic;
    opacity: 0.8;
}

/* Table container */
.td-table-container {
    /* baris 841-848, 879-887 */
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid var(--pln-gray-border);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    width: 100%;
    position: relative;
}

.td-table-loading {
    /* baris 883-887 */
    opacity: 0.5;
    pointer-events: none;
    transition: opacity 0.15s ease;
}

/* Loading overlay (tampil di atas tabel) */
.td-loading-overlay {
    /* baris 889-901 */
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    border-radius: 12px;
}

.td-loading-spinner {
    /* baris 903-910 */
    width: 32px;
    height: 32px;
    border: 3px solid #e5e7eb;
    border-top-color: #093c5d;
    border-radius: 50%;
    animation: td-spin 0.7s linear infinite;
}

@keyframes td-spin {
    /* baris 912-916 */
    to {
        transform: rotate(360deg);
    }
}
```

### 7.2. Tidak ada CSS lain yang mempengaruhi filter

- `resources/css/app.css` — tidak ada kontribusi
- `tailwind.config.js` — tidak ada konfigurasi khusus
- `patch-v3.css` — tidak ada kontribusi
- Tidak ada inline style di Blade
- Tidak ada CSS di dalam Blade (`<style>` tags)
- Filament default CSS — tidak ada override yang relevan

---

## 8. DOM STRUCTURE

### 8.1. Struktur HTML yang Dihasilkan Browser (Setelah Livewire Render)

```html
<main class="fi-main">
    <div class="fi-content">
        <!-- Page Content -->
        <div class="filament-panels-page">
            <!-- tambah-daya-container -->
            <div class="tambah-daya-container">
                <!-- FILTER GROUP -->
                <div class="td-status-tabs">
                    <!-- Tab 1: Menunggu (default active) -->
                    <button
                        type="button"
                        class="td-tab td-tab-active"
                        wire:click="setTab('menunggu')"
                        wire:loading.attr="disabled"
                        wire:target="setTab"
                    >
                        <span
                            wire:loading.remove=""
                            wire:target="setTab('menunggu')"
                            >Menunggu</span
                        >
                        <!-- loading text tersembunyi saat tidak loading -->
                        <span
                            wire:loading=""
                            wire:target="setTab('menunggu')"
                            class="td-tab-loading"
                            style="display: none;"
                            >Memuat...</span
                        >
                    </button>

                    <!-- Tab 2: Pending -->
                    <button
                        type="button"
                        class="td-tab"
                        wire:click="setTab('pending')"
                        wire:loading.attr="disabled"
                        wire:target="setTab"
                    >
                        <span
                            wire:loading.remove=""
                            wire:target="setTab('pending')"
                            >Pending</span
                        >
                        <span
                            wire:loading=""
                            wire:target="setTab('pending')"
                            class="td-tab-loading"
                            style="display: none;"
                            >Memuat...</span
                        >
                    </button>

                    <!-- Tab 3: Selesai -->
                    <button
                        type="button"
                        class="td-tab"
                        wire:click="setTab('selesai')"
                        wire:loading.attr="disabled"
                        wire:target="setTab"
                    >
                        <span
                            wire:loading.remove=""
                            wire:target="setTab('selesai')"
                            >Selesai</span
                        >
                        <span
                            wire:loading=""
                            wire:target="setTab('selesai')"
                            class="td-tab-loading"
                            style="display: none;"
                            >Memuat...</span
                        >
                    </button>
                </div>

                <!-- TABLE CONTAINER (saat loading: class = "td-table-container td-table-loading") -->
                <div
                    class="td-table-container"
                    wire:loading.class="td-table-loading"
                    wire:target="setTab"
                >
                    <!-- Loading Overlay (hidden saat tidak loading) -->
                    <div
                        wire:loading=""
                        wire:target="setTab"
                        class="td-loading-overlay"
                        style="display: none;"
                    >
                        <div class="td-loading-spinner"></div>
                    </div>

                    <!-- Filament Table -->
                    <div wire:sortable="" class="fi-ta-ctn">
                        <!-- ... tabel content dari Filament ... -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
```

### 8.2. Catatan DOM

- Loading state dari `wire:loading` menggunakan inline `style="display: none;"` saat tidak aktif
- Livewire menggunakan `aria-` attributes dan `data-` attributes secara internal
- Tabel di-render oleh Filament Table builder dengan class `fi-ta-ctn`

---

## 9. DEPENDENCY

### 9.1. File yang Perlu Disalin untuk Implementasi Identik

#### File PHP

| #   | File                                             | Keterangan                                                                      |
| --- | ------------------------------------------------ | ------------------------------------------------------------------------------- |
| 1   | `app/Filament/AdminLayanan/Pages/TambahDaya.php` | Hanya sebagai referensi logika `$activeTab` + `setTab()` + `getFilteredQuery()` |
| 2   | `app/Http/Controllers/PembayaranController.php`  | CONTROLLER EXISTING — perlu dimodifikasi                                        |

#### File Blade

| #   | File                                                                 | Keterangan                                 |
| --- | -------------------------------------------------------------------- | ------------------------------------------ |
| 3   | `resources/views/filament/admin-layanan/pages/tambah-daya.blade.php` | REFERENSI — struktur HTML filter           |
| 4   | `resources/views/pelanggan/pembayaran.blade.php`                     | VIEW EXISTING — perlu dimodifikasi         |
| 5   | `resources/views/pelanggan/pembayaran-empty.blade.php`               | VIEW EXISTING — mungkin perlu dimodifikasi |

#### File CSS

| #   | File                                             | Keterangan                                                      |
| --- | ------------------------------------------------ | --------------------------------------------------------------- |
| 6   | `resources/css/filament/admin-layanan/theme.css` | REFERENSI — semua CSS filter (baris 795-916)                    |
| 7   | `resources/css/app.css`                          | CSS EXISTING — tempat menambahkan style untuk halaman pelanggan |

#### File JS

| #   | File                     | Keterangan                                            |
| --- | ------------------------ | ----------------------------------------------------- |
| 8   | Tidak ada file JS khusus | Filter 100% menggunakan Livewire (built-in alpine.js) |

### 9.2. Runtime Dependencies yang Diperlukan

| Dependency         | Status di Pembayaran | Keterangan                                                  |
| ------------------ | -------------------- | ----------------------------------------------------------- |
| **Livewire 3**     | ❌ TIDAK ADA         | Pembayaran adalah Blade biasa tanpa Livewire                |
| **Alpine.js**      | ❌ TIDAK ADA         | Bundled dengan Livewire, tapi tidak tersedia di halaman ini |
| **Filament Table** | ❌ TIDAK ADA         | Tabel di Pembayaran adalah Blade manual                     |

> **⚠️ IMPLIKASI KRITIS:** Untuk mendapatkan perilaku filter yang identik, halaman Pembayaran perlu:
>
> 1. Mengimplementasikan Livewire (minimal komponen Livewire)
> 2. ATAU mengimplementasikan ulang semua behaviour dengan JavaScript vanilla / Alpine.js + AJAX

---

## 10. BLUEPRINT REPLIKASI

### 10.1. Strategi Replikasi

**Opsi A: Full Livewire Component** ✅ _DIREKOMENDASIKAN_

- Buat Livewire component baru untuk halaman Pembayaran
- Gunakan `InteractsWithTable` dari Filament (jika perlu tabel serupa)
- Implementasi identik dengan Tambah Daya

**Opsi B: HTML + Alpine.js + AJAX**

- Gunakan struktur HTML yang sama dengan Tambah Daya
- Gunakan Alpine.js untuk state management (toggle activeTab)
- Gunakan fetch/AJAX untuk update tabel
- Tabel di-render manual dengan Blade

**Opsi C: Pure JavaScript (Vanilla)**

- Sama dengan Opsi B, tapi tanpa Alpine.js
- Lebih banyak boilerplate

### 10.2. Blueprint Implementasi (Opsi A — Livewire)

#### Langkah 1: Buat Livewire Component

```bash
php artisan make:livewire Pelanggan/PembayaranFilter
```

**File dibuat:**

```
app/Livewire/Pelanggan/PembayaranFilter.php
resources/views/livewire/pelanggan/pembayaran-filter.blade.php
```

#### Langkah 2: PHP Component (Struktur)

```php
<?php

namespace App\Livewire\Pelanggan;

use Livewire\Component;

class PembayaranFilter extends Component
{
    public string $activeTab = 'menunggu';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $payments = $this->getFilteredQuery();

        return view('livewire.pelanggan.pembayaran-filter', [
            'payments' => $payments,
            'activeTab' => $this->activeTab,
        ]);
    }

    protected function getFilteredQuery()
    {
        $query = \App\Models\Payment::query()
            ->whereHas('serviceRequest', function($q) {
                // Filter by current user's NIK
            });

        return match ($this->activeTab) {
            'menunggu' => $query->where('status', 'pending'),
            'selesai'  => $query->where('status', 'completed'),
            'gagal'    => $query->where('status', 'failed'),
            default    => $query,
        };
    }
}
```

#### Langkah 3: Blade Component View (IDENTIK dengan Tambah Daya)

```blade
<div class="pembayaran-container">
    <div class="td-status-tabs">
        <button
            type="button"
            class="td-tab {{ $activeTab === 'menunggu' ? 'td-tab-active' : '' }}"
            wire:click="setTab('menunggu')"
            wire:loading.attr="disabled"
            wire:target="setTab"
        >
            <span wire:loading.remove wire:target="setTab('menunggu')">Menunggu</span>
            <span wire:loading wire:target="setTab('menunggu')" class="td-tab-loading">Memuat...</span>
        </button>
        <button
            type="button"
            class="td-tab {{ $activeTab === 'selesai' ? 'td-tab-active' : '' }}"
            wire:click="setTab('selesai')"
            wire:loading.attr="disabled"
            wire:target="setTab"
        >
            <span wire:loading.remove wire:target="setTab('selesai')">Selesai</span>
            <span wire:loading wire:target="setTab('selesai')" class="td-tab-loading">Memuat...</span>
        </button>
        <button
            type="button"
            class="td-tab {{ $activeTab === 'gagal' ? 'td-tab-active' : '' }}"
            wire:click="setTab('gagal')"
            wire:loading.attr="disabled"
            wire:target="setTab"
        >
            <span wire:loading.remove wire:target="setTab('gagal')">Gagal</span>
            <span wire:loading wire:target="setTab('gagal')" class="td-tab-loading">Memuat...</span>
        </button>
    </div>

    <div class="td-table-container" wire:loading.class="td-table-loading" wire:target="setTab">
        <div wire:loading wire:target="setTab" class="td-loading-overlay">
            <div class="td-loading-spinner"></div>
        </div>

        {{-- Tabel pembayaran --}}
        <div class="pembayaran-table">
            @forelse($payments as $payment)
                {{-- render row --}}
            @empty
                {{-- empty state --}}
            @endforelse
        </div>
    </div>
</div>
```

#### Langkah 4: CSS (IDENTIK — Salin dari theme.css)

Copy paste block CSS berikut ke `resources/css/app.css`:

```css
/* ─── PEMBAYARAN — SEGMENTED NAVIGATION TABS (REPLIKASI DARI TAMBAH DAYA) ─── */
.pembayaran-container {
    margin-top: 8px;
    width: 100%;
}

.pembayaran-container .td-status-tabs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    width: 100%;
    background: #093c5d;
    border-radius: 12px;
    padding: 8px;
    gap: 4px;
    margin-bottom: 20px;
    box-sizing: border-box;
}

.pembayaran-container .td-tab {
    background: transparent;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    outline: none;
    font-family: inherit;
    line-height: 1.2;
    text-align: center;
    width: 100%;
    box-sizing: border-box;
}

.pembayaran-container .td-tab:hover {
    background: rgba(255, 255, 255, 0.08);
}

.pembayaran-container .td-tab-active {
    background: #1fa8c9 !important;
    color: #ffffff;
    border-radius: 8px;
    font-weight: 600;
}

.pembayaran-container .td-tab:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.pembayaran-container .td-tab-loading {
    font-size: 13px;
    font-style: italic;
    opacity: 0.8;
}

.pembayaran-container .td-table-container {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    width: 100%;
    position: relative;
}

.pembayaran-container .td-table-loading {
    opacity: 0.5;
    pointer-events: none;
    transition: opacity 0.15s ease;
}

.pembayaran-container .td-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    border-radius: 12px;
}

.pembayaran-container .td-loading-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #e5e7eb;
    border-top-color: #093c5d;
    border-radius: 50%;
    animation: td-spin 0.7s linear infinite;
}

@keyframes td-spin {
    to {
        transform: rotate(360deg);
    }
}
```

#### Langkah 5: Update PembayaranController

```php
// PembayaranController.php
public function index()
{
    $user = Auth::guard('web')->user();
    if ($user->role !== 'pelanggan') {
        return redirect()->route('landing');
    }

    // Render Livewire component langsung di view
    return view('pelanggan.pembayaran');
}
```

#### Langkah 6: Update Pembayaran Blade View

```blade
@extends('layouts.pelanggan')

@section('content')
<h1 class="text-2xl font-bold text-slate-900 mb-6">Pembayaran</h1>
<livewire:pelanggan.pembayaran-filter />
@endsection
```

### 10.3. Verifikasi Konsistensi

| Aspek             | Target  | Cara Verifikasi                                    |
| ----------------- | ------- | -------------------------------------------------- |
| **Ukuran**        | Identik | Bandingkan computed style di browser DevTools      |
| **Posisi**        | Identik | Bandingkan margin, padding, layout flow            |
| **Spacing**       | Identik | Bandingkan gap, margin, padding                    |
| **Warna**         | Identik | #093c5d, #1fa8c9, #ffffff                          |
| **Behaviour**     | Identik | Livewire reactive, loading states, disabled states |
| **Responsivitas** | Identik | 3 kolom grid di semua viewport                     |

### 10.4. Checklist Implementasi

```
☐ Buat Livewire component: PembayaranFilter
☐ Implementasi PHP: $activeTab, setTab(), getFilteredQuery()
☐ Buat Blade view: pembayaran-filter.blade.php
☐ Copy CSS filter ke app.css atau file CSS pelanggan
☐ Pastikan layout pelanggan sudah load CSS yang dibutuhkan
☐ Update PembayaranController
☐ Update pembayaran.blade.php untuk render Livewire component
☐ Test loading state: klik tab, pastikan loading overlay muncul
☐ Test responsivitas: resize browser, pastikan 3 kolom tetap
☐ Test data: pastikan tabel berubah sesuai tab yang dipilih
☐ Test empty state: pastikan jika data kosong, tampilan tetap rapi
```

---

## RINGKASAN EKSEKUTIF

Filter pada halaman Tambah Daya adalah **segmented tab kustom** (bukan komponen Filament bawaan) yang terdiri dari:

1. **3 tombol dalam grid 3 kolom** (Menunggu | Pending | Selesai)
2. **Styling: background biru tua #093c5d**, tombol aktif **biru terang #1fa8c9**, teks putih
3. **Powered by Livewire** — reactive tanpa debounce, polling, atau navigasi
4. **Loading state** — button disabled + overlay spinner di tabel
5. **Ukuran: padding 8px container, padding 10px 16px tombol, border-radius 12px container, 8px tombol**
6. **Spacing: margin-top 8px, margin-bottom 20px**
7. **Responsif: tetap 3 kolom di semua viewport** (tidak ada perubahan)

Halaman Pembayaran saat ini adalah **Blade tradisional tanpa Livewire**. Untuk replikasi identik, **wajib mengimplementasikan Livewire** (atau Alpine.js + AJAX) agar behaviour loading state dan reactive filtering dapat berfungsi sama persis.

**File yang perlu dimodifikasi:**

1. **BUAT** `app/Livewire/Pelanggan/PembayaranFilter.php`
2. **BUAT** `resources/views/livewire/pelanggan/pembayaran-filter.blade.php`
3. **TAMBAH CSS** ke `resources/css/app.css` (atau file CSS pelanggan terpisah)
4. **UPDATE** `app/Http/Controllers/PembayaranController.php`
5. **UPDATE** `resources/views/pelanggan/pembayaran.blade.php`
