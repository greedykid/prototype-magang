# DOKUMEN PERANCANGAN SISTEM SIMASADI
## Bagian 4: Tahap Perancangan (Design)

---

### 4.1 Struktur Navigasi (Sitemap)

Struktur navigasi menggambarkan alur perpindahan antarmuka pengguna dalam mengakses seluruh menu dan fitur pada sistem SIMASADI. Sesuai dengan pembagian hak akses 4 peran nyata, struktur navigasi dibagi menjadi:
1. **Navigasi Utama Admin & PIC**: Menggambarkan hubungan dari halaman Login, menuju Dashboard utama, hingga ke seluruh menu operasional tingkat satu yang saling terhubung secara horizontal (*bi-directional*).
2. **Navigasi Khusus Portal Asesor & Portal LPK**: Menggambarkan antarmuka terfokus untuk Asesor Lapangan (`/assessor`) dan perwakilan LPK mandiri (`/portal`).
3. **Struktur Navigasi Hierarki Sub-Halaman**: Menggambarkan pohon navigasi dari setiap menu utama hingga ke halaman formulir penambahan, pengubahan, import massal, dan halaman detail data.

#### 4.1.1 Struktur Navigasi Tingkat Utama (Admin & PIC)

```mermaid
graph TD
    UserLogin["Login (/login)"] <--> Dashboard["Dashboard (/dashboard)"]

    Dashboard --- BusTrunk[" "]
    style BusTrunk width:0px,height:0px,stroke:none,fill:none

    BusTrunk --- M1["Data Master LPK"]
    BusTrunk --- M2["Program Asesmen"]
    BusTrunk --- M3["Kalender Kegiatan"]
    BusTrunk --- M4["Kepatuhan & Finansial"]
    BusTrunk --- M5["Manajemen Pengguna"]
    BusTrunk --- M6["Histori Backup"]

    M1 <--> M2
    M2 <--> M3
    M3 <--> M4
    M4 <--> M5
    M5 <--> M6

    classDef navBox fill:#ffffff,stroke:#2b2b2b,stroke-width:1.5px,color:#111111,font-size:13px;
    classDef invisibleTrunk fill:none,stroke:none;
    class UserLogin,Dashboard,M1,M2,M3,M4,M5,M6 navBox;
    class BusTrunk invisibleTrunk;
```

<p align="center"><b>Gambar 4. 1 Struktur Navigasi Utama SIMASADI</b></p>

---

#### 4.1.2 Struktur Navigasi Hierarki Sub-Halaman Lengkap

```mermaid
graph TD
    Login["Halaman Login (/login)"] <--> Dash["Dashboard (/dashboard)"]

    Dash --> LPK["Data LPK (/lpks)"]
    LPK <--> LPK_Add["Tambah LPK (/lpks/create)"]
    LPK <--> LPK_Import["Impor LPK (/lpks/import)"]
    LPK <--> LPK_Detail["Detail Profil LPK (/lpks/{id})"]
    LPK_Detail <--> LPK_Edit["Ubah Profil LPK (/lpks/{id}/edit)"]

    Dash --> ASM["Program Asesmen (/assessments)"]
    ASM <--> ASM_Add["Jadwalkan Asesmen (/assessments/create)"]
    ASM <--> ASM_Import["Impor Asesmen (/assessments/import)"]
    ASM <--> ASM_Detail["Detail Asesmen (/assessments/{id})"]
    ASM_Detail <--> ASM_Edit["Ubah Asesmen (/assessments/{id}/edit)"]
    ASM_Detail <--> ASM_Cost["Pelaporan & Verifikasi Biaya SBM"]
    ASM_Detail <--> ASM_TP["Pelacakan Tindakan Perbaikan (SLA)"]
    ASM_Detail <--> ASM_EHA["Evaluasi Hasil Asesmen (EHA)"]

    Dash --> CAL["Kalender Kegiatan (/calendar)"]
    CAL <--> CAL_Add["Klik Tanggal -> Tambah Agenda (/calendar/events/create)"]
    CAL <--> CAL_Detail["Detail Agenda Event (/calendar/events/{id})"]

    Dash --> FIN["Kepatuhan & Finansial"]
    FIN <--> FIN_Bill["Penerbitan Billing SIMPONI & Pembayaran"]
    FIN <--> FIN_Sign["Tanda Tangan Elektronik Dokumen SK (BSrE)"]
    FIN <--> FIN_Gate["Quality Gate Kesiapan Rilis SK"]

    Dash --> USR["Manajemen Pengguna (/users)"]
    USR <--> USR_Add["Tambah Pengguna (/users/create)"]
    USR <--> USR_Edit["Ubah Pengguna (/users/{id}/edit)"]

    Dash --> BCK["Histori Backup (/monitoring/backups)"]

    Dash --> PORTAL_LPK["Portal LPK (/portal) + QR Code"]
    Dash --> PORTAL_ASR["Portal Asesor (/assessor)"]

    classDef pageBox fill:#ffffff,stroke:#2b2b2b,stroke-width:1.5px,color:#111111,font-size:12px;
    class Login,Dash,LPK,LPK_Add,LPK_Import,LPK_Detail,LPK_Edit,ASM,ASM_Add,ASM_Import,ASM_Detail,ASM_Edit,ASM_Cost,ASM_TP,ASM_EHA,CAL,CAL_Add,CAL_Detail,FIN,FIN_Bill,FIN_Sign,FIN_Gate,USR,USR_Add,USR_Edit,BCK,PORTAL_LPK,PORTAL_ASR pageBox;
```

<p align="center"><b>Gambar 4. 2 Struktur Navigasi Hierarki Sub-Halaman SIMASADI</b></p>

---

### 4.2 Use Case Diagram

Diagram Use Case memodelkan fungsi-fungsi sistem dari sudut pandang 4 peran pengguna (*aktor*). Notasi yang digunakan merujuk pada standar UML (*Unified Modeling Language*):
* **Admin Unit Akreditasi Lab (`admin`)**: Pengelola operasional dengan akses menyeluruh.
* **PIC Laboratorium / Unit Teknis (`pic`)**: Pendamping LPK kelolaan.
* **Asesor KAN (`assessor`)**: Tenaga ahli pelaksana asesmen lapangan.
* **Lembaga Penilaian Kesesuaian (`lpk`)**: Entitas pemegang akreditasi KAN.

```mermaid
flowchart LR
    %% Definisi Aktor
    Admin["👤 Admin Unit Lab"]:::actorBox
    PIC["👤 PIC Unit Teknis"]:::actorBox
    Asesor["👤 Asesor KAN"]:::actorBox
    LPK["🏢 Lembaga (LPK)"]:::actorBox

    %% Use Case Kelompok Utama
    UC_Login(["Melakukan Login Sesi"]):::ucMain
    UC_Dash(["Melihat Dashboard & Alert Persisten"]):::ucMain
    UC_LPK_Manage(["Mengelola Data Master LPK"]):::ucMain
    UC_LPK_Import(["Mengimpor Massal LPK & Asesmen"]):::ucMain
    UC_Asm_Manage(["Mengelola 8 Tipe Asesmen KAN"]):::ucMain
    UC_Tolerance(["Memantau Toleransi 3 Tahap & Pembekuan"]):::ucMain
    UC_TP_SLA(["Mengelola SLA Tindakan Perbaikan (TP & VTP)"]): extension:::ucMain
    UC_EHA(["Mencatat Alur Sidang EHA & Lead Time SK"]):::ucMain
    UC_Cal(["Mengelola Kalender Interaktif 5 Event"]):::ucMain
    UC_SBM(["Melaporkan & Memverifikasi Biaya SBM"]):::ucMain
    UC_PNBP(["Menerbitkan Billing SIMPONI & Pelunasan"]):::ucMain
    UC_ESign(["Membubuhi e-Sign BSrE & Rilis SK"]):::ucMain
    UC_QR(["Memverifikasi Status via QR Code"]):::ucMain
    UC_User_Mgmt(["Mengelola Pengguna & Hak Akses"]):::ucMain

    %% Relasi Admin
    Admin --- UC_Login
    Admin --- UC_Dash
    Admin --- UC_LPK_Manage
    Admin --- UC_LPK_Import
    Admin --- UC_Asm_Manage
    Admin --- UC_Tolerance
    Admin --- UC_TP_SLA
    Admin --- UC_EHA
    Admin --- UC_Cal
    Admin --- UC_SBM
    Admin --- UC_PNBP
    Admin --- UC_ESign
    Admin --- UC_User_Mgmt

    %% Relasi PIC
    PIC --- UC_Login
    PIC --- UC_Dash
    PIC --- UC_Tolerance
    PIC --- UC_TP_SLA
    PIC --- UC_Cal
    PIC --- UC_SBM

    %% Relasi Asesor
    Asesor --- UC_Login
    Asesor --- UC_Asm_Manage
    Asesor --- UC_SBM
    Asesor --- UC_TP_SLA

    %% Relasi LPK
    LPK --- UC_Login
    LPK --- UC_Tolerance
    LPK --- UC_TP_SLA
    LPK --- UC_QR

    classDef actorBox fill:#f8fafc,stroke:#334155,stroke-width:1.5px,color:#0f172a,font-weight:bold;
    classDef ucMain fill:#ffffff,stroke:#4f46e5,stroke-width:1.5px,color:#1e1b4b,font-size:12px;
```

<p align="center"><b>Gambar 4. 3 Use Case Diagram Sistem SIMASADI</b></p>

---

### 4.3 Activity Diagram

#### 4.3.1 Activity Diagram: Penegakan Siklus Hidup 3 Tahap Surveilen KAN
Diagram ini memodelkan transisi status pengawasan dari kunjungan asesmen sampai keputusan akhir:

```mermaid
stateDiagram-v2
    [*] --> KunjunganAsesmen: Asesmen Lapangan Selesai (end_at)
    KunjunganAsesmen --> HitungToleransi: submission_due_date = end_at->endOfMonth()
    
    state CekBulanKunjungan <<choice>>
    HitungToleransi --> CekBulanKunjungan: Evaluasi Tanggal Saat Ini
    CekBulanKunjungan --> StatusNormal: Tanggal <= submission_due_date
    StatusNormal --> SelesaiNormal: Dokumen Asesmen Diselesaikan (Status: COMPLETED)
    SelesaiNormal --> [*]
    
    CekBulanKunjungan --> StatusDibekukan: Tanggal > submission_due_date & Belum Selesai
    
    state TahapPembekuan {
        StatusDibekukan --> AktifkanSuspension: Status LPK & Asesmen = SUSPENDED (Badge Ungu)
        AktifkanSuspension --> BeriJendela1Tahun: suspension_deadline = submission_due_date + 1 Tahun
        BeriJendela1Tahun --> HitungMundur: Tampilkan countdown hari & bulan tersisa
        
        state CekPenyelesaian1Tahun <<choice>>
        HitungMundur --> CekPenyelesaian1Tahun: Apakah LPK Menyelesaikan Kewajiban?
        CekPenyelesaian1Tahun --> PemulihanAkreditasi: Ya, Asesmen Selesai
        PemulihanAkreditasi --> StatusAktifKembali: Status Pulih Menjadi ACTIVE
        StatusAktifKembali --> [*]
        
        CekPenyelesaian1Tahun --> BatasWaktuHabis: Tidak (Waktu > 1 Tahun)
    }
    
    BatasWaktuHabis --> CabutAkreditasi: Status LPK Berubah Otomatis Menjadi REVOKED (Badge Merah)
    CabutAkreditasi --> [*]
```

---

#### 4.3.2 Activity Diagram: Alur Pengajuan & Validasi Perpanjangan Waktu SLA Tindakan Perbaikan (TP)

```mermaid
stateDiagram-v2
    [*] --> AsesmenSelesai: Laporan Asesmen Diserahkan
    AsesmenSelesai --> HitungSLA: Tentukan SLA Dasar (AA: 3 Bulan, Lainnya: 2 Bulan)
    HitungSLA --> StatusBerlangsung: Status TP Otomatis "Sedang Berlangsung"
    
    state CekProgresTemuan <<choice>>
    StatusBerlangsung --> CekProgresTemuan: Menjelang Batas SLA, Ada Permohonan Perpanjangan?
    
    CekProgresTemuan --> TolakPerpanjangan: Tidak Ada Perbaikan Sama Sekali (Kosong)
    TolakPerpanjangan --> OtomatisDibekukan: Perpanjangan Ditolak -> Status Menjadi SUSPENDED (Badge Ungu)
    OtomatisDibekukan --> [*]
    
    CekProgresTemuan --> IzinkanPerpanjangan: Ada Bukti Perbaikan Sebagian Temuan + Surat Resmi
    IzinkanPerpanjangan --> InputNomorSurat: Input tp_extension_letter_no & Aktifkan Perpanjangan 1 Bulan
    InputNomorSurat --> SLAExtended: tp_extended_due_date = tp_due_date + 1 Bulan
    
    state CekPemenuhan <<choice>>
    SLAExtended --> CekPemenuhan: Evaluasi Tanggal Pemenuhan (tp_satisfied_at)
    CekPemenuhan --> StatusMemenuhi: Tanggal Terisi -> Status Otomatis "Memenuhi / Selesai"
    StatusMemenuhi --> HitungLeadTimeSK: Trigger Pengingat SK 10 Hari
    HitungLeadTimeSK --> [*]
    
    CekPemenuhan --> LewatBatasExtended: Tanggal Kosong & Melewati SLA Perpanjangan
    LewatBatasExtended --> OtomatisDibekukan
```

---

### 4.4 Sequence Diagram

#### 4.4.1 Sequence Diagram: Realisasi Pembayaran SIMPONI & Quality Gate Rilis SK

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Admin Unit Lab
    participant View as Detail Page (Blade)
    participant Ctrl as AssessmentController
    participant Model as Assessment / Billing
    participant DB as SQLite Database

    Admin->>View: Buka Detail Asesmen / Akreditasi
    Admin->>View: Klik [ Terbitkan Billing SIMPONI ]
    View->>Ctrl: POST /accreditations/{id}/billings (nominal tarif KAN)
    Ctrl->>Model: AccreditationBilling::create(code: 15-digit, status: UNPAID)
    Model->>DB: INSERT INTO accreditation_billings
    DB-->>Ctrl: Saved
    Ctrl-->>View: Tampilkan Kode Billing SIMPONI

    Note over Admin,DB: Konfirmasi Setoran Kas Negara
    Admin->>View: Input Nomor NTPN 16-Karakter & Bank
    View->>Ctrl: POST /accreditations/{id}/billings/{billing}/pay
    Ctrl->>Model: Billing->update(status: PAID, paid_at: now(), ntpn)
    Model->>DB: UPDATE accreditation_billings
    DB-->>Ctrl: Saved

    Note over Admin,DB: Pengujian Quality Gate Kesiapan Terbit Dokumen
    Admin->>View: Klik [ Rilis SK Akreditasi ]
    View->>Ctrl: POST /accreditations/{id}/release
    Ctrl->>Ctrl: isReleaseReady() (Cek: Billing PAID && Biaya SBM TERVERIFIKASI)
    alt Syarat Belum Lengkap
        Ctrl-->>View: Return 422: SK Terkunci (Quality Gate Lock)
        View-->>Admin: Pop-up SweetAlert2: Syarat SBM/PNBP Belum Terpenuhi
    else Syarat Lengkap
        Ctrl->>Model: Generate SHA-256 Hash & Nomor Seri BSrE
        Model->>DB: UPDATE accreditations SET output_released_at = now()
        DB-->>Ctrl: Saved
        Ctrl-->>View: Status Rilis Aktif & QR Code Keabsahan Terbentuk
        View-->>Admin: Toast Sukses: SK Akreditasi Resmi Dirilis
    end
```

---

### 4.5 Rancangan Basis Data (Entity Relationship Diagram - ERD)

Skema basis data SIMASADI terdiri dari 9 entitas tabel relasional terpadu yang memadukan data master lembaga, agenda lapangan, penegakan regulasi KAN U-01, kepatuhan finansial, serta jejak audit:

```mermaid
erDiagram
    USERS ||--o{ LPKS : "assigned_as_pic"
    USERS ||--o{ ASSESSMENTS : "creates"
    USERS ||--o{ ASSESSMENT_EXPENSES : "reports_or_verifies"
    USERS ||--o{ CALENDAR_EVENTS : "creates"
    USERS ||--o{ BACKUPS : "records"

    LPKS ||--o{ ASSESSMENTS : "undergoes"
    LPKS ||--o{ ACCREDITATIONS : "possesses"
    LPKS ||--o{ CALENDAR_EVENTS : "associated_with"

    ASSESSMENTS ||--o| ASSESSMENT_EXPENSES : "incurs"
    ACCREDITATIONS ||--o{ ACCREDITATION_BILLINGS : "billed_by"
    ACCREDITATIONS ||--o| ACCREDITATION_SIGNATURES : "certified_by"

    USERS {
        int id PK
        string name
        string email
        string password
        string role "admin | pic | assessor | lpk"
        datetime created_at
    }

    LPKS {
        int id PK
        string no_reg "Nomor Registrasi KAN unik"
        string registration_number
        string name
        string kan_schema "Skema Akreditasi KAN"
        text scope "Ruang Lingkup Akreditasi"
        string category
        string city
        string province
        string address
        string email
        string phone
        string status "ACTIVE | SUSPENDED | REVOKED | INACTIVE"
        date certificate_date
        date expired_at
        string drive_url
        int pic_user_id FK "Relasi ke Users"
        datetime last_surveillance_notified_at
        text notes
        datetime created_at
    }

    ASSESSMENTS {
        int id PK
        int lpk_id FK
        int created_by FK
        string title
        string assessment_type "8 Tipe KAN U-01"
        datetime start_at
        datetime end_at
        string location
        string lead_assessor
        string status "PLANNED | IN_PROGRESS | SUSPENDED | COMPLETED | CANCELLED"
        text notes
        int tp_sla_months "3 bln AA, 2 bln lainnya"
        date tp_due_date "Batas Waktu Awal SLA"
        boolean tp_has_extension
        int tp_extension_months "Max 1 bulan"
        string tp_extension_letter_no
        date tp_extended_due_date
        date tp_submitted_at
        date tp_satisfied_at "Tanggal Pemenuhan TP"
        text tp_unresolved_notes
        date sk_issued_at
        string sk_number
        int sk_lead_time_days
        date eha_scheduled_date
        date eha_completed_at
        text eha_notes
        datetime created_at
    }

    ASSESSMENT_EXPENSES {
        int id PK
        int assessment_id FK
        int reported_by FK
        int transport_cost
        int accommodation_cost
        int daily_allowance
        int package_data_cost
        int total_cost
        string receipt_note
        string status "BELUM_DILAPORKAN | MENUNGGU_VERIFIKASI | TERVERIFIKASI | PERLU_REVISI"
        text verification_notes
        int verified_by FK
        datetime verified_at
        datetime created_at
    }

    ACCREDITATIONS {
        int id PK
        int lpk_id FK
        date start_date
        date pantek_at
        date target_date
        date target_output_at
        date output_released_at
        string pic
        string status
        text notes
        datetime created_at
    }

    ACCREDITATION_BILLINGS {
        int id PK
        int accreditation_id FK
        string billing_code "15-digit SIMPONI"
        int amount
        date expired_at
        string status "UNPAID | PAID | EXPIRED"
        string ntpn "16-digit Nomor Transaksi Negara"
        string ntb
        string payment_channel
        datetime paid_at
        datetime created_at
    }

    ACCREDITATION_SIGNATURES {
        int id PK
        int accreditation_id FK
        string signer_name
        string signer_role
        string signer_nip
        string cert_serial
        datetime signed_at
        string verify_hash "SHA-256 Hash"
        boolean is_signed
        datetime created_at
    }

    CALENDAR_EVENTS {
        int id PK
        int lpk_id FK
        int created_by FK
        string title
        string event_type "assessment | surveillance_reminder | tp_reminder | tp_overdue | sk_reminder"
        datetime start_at
        datetime end_at
        string location
        boolean is_all_day
        text notes
        datetime created_at
    }

    BACKUPS {
        int id PK
        int recorded_by FK
        string filename
        int size_bytes
        boolean is_successful
        text notes
        datetime created_at
    }
```

<p align="center"><b>Gambar 4. 4 Entity Relationship Diagram (ERD) SIMASADI Terkini</b></p>

---

### 4.6 Class Diagram

Class diagram menggambarkan arsitektur berbasis objek pada layer Model dan Controller di Laravel:

```mermaid
classDiagram
    class User {
        +int id
        +string name
        +string email
        +string role
        +isAdmin() bool
        +isPic() bool
        +isAssessor() bool
        +isLpk() bool
        +lpks() HasMany
        +assessments() HasMany
    }

    class Lpk {
        +int id
        +string no_reg
        +string name
        +string kan_schema
        +date certificate_date
        +date expired_at
        +int pic_user_id
        +getDynamicStatusAttribute() string
        +getDynamicKeteranganAttribute() string
        +getSurveillanceMilestonesAttribute() array
        +assessments() HasMany
        +picUser() BelongsTo
    }

    class Assessment {
        +int id
        +string assessment_type
        +datetime start_at
        +datetime end_at
        +string status
        +int tp_sla_months
        +date tp_due_date
        +date tp_satisfied_at
        +getEffectiveTpDueDateAttribute() Carbon
        +getIsTpOverdueAttribute() bool
        +getTpDynamicStatusAttribute() string
        +getIsSubmissionOverdueAttribute() bool
        +getIsSuspensionExpiredAttribute() bool
        +lpk() BelongsTo
        +expense() HasOne
    }

    class AssessmentExpense {
        +int id
        +int total_cost
        +string status
        +calculateTotal() int
        +isVerified() bool
        +assessment() BelongsTo
    }

    class Accreditation {
        +int id
        +string status
        +isReleaseReady() bool
        +lpk() BelongsTo
        +billings() HasMany
        +signature() HasOne
    }

    class CalendarEvent {
        +int id
        +string event_type
        +datetime start_at
        +datetime end_at
        +lpk() BelongsTo
    }

    User "1" --> "*" Lpk : assigns
    User "1" --> "*" Assessment : creates
    Lpk "1" --> "*" Assessment : undergoes
    Lpk "1" --> "*" Accreditation : holds
    Assessment "1" --> "1" AssessmentExpense : has
    Accreditation "1" --> "*" CalendarEvent : schedules
```

---

### 4.7 Rancangan Antarmuka Pengguna (Wireframe Desktop & Mobile)

#### 4.7.1 Wireframe Halaman Detail Asesmen (Desktop)
```text
+---------------------------------------------------------------------------------------------------+
|  [LOGO BSN/KAN] SIMASADI Workspace                       [Lonceng: 2] [User: Admin Unit Lab v]    |
+---------------------------------------------------------------------------------------------------+
|  <- Kembali ke Daftar Asesmen | No. Reg: LP-001-IDN | Status: [ DIBEKUKAN ] (Badge Ungu)          |
+---------------------------------------------------------------------------------------------------+
|  RINGKASAN ASESMEN                                                                                |
|  * Tipe Asesmen   : Surveilen 1 (S1)           * Tanggal Pelaksanaan : 29/03/2027 s/d 30/03/2027   |
|  * Lead Assessor  : Dr. Ir. Budi Santoso       * Toleransi Pengisian : 31/03/2027 (Lewat Batas)    |
|  * Sisa Pembekuan : 312 Hari (10 Bulan)        * Batas Pencabutan    : 31/03/2028                 |
+---------------------------------------------------------------------------------------------------+
|  [TAB: RINCIAN ASESMEN] | [TAB: TINDAKAN PERBAIKAN (TP)] | [TAB: BIAYA SBM] | [TAB: ALUR EHA & SK] |
+---------------------------------------------------------------------------------------------------+
|  PELACAKAN TINDAKAN PERBAIKAN (SLA KAN):                                                          |
|  * Status TP Otomatis : [ DIBEKUKAN ] (Melewati SLA dasar 2 bulan tanpa pemenuhan)                |
|  * Batas Awal SLA     : 30/05/2027             * Perpanjangan Waktu  : [ Ya ] (Max 1 Bulan)       |
|  * No. Surat Permohonan : 142/LPK-EXT/V/2027   * Batas Perpanjangan  : 30/06/2027                 |
|  * Tanggal Memenuhi   : [ DD/MM/YYYY ] (Input saat perbaikan disetujui lead assessor)             |
+---------------------------------------------------------------------------------------------------+
|  BIAYA PERJALANAN DINAS ASESOR (SBM PMK):                                                         |
|  * Transportasi: Rp 1.500.000  * Uang Harian: Rp 860.000  * Total: Rp 2.360.000                   |
|  * Status Verifikasi: [ TERVERIFIKASI SBM ] (Diverifikasi oleh: Admin pada 01/04/2027)            |
+---------------------------------------------------------------------------------------------------+
|  EVALUASI HASIL ASESMEN & SK KAN:                                                                 |
|  * Tanggal Sidang EHA : 10/07/2027             * No. SK KAN          : 452/KAN/SK/07/2027         |
|  * Tanggal Terbit SK  : 15/07/2027             * Lead Time Terbit SK : 15 Hari                    |
+---------------------------------------------------------------------------------------------------+
```

#### 4.7.2 Wireframe Tampilan Mobile (Responsif & Filter Drawer)
```text
+-----------------------------+
| [=] SIMASADI        [Avatar]|
+-----------------------------+
| ALERT PERSISTEN PRIORITAS   |
| ! 3 Pengawasan Butuh Aksi   |
| [ Tinjau LPK Jatuh Tempo ->]|
+-----------------------------+
| DAFTAR MASTER LPK           |
| [ Filter Data ] [Tabel|Grid]|
| Menampilkan: 10 per halaman |
+-----------------------------+
| +-------------------------+ |
| | LP-001-IDN              | |
| | Balai Besar Pengujian   | |
| | Skema: Lab Uji ISO 17025| |
| | Status: [ DIBEKUKAN ]   | |
| | Sisa Waktu: 10 Bulan    | |
| | [ Detail Asesmen -> ]   | |
| +-------------------------+ |
| | LK-015-IDN              | |
| | Laboratorium Kalibrasi  | |
| | Status: [ AKTIF ]       | |
| | [ Detail Asesmen -> ]   | |
| +-------------------------+ |
+-----------------------------+
```
