# DOKUMEN PERANCANGAN SISTEM SIMASADI
## Bagian 4: Tahap Perancangan (Design)

---

### 4.1 Struktur Navigasi (Sitemap)

Struktur navigasi menggambarkan alur perpindahan antarmuka pengguna dalam mengakses seluruh menu dan fitur pada sistem SIMASADI. Sesuai dengan konvensi perancangan sistem informasi, struktur navigasi dibagi menjadi:
1. **Struktur Navigasi Tingkat Utama (Menu Bar & Sidebar):** Menggambarkan hubungan dari halaman Login, menuju Dashboard utama, hingga ke seluruh menu tingkat satu yang saling terhubung secara horizontal (*bi-directional*).
2. **Struktur Navigasi Hierarki Lengkap (Sub-Menu & Aksi CRUD):** Menggambarkan pohon navigasi dari setiap menu utama hingga ke halaman formulir penambahan, pengubahan, dan halaman detail data.

#### 4.1.1 Struktur Navigasi Tingkat Utama (Admin & Pengguna)
Diagram di bawah mengadopsi model navigasi hierarki dengan bus transfer horizontal antarmenu (dapat berpindah antarmenu secara langsung melalui navigasi sidebar):

```mermaid
graph TD
    AdminLogin["Admin Login"] <--> DashboardAdmin["Dashboard Admin"]

    DashboardAdmin --- BusTrunk[" "]
    style BusTrunk width:0px,height:0px,stroke:none,fill:none

    BusTrunk --- M1["Data LPK"]
    BusTrunk --- M2["Proses Akreditasi"]
    BusTrunk --- M3["Pengajuan Amandemen"]
    BusTrunk --- M4["Program Asesmen"]
    BusTrunk --- M5["Kalender Kegiatan"]
    BusTrunk --- M6["Laporan Masalah"]
    BusTrunk --- M7["Layanan KANMIS"]
    BusTrunk --- M8["Histori Backup"]

    M1 <--> M2
    M2 <--> M3
    M3 <--> M4
    M4 <--> M5
    M5 <--> M6
    M6 <--> M7
    M7 <--> M8

    classDef navBox fill:#ffffff,stroke:#2b2b2b,stroke-width:1.5px,color:#111111,font-size:13px;
    classDef invisibleTrunk fill:none,stroke:none;
    class AdminLogin,DashboardAdmin,M1,M2,M3,M4,M5,M6,M7,M8 navBox;
    class BusTrunk invisibleTrunk;
```

<p align="center"><b>Gambar 3. 1 Struktur Navigasi Admin SIMASADI</b></p>

---

#### 4.1.2 Struktur Navigasi Hierarki Sub-Halaman Lengkap
Diagram berikut merinci sub-halaman formulir aksi (*Create, Read, Update, Detail*) yang dapat diakses dari masing-masing menu utama:

```mermaid
graph TD
    Login["Halaman Login (/login)"] <--> Dash["Dashboard (/dashboard)"]

    Dash --> LPK["Data LPK (/lpks)"]
    LPK <--> LPK_Add["Tambah LPK (/create)"]
    LPK <--> LPK_Detail["Detail Profil (/show)"]
    LPK_Detail <--> LPK_Edit["Ubah Profil (/edit)"]

    Dash --> AKR["Proses Akreditasi (/accreditations)"]
    AKR <--> AKR_Detail["Detail Akreditasi (/show)"]

    Dash --> AMD["Amandemen (/amendments)"]
    AMD <--> AMD_Add["Tambah Amandemen (/create)"]
    AMD <--> AMD_Detail["Detail Amandemen (/show)"]
    AMD_Detail <--> AMD_Edit["Ubah Amandemen (/edit)"]

    Dash --> ASM["Program Asesmen (/assessments)"]
    ASM <--> ASM_Add["Tambah Asesmen (/create)"]
    ASM <--> ASM_Detail["Detail Asesmen (/show)"]
    ASM_Detail <--> ASM_Edit["Ubah Asesmen (/edit)"]

    Dash --> CAL["Kalender Kegiatan (/calendar)"]
    CAL <--> CAL_Add["Klik Tanggal -> Tambah (/events/create)"]
    CAL <--> CAL_Detail["Detail Agenda (/events/show)"]
    CAL_Detail <--> CAL_Edit["Ubah Agenda (/events/edit)"]

    Dash --> ISS["Laporan Masalah (/issues)"]
    ISS <--> ISS_Add["Buat Laporan (/create)"]
    ISS <--> ISS_Detail["Detail & Form Follow-Up (/show)"]

    Dash --> SRV["Layanan KANMIS (/monitoring/services)"]
    Dash --> BCK["Histori Backup (/monitoring/backups)"]

    classDef pageBox fill:#ffffff,stroke:#2b2b2b,stroke-width:1.5px,color:#111111,font-size:12px;
    class Login,Dash,LPK,LPK_Add,LPK_Detail,LPK_Edit,AKR,AKR_Detail,AMD,AMD_Add,AMD_Detail,AMD_Edit,ASM,ASM_Add,ASM_Detail,ASM_Edit,CAL,CAL_Add,CAL_Detail,CAL_Edit,ISS,ISS_Add,ISS_Detail,SRV,BCK pageBox;
```

<p align="center"><b>Gambar 3. 2 Struktur Navigasi Hierarki Sub-Halaman SIMASADI</b></p>

---

### 4.2 Use Case Diagram

Diagram Use Case memodelkan fungsi-fungsi sistem dari sudut pandang pengguna luar (*aktor*). Notasi yang digunakan merujuk pada standar UML (*Unified Modeling Language*), di mana:
* **Aktor (*Actor*):** Pengguna yang berinteraksi dengan sistem (Staf Administrasi Akreditasi, Asesor/Auditor, Administrator Sistem).
* **Use Case:** Fungsi atau skenario kerja sistem yang digambarkan dalam bentuk elips (*oval*).
* **Asosiasi (*Association*):** Garis lurus yang menghubungkan aktor dengan use case utama.
* **`<<include>>`:** Relasi ketergantungan di mana suatu use case wajib memanggil use case lain untuk menyelesaikan fungsinya.
* **`<<extend>>`:** Relasi perluasan opsional di mana suatu use case dapat diperluas fungsinya dalam kondisi tertentu.

#### 4.2.1 Use Case Diagram Pengguna / Asesor SIMASADI
Diagram di bawah menyajikan seluruh fungsionalitas operasional yang dapat diakses oleh pengguna internal sistem:

```mermaid
flowchart LR
    %% Definisi Aktor
    Pengguna["👤<br/><b>Pengguna</b><br/>(Staf / Asesor)"]:::actorBox

    %% Definisi Use Case Utama (Ovals)
    UC_Login(["Melakukan Login"]):::ucMain
    UC_Dash(["Melihat Dashboard & Ringkasan KPI"]):::ucMain
    UC_LPK(["Mengelola Data LPK"]):::ucMain
    UC_Akr(["Memantau Siklus Akreditasi"]):::ucMain
    UC_Asm(["Mengelola Program Asesmen"]):::ucMain
    UC_Cal(["Mengelola Kalender & Agenda Kerja"]):::ucMain
    UC_Amd(["Mengajukan Amandemen Ruang Lingkup"]):::ucMain
    UC_Iss(["Melaporkan Masalah Operasional"]):::ucMain
    UC_Mon(["Memantau Layanan KANMIS & Backup"]):::ucMain
    UC_View(["Mengubah Mode Tampilan Data"]):::ucMain
    UC_Filter(["Memfilter Data Server-Side"]):::ucMain

    %% Definisi Sub Use Case (Include & Extend)
    UC_Logout(["Melakukan Logout Sesi"]):::ucSub
    UC_StatusChart(["Melihat Analisis Status Akreditasi"]):::ucSub
    UC_LPK_CRUD(["Menambah & Mengubah Data LPK"]):::ucSub
    UC_LPK_Detail(["Melihat Detail Profil & Asesmen Terkait"]):::ucSub
    UC_Akr_Stage(["Melihat Tahapan Monitoring & Target"]):::ucSub
    UC_Asm_Sched(["Menjadwalkan Asesmen Surveilen & Awal"]):::ucSub
    UC_Asm_Status(["Mengubah Status Pelaksanaan Asesmen"]):::ucSub
    UC_Cal_Click(["Klik Kotak Tanggal Buat Agenda Otomatis"]):::ucSub
    UC_Cal_Agenda(["Beralih ke Tampilan Mode Agenda Mobile"]):::ucSub
    UC_Amd_Input(["Menginput Nomor Surat & Target"]):::ucSub
    UC_Iss_Follow(["Mencatat Catatan Tindak Lanjut"]):::ucSub
    UC_Iss_Resolve(["Mengubah Status Masalah ke Resolved"]):::ucSub
    UC_View_Toggle(["Beralih Antara Mode Tabel dan Grid Card"]):::ucSub
    UC_Filter_Drawer(["Menggunakan Mobile Slide-Out Drawer"]):::ucSub
    UC_Filter_Chips(["Menghapus Filter per Chip Aktif"]):::ucSub

    %% Relasi Aktor ke Use Case Utama (Solid lines)
    Pengguna --- UC_Login
    Pengguna --- UC_Dash
    Pengguna --- UC_LPK
    Pengguna --- UC_Akr
    Pengguna --- UC_Asm
    Pengguna --- UC_Cal
    Pengguna --- UC_Amd
    Pengguna --- UC_Iss
    Pengguna --- UC_Mon
    Pengguna --- UC_View
    Pengguna --- UC_Filter

    %% Relasi Extend & Include (Dashed arrows with stereotypes)
    UC_Login -.->|"<<extend>>"| UC_Logout
    UC_Dash -.->|"<<include>>"| UC_StatusChart
    UC_LPK -.->|"<<include>>"| UC_LPK_CRUD
    UC_LPK -.->|"<<extend>>"| UC_LPK_Detail
    UC_Akr -.->|"<<include>>"| UC_Akr_Stage
    UC_Asm -.->|"<<include>>"| UC_Asm_Sched
    UC_Asm -.->|"<<extend>>"| UC_Asm_Status
    UC_Cal -.->|"<<include>>"| UC_Cal_Click
    UC_Cal -.->|"<<extend>>"| UC_Cal_Agenda
    UC_Amd -.->|"<<include>>"| UC_Amd_Input
    UC_Iss -.->|"<<include>>"| UC_Iss_Follow
    UC_Iss -.->|"<<extend>>"| UC_Iss_Resolve
    UC_View -.->|"<<include>>"| UC_View_Toggle
    UC_Filter -.->|"<<include>>"| UC_Filter_Drawer
    UC_Filter -.->|"<<extend>>"| UC_Filter_Chips

    %% Styling Kelas
    classDef actorBox fill:#fdfcfa,stroke:#1a1a1a,stroke-width:2px,color:#1a1a1a,font-size:13px;
    classDef ucMain fill:#ffffff,stroke:#1a1a1a,stroke-width:1.8px,color:#111111,font-size:12.5px;
    classDef ucSub fill:#f5f3ff,stroke:#5645d4,stroke-width:1.4px,stroke-dasharray: 4 4,color:#322394,font-size:11.5px;
```

<p align="center"><b>Gambar 3. 3 Use Case Diagram Pengguna SIMASADI</b></p>

---

#### 4.2.2 Tabel Spesifikasi Use Case Kunci

| ID Use Case | Nama Use Case | Aktor | Pre-kondisi | Skenario Alur Utama | Post-kondisi |
|---|---|---|---|---|---|
| **UC-01** | Melakukan Login | Semua Pengguna | Pengguna memiliki email dan password terdaftar. | 1. Buka `/login`.<br/>2. Isi email dan password.<br/>3. Klik Masuk.<br/>4. Sistem memverifikasi kredensial. | Sesi terbuat, diarahkan ke Dashboard. |
| **UC-02** | Melihat Dashboard & Ringkasan KPI | Semua Pengguna | Pengguna telah login. | 1. Akses `/dashboard`.<br/>2. Sistem mengambil agregasi metrik dari database.<br/>3. Sistem merender kartu statistik dan progress bar status akreditasi. | Pengguna melihat statistik dan isu prioritas terkini. |
| **UC-03** | Mengelola Data LPK | Staf / Admin | Pengguna telah login. | 1. Akses `/lpks`.<br/>2. Klik Tambah LPK.<br/>3. Isi nomor registrasi, nama lembaga, kategori, dan kota.<br/>4. Simpan. | LPK baru tersimpan di database dan tampil di daftar. |
| **UC-05** | Mengelola Program Asesmen | Auditor / Staf | Data LPK telah ada di sistem. | 1. Akses `/assessments/create`.<br/>2. Pilih LPK, jenis asesmen, tanggal mulai, dan tanggal selesai.<br/>3. Sistem memvalidasi waktu selesai >= waktu mulai.<br/>4. Simpan. | Jadwal asesmen tersimpan dan tercatat di kalender. |
| **UC-06** | Mengelola Kalender Kerja | Auditor / Staf | Pengguna telah login. | 1. Buka `/calendar`.<br/>2. Klik kotak tanggal tertentu (misal: 24 September).<br/>3. Sistem membuka form dengan tanggal terisi otomatis.<br/>4. Isi judul dan jam kegiatan lalu simpan. | Agenda muncul pada tanggal terkait di kalender. |
| **UC-08** | Melaporkan Masalah & Follow-up | Semua Pengguna | Pengguna menemukan kendala operasional. | 1. Buka `/issues/create`.<br/>2. Pilih LPK, judul, dan tingkat prioritas (High/Medium/Low).<br/>3. Simpan laporan.<br/>4. Pada detail masalah, tulis catatan follow-up dan simpan. | Log tindak lanjut bertambah berantai secara kronologis. |
| **UC-11** | Mengubah Mode Tampilan | Semua Pengguna | Berada pada halaman berdata master (LPK/Asesmen/dll). | 1. Klik tombol toggle [ Grid ] pada toolbar.<br/>2. Layout tabel berganti menjadi kartu 2-kolom terstruktur.<br/>3. Preferensi disimpan ke `localStorage`. | Tampilan data tetap dalam mode Grid saat halaman dimuat ulang. |
| **UC-12** | Memfilter Data Server-Side | Semua Pengguna | Berada pada halaman berdata master. | 1. Pada mobile, klik [ Filter data ] untuk membuka drawer kanan.<br/>2. Pilih kriteria filter dan klik Terapkan.<br/>3. Sistem merefresh data sesuai filter dan menampilkan active chips. | Data tersaring akurat; chip filter dapat dihapus satu per satu. |

---

### 4.3 Activity Diagram (Diagram Aktivitas)

#### 4.3.1 Activity Diagram: Autentikasi Pengguna (Login)
```mermaid
stateDiagram-v2
    [*] --> BukaHalamanLogin: Akses URL /login
    BukaHalamanLogin --> InputKredensial: Masukkan Email & Password
    InputKredensial --> SubmitLogin: Klik Tombol "Masuk ke workspace"
    SubmitLogin --> ValidasiData: Server memvalidasi form
    
    state ValidasiData <<choice>>
    ValidasiData --> GagalLogin: Format tidak valid / Password salah
    GagalLogin --> InputKredensial: Tampilkan pesan error & SweetAlert2
    
    ValidasiData --> SuksesLogin: Kredensial Cocok
    SuksesLogin --> GenerateSession: Regenerasi session ID
    GenerateSession --> RedirectDashboard: Redirect ke /dashboard
    RedirectDashboard --> [*]
```

#### 4.3.2 Activity Diagram: Pembuatan Agenda dari Kalender Interaktif
```mermaid
stateDiagram-v2
    [*] --> BukaKalender: Buka Halaman /calendar
    BukaKalender --> PilihTanggal: Klik kotak tanggal pada grid kalender
    PilihTanggal --> BukaFormAgenda: Sistem membuka /calendar/events/create?date=YYYY-MM-DD
    BukaFormAgenda --> FormAutoFilled: Input start_date & end_date otomatis terisi tanggal yang diklik
    FormAutoFilled --> InputDetail: Pilih LPK, Judul Agenda, Jam Mulai & Selesai
    InputDetail --> SubmitForm: Klik Simpan Agenda
    SubmitForm --> ValidasiWaktu: Validasi end_at >= start_at
    
    state ValidasiWaktu <<choice>>
    ValidasiWaktu --> ErrorWaktu: Jam selesai lebih awal dari jam mulai
    ErrorWaktu --> InputDetail: Tampilkan notifikasi validasi
    
    ValidasiWaktu --> SimpanDatabase: Valid
    SimpanDatabase --> TampilkanDetailAgenda: Redirect ke /calendar/events/{id}
    TampilkanDetailAgenda --> [*]
```

#### 4.3.3 Activity Diagram: Pelaporan Masalah & Penambahan Follow-Up
```mermaid
stateDiagram-v2
    [*] --> BukaDaftarMasalah: Buka /issues
    BukaDaftarMasalah --> KlikTambahMasalah: Klik "Buat laporan"
    KlikTambahMasalah --> IsiFormMasalah: Pilih LPK, Judul, Prioritas, & Deskripsi
    IsiFormMasalah --> SimpanMasalah: Submit Form
    SimpanMasalah --> HalamanDetailMasalah: Masalah tersimpan (Status: OPEN)
    
    state SiklusFollowUp {
        HalamanDetailMasalah --> TulisFollowUp: Tulis catatan tindakan di form follow-up
        TulisFollowUp --> SubmitFollowUp: Klik "Simpan catatan"
        SubmitFollowUp --> CatatLog: Sistem mencatat note, user_id, timestamp
        CatatLog --> UpdateTampilanLog: Catatan tampil berantai di halaman detail
    }
    
    UpdateTampilanLog --> [*]
```

---

### 4.4 Sequence Diagram (Diagram Urutan)

#### 4.4.1 Sequence Diagram: Server-Side Filtering & Toggle Mode Tampilan
```mermaid
sequenceDiagram
    autonumber
    actor User as Pengguna (Browser)
    participant DOM as Frontend UI (app.js)
    participant Ctrl as AssessmentController
    participant Model as Assessment (Eloquent)
    participant DB as Database SQLite

    User->>DOM: Klik [ Filter data ] & pilih status="COMPLETED"
    User->>DOM: Klik [ Terapkan filter ]
    DOM->>Ctrl: HTTP GET /assessments?status=COMPLETED
    Ctrl->>Model: Assessment::query()->where('status', 'COMPLETED')
    Model->>DB: SELECT * FROM assessments WHERE status = 'COMPLETED' ...
    DB-->>Model: Return data baris
    Model-->>Ctrl: Collection + LengthAwarePaginator
    Ctrl-->>DOM: Render Blade View (Tabel / Card)
    DOM->>DOM: Render Active Filter Chip: "Status: Selesai"
    DOM-->>User: Tampilan daftar hasil filter muncul

    User->>DOM: Klik tombol toggle [ Grid ]
    DOM->>DOM: tableWrap.classList.add('table-mode-grid')
    DOM->>DOM: localStorage.setItem('table-view-assessments', 'grid')
    DOM-->>User: Tampilan berganti seketika ke Mode Grid Cards 2-kolom
```

#### 4.4.2 Sequence Diagram: Penambahan Catatan Tindak Lanjut (Follow-Up)
```mermaid
sequenceDiagram
    autonumber
    actor User as Asesor / Staf
    participant View as View (issues/show.blade.php)
    participant Ctrl as IssueController
    participant Model as IssueFollowup
    participant DB as Database SQLite

    User->>View: Buka detail masalah /issues/{id}
    User->>View: Isi input catatan: "Sudah dikonfirmasi ke LPK"
    User->>View: Klik tombol [ Simpan catatan ]
    View->>Ctrl: HTTP POST /issues/{id}/follow-ups (payload: note, CSRF)
    Ctrl->>Ctrl: Validasi input (note wajib diisi string)
    Ctrl->>Model: IssueFollowup::create([...])
    Model->>DB: INSERT INTO issue_followups (issue_id, user_id, note, created_at...)
    DB-->>Model: Success ID
    Ctrl-->>View: Redirect back() with session flash success
    View->>View: Tampilkan Toast SweetAlert2 "Catatan tindak lanjut berhasil ditambahkan."
    View-->>User: Riwayat follow-up baru muncul di urutan teratas
```

#### 4.4.4 Sequence Diagram: Alur Kepatuhan SIMASADI (Biaya Asesor, Billing PNBP, dan e-Sign BSrE)
```mermaid
sequenceDiagram
    autonumber
    actor User as Asesor / Petugas KAN
    participant View as Detail Page (Blade)
    participant Ctrl as Controller
    participant Model as Eloquent Model
    participant DB as SQLite Database

    Note over User,DB: 1. Pelaporan & Verifikasi Biaya Perjalanan Dinas Asesor
    User->>View: Input Uang Harian, Transport, Akomodasi, Paket Data
    View->>Ctrl: POST /assessments/{id}/expenses
    Ctrl->>Model: AssessmentExpense::updateOrCreate([...])
    Model->>DB: INSERT/UPDATE assessment_expenses (status: MENUNGGU_VERIFIKASI)
    DB-->>Ctrl: Saved
    User->>View: Verifikator KAN klik [ Verifikasi SBM ]
    View->>Ctrl: POST /assessments/{id}/expenses/verify (status: TERVERIFIKASI)
    Ctrl->>Model: Expense->update(['status' => 'TERVERIFIKASI', 'verified_by' => user_id])
    Model->>DB: UPDATE assessment_expenses
    DB-->>Ctrl: Saved

    Note over User,DB: 2. Realisasi Billing PNBP (SIMPONI Kemenkeu)
    User->>View: Klik [ Terbitkan Kode Billing SIMPONI ]
    View->>Ctrl: POST /accreditations/{id}/billings
    Ctrl->>Model: AccreditationBilling::create([...])
    Model->>DB: INSERT INTO accreditation_billings (status: UNPAID, 15-digit code)
    DB-->>Ctrl: Saved
    User->>View: Konfirmasi Setoran Kas Negara (Input Channel & NTPN)
    View->>Ctrl: POST /accreditations/{id}/billings/{billing}/pay
    Ctrl->>Model: Billing->update(['status' => 'PAID', 'ntpn' => 'NTPN...', 'paid_at' => now()])
    Model->>DB: UPDATE accreditation_billings
    DB-->>Ctrl: Saved

    Note over User,DB: 3. Pembubuhan Tanda Tangan Elektronik SK (BSrE)
    User->>View: Klik [ Tandatangani SK Secara Digital ]
    View->>Ctrl: POST /accreditations/{id}/esign (Passphrase Token)
    Ctrl->>Ctrl: Generate SHA-256 Hash Integritas Dokumen & Nomor Seri BSrE
    Ctrl->>Model: AccreditationSignature::create([...])
    Model->>DB: INSERT INTO accreditation_signatures (is_signed: true, verify_hash)
    Ctrl->>Model: Accreditation->update(['status' => 'COMPLETED', 'output_released_at' => now()])
    Model->>DB: UPDATE accreditations
    DB-->>Ctrl: Release Output Success
    Ctrl-->>View: Redirect back() dengan Cap Segel Digital BSrE Aktif
```

---

### 4.5 Rancangan Basis Data (Entity Relationship Diagram - ERD)

SIMASADI menggunakan skema relasional dengan 14 tabel yang saling terintegrasi mencakup master data, agenda kerja, log kendala, hingga administrasi finansial dan sertifikasi digital:

```mermaid
erDiagram
    USERS ||--o{ CALENDAR_EVENTS : "creates"
    USERS ||--o{ ISSUE_FOLLOWUPS : "records"
    USERS ||--o{ BACKUPS : "logs"
    USERS ||--o{ ASSESSMENT_EXPENSES : "reports/verifies"
    
    LPKS ||--o{ ACCREDITATIONS : "possesses"
    LPKS ||--o{ ASSESSMENTS : "undergoes"
    LPKS ||--o{ AMENDMENTS : "applies"
    LPKS ||--o{ ISSUES : "has"
    LPKS ||--o{ CALENDAR_EVENTS : "associated_with"

    ASSESSMENTS ||--o| ASSESSMENT_EXPENSES : "incurs"
    ACCREDITATIONS ||--o{ ACCREDITATION_BILLINGS : "billed_by"
    ACCREDITATIONS ||--o| ACCREDITATION_SIGNATURES : "certified_by"

    ISSUES ||--o{ ISSUE_FOLLOWUPS : "followed_up_by"
    SERVICES ||--o{ SERVICE_CHECKS : "checked_by"

    USERS {
        int id PK
        string name
        string email
        string password
        datetime created_at
    }

    LPKS {
        int id PK
        string registration_number
        string name
        string category
        string city
        string province
        int scope_count
        string status
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

    ASSESSMENTS {
        int id PK
        int lpk_id FK
        int created_by FK
        string title
        string assessment_type
        datetime start_at
        datetime end_at
        string location
        string lead_assessor
        string status
        text notes
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
        string status
        text verification_notes
        int verified_by FK
        datetime verified_at
        datetime created_at
    }

    ACCREDITATION_BILLINGS {
        int id PK
        int accreditation_id FK
        string billing_code
        string tariff_name
        int amount
        datetime issued_at
        datetime expired_at
        string status
        string ntpn
        string ntb
        string payment_channel
        datetime paid_at
        datetime created_at
    }

    ACCREDITATION_SIGNATURES {
        int id PK
        int accreditation_id FK
        string sk_number
        string signer_name
        string signer_title
        string signer_nip
        boolean is_signed
        datetime signed_at
        string certificate_series
        string verify_hash
        datetime created_at
    }

    AMENDMENTS {
        int id PK
        int lpk_id FK
        string submission_number
        string reference_number
        string amendment_type
        date submitted_at
        date target_date
        string status
        text notes
        datetime created_at
    }

    ISSUES {
        int id PK
        int lpk_id FK
        string title
        text description
        string priority
        string status
        date due_date
        datetime created_at
    }

    ISSUE_FOLLOWUPS {
        int id PK
        int issue_id FK
        int user_id FK
        text note
        datetime created_at
    }

    CALENDAR_EVENTS {
        int id PK
        int lpk_id FK
        int created_by FK
        string title
        text description
        datetime start_at
        datetime end_at
        string status
        datetime created_at
    }

    SERVICES {
        int id PK
        string name
        string system
        string status
        datetime last_checked_at
        datetime created_at
    }

    SERVICE_CHECKS {
        int id PK
        int service_id FK
        string status
        string message
        datetime created_at
    }

    BACKUPS {
        int id PK
        int recorded_by FK
        string system
        string status
        string size
        datetime finished_at
        datetime created_at
    }
```

---

### 4.6 Class Diagram

Diagram kelas menyajikan struktur Model Eloquent, Relasi, dan Controller utama dalam aplikasi Laravel:

```mermaid
classDiagram
    direction TB

    class User {
        +int id
        +string name
        +string email
        +calendarEvents() HasMany
        +issueFollowups() HasMany
        +backups() HasMany
    }

    class Lpk {
        +int id
        +string registration_number
        +string name
        +string category
        +string status
        +accreditations() HasMany
        +assessments() HasMany
        +amendments() HasMany
        +issues() HasMany
        +calendarEvents() HasMany
    }

    class Accreditation {
        +int id
        +int lpk_id
        +string status
        +date start_date
        +date target_date
        +lpk() BelongsTo
    }

    class Assessment {
        +int id
        +int lpk_id
        +string title
        +string assessment_type
        +datetime start_at
        +datetime end_at
        +string status
        +lpk() BelongsTo
    }

    class CalendarEvent {
        +int id
        +int lpk_id
        +int created_by
        +string title
        +datetime start_at
        +datetime end_at
        +string status
        +lpk() BelongsTo
        +creator() BelongsTo
    }

    class Issue {
        +int id
        +int lpk_id
        +string title
        +string priority
        +string status
        +lpk() BelongsTo
        +followups() HasMany
    }

    class IssueFollowup {
        +int id
        +int issue_id
        +int user_id
        +text note
        +issue() BelongsTo
        +user() BelongsTo
    }

    class AssessmentExpense {
        +int id
        +int assessment_id
        +int daily_allowance
        +int transport_cost
        +int accommodation_cost
        +int package_data_cost
        +int total_cost
        +string status
        +string receipt_note
        +assessment() BelongsTo
        +isVerified() bool
    }

    class AccreditationBilling {
        +int id
        +int accreditation_id
        +string billing_code
        +int amount
        +string status
        +string ntpn
        +accreditation() BelongsTo
        +isPaid() bool
    }

    class AccreditationSignature {
        +int id
        +int accreditation_id
        +string sk_number
        +string signer_name
        +boolean is_signed
        +string verify_hash
        +accreditation() BelongsTo
    }

    class LpkController {
        +index(Request) View
        +create() View
        +store(Request) Redirect
        +show(Lpk) View
        +edit(Lpk) View
        +update(Request, Lpk) Redirect
    }

    class AssessmentController {
        +index(Request) View
        +create() View
        +store(Request) Redirect
        +show(Assessment) View
        +edit(Assessment) View
        +update(Request, Assessment) Redirect
    }

    class AssessmentExpenseController {
        +storeOrUpdate(Request, Assessment) Redirect
        +verify(Request, Assessment) Redirect
    }

    class AccreditationBillingController {
        +store(Request, Accreditation) Redirect
        +pay(Request, Accreditation, AccreditationBilling) Redirect
    }

    class AccreditationSignatureController {
        +sign(Request, Accreditation) Redirect
        +verifyPublic(string) View
    }

    class CalendarEventController {
        +index(Request) View
        +create(Request) View
        +store(Request) Redirect
        +show(CalendarEvent) View
        +edit(CalendarEvent) View
        +update(Request, CalendarEvent) Redirect
    }

    class IssueController {
        +index(Request) View
        +create() View
        +store(Request) Redirect
        +show(Issue) View
        +update(Request, Issue) Redirect
        +storeFollowup(Request, Issue) Redirect
    }

    Lpk "1" -- "*" Accreditation : has
    Lpk "1" -- "*" Assessment : undergoes
    Lpk "1" -- "*" CalendarEvent : scheduled
    Lpk "1" -- "*" Issue : reports
    Assessment "1" -- "0..1" AssessmentExpense : incurs
    Accreditation "1" -- "*" AccreditationBilling : billed
    Accreditation "1" -- "0..1" AccreditationSignature : signed
    Issue "1" -- "*" IssueFollowup : contains
    User "1" -- "*" IssueFollowup : writes
    User "1" -- "*" CalendarEvent : creates

    LpkController ..> Lpk : uses
    AssessmentController ..> Assessment : uses
    AssessmentExpenseController ..> AssessmentExpense : uses
    AccreditationBillingController ..> AccreditationBilling : uses
    AccreditationSignatureController ..> AccreditationSignature : uses
    CalendarEventController ..> CalendarEvent : uses
    IssueController ..> Issue : uses
    IssueController ..> IssueFollowup : uses
```

---

### 4.7 Rancangan Antarmuka Pengguna (Wireframe)

Berikut adalah representasi tata letak antarmuka halaman-halaman kunci aplikasi SIMASADI:

#### 4.7.1 Wireframe: Dashboard Ringkasan (Desktop)
```text
+----------------------------------------------------------------------------------------------------+
| [Logo BSN] SIMASADI | Sistem Informasi & Administrasi Akreditasi       [Admin (Staf) v] [Logout]   |
+----------------------------------------------------------------------------------------------------+
| [SIDEBAR]       | Ringkasan Operasional                                                            |
| - Ringkasan     | Pantau status LPK, akreditasi, dan jadwal kerja.                                 |
| - Data LPK      | +------------------+ +------------------+ +------------------+ +---------------+ |
| - Akreditasi    | | Total LPK        | | Akreditasi Aktif | | Asesmen Terjadwal| | Masalah Open  | |
| - Amandemen     | | 128              | | 42               | | 18               | | 5             | |
| - Program Ases. | +------------------+ +------------------+ +------------------+ +---------------+ |
| - Kalender      |                                                                                  |
| - Masalah       | +------------------------------------------------------------------------------+ |
| - Layanan       | | Analisis Status Akreditasi                                                   | |
| - Backup        | | [======== 25% Belum Mulai ========][====== 50% Berjalan ======][= 25% Sels=] | |
|                 | +------------------------------------------------------------------------------+ |
|                 | +------------------------------------+ +-------------------------------------+ |
|                 | | Masalah Prioritas Tinggi           | | Aktivitas Asesmen Terkini           | |
|                 | | - PT Qualis (High, Due: 24 Sep)    | | - Surveilen PT Citrabuana (24 Sep)  | |
|                 | | - PT Petrokimia (High, Due: 28 Sep)| | - Asesmen Awal PT Sucofindo (28 Sep)| |
|                 | +------------------------------------+ +-------------------------------------+ |
+----------------------------------------------------------------------------------------------------+
```

#### 4.7.2 Wireframe: Tampilan Tabel Data dengan Tombol Detail Interaktif (Desktop)
```text
+----------------------------------------------------------------------------------------------------+
| Program Asesmen                                                            [+ Tambah Asesmen]      |
| Jadwal dan progres asesmen semua LPK.                                                              |
+----------------------------------------------------------------------------------------------------+
| [Filter: Cari agenda...] [LPK: Semua v] [Jenis: Semua v] [Status: Semua v] [Terapkan Filter] [Reset]|
+----------------------------------------------------------------------------------------------------+
| FILTER AKTIF:  [ Status: Selesai (x) ]   [ Jenis: Surveilen (x) ]                 Reset Filter      |
+----------------------------------------------------------------------------------------------------+
| Show [ 10 v ] entries                                                      [ [=] Tabel | [::] Grid ]|
+----------------------------------------------------------------------------------------------------+
| AGENDA                     | LPK                       | WAKTU             | STATUS       | AKSI   |
+----------------------------+---------------------------+-------------------+--------------+--------+
| Surveilen                  | PT Citrabuana Indoloka    | 24 Sep 2026, 09:00| [Direncanak] |[Detail→|
| REASSESSMENT               |                           |                   |              |        |
+----------------------------+---------------------------+-------------------+--------------+--------+
| Asesmen Awal               | PT Qualis Indonesia       | 25 Sep 2026, 09:00| [Terjadwal ] |[Detail→|
| INITIAL                    |                           |                   |              |        |
+----------------------------+---------------------------+-------------------+--------------+--------+
| Showing 1 to 10 of 18 entries                                                 [<] [1] [2] [>]      |
+----------------------------------------------------------------------------------------------------+
```

#### 4.7.3 Wireframe: Tampilan Mode Grid Card & Penataan Sejajar Mobile (Mobile <= 600px)
```text
+------------------------------------------+
| [=] SIMASADI                    [User v] |
+------------------------------------------+
| Program Asesmen                          |
| [+ Tambah Asesmen]                       |
+------------------------------------------+
| [ ☩ Filter data ]       [ [=]Tab | [::]Grd] |  <-- Sejajar (Side-by-Side)
|                                          |
| Show [ 10 v ] entries                    |
+------------------------------------------+
| +--------------------------------------+ |
| | HEADER: Surveilen                    | |
| | [ REASSESSMENT ]                     | |  <-- Lavender pill badge
| +--------------------------------------+ |
| | LPK     : PT Citrabuana Indoloka     | |
| |           Laboratorium LSPro         | |  <-- Kolom 84px rata rapi
| | WAKTU   : 24 Sep 2026, 09:00         | |
| | STATUS  : [ Direncanakan ]           | |
| +--------------------------------------+ |
| | FOOTER:                 [ Detail → ] | |  <-- Card footer bar
| +--------------------------------------+ |
|                                          |
| +--------------------------------------+ |
| | HEADER: Asesmen Awal                 | |
| | [ INITIAL ]                          | |
| +--------------------------------------+ |
| | LPK     : PT Qualis Indonesia        | |
| | WAKTU   : 25 Sep 2026, 09:00         | |
| | STATUS  : [ Terjadwal ]              | |
| +--------------------------------------+ |
| | FOOTER:                 [ Detail → ] | |
| +--------------------------------------+ |
+------------------------------------------+
```

#### 4.7.4 Wireframe: Kalender Interaktif (Desktop)
```text
+----------------------------------------------------------------------------------------------------+
| Kalender Kegiatan                                           [Mode Kalender | Mode Agenda]          |
| Klik kotak tanggal untuk membuat agenda langsung.                                                  |
+----------------------------------------------------------------------------------------------------+
| < September 2026 >                                                                                 |
+------------+------------+------------+------------+------------+------------+----------------------+
| SEN        | SEL        | RAB        | KAM        | JUM        | SAB        | MIN                  |
+------------+------------+------------+------------+------------+------------+----------------------+
| 1          | 2          | 3          | 4          | 5          | 6          | 7                    |
|            |            |            | [Surveilen]|            |            |                      |
+------------+------------+------------+------------+------------+------------+----------------------+
| 8          | 9          | 10         | 11         | 12         | 13         | 14                   |
|            |            | [Rapat KAN]|            |            |            |                      |
+------------+------------+------------+------------+------------+------------+----------------------+
| 15         | 16         | 17         | 18         | 19 (TODAY) | 20         | 21 (KLIK TANGGAL)    |
|            |            |            |            | [Review]   |            | [ + Buat Agenda... ] |
+------------+------------+------------+------------+------------+------------+----------------------+
```
