<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use XMLReader;
use ZipArchive;

class XlsxToCsvConverter
{
    private const MAX_UNCOMPRESSED_BYTES = 104_857_600;

    /**
     * Convert the first worksheet of an XLSX workbook to a streamed CSV file.
     */
    public function convert(string $xlsxPath, string $csvPath): void
    {
        if (! class_exists(ZipArchive::class) || ! class_exists(XMLReader::class)) {
            throw new RuntimeException('The PHP zip and xmlreader extensions are required to read Excel files.');
        }

        $archive = new ZipArchive();

        if ($archive->open($xlsxPath) !== true) {
            throw new RuntimeException('The Excel workbook could not be opened.');
        }

        $temporaryFiles = [];
        $output = null;
        $reader = null;

        try {
            $this->validateArchive($archive);
            $sharedStrings = $this->readSharedStrings($archive, $temporaryFiles);
            $worksheetPath = $this->readFirstWorksheetPath($archive, $temporaryFiles);
            $reader = new XMLReader();

            if (! $reader->open($worksheetPath, null, LIBXML_NONET | LIBXML_COMPACT)) {
                throw new RuntimeException('The first worksheet XML could not be read.');
            }

            $output = fopen($csvPath, 'wb');

            if ($output === false) {
                throw new RuntimeException('The temporary CSV file could not be created.');
            }

            $rowCount = 0;

            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }

                if (++$rowCount > 1_048_576) {
                    throw new RuntimeException('The Excel worksheet exceeds the supported row limit.');
                }

                $rowXml = $reader->readOuterXML();
                $row = simplexml_load_string(
                    $rowXml,
                    SimpleXMLElement::class,
                    LIBXML_NONET | LIBXML_NOBLANKS
                );

                if ($row === false) {
                    throw new RuntimeException('A worksheet row contains invalid XML.');
                }

                $cells = [];

                foreach ($row->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->c as $cell) {
                    $attributes = $cell->attributes();
                    $reference = (string) ($attributes['r'] ?? '');
                    $columnIndex = $this->columnIndex($reference);
                    $type = (string) ($attributes['t'] ?? '');
                    $value = match ($type) {
                        's' => $sharedStrings[(int) $cell->v] ?? '',
                        'inlineStr' => $this->inlineString($cell),
                        'b' => (string) $cell->v === '1' ? 'TRUE' : 'FALSE',
                        default => (string) $cell->v,
                    };
                    $cells[$columnIndex] = $value;
                }

                if ($cells !== []) {
                    ksort($cells);
                    $lastColumn = max(array_keys($cells));
                    $values = array_fill(0, $lastColumn + 1, '');

                    foreach ($cells as $column => $value) {
                        $values[$column] = $value;
                    }

                    fputcsv($output, $values);
                } else {
                    fputcsv($output, []);
                }
            }

            $reader->close();
            $reader = null;
            fclose($output);
            $output = null;
        } catch (\Throwable $exception) {
            if ($reader instanceof XMLReader) {
                $reader->close();
            }

            if (is_resource($output)) {
                fclose($output);
            }

            if (is_file($csvPath)) {
                unlink($csvPath);
            }

            throw $exception;
        } finally {
            $archive->close();

            foreach ($temporaryFiles as $temporaryFile) {
                if (is_file($temporaryFile)) {
                    unlink($temporaryFile);
                }
            }
        }
    }

    private function validateArchive(ZipArchive $archive): void
    {
        $requiredEntries = [
            '[Content_Types].xml',
            'xl/workbook.xml',
            'xl/_rels/workbook.xml.rels',
        ];
        $totalUncompressedBytes = 0;

        for ($index = 0; $index < $archive->numFiles; $index++) {
            $stat = $archive->statIndex($index);

            if ($stat === false) {
                throw new RuntimeException('The Excel workbook contains an unreadable entry.');
            }

            $totalUncompressedBytes += $stat['size'];

            if ($totalUncompressedBytes > self::MAX_UNCOMPRESSED_BYTES) {
                throw new RuntimeException('The uncompressed Excel workbook exceeds the 100 MB safety limit.');
            }
        }

        foreach ($requiredEntries as $entry) {
            if ($archive->locateName($entry) === false) {
                throw new RuntimeException('The uploaded file is not a supported Excel workbook.');
            }
        }
    }

    /**
     * @param  array<int, string>  $temporaryFiles
     */
    private function readFirstWorksheetPath(ZipArchive $archive, array &$temporaryFiles): string
    {
        $workbookPath = $this->extractEntry($archive, 'xl/workbook.xml', $temporaryFiles);
        $relationshipsPath = $this->extractEntry(
            $archive,
            'xl/_rels/workbook.xml.rels',
            $temporaryFiles
        );
        $workbook = simplexml_load_file(
            $workbookPath,
            SimpleXMLElement::class,
            LIBXML_NONET | LIBXML_NOBLANKS
        );
        $relationships = simplexml_load_file(
            $relationshipsPath,
            SimpleXMLElement::class,
            LIBXML_NONET | LIBXML_NOBLANKS
        );

        if ($workbook === false || $relationships === false) {
            throw new RuntimeException('The Excel workbook metadata contains invalid XML.');
        }

        $sheets = $workbook->children(
            'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
        )->sheets->sheet;
        $firstSheet = $sheets[0] ?? null;
        $relationshipAttributes = $firstSheet?->attributes(
            'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
        );
        $relationshipId = (string) ($relationshipAttributes['id'] ?? '');

        if ($relationshipId === '') {
            throw new RuntimeException('The Excel workbook does not contain a readable worksheet.');
        }

        foreach ($relationships->children(
            'http://schemas.openxmlformats.org/package/2006/relationships'
        )->Relationship as $relationship) {
            $attributes = $relationship->attributes();

            if ((string) ($attributes['Id'] ?? '') !== $relationshipId) {
                continue;
            }

            $target = str_replace('\\', '/', (string) ($attributes['Target'] ?? ''));

            if (($attributes['TargetMode'] ?? '') === 'External'
                || ! preg_match('#^(?:/?xl/)?worksheets/[A-Za-z0-9_.-]+\.xml$#', $target)) {
                throw new RuntimeException('The first worksheet path is not supported.');
            }

            $entryName = ltrim($target, '/');

            if (! str_starts_with($entryName, 'xl/')) {
                $entryName = 'xl/'.$entryName;
            }

            if ($archive->locateName($entryName) === false) {
                throw new RuntimeException('The first worksheet is missing from the Excel workbook.');
            }

            return $this->extractEntry($archive, $entryName, $temporaryFiles);
        }

        throw new RuntimeException('The Excel workbook worksheet relationship is missing.');
    }

    /**
     * @param  array<int, string>  $temporaryFiles
     * @return array<int, string>
     */
    private function readSharedStrings(ZipArchive $archive, array &$temporaryFiles): array
    {
        $index = $archive->locateName('xl/sharedStrings.xml');

        if ($index === false) {
            return [];
        }

        $path = $this->extractEntry($archive, 'xl/sharedStrings.xml', $temporaryFiles);
        $reader = new XMLReader();

        if (! $reader->open($path, null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new RuntimeException('Excel shared strings could not be read.');
        }

        $strings = [];

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'si') {
                continue;
            }

            $item = simplexml_load_string(
                $reader->readOuterXML(),
                SimpleXMLElement::class,
                LIBXML_NONET | LIBXML_NOBLANKS
            );

            if ($item === false) {
                $reader->close();
                throw new RuntimeException('Excel shared strings contain invalid XML.');
            }

            $text = '';

            foreach ($item->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
                $text .= (string) $textNode;
            }

            $strings[] = $text;

            if (count($strings) > 500_000) {
                $reader->close();
                throw new RuntimeException('The Excel workbook contains too many shared strings.');
            }
        }

        $reader->close();

        return $strings;
    }

    /**
     * @param  array<int, string>  $temporaryFiles
     */
    private function extractEntry(ZipArchive $archive, string $entryName, array &$temporaryFiles): string
    {
        $input = $archive->getStream($entryName);

        if ($input === false) {
            throw new RuntimeException("The Excel workbook entry {$entryName} could not be opened.");
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx-import-');
        $output = $path === false ? false : fopen($path, 'wb');

        if ($path === false || $output === false) {
            fclose($input);

            if ($path !== false && is_file($path)) {
                unlink($path);
            }

            throw new RuntimeException('A temporary Excel parsing file could not be created.');
        }

        $temporaryFiles[] = $path;

        try {
            stream_copy_to_stream($input, $output);
        } finally {
            fclose($input);
            fclose($output);
        }

        return $path;
    }

    private function columnIndex(string $reference): int
    {
        if (! preg_match('/^([A-Z]+)/i', $reference, $matches)) {
            throw new RuntimeException('The Excel worksheet contains an invalid cell reference.');
        }

        $index = 0;

        foreach (str_split(strtoupper($matches[1])) as $letter) {
            $index = $index * 26 + ord($letter) - ord('A') + 1;
        }

        $index--;

        if ($index > 16_383) {
            throw new RuntimeException('The Excel worksheet contains a cell beyond the supported column limit.');
        }

        return $index;
    }

    private function inlineString(SimpleXMLElement $cell): string
    {
        $text = '';

        foreach ($cell->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
            $text .= (string) $textNode;
        }

        return $text;
    }
}
