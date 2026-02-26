<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class ImportProductsFromExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import-excel
        {--file= : Path to the XLSX file relative to the project root}
        {--brand= : Brand name to apply to all imported products}
        {--category= : Category name to apply to all imported products}
        {--status=active : Product status (draft, active, discontinued)}
        {--variant-status=active : Variant status (active, inactive)}
        {--limit= : Limit the number of items imported}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from a single-sheet Excel file into products and variants.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->resolveFilePath((string) $this->option('file'));

        if ($filePath === null) {
            $this->error('Please provide --file with a valid XLSX path.');

            return self::FAILURE;
        }

        $brandName = trim((string) $this->option('brand'));
        $categoryName = trim((string) $this->option('category'));
        $status = trim((string) $this->option('status')) ?: 'active';
        $variantStatus = trim((string) $this->option('variant-status')) ?: 'active';

        if ($brandName === '' || $categoryName === '') {
            $this->error('Options --brand and --category are required.');

            return self::FAILURE;
        }

        if (! in_array($status, ['draft', 'active', 'discontinued'], true)) {
            $this->error('Invalid --status. Allowed: draft, active, discontinued.');

            return self::FAILURE;
        }

        if (! in_array($variantStatus, ['active', 'inactive'], true)) {
            $this->error('Invalid --variant-status. Allowed: active, inactive.');

            return self::FAILURE;
        }

        $limit = $this->option('limit');
        $limit = $limit !== null ? max(0, (int) $limit) : null;

        try {
            $items = $this->parseItems($filePath);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($limit !== null) {
            $items = array_slice($items, 0, $limit);
        }

        if ($items === []) {
            $this->warn('No product rows found in the spreadsheet.');

            return self::SUCCESS;
        }

        $brand = Brand::query()->firstOrCreate(
            ['name' => $brandName],
            ['slug' => Str::slug($brandName)]
        );

        $category = Category::query()->firstOrCreate(
            ['name' => $categoryName],
            ['slug' => Str::slug($categoryName), 'parent_id' => null]
        );

        $created = 0;
        $skipped = 0;

        $this->info(sprintf('Importing %d items from %s', count($items), $filePath));
        $bar = $this->output->createProgressBar(count($items));
        $bar->start();

        DB::transaction(function () use ($items, $brand, $category, $status, $variantStatus, &$created, &$skipped, $bar): void {
            foreach ($items as $item) {
                $existingVariant = ProductVariant::query()
                    ->where('sku', $item['code'])
                    ->first();

                if ($existingVariant !== null) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                $product = Product::query()->firstOrCreate(
                    ['name' => $item['commodity']],
                    [
                        'brand_id' => $brand->getKey(),
                        'category_id' => $category->getKey(),
                        'status' => $status,
                        'hs_code' => $item['hs_code'] ?: null,
                    ]
                );

                if ($product->hs_code === null && $item['hs_code'] !== '') {
                    $product->update(['hs_code' => $item['hs_code']]);
                }

                if ($product->brand_id !== $brand->getKey() || $product->category_id !== $category->getKey()) {
                    $product->update([
                        'brand_id' => $brand->getKey(),
                        'category_id' => $category->getKey(),
                    ]);
                }

                $price = $this->normalizePrice($item['price']);
                $product->variants()->create([
                    'sku' => $item['code'],
                    'cost_price' => $price,
                    'selling_price' => $price,
                    'status' => $variantStatus,
                ]);

                $created++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Imported products: {$created}");
        $this->info("Skipped (existing SKU): {$skipped}");

        return self::SUCCESS;
    }

    private function resolveFilePath(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        $resolved = $path;

        if (! str_starts_with($resolved, DIRECTORY_SEPARATOR)) {
            $resolved = base_path($resolved);
        }

        return is_file($resolved) ? $resolved : null;
    }

    /**
     * @return array<int, array{code: string, commodity: string, price: string, quantity: string, unit: string, total: string, cbm: string, hs_code: string}>
     */
    private function parseItems(string $filePath): array
    {
        $zip = new ZipArchive;

        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('Unable to open the XLSX file.');
        }

        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('Sheet1 was not found in the XLSX file.');
        }

        $sharedStrings = $sharedStringsXml !== false
            ? $this->parseSharedStrings($sharedStringsXml)
            : [];

        $rows = $this->parseRows($sheetXml, $sharedStrings);

        $headerRow = null;
        $headerCells = [];

        foreach ($rows as $rowNumber => $cells) {
            $normalized = array_map(
                fn (string $value): string => Str::of($value)->trim()->lower()->value(),
                $cells
            );

            if (in_array('code', $normalized, true) && in_array('commodity', $normalized, true)) {
                $headerRow = $rowNumber;
                $headerCells = $cells;
                break;
            }
        }

        if ($headerRow === null) {
            throw new RuntimeException('Unable to locate header row with "code" and "commodity" columns.');
        }

        $headerMap = [];
        foreach ($headerCells as $column => $value) {
            $key = Str::of($value)->trim()->lower()->value();
            if ($key !== '') {
                $headerMap[$key] = $column;
            }
        }

        $requiredHeaders = ['code', 'commodity', 'price', 'quantity', 'unit', 'total', 'cbm', 'hs code'];
        foreach ($requiredHeaders as $header) {
            if (! array_key_exists($header, $headerMap)) {
                throw new RuntimeException("Missing required column: {$header}");
            }
        }

        $items = [];

        foreach ($rows as $rowNumber => $cells) {
            if ($rowNumber <= $headerRow) {
                continue;
            }

            $code = trim($cells[$headerMap['code']] ?? '');
            $commodity = trim($cells[$headerMap['commodity']] ?? '');
            $price = trim($cells[$headerMap['price']] ?? '');
            $quantity = trim($cells[$headerMap['quantity']] ?? '');
            $unit = trim($cells[$headerMap['unit']] ?? '');
            $total = trim($cells[$headerMap['total']] ?? '');
            $cbm = trim($cells[$headerMap['cbm']] ?? '');
            $hsCode = trim($cells[$headerMap['hs code']] ?? '');

            if (! $this->isItemRow($code, $commodity, $price, $quantity)) {
                continue;
            }

            $items[] = [
                'code' => $code,
                'commodity' => $commodity,
                'price' => $price,
                'quantity' => $quantity,
                'unit' => $unit,
                'total' => $total,
                'cbm' => $cbm,
                'hs_code' => $hsCode,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, string>
     */
    private function parseSharedStrings(string $xml): array
    {
        $shared = $this->loadXml($xml);

        $strings = [];
        foreach ($shared->xpath('//s:si') as $si) {
            $si->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $texts = [];
            foreach ($si->xpath('.//s:t') as $text) {
                $texts[] = (string) $text;
            }
            $strings[] = implode('', $texts);
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<string, string>>
     */
    private function parseRows(string $xml, array $sharedStrings): array
    {
        $sheet = $this->loadXml($xml);

        $rows = [];

        foreach ($sheet->xpath('//s:sheetData/s:row') as $row) {
            $row->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rowNumber = (int) $row['r'];
            $cells = [];

            foreach ($row->xpath('s:c') as $cell) {
                $cellRef = (string) $cell['r'];
                if ($cellRef === '') {
                    continue;
                }

                $column = preg_replace('/\d+/', '', $cellRef);
                if ($column === '') {
                    continue;
                }

                $cells[$column] = $this->parseCellValue($cell, $sharedStrings);
            }

            if ($cells !== []) {
                $rows[$rowNumber] = $cells;
            }
        }

        ksort($rows);

        return $rows;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    private function parseCellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            $index = (int) ($cell->v ?? 0);

            return $sharedStrings[$index] ?? '';
        }

        if ($type === 'inlineStr') {
            $cell->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $texts = [];
            foreach ($cell->xpath('s:is/s:t') as $text) {
                $texts[] = (string) $text;
            }

            return implode('', $texts);
        }

        return isset($cell->v) ? (string) $cell->v : '';
    }

    private function loadXml(string $xml): SimpleXMLElement
    {
        $useErrors = libxml_use_internal_errors(true);
        $element = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($useErrors);

        if ($element === false) {
            throw new RuntimeException('Unable to parse the XLSX XML content.');
        }

        $element->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        return $element;
    }

    private function isItemRow(string $code, string $commodity, string $price, string $quantity): bool
    {
        if ($code === '' || $commodity === '' || $price === '' || $quantity === '') {
            return false;
        }

        return (bool) preg_match('/^\d+(\.\d+)?$/', $code);
    }

    private function normalizePrice(string $price): ?string
    {
        if ($price === '' || ! is_numeric($price)) {
            return null;
        }

        return number_format((float) $price, 2, '.', '');
    }
}
