<?php

$rows = isset($argv[1]) ? filter_var($argv[1], FILTER_VALIDATE_INT) : 100_000;
$output = $argv[2] ?? 'storage/app/private/fake-users.csv';

if ($rows === false || $rows < 1) {
    fwrite(STDERR, "Usage: php scripts/generate_fake_users_csv.php [rows] [output.csv]\n");
    exit(1);
}

$directory = dirname($output);

if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
    throw new RuntimeException("Could not create output directory: {$directory}");
}

if (strtolower(pathinfo($output, PATHINFO_EXTENSION)) === 'xlsx') {
    writeXlsx($output, $rows);

    fwrite(STDOUT, sprintf("Wrote %s synthetic rows to %s\n", number_format($rows), realpath($output)));
    exit(0);
}

$handle = fopen($output, 'wb');

if ($handle === false) {
    throw new RuntimeException("Could not create output file: {$output}");
}

try {
    fputcsv($handle, ['Name', 'Email', 'Phone']);

    for ($number = 1; $number <= $rows; $number++) {
        fputcsv($handle, [
            sprintf('Fake User %06d', $number),
            sprintf('fake.user.%06d@example.test', $number),
            sprintf('+1555%07d', $number),
        ]);
    }
} finally {
    fclose($handle);
}

fwrite(STDOUT, sprintf("Wrote %s synthetic rows to %s\n", number_format($rows), realpath($output)));

function writeXlsx(string $output, int $rows): void
{
    if (! class_exists(ZipArchive::class)) {
        throw new RuntimeException('The PHP zip extension is required to create an XLSX file.');
    }

    $temporaryDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'fake-users-xlsx-'.bin2hex(random_bytes(8));

    if (! mkdir($temporaryDirectory, 0700)) {
        throw new RuntimeException('Could not create temporary workbook directory.');
    }

    $sheetPath = $temporaryDirectory.DIRECTORY_SEPARATOR.'sheet1.xml';
    $sheet = fopen($sheetPath, 'wb');

    if ($sheet === false) {
        rmdir($temporaryDirectory);
        throw new RuntimeException('Could not create temporary worksheet.');
    }

    $archive = new ZipArchive();
    $archiveOpened = false;

    try {
        fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($sheet, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>');
        writeWorksheetRow($sheet, 1, ['Name', 'Email', 'Phone']);

        for ($number = 1; $number <= $rows; $number++) {
            writeWorksheetRow($sheet, $number + 1, [
                sprintf('Fake User %06d', $number),
                sprintf('fake.user.%06d@example.test', $number),
                sprintf('+1555%07d', $number),
            ]);
        }

        fwrite($sheet, '</sheetData></worksheet>');
        fclose($sheet);

        if ($archive->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not open output workbook.');
        }
        $archiveOpened = true;

        $archive->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>');
        $archive->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');
        $archive->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Fake Users" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $archive->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>');
        $archive->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
        $archive->close();
        $archiveOpened = false;
    } finally {
        if (is_resource($sheet)) {
            fclose($sheet);
        }

        if ($archiveOpened) {
            $archive->close();
        }

        if (is_file($sheetPath)) {
            unlink($sheetPath);
        }

        rmdir($temporaryDirectory);
    }
}

/**
 * @param  resource  $stream
 * @param  array<int, string>  $values
 */
function writeWorksheetRow($stream, int $rowNumber, array $values): void
{
    fwrite($stream, '<row r="'.$rowNumber.'">');

    foreach ($values as $column => $value) {
        $cell = chr(ord('A') + $column).$rowNumber;
        $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        fwrite($stream, '<c r="'.$cell.'" t="inlineStr"><is><t>'.$escaped.'</t></is></c>');
    }

    fwrite($stream, '</row>');
}
