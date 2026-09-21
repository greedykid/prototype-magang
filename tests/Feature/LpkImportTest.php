<?php

namespace Tests\Feature;

use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LpkImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@simasadi.local',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->pic = User::factory()->create([
            'email' => 'pic@simasadi.local',
            'role' => User::ROLE_PIC,
        ]);
    }

    public function test_admin_can_download_import_template(): void
    {
        $response = $this->actingAs($this->admin)->get(route('lpks.import.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('template-import-lpk.csv', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('nomor_registrasi', $content);
        $this->assertStringContainsString('nama_lpk', $content);
        $this->assertStringContainsString('LP-101-IDN', $content);
    }

    public function test_pic_is_forbidden_from_importing_or_downloading_template(): void
    {
        $templateResponse = $this->actingAs($this->pic)->get(route('lpks.import.template'));
        $templateResponse->assertForbidden();

        $importResponse = $this->actingAs($this->pic)->post(route('lpks.import'), []);
        $importResponse->assertForbidden();
    }

    public function test_admin_can_import_lpks_from_csv_file(): void
    {
        $csvData = "nomor_registrasi,nama_lpk,alamat,email,telepon,status,catatan\n" .
            "LP-901-IDN,\"Lab Kalibrasi Sentosa\",\"Jl. Merdeka 10, Jakarta\",\"info@sentosa.id\",\"021-123456\",ACTIVE,\"Lab modern\"\n" .
            "LK-902-IDN,\"Balai Pengujian Utama\",\"Jl. Asia Afrika 20, Bandung\",\"kontak@utama.id\",\"022-654321\",INACTIVE,\"Sedang renovasi\"\n";

        $file = UploadedFile::fake()->createWithContent('lpk-batch.csv', $csvData);

        $response = $this->actingAs($this->admin)->post(route('lpks.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('lpks.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('lpks', [
            'registration_number' => 'LP-901-IDN',
            'name' => 'Lab Kalibrasi Sentosa',
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('lpks', [
            'registration_number' => 'LK-902-IDN',
            'name' => 'Balai Pengujian Utama',
            'status' => 'INACTIVE',
        ]);
    }

    public function test_import_with_semicolon_delimiter_and_smart_upsert(): void
    {
        // Existing LPK to be updated
        $existing = Lpk::factory()->create([
            'registration_number' => 'LP-EXIST-01',
            'name' => 'Nama Lama Lab',
            'status' => 'INACTIVE',
        ]);

        $csvData = "nomor_registrasi;nama_lpk;alamat;email;telepon;status;catatan\n" .
            "LP-EXIST-01;Nama Baru Lab Terakreditasi;Jl. Baru No. 1;baru@lab.id;021-999;ACTIVE;Diperbarui\n" .
            "LP-BRAND-NEW;Laboratorium Uji Segar;Jl. Segar No. 2;segar@lab.id;021-888;ACTIVE;Data baru\n";

        $file = UploadedFile::fake()->createWithContent('lpk-semicolon.csv', $csvData);

        $response = $this->actingAs($this->admin)->post(route('lpks.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('lpks.index'));

        // Existing should be updated
        $this->assertDatabaseHas('lpks', [
            'registration_number' => 'LP-EXIST-01',
            'name' => 'Nama Baru Lab Terakreditasi',
            'status' => 'ACTIVE',
        ]);

        // Brand new should be created
        $this->assertDatabaseHas('lpks', [
            'registration_number' => 'LP-BRAND-NEW',
            'name' => 'Laboratorium Uji Segar',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_import_validates_required_headers(): void
    {
        $invalidCsv = "alamat,telepon,status\n\"Jl. Sudirman\",\"021-123\",ACTIVE\n";
        $file = UploadedFile::fake()->createWithContent('invalid-columns.csv', $invalidCsv);

        $response = $this->actingAs($this->admin)->post(route('lpks.import'), [
            'csv_file' => $file,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('nomor_registrasi', session('error'));
    }

    public function test_import_from_google_sheets_url(): void
    {
        $csvBody = "nomor_registrasi,nama_lpk,status\n" .
            "LP-SHEETS-01,\"Lab Google Sheets Live\",ACTIVE\n";

        Http::fake([
            'https://docs.google.com/spreadsheets/d/test12345/export*' => Http::response($csvBody, 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('lpks.import'), [
            'sheets_url' => 'https://docs.google.com/spreadsheets/d/test12345/edit#gid=0',
        ]);

        $response->assertRedirect(route('lpks.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('lpks', [
            'registration_number' => 'LP-SHEETS-01',
            'name' => 'Lab Google Sheets Live',
        ]);
    }

    public function test_ui_renders_import_button_for_admin_and_hides_for_pic(): void
    {
        // Admin should see Impor LPK button and modal
        $adminResponse = $this->actingAs($this->admin)->get(route('lpks.index'));
        $adminResponse->assertOk();
        $adminResponse->assertSee('Impor LPK');
        $adminResponse->assertSee('modal-import-lpk');

        // PIC should NOT see Impor LPK button or modal
        $picResponse = $this->actingAs($this->pic)->get(route('lpks.index'));
        $picResponse->assertOk();
        $picResponse->assertDontSee('modal-import-lpk');
    }

    public function test_import_with_custom_spreadsheet_headers_and_numeric_numbers(): void
    {
        $csvData = "\"NO. AKREDITASI\",\"NAMA LPK\",\"ALAMAT\",\"TELEPON / FAX\",\"EMAIL\",\"LINGKUP\",\"MASA BERLAKU AKREDITASI (EXPIRED)\",\"LINK\"\n" .
            "\"077\",\"Laboratorium Kekuatan Struktur BRIN\",\"Kawasan Puspiptek Serpong\",\"021-7560562\",\"b2tks@brin.go.id\",\"Pengujian Material dan Struktur\",\"24/05/2027\",\"https://drive.google.com/drive/folders/demo-077\"\n" .
            "\"LP-186-IDN\",\"UPT Lab Konstruksi PU Jatim\",\"Jl. Gayung Kebonsari Surabaya\",\"031-8290123\",\"lab.jatim@pu.go.id\",\"Pengujian Beton dan Aspal\",\"25 Mei 2028\",\"https://drive.google.com/drive/folders/demo-186\"\n";

        $file = UploadedFile::fake()->createWithContent('custom-labs.csv', $csvData);

        $response = $this->actingAs($this->admin)->post(route('lpks.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('lpks.index'));
        $response->assertSessionHas('success');

        // Verify numeric '077' automatically converted to 'LP-077-IDN'
        $this->assertDatabaseHas('lpks', [
            'registration_number' => 'LP-077-IDN',
            'name' => 'Laboratorium Kekuatan Struktur BRIN',
            'address' => 'Kawasan Puspiptek Serpong',
            'phone' => '021-7560562',
            'email' => 'b2tks@brin.go.id',
            'expired_at' => '2027-05-24 00:00:00',
            'drive_url' => 'https://drive.google.com/drive/folders/demo-077',
        ]);

        // Verify LP-186-IDN with Indonesian month parsing
        $this->assertDatabaseHas('lpks', [
            'registration_number' => 'LP-186-IDN',
            'name' => 'UPT Lab Konstruksi PU Jatim',
            'expired_at' => '2028-05-25 00:00:00',
            'drive_url' => 'https://drive.google.com/drive/folders/demo-186',
        ]);
    }
}
