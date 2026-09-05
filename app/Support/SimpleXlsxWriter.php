<?php

namespace App\Support;

use ZipArchive;

/**
 * Générateur XLSX minimal, sans dépendance externe (utilise ZipArchive).
 *
 * Produit un vrai fichier .xlsx (une feuille) qu'Excel/LibreOffice ouvrent
 * sans avertissement. Suffisant pour exporter des tableaux de données :
 * en-tête en gras, cellules texte ou numériques, largeurs de colonnes.
 *
 * Usage :
 *   $xlsx = new SimpleXlsxWriter('Ma feuille');
 *   $xlsx->setColumnWidths([6, 14, 30]);
 *   $xlsx->addRow(['N°', 'Code', 'Nom'], true); // en-tête (gras)
 *   $xlsx->addRow([1, 'E001', 'Dupont']);
 *   return $xlsx->download('export.xlsx');
 */
class SimpleXlsxWriter
{
    /** @var array<int, array{values: array, bold: bool}> */
    private array $rows = [];
    private array $columnWidths = [];
    private string $sheetName;
    private bool $landscape = false;

    public function __construct(string $sheetName = 'Feuille1')
    {
        // Excel limite le nom d'onglet à 31 caractères et interdit : \ / ? * [ ]
        $clean = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $sheetName);
        $this->sheetName = mb_substr(trim($clean), 0, 31) ?: 'Feuille1';
    }

    public function setColumnWidths(array $widths): self
    {
        $this->columnWidths = $widths;
        return $this;
    }

    /**
     * Impression en paysage (utile pour les tableaux à nombreuses colonnes).
     */
    public function setLandscape(bool $landscape = true): self
    {
        $this->landscape = $landscape;
        return $this;
    }

    public function addRow(array $values, bool $bold = false): self
    {
        $this->rows[] = ['values' => array_values($values), 'bold' => $bold];
        return $this;
    }

    /**
     * Construit le fichier .xlsx et le renvoie en téléchargement.
     */
    public function download(string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $binary = $this->build();

        return response()->streamDownload(function () use ($binary) {
            echo $binary;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Génère le contenu binaire du .xlsx.
     */
    public function build(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');

        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->relsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml());

        $zip->close();

        $binary = file_get_contents($tmp);
        @unlink($tmp);

        return $binary;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->escape($this->sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        // cellStyleXfs[0] = base ; cellXfs[0] = normal, cellXfs[1] = gras
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    private function sheetXml(): string
    {
        $cols = '';
        if (!empty($this->columnWidths)) {
            $cols .= '<cols>';
            foreach ($this->columnWidths as $i => $width) {
                $n = $i + 1;
                $cols .= '<col min="' . $n . '" max="' . $n . '" width="' . (float) $width . '" customWidth="1"/>';
            }
            $cols .= '</cols>';
        }

        $sheetData = '<sheetData>';
        foreach ($this->rows as $rowIndex => $row) {
            $r = $rowIndex + 1;
            $sheetData .= '<row r="' . $r . '">';
            foreach ($row['values'] as $colIndex => $value) {
                $ref = $this->columnLetter($colIndex) . $r;
                $style = $row['bold'] ? ' s="1"' : '';

                if ($this->isNumeric($value)) {
                    $sheetData .= '<c r="' . $ref . '"' . $style . '><v>' . $value . '</v></c>';
                } else {
                    $sheetData .= '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">'
                        . $this->escape((string) $value) . '</t></is></c>';
                }
            }
            $sheetData .= '</row>';
        }
        $sheetData .= '</sheetData>';

        // pageMargins / pageSetup doivent suivre sheetData (ordre imposé par le schéma).
        $pageSetup = $this->landscape
            ? '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
                . '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0" paperSize="9"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . ($this->landscape ? '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>' : '')
            . $cols
            . $sheetData
            . $pageSetup
            . '</worksheet>';
    }

    private function isNumeric($value): bool
    {
        // On traite comme numérique uniquement les vrais int/float, pas les
        // chaînes "numériques" (codes employé à zéros initiaux, etc.).
        return is_int($value) || is_float($value);
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        $index += 1;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - 1, 26);
        }
        return $letter;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
