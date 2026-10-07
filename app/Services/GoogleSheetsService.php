<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class GoogleSheetsService
{
    private Sheets $sheets;

    private string $spreadsheetId;

    public function __construct()
    {
        $client = new Client();

        $client->setAuthConfig(
            config('services.google_sheets.credentials')
        );

        $client->setScopes([
            Sheets::SPREADSHEETS,
        ]);

        $this->sheets = new Sheets($client);

        $this->spreadsheetId = config(
            'services.google_sheets.spreadsheet_id'
        );
    }

    public function append(string $range, array $values): void
    {
        $body = new ValueRange([
            'values' => [$values],
        ]);

        $this->sheets->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            [
                'valueInputOption' => 'USER_ENTERED',
            ]
        );
    }

    public function values(string $range): array
    {
        $response = $this->sheets
            ->spreadsheets_values
            ->get(
                $this->spreadsheetId,
                $range
            );

        return $response->getValues() ?? [];
    }
}
