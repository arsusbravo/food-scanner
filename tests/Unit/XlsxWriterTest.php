<?php

use App\Support\XlsxWriter;

test('it builds a valid workbook with typed columns', function () {
    $binary = (new XlsxWriter)->build(
        ['Date', 'Name', 'Amount', 'Count'],
        [XlsxWriter::DATE, XlsxWriter::TEXT, XlsxWriter::MONEY, XlsxWriter::INTEGER],
        [['2026-01-01', "Caf\u{e9} \"A\" & B", 12.5, 3], [null, 'Empty', null, null]],
        'Test/Sheet',
    );

    $path = tempnam(sys_get_temp_dir(), 'xlsx-unit');
    file_put_contents($path, $binary);
    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();

    foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/styles.xml', 'xl/worksheets/sheet1.xml'] as $part) {
        expect(simplexml_load_string((string) $zip->getFromName($part)))->not->toBeFalse("{$part} must be well-formed XML");
    }

    $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
    $workbook = (string) $zip->getFromName('xl/workbook.xml');
    $zip->close();
    unlink($path);

    expect($sheet)->toContain('<v>46023</v>')
        ->and($sheet)->toContain('<v>12.5</v>')
        ->and($sheet)->toContain('<v>3</v>')
        ->and($sheet)->toContain('Caf')
        ->and($sheet)->toContain('&amp; B')
        ->and($sheet)->not->toContain('r="C3"')
        ->and($workbook)->toContain('name="Test Sheet"');
});
