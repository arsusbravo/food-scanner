<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Minimal single-sheet .xlsx writer built on ZipArchive (no extra package).
 * Supports text, date, money and integer columns, a bold frozen header row and
 * automatic column widths.
 */
class XlsxWriter
{
    public const TEXT = 'text';

    public const DATE = 'date';

    public const MONEY = 'money';

    public const INTEGER = 'integer';

    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const STYLE_HEADER = 1;

    private const STYLE_DATE = 2;

    private const STYLE_MONEY = 3;

    public static function isAvailable(): bool
    {
        return class_exists(ZipArchive::class);
    }

    /**
     * @param  list<string>  $header  column titles
     * @param  list<string>  $types  one of the type constants per column
     * @param  list<list<mixed>>  $rows  data rows; dates as Y-m-d strings, numbers as int/float, null for empty
     * @return string binary contents of the .xlsx file
     */
    public function build(array $header, array $types, array $rows, string $sheetName = 'Sheet1'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;

        if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the spreadsheet file.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet($header, $types, $rows));
        $zip->close();

        $contents = (string) file_get_contents($path);
        @unlink($path);

        return $contents;
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $types
     * @param  list<list<mixed>>  $rows
     */
    private function sheet(array $header, array $types, array $rows): string
    {
        $widths = array_map(fn (string $title) => mb_strlen($title), $header);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="'.self::NS.'">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';

        $body = '<row r="1">';

        foreach ($header as $column => $title) {
            $body .= $this->textCell($this->reference($column, 1), $title, self::STYLE_HEADER);
        }

        $body .= '</row>';

        foreach ($rows as $index => $row) {
            $r = $index + 2;
            $body .= '<row r="'.$r.'">';

            foreach ($types as $column => $type) {
                $value = $row[$column] ?? null;

                if ($value === null || $value === '') {
                    continue;
                }

                $widths[$column] = max($widths[$column] ?? 0, $type === self::TEXT ? mb_strlen((string) $value) : 12);
                $body .= $this->cell($this->reference($column, $r), $type, $value);
            }

            $body .= '</row>';
        }

        $xml .= '<cols>';

        foreach ($types as $column => $type) {
            $width = min(60, max(10, ($widths[$column] ?? 10) + 2));
            $xml .= '<col min="'.($column + 1).'" max="'.($column + 1).'" width="'.$width.'" customWidth="1"/>';
        }

        return $xml.'</cols><sheetData>'.$body.'</sheetData></worksheet>';
    }

    private function cell(string $reference, string $type, mixed $value): string
    {
        return match ($type) {
            self::DATE => '<c r="'.$reference.'" s="'.self::STYLE_DATE.'"><v>'.$this->dateSerial((string) $value).'</v></c>',
            self::MONEY => '<c r="'.$reference.'" s="'.self::STYLE_MONEY.'"><v>'.$this->number($value).'</v></c>',
            self::INTEGER => '<c r="'.$reference.'"><v>'.(int) $value.'</v></c>',
            default => $this->textCell($reference, (string) $value, 0),
        };
    }

    private function textCell(string $reference, string $text, int $style): string
    {
        return '<c r="'.$reference.'" t="inlineStr"'.($style ? ' s="'.$style.'"' : '').'><is><t xml:space="preserve">'.$this->escape($text).'</t></is></c>';
    }

    private function number(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.') ?: '0';
    }

    /**
     * Excel stores dates as days since 1899-12-30.
     */
    private function dateSerial(string $date): int
    {
        $timestamp = strtotime($date.' 00:00:00 UTC');

        return $timestamp === false ? 0 : intdiv($timestamp, 86400) + 25569;
    }

    private function escape(string $text): string
    {
        // Control characters other than tab/newline are not valid in XML 1.0.
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? '';

        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function reference(int $column, int $row): string
    {
        $letters = '';

        for ($n = $column + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letters = chr(65 + ($n - 1) % 26).$letters;
        }

        return $letters.$row;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook(string $sheetName): string
    {
        $name = htmlspecialchars(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $sheetName) ?? 'Sheet1', 0, 31), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="'.self::NS.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$name.'" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="'.self::NS.'">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="4">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="14" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }
}
