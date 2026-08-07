# Setup Cron Job

> Navigasi: [Runbook scheduler](../OPERATIONS_RUNBOOK.md#7-aktifkan-scheduler) · [Referensi job](./CRONJOB_JOBS.md) · [Docker](./DOCKER_DEPLOYMENT.md) · [Indeks](../README.md)

> Terkait: [CRONJOB_JOBS.md](./CRONJOB_JOBS.md) (penjelasan detail per file), [API_ENDPOINTS.md](../reference/API_ENDPOINTS.md) (federasi provider/partner).
>
> Dokumen ini melengkapi bagian "Perlu konfirmasi" di `CRONJOB_JOBS.md`/`docs/guides/11-cronjob-dan-otomatisasi.md` yang sebelumnya menyebut "tidak ada file crontab di repo, jadwal perlu konfirmasi ke tim operasional" — jadwal di bawah ini adalah contoh nyata yang sudah dipakai di produksi.

## 1. Cara Pasang

Tidak ada file crontab yang ikut di-commit ke repo ini (dan memang seharusnya tidak — jadwal cron diatur di panel hosting/cPanel/`crontab -e`, bukan di kode). Daftar di bawah ini contoh isian **Cron Jobs** di cPanel (atau baris `crontab -e` kalau akses shell langsung).

**Ganti `https://YOUR-DOMAIN.com` dengan domain ad network Anda sendiri** (domain yang sama dengan isian `DOMAIN_NAME` di file `.env` — lihat `README.md`).

| Menit | Jam | Tanggal | Bulan | Hari | Command |
|---|---|---|---|---|---|
| `*` | `*` | `*` | `*` | `*` | `curl https://YOUR-DOMAIN.com/cronjob/click_audit.php >/dev/null 2>&1` |
| `*/7` | `*` | `*` | `*` | `*` | `curl https://YOUR-DOMAIN.com/cronjob/mapping_ads_publisher.php >/dev/null 2>&1` |
| `*/9` | `*` | `*` | `*` | `*` | `curl https://YOUR-DOMAIN.com/cronjob/mapping_ads_publisher_check_rate.php >/dev/null 2>&1` |
| `*/9` | `*` | `*` | `*` | `*` | `curl https://YOUR-DOMAIN.com/cronjob/rekap_harian_local.php >/dev/null 2>&1` |
| `*/8` | `*` | `*` | `*` | `*` | `curl https://YOUR-DOMAIN.com/cronjob/rekap_harian_publisher.php >/dev/null 2>&1` |
| `0,30` | `*` | `*` | `*` | `*` | `curl https://YOUR-DOMAIN.com/cronjob/rekap_total_publisher.php >/dev/null 2>&1` |
| `*` | `*` | `*` | `*` | `*` | `curl https://YOUR-DOMAIN.com/cronjob/update_titleads_sitename_clickads.php >/dev/null 2>&1` |
| `0,30` | `*` | `*` | `*` | `*` | `curl https://YOUR-DOMAIN.com/cronjob/calculate_budgetspentads.php >/dev/null 2>&1` |
| `0,30` | `*` | `*` | `*` | `*` | `curl https://YOUR-DOMAIN.com/genJSON/last10publishers.php >/dev/null 2>&1` |

Versi siap-tempel untuk `crontab -e`:

```cron
* * * * * curl https://YOUR-DOMAIN.com/cronjob/click_audit.php >/dev/null 2>&1
*/7 * * * * curl https://YOUR-DOMAIN.com/cronjob/mapping_ads_publisher.php >/dev/null 2>&1
*/9 * * * * curl https://YOUR-DOMAIN.com/cronjob/mapping_ads_publisher_check_rate.php >/dev/null 2>&1
*/9 * * * * curl https://YOUR-DOMAIN.com/cronjob/rekap_harian_local.php >/dev/null 2>&1
*/8 * * * * curl https://YOUR-DOMAIN.com/cronjob/rekap_harian_publisher.php >/dev/null 2>&1
0,30 * * * * curl https://YOUR-DOMAIN.com/cronjob/rekap_total_publisher.php >/dev/null 2>&1
* * * * * curl https://YOUR-DOMAIN.com/cronjob/update_titleads_sitename_clickads.php >/dev/null 2>&1
0,30 * * * * curl https://YOUR-DOMAIN.com/cronjob/calculate_budgetspentads.php >/dev/null 2>&1
0,30 * * * * curl https://YOUR-DOMAIN.com/genJSON/last10publishers.php >/dev/null 2>&1
```

## 2. Kenapa dipanggil lewat `curl`, bukan `php script.php`

Semua file di `cronjob/` (lihat [CRONJOB_JOBS.md](./CRONJOB_JOBS.md) §1) mencetak output HTML lengkap dan mengandalkan `include("../db.php")` yang membaca path relatif terhadap web root — dirancang untuk dipanggil sebagai **URL HTTP**, bukan dieksekusi langsung lewat CLI PHP (`php cronjob/click_audit.php` dari command line akan salah baca path relatif dan tidak mendapat environment web server). `>/dev/null 2>&1` membuang output HTML-nya supaya cPanel/cron tidak mengirim email berisi halaman HTML penuh di tiap eksekusi.

## 3. Fungsi Singkat Tiap Job (lihat detail lengkap di CRONJOB_JOBS.md)

| File | Frekuensi di atas | Fungsi |
|---|---|---|
| `cronjob/click_audit.php` | tiap menit | Audit anti-fraud klik yang belum diperiksa (`isaudit=0`) |
| `cronjob/mapping_ads_publisher.php` | tiap 7 menit | Cocokkan iklan lokal aktif ke semua situs publisher |
| `cronjob/mapping_ads_publisher_check_rate.php` | tiap 9 menit | Re-validasi rate/budget mapping yang sudah ada |
| `cronjob/rekap_harian_local.php` | tiap 9 menit | Rekap harian spending iklan (sumber klik lokal) |
| `cronjob/rekap_harian_publisher.php` | tiap 8 menit | Rekap harian revenue per situs publisher |
| `cronjob/rekap_total_publisher.php` | tiap 30 menit (`:00`, `:30`) | Hitung ulang saldo revenue situs publisher |
| `cronjob/update_titleads_sitename_clickads.php` | tiap menit | Lengkapi `title_ads`/`site_name`/`site_domain` yang kosong di baris klik |
| `cronjob/calculate_budgetspentads.php` | tiap 30 menit | Jumlahkan spending iklan lokal, auto-expire di ≥70% budget |
| `genJSON/last10publishers.php` | tiap 30 menit | Generate snapshot JSON publik 10 publisher terbaru (`JSON/last10publishers.json`) — di luar folder `cronjob/`, baru diketahui dari jadwal produksi ini |

## 4. Yang TIDAK ada di jadwal ini — federasi provider/partner

Jadwal di atas hanya mencakup jalur **lokal** (iklan & publisher milik provider sendiri). Kalau instance Anda sudah/akan terhubung dengan jaringan partner lain (lihat alur federasi di [API_ENDPOINTS.md](../reference/API_ENDPOINTS.md) §5), job-job berikut **juga perlu dijadwalkan** — tidak ada di contoh di atas kemungkinan karena instance sumber jadwal ini belum (atau belum lagi) punya partner aktif:

| Modul | File | Fungsi |
|---|---|---|
| Mapping partner | `cronjob/mapping_ads_publisher_partner.php`, `cronjob/mapping_ads_publisher_check_rate_partner.php` | Versi lintas-jaringan dari 2 job mapping di atas |
| Budget partner | `cronjob/calculate_budgetspentads_partner.php` | Versi partner dari `calculate_budgetspentads.php` |
| Rekap partner | `cronjob/rekap_harian_partner.php`, `cronjob/rekap_harian_provider_partner.php`, `cronjob/rekapPublisherRevenueHarianPartner.php` | Versi partner dari rekap harian |
| Push sinkronisasi (outbound) | `cronjob/push_sync_ads.php`, `push_sync_ads_expired.php`, `push_sync_publishers.php`, `push_sync_click_ads.php`, `push_sync_mapping_ads_publisher.php`, `push_payment_partner_pubs.php`, `push_payment_partner_providers.php` | Push data ke tiap partner (lihat [CRONJOB_JOBS.md](./CRONJOB_JOBS.md) Modul E) |
| Info & kesehatan koneksi | `cronjob/getinfoOwnerPublisherGlobal.php`, `cronjob/check_partner_connection.php` | Ambil info publisher lintas-jaringan, cek partner masih hidup |

Kalau belum berpartner dengan siapa pun, job-job di atas aman untuk tidak dijadwalkan dulu — cukup jalankan 9 job di §1. Begitu punya partner pertama, tambahkan job-job ini juga (frekuensi disarankan mengikuti pola yang sama: mapping tiap ~7-10 menit, rekap tiap ~8-10 menit, push tiap ~10-15 menit — sesuaikan dengan beban server Anda).

## 5. Perlu konfirmasi

- Jadwal di atas adalah **satu contoh nyata dari satu deployment**, bukan rekomendasi resmi dari tim proyek — sesuaikan interval dengan kapasitas server dan volume trafik Anda sendiri.
- `genJSON/last500click.php` dan `genJSON/geninfo_provider.php` (dua file lain di folder `genJSON/`, menghasilkan `JSON/last500clicked.json` dan `JSON/providers_data.json`) **tidak muncul** di jadwal yang diberikan — belum jelas apakah sengaja tidak dijadwalkan atau memang di-generate lewat cara lain.
