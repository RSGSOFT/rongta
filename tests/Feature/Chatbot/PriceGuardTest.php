<?php

namespace Tests\Feature\Chatbot;

use App\Services\Chatbot\PriceGuard;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;

class PriceGuardTest extends ChatbotTestCase
{
    public static function amounts(): array
    {
        return array_map(fn (string $text) => [$text], [
            // Without a currency word.
            'Цена 5 500 000', 'narxi 5 500 000', 'price is 5,500,000', 'около 5.5 млн', 'стоит 5,5 миллиона',
            'от 4 625 000 за штуку, 10 + 1', 'пять с половиной миллионов сум',
            // With one.
            '5 500 000 сум', '5 500 000 UZS', '5 500 000 сўм', '5.5 mln soʻm', '$400', 'around 450 dollars', '12 000 soʻm/kg',
            'от 4625000 сум', '5,5 млн сум', "120 000 so'm", '430 USD', 'UZS 5500000', '100 долларов',
            // Variations.
            'Цена: 450', 'Стоимость составляет примерно 5500', 'полмиллиона', 'five and a half million', 'besh yarim million',
            'Действует акция 10+1', 'Narxi: 4 625 000', '4625000 turadi', 'The price of the RLS1000 is 5 500 000.',
            "Весы RLS1000.\nЦена 5 500 000.", '5 500 000',
        ]);
    }

    #[DataProvider('amounts')]
    public function test_money_amounts_are_recognised(string $text): void
    {
        $this->assertTrue(PriceGuard::hasAmount($text), $text);
    }

    public static function technical(): array
    {
        return array_map(fn (string $text) => [$text], [
            '6 / 15 kg', '203 dpi', '90 mm/s', 'AC 100–240 V, 50/60 Hz', '433 × 372 × 506 mm', '112 × 2 keys',
            'about 10 000 products', '1366 × 768', 'цена деления 2 г', 'Цена деления 1 г, память на 10 000 товаров',
            'RLS1000/RLS1100A', 'RLS1315/RLS1330', 'AURORA D2-AI', '+998 90 123 45 67', '17.11.2026', '2026', 'в 2026 году', '2026-11-17',
            'Цену уточните у дилера по телефону +998 90 123 45 67', 'Цену на RLS1000 назовёт дилер',
            'The price is set by the dealer. Memory: 10 000 PLU.', 'Narxni diler aytadi. Xotira 10 000 ta mahsulot.',
            'Память: 10 тыс. товаров', 'Ресурс ножа 1 млн резов', '1 000 000 взвешиваний', 'IP 192.168.100.200',
            'Стоит выбрать C11: 30 кг', 'e = 5 g, 100–240 V', 'сумма граммов', '2 summary lines', '15 kg / 30 kg',
            'Print speed: 90 mm/s', '', '   ',
        ]);
    }

    #[DataProvider('technical')]
    public function test_technical_text_is_not_an_amount(string $text): void
    {
        $this->assertFalse(PriceGuard::hasAmount($text), $text);
    }

    /**
     * Every seeded text of the catalog, knowledge base and interface, line by line. The only
     * amounts are on the hero's decorative scale readout (weight, unit price, total).
     */
    public function test_seeded_content_has_no_false_positives(): void
    {
        $tables = ['families', 'scale_models', 'model_features', 'model_specs', 'faq_topics', 'faq_items', 'guide_entries',
            'guide_checks', 'chooser_options', 'ui_strings', 'documents', 'videos', 'knowledge_articles', 'regions', 'cities'];
        $skip = ['id', 'created_at', 'updated_at', 'file_path', 'image', 'gallery', 'source_url', 'url'];

        $hits = [];
        $lines = 0;
        $walk = function (mixed $value, string $where) use (&$walk, &$hits, &$lines) {
            if (is_string($value) && is_array($decoded = json_decode($value, true))) {
                $value = $decoded;
            }
            if (is_array($value)) {
                foreach ($value as $key => $item) {
                    $walk($item, $where.'.'.$key);
                }
            } elseif (is_string($value) && ! is_numeric($value)) {
                foreach (preg_split('/\R/u', $value) ?: [] as $line) {
                    $lines++;
                    if (PriceGuard::hasAmount($line)) {
                        $hits[] = $where.': '.$line;
                    }
                }
            }
        };

        foreach ($tables as $table) {
            foreach (DB::table($table)->get() as $row) {
                $row = (array) $row;
                $id = $row['key'] ?? $row['slug'] ?? $row['id'];
                if ($table === 'ui_strings' && $id === 'hero.readout.alt') {
                    continue;
                }
                foreach (array_diff_key($row, array_flip($skip)) as $column => $value) {
                    $walk($value, "$table#$id.$column");
                }
            }
        }

        $this->assertGreaterThan(2000, $lines);
        $this->assertSame([], $hits);
    }
}
