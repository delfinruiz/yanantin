<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FormSubmissionsExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected array $rows;
    protected array $headers;

    public function __construct(array $rows, array $headers)
    {
        $this->rows = $rows;
        $this->headers = $headers;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headers;
    }
}
