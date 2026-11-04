<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Prodi;
use App\Models\Sekolah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadValidationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sales;
    protected Prodi $prodi;
    protected Sekolah $sekolah;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Disable CSRF in Laravel 12
        if (class_exists(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)) {
            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        }
        if (class_exists(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)) {
            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        }

        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->sales = User::factory()->create(['role' => 'Sales', 'status' => 'Aktif']);

        $this->prodi = Prodi::create([
            'kode' => 'TI',
            'nama' => 'Teknik Informatika',
            'jenjang' => 'S1',
            'fakultas' => 'FTIK',
            'status' => 'Aktif',
        ]);

        $this->sekolah = Sekolah::create([
            'kode' => 'SCH-001',
            'nama' => 'SMA Test Cirebon',
            'status' => 'Aktif',
            'lat' => -6.72620000,
            'lng' => 108.55740000,
        ]);
    }

    /**
     * Test 1: API Visit Controller (/api/v1/kunjungan)
     * Hanya menerima JPG/JPEG, menolak PNG, WebP, GIF, PHP, TXT, dan input kosong.
     */
    public function test_api_visit_rejects_non_jpg_and_accepts_jpg_jpeg(): void
    {
        $basePayload = [
            'prodi_id'     => $this->prodi->id,
            'sekolah_id'   => $this->sekolah->id,
            'tanggal'      => '2026-11-02',
            'lat'          => -6.72620000,
            'lng'          => 108.55740000,
            'pic_name'     => 'Bapak Kepala',
            'pic_whatsapp' => '+6281234567890',
        ];

        // 1. Missing foto (required) -> fails 422
        $resEmpty = $this->actingAs($this->sales, 'sanctum')->postJson('/api/v1/kunjungan', $basePayload);
        $resEmpty->assertStatus(422)->assertJsonValidationErrors('foto');

        // 2. Disallowed types: PNG, WebP, GIF, PHP, TXT -> all fail 422
        $disallowed = [
            'PNG'  => UploadedFile::fake()->create('image.png', 100, 'image/png'),
            'WebP' => UploadedFile::fake()->create('image.webp', 100, 'image/webp'),
            'GIF'  => UploadedFile::fake()->create('image.gif', 100, 'image/gif'),
            'PHP'  => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            'TXT'  => UploadedFile::fake()->create('note.txt', 10, 'text/plain'),
        ];

        foreach ($disallowed as $type => $file) {
            $payload = array_merge($basePayload, ['foto' => $file]);
            $response = $this->actingAs($this->sales, 'sanctum')->postJson('/api/v1/kunjungan', $payload);
            $response->assertStatus(422)->assertJsonValidationErrors('foto');
        }

        // 3. Allowed types: JPG & JPEG -> success 200/201
        $jpgFile = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');
        $resJpg = $this->actingAs($this->sales, 'sanctum')->postJson('/api/v1/kunjungan', array_merge($basePayload, ['foto' => $jpgFile]));
        $this->assertTrue(in_array($resJpg->status(), [200, 201]));

        $jpegFile = UploadedFile::fake()->create('photo.jpeg', 100, 'image/jpeg');
        $resJpeg = $this->actingAs($this->sales, 'sanctum')->postJson('/api/v1/kunjungan', array_merge($basePayload, ['foto' => $jpegFile]));
        $this->assertTrue(in_array($resJpeg->status(), [200, 201]));
    }

    /**
     * Test 2: Sales Visit Store (Web /sales/kunjungan)
     * Hanya menerima JPG/JPEG, menolak PNG, WebP, GIF, PHP, TXT, dan input kosong.
     */
    public function test_sales_web_visit_rejects_non_jpg_and_accepts_jpg_jpeg(): void
    {
        $basePayload = [
            'jenis'        => 'Sekolah',
            'sekolah_id'   => $this->sekolah->id,
            'prodi_id'     => $this->prodi->id,
            'tanggal'      => '2026-11-02',
            'waktu'        => '10:00',
            'lat'          => -6.72620000,
            'lng'          => 108.55740000,
            'pic_name'     => 'Bapak Kepala',
            'pic_whatsapp' => '+6281234567890',
        ];

        // 1. Missing foto (required) -> fails validation in session
        $resEmpty = $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $basePayload);
        $resEmpty->assertSessionHasErrors(['foto']);

        // 2. Disallowed types -> fail validation in session
        $disallowed = [
            'PNG'  => UploadedFile::fake()->create('image.png', 100, 'image/png'),
            'WebP' => UploadedFile::fake()->create('image.webp', 100, 'image/webp'),
            'GIF'  => UploadedFile::fake()->create('image.gif', 100, 'image/gif'),
            'PHP'  => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            'TXT'  => UploadedFile::fake()->create('note.txt', 10, 'text/plain'),
        ];

        foreach ($disallowed as $type => $file) {
            $payload = array_merge($basePayload, ['foto' => $file]);
            $response = $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payload);
            $response->assertSessionHasErrors(['foto']);
        }

        // 3. Allowed JPG -> success redirect
        $jpgFile = UploadedFile::fake()->create('valid.jpg', 100, 'image/jpeg');
        $resJpg = $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), array_merge($basePayload, ['foto' => $jpgFile]));
        $resJpg->assertSessionHasNoErrors();
        $resJpg->assertRedirect();
    }

    /**
     * Test 3: Attendance Store (/absensi/input)
     * Hanya menerima JPG/JPEG, menolak PNG, WebP, GIF, PHP, TXT, dan input kosong.
     */
    public function test_attendance_rejects_non_jpg_and_accepts_jpg_jpeg(): void
    {
        $basePayload = [
            'status' => 'Hadir',
            'notes'  => 'Hadir di kantor',
        ];

        // 1. Missing photo (required) -> fails
        $resEmpty = $this->actingAs($this->sales)->post(route('attendance.store'), $basePayload);
        $resEmpty->assertSessionHasErrors(['photo']);

        // 2. Disallowed types -> fail
        $disallowed = [
            'PNG'  => UploadedFile::fake()->create('att.png', 100, 'image/png'),
            'WebP' => UploadedFile::fake()->create('att.webp', 100, 'image/webp'),
            'GIF'  => UploadedFile::fake()->create('att.gif', 100, 'image/gif'),
            'PHP'  => UploadedFile::fake()->create('att.php', 10, 'application/x-php'),
            'TXT'  => UploadedFile::fake()->create('att.txt', 10, 'text/plain'),
        ];

        foreach ($disallowed as $type => $file) {
            $payload = array_merge($basePayload, ['photo' => $file]);
            $response = $this->actingAs($this->sales)->post(route('attendance.store'), $payload);
            $response->assertSessionHasErrors(['photo']);
        }

        // 3. Allowed JPG -> success
        $jpgFile = UploadedFile::fake()->create('att.jpg', 100, 'image/jpeg');
        $resJpg = $this->actingAs($this->sales)->post(route('attendance.store'), array_merge($basePayload, ['photo' => $jpgFile]));
        $resJpg->assertSessionHasNoErrors();
        $resJpg->assertRedirect(route('attendance.create'));
    }

    /**
     * Test 4: Profile Update Avatar (/profil)
     * Bersifat nullable, menolak format selain JPG/JPEG.
     */
    public function test_profile_avatar_rejects_non_jpg_and_accepts_jpg_or_empty(): void
    {
        $basePayload = [
            'name'  => 'Sales Updated',
            'phone' => '081234567890',
        ];

        // 1. Empty avatar (nullable) -> success
        $resEmpty = $this->actingAs($this->sales)->post(route('profil.update'), $basePayload);
        $resEmpty->assertSessionHasNoErrors();
        $resEmpty->assertRedirect();

        // 2. Disallowed types -> fail
        $disallowed = [
            'PNG'  => UploadedFile::fake()->create('avatar.png', 100, 'image/png'),
            'WebP' => UploadedFile::fake()->create('avatar.webp', 100, 'image/webp'),
            'GIF'  => UploadedFile::fake()->create('avatar.gif', 100, 'image/gif'),
            'PHP'  => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            'TXT'  => UploadedFile::fake()->create('info.txt', 10, 'text/plain'),
        ];

        foreach ($disallowed as $type => $file) {
            $payload = array_merge($basePayload, ['avatar' => $file]);
            $response = $this->actingAs($this->sales)->post(route('profil.update'), $payload);
            $response->assertSessionHasErrors(['avatar']);
        }

        // 3. Allowed JPG -> success
        $jpgFile = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        $resJpg = $this->actingAs($this->sales)->post(route('profil.update'), array_merge($basePayload, ['avatar' => $jpgFile]));
        $resJpg->assertSessionHasNoErrors();
        $resJpg->assertRedirect();
    }
}
