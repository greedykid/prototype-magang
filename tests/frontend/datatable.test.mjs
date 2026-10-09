import test from 'node:test';
import assert from 'node:assert/strict';
import { parseCellValue } from '../../resources/js/modules/datatable.js';

test('parseCellValue parses currency strings correctly', () => {
    assert.equal(parseCellValue('Rp. 5.000.000'), 5000000);
    assert.equal(parseCellValue('Rp 14.500.000,50'), 14500000.5);
    assert.equal(parseCellValue('12345'), 12345);
});

test('parseCellValue handles blank or placeholder values', () => {
    assert.equal(parseCellValue(''), '');
    assert.equal(parseCellValue('-'), '');
    assert.equal(parseCellValue('Tanpa Target'), '');
    assert.equal(parseCellValue('belum diisi'), '');
});

test('parseCellValue parses dates correctly', () => {
    const isoDate = parseCellValue('2026-05-15');
    assert.equal(typeof isoDate, 'number');
    assert.equal(new Date(isoDate).toISOString().slice(0, 10), '2026-05-15');

    const dmyDate = parseCellValue('15/05/2026');
    assert.equal(typeof dmyDate, 'number');
    assert.equal(new Date(dmyDate).getFullYear(), 2026);
    assert.equal(new Date(dmyDate).getMonth(), 4); // May is index 4
    assert.equal(new Date(dmyDate).getDate(), 15);
});

test('parseCellValue falls back to lowercased text', () => {
    assert.equal(parseCellValue('Laboratorium Kalibrasi LEMIGAS'), 'laboratorium kalibrasi lemigas');
});
