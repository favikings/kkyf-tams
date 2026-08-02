<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

requireSuperAdmin();

unset($_SESSION['import_pending']);

$tentsStmt = db()->prepare('SELECT id, name FROM tents ORDER BY name ASC');
$tentsStmt->execute();
$tentsForJs = array_map(
    static fn (array $t): array => ['id' => (int) $t['id'], 'name' => (string) $t['name']],
    $tentsStmt->fetchAll()
);

$buttonBase = 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-md text-[14px] leading-5 font-semibold font-display tracking-[0.02em] active:scale-[0.98] motion-safe:transition-transform disabled:opacity-50 disabled:pointer-events-none';
$primaryButton = $buttonBase . ' bg-primary text-on-primary shadow-card';
$secondaryButton = $buttonBase . ' bg-surface-container text-on-surface border border-outline-variant';
$inputClass = 'mt-1 w-full min-h-[44px] rounded-md border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60';

$pageTitle = 'Import Members';

require_once __DIR__ . '/../app/includes/header.php';
?>
<div class="mx-auto max-w-4xl">
  <header class="mb-6 md:mb-8">
    <h1 class="font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10">Import Members</h1>
    <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">Bulk-add members from a CSV or Excel file. Attendance is never imported — member records only.</p>
  </header>

  <div x-data="importWizard({
    tents: <?= e(json_encode($tentsForJs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>,
    csrf: '<?= e(csrfToken()) ?>',
  })">
    <ol class="mb-8 flex items-start gap-2">
      <li class="flex flex-1 flex-col items-center gap-1.5 text-center">
        <span class="flex h-8 w-8 items-center justify-center rounded-full font-display text-[13px] leading-4 font-semibold" :class="step >= 1 ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant'">1</span>
        <span class="font-display text-[11px] leading-4 tracking-[0.04em] uppercase" :class="step === 1 ? 'font-semibold text-primary' : 'text-on-surface-variant'">Tent</span>
      </li>
      <li class="flex flex-1 flex-col items-center gap-1.5 text-center">
        <span class="flex h-8 w-8 items-center justify-center rounded-full font-display text-[13px] leading-4 font-semibold" :class="step >= 2 ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant'">2</span>
        <span class="font-display text-[11px] leading-4 tracking-[0.04em] uppercase" :class="step === 2 ? 'font-semibold text-primary' : 'text-on-surface-variant'">Upload</span>
      </li>
      <li class="flex flex-1 flex-col items-center gap-1.5 text-center">
        <span class="flex h-8 w-8 items-center justify-center rounded-full font-display text-[13px] leading-4 font-semibold" :class="step >= 3 ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant'">3</span>
        <span class="font-display text-[11px] leading-4 tracking-[0.04em] uppercase" :class="step === 3 ? 'font-semibold text-primary' : 'text-on-surface-variant'">Map</span>
      </li>
      <li class="flex flex-1 flex-col items-center gap-1.5 text-center">
        <span class="flex h-8 w-8 items-center justify-center rounded-full font-display text-[13px] leading-4 font-semibold" :class="step >= 4 ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant'">4</span>
        <span class="font-display text-[11px] leading-4 tracking-[0.04em] uppercase" :class="step === 4 ? 'font-semibold text-primary' : 'text-on-surface-variant'">Validate</span>
      </li>
      <li class="flex flex-1 flex-col items-center gap-1.5 text-center">
        <span class="flex h-8 w-8 items-center justify-center rounded-full font-display text-[13px] leading-4 font-semibold" :class="step >= 5 ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant'">5</span>
        <span class="font-display text-[11px] leading-4 tracking-[0.04em] uppercase" :class="step === 5 ? 'font-semibold text-primary' : 'text-on-surface-variant'">Import</span>
      </li>
    </ol>

    <p x-show="error" x-cloak class="mb-4 rounded-lg bg-error-container px-4 py-3 text-[14px] leading-5 text-on-error-container" x-text="error"></p>

    <!-- Step 1: tent -->
    <section x-show="step === 1" class="rounded-lg bg-surface-lowest p-6 shadow-card md:p-8">
      <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface">Which tent is this list for?</h2>
      <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">All imported members are added to this tent.</p>
      <label for="import-tent" class="mt-6 block font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Tent</label>
      <select id="import-tent" x-model="tentId" class="<?= $inputClass ?>">
        <option value="">Choose a tent</option>
        <?php foreach ($tentsForJs as $t): ?>
          <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <p x-show="tentId === ''" class="mt-2 text-[13px] leading-4 text-on-surface-variant/70">No tents yet? Add one under Admin → Tents first.</p>
      <div class="mt-6 flex justify-end">
        <button type="button" @click="nextToUpload()" :disabled="tentId === ''" class="<?= $primaryButton ?>">Continue</button>
      </div>
    </section>

    <!-- Step 2: upload -->
    <section x-show="step === 2" x-cloak class="rounded-lg bg-surface-lowest p-6 shadow-card md:p-8">
      <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface">Upload your member list</h2>
      <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">.csv or .xlsx, up to 10MB. The first row must be column headers. Save as CSV if Excel drops your phone numbers' leading zeros.</p>

      <label @dragover.prevent @drop.prevent="dropFile($event)"
             class="mt-6 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-outline-variant bg-surface-container-low px-6 py-12 text-center transition-colors hover:border-primary">
        <i data-lucide="upload-cloud" class="h-9 w-9 text-on-surface-variant"></i>
        <span class="font-display text-[15px] leading-5 font-semibold text-on-surface">Choose a file or drag it here</span>
        <span class="text-[13px] leading-4 text-on-surface-variant">CSV or XLSX · max 10MB</span>
        <input type="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="hidden" @change="pickFile($event)">
      </label>

      <div x-show="file" x-cloak class="mt-4 flex items-center gap-3 rounded-lg bg-surface-container px-4 py-3">
        <i data-lucide="file-text" class="h-5 w-5 shrink-0 text-primary"></i>
        <div class="min-w-0 flex-1">
          <p class="truncate font-display text-[14px] leading-5 font-semibold text-on-surface" x-text="file.name"></p>
          <p class="text-[13px] leading-4 text-on-surface-variant" x-text="(file.size / 1024).toFixed(1) + ' KB'"></p>
        </div>
        <button type="button" @click="file = null" class="font-display text-[13px] leading-4 font-semibold text-error">Remove</button>
      </div>
      <p x-show="fileError" x-cloak class="mt-3 text-[14px] leading-5 text-on-error-container" x-text="fileError"></p>

      <div class="mt-6 flex items-center justify-between gap-3">
        <button type="button" @click="step = 1" class="<?= $secondaryButton ?>">Back</button>
        <button type="button" @click="parseFile()" :disabled="parsing" class="<?= $primaryButton ?>">
          <span x-text="parsing ? 'Parsing…' : 'Parse & preview'"></span>
        </button>
      </div>
    </section>

    <!-- Step 3: map columns -->
    <section x-show="step === 3" x-cloak class="rounded-lg bg-surface-lowest p-6 shadow-card md:p-8">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface">Map columns to fields</h2>
          <p class="mt-1 text-[14px] leading-5 text-on-surface-variant"><span x-text="totalRows"></span> rows found. Full Name is required; the rest are optional.</p>
        </div>
        <button type="button" @click="step = 2" class="<?= $secondaryButton ?>">Choose another file</button>
      </div>

      <div class="mt-5 overflow-x-auto rounded-lg border border-outline-variant">
        <table class="w-full border-collapse text-left">
          <thead>
            <tr class="bg-surface-container">
              <template x-for="(col, i) in columns" :key="i">
                <th class="min-w-[150px] p-3 align-top">
                  <span class="block truncate font-display text-[13px] leading-4 font-semibold text-on-surface" x-text="col === '' ? ('Column ' + (i + 1)) : col"></span>
                  <select x-model="colField[i]" class="mt-1.5 w-full min-h-[40px] rounded-md border border-outline-variant bg-surface-lowest px-2 py-1.5 text-[14px] leading-5 text-on-surface focus:border-primary focus:outline-none">
                    <option value="">— Skip —</option>
                    <option value="full_name">Full Name</option>
                    <option value="phone">Phone</option>
                    <option value="date_of_birth">Date of Birth</option>
                    <option value="birth_month">Birth Month</option>
                    <option value="birth_day">Birth Day</option>
                    <option value="occupation">Occupation</option>
                    <option value="school_name">School Name</option>
                  </select>
                </th>
              </template>
            </tr>
          </thead>
          <tbody>
            <template x-for="(row, ri) in previewRows" :key="ri">
              <tr class="border-t border-outline-variant">
                <template x-for="(cell, ci) in row" :key="ci">
                  <td class="max-w-[220px] truncate p-3 text-[14px] leading-5 text-on-surface" x-text="cell === '' ? '—' : cell"></td>
                </template>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <p x-show="mappingError" x-cloak class="mt-3 text-[14px] leading-5 text-on-error-container" x-text="mappingError"></p>

      <div class="mt-6 flex items-center justify-between gap-3">
        <button type="button" @click="step = 2" class="<?= $secondaryButton ?>">Back</button>
        <button type="button" @click="nextToValidate()" class="<?= $primaryButton ?>">Validate rows</button>
      </div>
    </section>

    <!-- Step 4: validate -->
    <section x-show="step === 4" x-cloak class="rounded-lg bg-surface-lowest p-6 shadow-card md:p-8">
      <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface">Validation results</h2>
      <p x-show="validating" x-cloak class="mt-2 text-[14px] leading-5 text-on-surface-variant">Checking <span x-text="totalRows"></span> rows…</p>

      <template x-if="validated && !validating">
        <div>
          <div class="mt-5 grid grid-cols-2 gap-4">
            <div class="rounded-xl bg-primary-container p-5 shadow-card">
              <div class="mb-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-primary-container">Valid rows</div>
              <div class="font-display text-[32px] leading-9 font-bold tracking-[-0.02em] text-primary" x-text="validated.valid"></div>
            </div>
            <div class="rounded-xl bg-error-container p-5 shadow-card">
              <div class="mb-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-error-container">Rows with errors</div>
              <div class="font-display text-[32px] leading-9 font-bold tracking-[-0.02em] text-error" x-text="validated.invalid"></div>
            </div>
          </div>

          <template x-if="validated.row_errors.length > 0">
            <div class="mt-6">
              <h3 class="mb-3 font-display text-[12px] leading-4 font-medium uppercase tracking-[0.04em] text-on-surface-variant">Errors by row</h3>
              <div class="space-y-2">
                <template x-for="item in validated.row_errors" :key="item.row">
                  <div class="rounded-lg bg-surface-container-low px-4 py-3">
                    <p class="font-display text-[14px] leading-5 font-semibold text-on-surface">Row <span x-text="item.row"></span><span x-show="item.name"> — <span x-text="item.name"></span></span></p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-5 text-[13px] leading-5 text-on-error-container">
                      <template x-for="(err, ei) in item.errors" :key="ei">
                        <li x-text="err"></li>
                      </template>
                    </ul>
                  </div>
                </template>
              </div>
            </div>
          </template>

          <p class="mt-6 text-[13px] leading-4 text-on-surface-variant">Rows with errors are skipped — valid rows still import.</p>
        </div>
      </template>

      <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <button type="button" @click="step = 3" class="<?= $secondaryButton ?>">Back</button>
        <button type="button" @click="validate()" :disabled="validating" class="<?= $secondaryButton ?>">Re-run validation</button>
        <button type="button" @click="step = 5" :disabled="!validated || validated.valid < 1 || validating" class="<?= $primaryButton ?>">
          Import <span x-text="validated ? validated.valid : 0"></span> valid rows
        </button>
      </div>
    </section>

    <!-- Step 5: confirm + report -->
    <section x-show="step === 5" x-cloak class="rounded-lg bg-surface-lowest p-6 shadow-card md:p-8">
      <template x-if="!report">
        <div>
          <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface">Ready to import</h2>
          <p class="mt-2 text-[15px] leading-6 text-on-surface">
            Import <span x-text="validated.valid"></span> valid members into <span class="font-semibold" x-text="tentName()"></span>?
          </p>
          <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">
            Invalid rows (<span x-text="validated.invalid"></span>) are skipped. No attendance history is imported.
          </p>
          <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <button type="button" @click="step = 4" class="<?= $secondaryButton ?>">Back</button>
            <button type="button" @click="importNow()" :disabled="importing" class="<?= $primaryButton ?>">
              <span x-text="importing ? 'Importing…' : 'Confirm & import'"></span>
            </button>
          </div>
        </div>
      </template>

      <template x-if="report">
        <div>
          <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-primary-container">
              <i data-lucide="check-circle-2" class="h-6 w-6 text-primary"></i>
            </span>
            <div>
              <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface">Import complete</h2>
              <p class="text-[14px] leading-5 text-on-surface-variant">Members were added to <span class="font-semibold" x-text="tentName()"></span>.</p>
            </div>
          </div>

          <div class="mt-5 grid grid-cols-2 gap-4">
            <div class="rounded-xl bg-primary-container p-5 shadow-card">
              <div class="mb-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-primary-container">Imported</div>
              <div class="font-display text-[32px] leading-9 font-bold tracking-[-0.02em] text-primary" x-text="report.imported"></div>
            </div>
            <div class="rounded-xl bg-tertiary-container p-5 shadow-card">
              <div class="mb-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-tertiary-container">Skipped</div>
              <div class="font-display text-[32px] leading-9 font-bold tracking-[-0.02em] text-tertiary" x-text="report.skipped"></div>
            </div>
          </div>

          <template x-if="report.errors.length > 0">
            <div class="mt-6">
              <h3 class="mb-3 font-display text-[12px] leading-4 font-medium uppercase tracking-[0.04em] text-on-surface-variant">Skipped rows</h3>
              <div class="space-y-2">
                <template x-for="item in report.errors" :key="item.row">
                  <div class="rounded-lg bg-surface-container-low px-4 py-3">
                    <p class="font-display text-[14px] leading-5 font-semibold text-on-surface">Row <span x-text="item.row"></span><span x-show="item.name"> — <span x-text="item.name"></span></span></p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-5 text-[13px] leading-5 text-on-error-container">
                      <template x-for="(err, ei) in item.errors" :key="ei">
                        <li x-text="err"></li>
                      </template>
                    </ul>
                  </div>
                </template>
              </div>
            </div>
          </template>

          <div class="mt-6 flex justify-end">
            <button type="button" @click="resetAll()" class="<?= $primaryButton ?>">Import another file</button>
          </div>
        </div>
      </template>
    </section>
  </div>
</div>

<script>
  function importWizard(initial) {
    return {
      step: 1,
      csrf: initial.csrf,
      tents: initial.tents,
      tentId: '',
      file: null,
      fileError: '',
      parsing: false,
      columns: [],
      previewRows: [],
      totalRows: 0,
      colField: [],
      mappingError: '',
      validating: false,
      validated: null,
      importing: false,
      report: null,
      error: '',
      MAX_BYTES: 10 * 1024 * 1024,

      tentName() {
        const t = this.tents.find((x) => String(x.id) === String(this.tentId));
        return t ? t.name : '';
      },

      pickFile(e) {
        this.file = e.target.files[0] || null;
        this.fileError = '';
      },
      dropFile(e) {
        this.file = e.dataTransfer.files[0] || null;
        this.fileError = '';
      },
      nextToUpload() {
        this.error = '';
        this.step = 2;
      },

      guessField(header) {
        const h = String(header).toLowerCase();
        if (h.includes('school')) return 'school_name';
        if (h.includes('date of birth') || h.includes('birthday') || h.includes('dob') || h.includes('d.o.b')) return 'date_of_birth';
        if (h.includes('month')) return 'birth_month';
        if (h.includes('day')) return 'birth_day';
        if (h.includes('occupation') || h.includes('job')) return 'occupation';
        if (h.includes('phone') || h.includes('mobile') || h.includes('tel')) return 'phone';
        if (h.includes('name')) return 'full_name';
        return '';
      },
      autoMap() {
        const seen = {};
        this.colField = this.columns.map((header) => {
          const f = this.guessField(header);
          if (f === '') return f;
          if (seen[f]) return '';
          seen[f] = true;
          return f;
        });
      },

      async parseFile() {
        this.fileError = '';
        this.error = '';
        if (!this.file) {
          this.fileError = 'Choose a .csv or .xlsx file first.';
          return;
        }
        if (this.file.size > this.MAX_BYTES) {
          this.fileError = 'File must be 10MB or smaller.';
          return;
        }
        const name = this.file.name.toLowerCase();
        if (!name.endsWith('.csv') && !name.endsWith('.xlsx')) {
          this.fileError = 'Only .csv or .xlsx files are supported.';
          return;
        }

        const fd = new FormData();
        fd.append('file', this.file);
        fd.append('csrf', this.csrf);
        this.parsing = true;
        try {
          const res = await fetch('api/import-parse.php', { method: 'POST', body: fd });
          const json = await res.json().catch(() => null);
          if (json && json.success) {
            this.columns = json.data.columns;
            this.previewRows = json.data.first_five;
            this.totalRows = json.data.total;
            this.autoMap();
            this.mappingError = '';
            this.validated = null;
            this.report = null;
            this.step = 3;
            return;
          }
          this.fileError = (json && json.error) || 'Could not parse the file.';
        } catch (networkError) {
          this.fileError = 'Network error — try again.';
        } finally {
          this.parsing = false;
        }
      },

      buildMapping() {
        const m = {};
        this.colField.forEach((f, i) => {
          if (f !== '') m[f] = i;
        });
        return m;
      },
      nextToValidate() {
        this.mappingError = '';
        if (!this.colField.includes('full_name')) {
          this.mappingError = 'Map one column to Full Name before continuing.';
          return;
        }
        this.step = 4;
        this.validated = null;
        this.validate();
      },

      async validate() {
        this.error = '';
        this.validating = true;
        try {
          const res = await fetch('api/import-validate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tent_id: this.tentId, mapping: this.buildMapping(), csrf: this.csrf }),
          });
          const json = await res.json().catch(() => null);
          if (json && json.success) {
            this.validated = json.data;
            return;
          }
          this.error = (json && json.error) || 'Validation failed.';
        } catch (networkError) {
          this.error = 'Network error — try again.';
        } finally {
          this.validating = false;
        }
      },

      async importNow() {
        this.error = '';
        this.importing = true;
        try {
          const res = await fetch('api/import-run.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tent_id: this.tentId, mapping: this.buildMapping(), csrf: this.csrf }),
          });
          const json = await res.json().catch(() => null);
          if (json && json.success) {
            this.report = json.data;
            return;
          }
          this.error = (json && json.error) || 'Import failed.';
        } catch (networkError) {
          this.error = 'Network error — try again.';
        } finally {
          this.importing = false;
        }
      },

      resetAll() {
        Object.assign(this, {
          step: 1,
          tentId: '',
          file: null,
          fileError: '',
          columns: [],
          previewRows: [],
          totalRows: 0,
          colField: [],
          mappingError: '',
          validated: null,
          report: null,
          error: '',
        });
      },
    };
  }
</script>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
