/*
 | Admin screen /admin/serials/import. The chosen file is read here, in the browser; its rows go
 | to the server in portions: first to be checked (the preview), then, on "load", to be saved.
 | Server side: App\Livewire\Admin\Serials\Import.
 */

import { readRaw, toRows, SheetError } from './serial-sheet.js';

const PREVIEW_ROWS = 20;
const PREVIEW_PROBLEMS = 100;
const BROKEN = 'Сервер не ответил. Обновите страницу и попробуйте снова.';

function portions(rows, size) {
    const parts = [];
    for (let i = 0; i < rows.length; i += size) parts.push(rows.slice(i, i + size));

    return parts;
}

function serialImport(config) {
    return {
        hasHeader: true,
        fileName: '',
        raw: null,
        rows: [],
        preview: null,
        busy: null,
        failure: null,
        result: null,

        async pick(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.clear();
            this.fileName = file.name;
            this.busy = { title: 'Читаем файл…', done: 0, total: 0 };
            try {
                this.raw = await readRaw(file, config);
            } catch (e) {
                this.fail(e instanceof SheetError ? e.message : BROKEN);

                return;
            }
            await this.check();
        },

        /** Sends the rows to the server to be checked, portion by portion. Nothing is saved. */
        async check() {
            if (!this.raw) return;

            this.preview = null;
            this.rows = toRows(this.raw, this.hasHeader);
            if (!this.rows.length) {
                this.fail('В файле нет строк с данными.');

                return;
            }

            this.busy = { title: 'Проверяем строки…', done: 0, total: this.rows.length };
            const valid = [];
            const problems = [];
            const seen = {};
            try {
                for (const part of portions(this.rows, config.chunk)) {
                    const checked = await this.$wire.check(part);
                    if (!checked) throw new Error('no answer');
                    problems.push(...checked.problems);
                    for (const row of checked.valid) {
                        // The server sees one portion at a time; repeats across portions are caught here.
                        if (seen[row.serial]) problems.push({ line: row.line, serial: row.serial, reason: `Повтор в файле: такой же номер в строке ${seen[row.serial]}.` });
                        else { seen[row.serial] = row.line; valid.push(row); }
                    }
                    this.busy.done += part.length;
                }
            } catch (e) {
                this.fail(BROKEN);

                return;
            }

            problems.sort((a, b) => a.line - b.line);
            const models = {};
            valid.forEach((row) => { models[row.model] = (models[row.model] || 0) + 1; });

            this.preview = {
                total: this.rows.length,
                valid: valid.length,
                skipped: problems.length,
                models: Object.entries(models).sort((a, b) => a[0].localeCompare(b[0])),
                sample: valid.slice(0, PREVIEW_ROWS),
                problems: problems.slice(0, PREVIEW_PROBLEMS),
            };
            this.busy = null;
        },

        /** Saves the rows as one batch, portion by portion. The server checks every row again. */
        async load() {
            if (!this.preview || !this.preview.valid) return;

            this.busy = { title: 'Загружаем серийные номера в базу…', done: 0, total: this.rows.length };
            let started = false;
            try {
                started = await this.$wire.start(this.fileName);
                if (!started) throw new Error('no answer');
                for (const part of portions(this.rows, config.chunk)) {
                    if (!(await this.$wire.add(part))) throw new Error('no answer');
                    this.busy.done += part.length;
                }
                const done = await this.$wire.finish();
                if (!done) throw new Error('no answer');
                if (!done.batch) {
                    this.fail('В файле нет строк, которые можно загрузить.');

                    return;
                }
                this.clear();
                this.result = done;
            } catch (e) {
                this.fail(started ? 'Загрузка прервалась. Часть номеров могла сохраниться: проверьте последнюю партию в списке, удалите её и загрузите файл снова.' : BROKEN);
            }
        },

        fail(message) {
            this.busy = null;
            this.failure = message;
        },

        clear() {
            this.raw = null;
            this.rows = [];
            this.preview = null;
            this.busy = null;
            this.failure = null;
            this.result = null;
        },

        /** Closes the status window and clears the form for the next file. */
        again() {
            this.clear();
            this.fileName = '';
            this.$refs.file.value = '';
            this.$refs.file.focus();
        },

        day(iso) {
            return iso.split('-').reverse().join('.');
        },

        get percent() {
            return this.busy && this.busy.total ? Math.round((this.busy.done / this.busy.total) * 100) : 0;
        },
    };
}

const register = () => window.Alpine.data('serialImport', serialImport);
if (window.Alpine) register();
else document.addEventListener('alpine:init', register);
