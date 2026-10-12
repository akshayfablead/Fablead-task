<?php

namespace Tests\Feature;

use App\Jobs\ImportUsersCsv;
use App\Models\User;
use App\Models\UserImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class AdminUserImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_import_page_is_restricted_to_admins(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/user-imports')
            ->assertForbidden();
    }

    public function test_upload_only_stages_a_private_file_until_admin_confirmation(): void
    {
        config(['queue.default' => 'database']);
        Storage::fake('local');
        Queue::fake();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post('/admin/user-imports', [
                'file' => UploadedFile::fake()->createWithContent(
                    'users.csv',
                    "Name,Email,Phone\nFake User,fake@example.test,+15550000001\n"
                ),
            ])
            ->assertRedirect();

        $this->get(route('admin.user-imports.index'))
            ->assertOk()
            ->assertSee('Security checklist')
            ->assertSee('admin-only')
            ->assertSee('CSRF protection')
            ->assertSee('explicitly confirm');

        $import = UserImport::query()->firstOrFail();

        $this->assertSame(UserImport::STATUS_PENDING, $import->status);
        $this->assertSame(1, $import->total_rows);
        $this->assertSame(1, User::query()->count());
        Storage::disk('local')->assertExists($import->file_path);
        Queue::assertNothingPushed();

        $this->actingAs($admin)
            ->post(route('admin.user-imports.confirm', $import))
            ->assertRedirect(route('admin.user-imports.show', $import));

        $this->assertSame(UserImport::STATUS_QUEUED, $import->fresh()->status);
        Queue::assertPushed(ImportUsersCsv::class, 1);
        $this->assertSame(1, User::query()->count());
    }

    public function test_admin_can_review_staged_import_and_see_failure_report(): void
    {
        config(['queue.default' => 'database']);
        Storage::fake('local');
        $admin = $this->createAdmin();
        $import = $this->createImport(
            $admin,
            "Name,Email\nFake User,fake@example.test\n"
        );
        $import->update(['status' => UserImport::STATUS_PENDING]);

        $this->actingAs($admin)
            ->get(route('admin.user-imports.index'))
            ->assertOk()
            ->assertSee('Test user imports');

        $this->get(route('admin.user-imports.show', $import))
            ->assertOk()
            ->assertSee('Review import')
            ->assertSee('Start background import');
    }

    public function test_xlsx_upload_is_converted_and_staged_without_importing_users(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();
        $xlsx = $this->createXlsx([
            '[Content_Types].xml' => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>',
            'xl/workbook.xml' => '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Users" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8"?>
                <worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
                    <sheetData>
                        <row r="1"><c r="A1" t="inlineStr"><is><t>Name</t></is></c><c r="B1" t="inlineStr"><is><t>Email</t></is></c><c r="C1" t="inlineStr"><is><t>Phone</t></is></c></row>
                        <row r="2"><c r="A2" t="inlineStr"><is><t>Excel User</t></is></c><c r="B2" t="inlineStr"><is><t>excel@example.test</t></is></c><c r="C2" t="inlineStr"><is><t>+15550000001</t></is></c></row>
                    </sheetData>
                </worksheet>',
        ]);

        $this->actingAs($admin)
            ->post('/admin/user-imports', [
                'file' => UploadedFile::fake()->createWithContent('users.xlsx', $xlsx),
            ])
            ->assertRedirect();

        $import = UserImport::query()->firstOrFail();
        $this->assertSame('users.xlsx', $import->original_name);
        $this->assertSame(1, $import->total_rows);
        $this->assertSame(1, User::query()->count());
        Storage::disk('local')->assertExists($import->file_path);
        $this->assertSame(
            "Name,Email,Phone\n\"Excel User\",excel@example.test,+15550000001\n",
            Storage::disk('local')->get($import->file_path)
        );
    }

    public function test_invalid_xlsx_upload_is_rejected_without_staging_a_file(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->from('/admin/user-imports')
            ->post('/admin/user-imports', [
                'file' => UploadedFile::fake()->createWithContent('invalid.xlsx', 'not a zip workbook'),
            ])
            ->assertRedirect('/admin/user-imports')
            ->assertSessionHasErrors('file');

        $this->assertSame(0, UserImport::query()->count());
        Storage::disk('local')->assertDirectoryEmpty('user-imports');
        $this->assertSame(1, User::query()->count());
    }

    public function test_xlsx_upload_reads_shared_string_cells(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();
        $xlsx = $this->createXlsx([
            '[Content_Types].xml' => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>',
            'xl/workbook.xml' => '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Users" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
            'xl/sharedStrings.xml' => '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>Name</t></si><si><t>Email</t></si><si><t>Shared User</t></si><si><t>shared@example.test</t></si></sst>',
            'xl/worksheets/sheet1.xml' => '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c></row><row r="2"><c r="A2" t="s"><v>2</v></c><c r="B2" t="s"><v>3</v></c></row></sheetData></worksheet>',
        ]);

        $this->actingAs($admin)
            ->post('/admin/user-imports', [
                'file' => UploadedFile::fake()->createWithContent('shared.xlsx', $xlsx),
            ])
            ->assertRedirect();

        $import = UserImport::query()->firstOrFail();
        $this->assertSame(
            "Name,Email\n\"Shared User\",shared@example.test\n",
            Storage::disk('local')->get($import->file_path)
        );
    }

    public function test_import_job_skips_existing_email_and_reports_invalid_rows_without_assigning_roles(): void
    {
        Storage::fake('local');
        $existing = User::factory()->create([
            'email' => 'Existing@Example.test',
            'phone' => '+15550000009',
            'name' => 'Existing Account',
        ]);
        $admin = $this->createAdmin();
        $import = $this->createImport(
            $admin,
            "Name,Email,Phone\n"
                ."Imported User,imported@example.test,+15550000001\n"
                ."Existing Email,existing@example.test,+15550000002\n"
                ."Invalid Email,not-an-email,+15550000003\n"
                ."Duplicate Phone,phone@example.test,+15550000009\n"
        );
        Queue::fake();

        (new ImportUsersCsv($import->getKey()))->handle();

        $import->refresh();
        $this->assertSame(UserImport::STATUS_COMPLETED, $import->status);
        $this->assertSame(4, $import->processed_rows);
        $this->assertSame(1, $import->imported_rows);
        $this->assertSame(1, $import->skipped_rows);
        $this->assertSame(2, $import->failed_rows);
        $this->assertNotNull($import->duration_ms);
        $this->assertSame('Existing Account', $existing->fresh()->name);

        $created = User::query()
            ->where('import_batch_id', $import->getKey())
            ->firstOrFail();
        $this->assertSame('imported@example.test', $created->email);
        $this->assertSame(0, $created->roles()->count());
        $this->assertDatabaseHas('user_import_failures', [
            'user_import_id' => $import->getKey(),
            'row_number' => 5,
            'reason' => 'Phone number already exists in the database.',
        ]);
        Storage::disk('local')->assertMissing($import->file_path);

        $this->actingAs($admin)
            ->getJson(route('admin.user-imports.status', $import))
            ->assertOk()
            ->assertJsonPath('status', UserImport::STATUS_COMPLETED)
            ->assertJsonPath('total_rows', 4)
            ->assertJsonPath('processed_rows', 4)
            ->assertJsonPath('imported_rows', 1)
            ->assertJsonPath('skipped_rows', 1)
            ->assertJsonPath('failed_rows', 2);
    }

    public function test_import_job_processes_and_checkpoints_rows_in_batches_of_one_thousand(): void
    {
        Storage::fake('local');
        Queue::fake();
        $admin = $this->createAdmin();
        $csv = "Name,Email,Phone\n";

        for ($number = 1; $number <= 1001; $number++) {
            $csv .= sprintf(
                "Fake User %04d,fake.user.%04d@example.test,+1555%07d\n",
                $number,
                $number,
                $number
            );
        }

        $import = $this->createImport($admin, $csv);

        (new ImportUsersCsv($import->getKey()))->handle();

        $import->refresh();
        $this->assertSame(UserImport::STATUS_PROCESSING, $import->status);
        $this->assertSame(1000, $import->processed_rows);
        $this->assertSame(1000, $import->imported_rows);
        $this->assertGreaterThan(0, $import->next_byte_offset);
        Queue::assertPushed(ImportUsersCsv::class, 1);

        (new ImportUsersCsv($import->getKey()))->handle();

        $import->refresh();
        $this->assertSame(UserImport::STATUS_COMPLETED, $import->status);
        $this->assertSame(1001, $import->imported_rows);
        $this->assertSame(1001, User::query()
            ->where('import_batch_id', $import->getKey())
            ->count());

        (new ImportUsersCsv($import->getKey()))->handle();
        $this->assertSame(1001, User::query()
            ->where('import_batch_id', $import->getKey())
            ->count());
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $admin->assignRole($role);

        return $admin;
    }

    private function createImport(User $admin, string $csv): UserImport
    {
        $id = (string) Str::uuid();
        $path = "user-imports/{$id}.csv";
        Storage::disk('local')->put($path, $csv);

        return UserImport::query()->create([
            'id' => $id,
            'uploaded_by' => $admin->getKey(),
            'original_name' => 'users.csv',
            'file_path' => $path,
            'status' => UserImport::STATUS_QUEUED,
            'total_rows' => substr_count($csv, "\n") - 1,
        ]);
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function createXlsx(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'user-import-test-');
        $archive = new ZipArchive;

        if ($path === false || $archive->open($path, ZipArchive::OVERWRITE) !== true) {
            $this->fail('Could not create an XLSX test fixture.');
        }

        foreach ($entries as $name => $contents) {
            $archive->addFromString($name, $contents);
        }

        $archive->close();

        $contents = file_get_contents($path);
        unlink($path);

        return $contents;
    }
}
