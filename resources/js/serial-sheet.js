/*
 | Reads an Excel (.xlsx) or CSV file of imported scales in the browser. The file itself never
 | leaves the computer: only its rows are sent to the server, which checks every one of them.
 | Columns, in this order: serial number, model, import date.
 */

import { readSheet } from 'read-excel-file/web-worker';

/** An error whose message is ready to show to the admin (Russian). */
export class SheetError extends Error {}

function extension(name) {
    return (String(name).match(/\.([^.]+)$/)?.[1] || '').toLowerCase();
}

function decode(buffer) {
    try {
        return new TextDecoder('utf-8', { fatal: true }).decode(buffer);
    } catch (e) {
        return new TextDecoder('windows-1251').decode(buffer);   // Excel's "CSV" on a Russian Windows
    }
}

/** CSV text as rows of cells. The delimiter is the one used most in the first line: ; , or tab. */
export function parseCsv(text) {
    text = text.replace(/^﻿/, '');
    const firstLine = text.split(/\r\n|\r|\n/, 1)[0];
    const delimiter = [';', ',', '\t']
        .map((d) => [d, firstLine.split(d).length - 1])
        .sort((a, b) => b[1] - a[1])[0][0];

    const rows = [];
    let row = [];
    let cell = '';
    let quoted = false;

    for (let i = 0; i < text.length; i++) {
        const ch = text[i];
        if (quoted) {
            if (ch !== '"') cell += ch;
            else if (text[i + 1] === '"') { cell += '"'; i++; }
            else quoted = false;
        } else if (ch === '"' && cell === '') {
            quoted = true;
        } else if (ch === delimiter) {
            row.push(cell);
            cell = '';
        } else if (ch === '\n' || ch === '\r') {
            if (ch === '\r' && text[i + 1] === '\n') i++;
            row.push(cell);
            rows.push(row);
            row = [];
            cell = '';
        } else {
            cell += ch;
        }
    }
    if (cell !== '' || row.length) {
        row.push(cell);
        rows.push(row);
    }

    return rows;
}

/**
 * Every row of the file's first sheet, as the file has them.
 *
 * @param {File} file
 * @param {{maxBytes: number, maxRows: number}} limits
 * @returns {Promise<Array<Array<*>>>}
 */
export async function readRaw(file, limits) {
    const type = extension(file.name);
    if (type !== 'xlsx' && type !== 'csv') {
        throw new SheetError('Нужен файл Excel (.xlsx) или CSV (.csv).');
    }
    if (file.size > limits.maxBytes) {
        throw new SheetError(`Файл больше ${Math.round(limits.maxBytes / 1048576)} МБ. Разделите его на несколько файлов.`);
    }

    let raw;
    try {
        raw = type === 'xlsx' ? await readSheet(file) : parseCsv(decode(await file.arrayBuffer()));
    } catch (e) {
        throw new SheetError('Файл не читается. Сохраните его как Excel (.xlsx) или CSV и выберите снова.');
    }

    if (raw.length > limits.maxRows + 1) {
        throw new SheetError(`В файле больше ${limits.maxRows.toLocaleString('ru-RU')} строк. Разделите его на несколько файлов.`);
    }

    return raw;
}

function isoDate(date) {
    return Number.isNaN(date.getTime()) ? '' : date.toISOString().slice(0, 10);
}

function text(cell) {
    if (cell instanceof Date) return isoDate(cell);
    if (typeof cell === 'number') return String(cell);

    return typeof cell === 'string' ? cell.trim() : '';
}

/** A date cell stays a day (Y-m-d); a bare number stays a number: Excel's day count, decided by the server. */
function dateCell(cell) {
    return typeof cell === 'number' ? cell : text(cell);
}

/**
 * The non-empty rows with their line numbers in the file, three cells each.
 *
 * @returns {Array<{line: number, cells: Array<string|number>}>}
 */
export function toRows(raw, hasHeader) {
    const rows = [];
    raw.forEach((cells, index) => {
        if (hasHeader && index === 0) return;
        cells = Array.isArray(cells) ? cells : [];
        const row = [text(cells[0]), text(cells[1]), dateCell(cells[2])];
        if (row.join('') === '') return;
        rows.push({ line: index + 1, cells: row });
    });

    return rows;
}
