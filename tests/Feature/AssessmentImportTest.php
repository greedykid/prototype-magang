<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AssessmentImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pic;
    protected Lpk $lpk;

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

        $this->lpk = Lpk::factory()->create([
            'registration_number' => 'LP-077-IDN',
            'name' => 'Balai Pengujian Standar Industri',
        ]);
    }

    public function test_user_can_download_assessment_import_template_csv(): void
    {
        $response = $this->actingAs($this->pic)->get(route('assessments.import.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('template-import-asesmen.csv', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('nomor_registrasi_lpk', $content);
        $this->assertStringContainsString('judul_agenda', $content);
        $this->assertStringContainsString('jenis_asesmen', $content);
    }

    public function test_user_can_download_assessment_import_template_xlsx(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assessments.import.template', ['format' => 'xlsx']));

        $response->assertOk();
        $this->assertStringContainsString('template-import-asesmen.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_pic_can_import_assessments_from_csv(): void
    {
        $csvContent = implode("\n", [
            'nomor_registrasi_lpk,judul_agenda,jenis_asesmen,tanggal_mulai,tanggal_selesai,lokasi,status,ketua_asesor,status_tp',
            'LP-077-IDN,Surveilen Berkala Lab BPS,Surveilen,2026-10-15 09:00,2026-10-17 17:00,Serpong,SCHEDULED,Dr. Suryadi,NONE',
        ]);

        $file = UploadedFile::fake()->createWithContent('assessments.csv', $csvContent);

        $response = $this->actingAs($this->pic)->post(route('assessments.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('assessments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('assessments', [
            'lpk_id' => $this->lpk->id,
            'title' => 'Surveilen Berkala Lab BPS',
            'assessment_type' => 'Surveilen',
            'status' => 'SCHEDULED',
            'lead_assessor' => 'Dr. Suryadi',
        ]);
    }

    public function test_admin_can_import_assessments_from_xlsx(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['nomor_registrasi_lpk', 'judul_agenda', 'jenis_asesmen', 'tanggal_mulai', 'tanggal_selesai', 'lokasi', 'status', 'ketua_asesor', 'status_tp'],
            ['LP-077-IDN', 'Re-akreditasi 5 Tahunan', 'Re-asesmen', '2026-11-01 08:30', '2026-11-03 16:30', 'Gedung Lab Utama', 'SCHEDULED', 'Dra. Nurul', 'NONE'],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_import_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile($tempPath, 'assessments.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->admin)->post(route('assessments.import'), [
            'csv_file' => $file,
        ]);

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }

        $response->assertRedirect(route('assessments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('assessments', [
            'lpk_id' => $this->lpk->id,
            'title' => 'Re-akreditasi 5 Tahunan',
            'assessment_type' => 'Re-asesmen',
        ]);
    }

    public function test_ui_renders_import_button_for_pic_and_admin(): void
    {
        $adminView = $this->actingAs($this->admin)->get(route('assessments.index'));
        $adminView->assertOk()->assertSee('Import Asesmen')->assertSee('modal-import-assessments');

        $picView = $this->actingAs($this->pic)->get(route('assessments.index'));
        $picView->assertOk()->assertSee('Import Asesmen')->assertSee('modal-import-assessments');
    }
}
