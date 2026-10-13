<?php

namespace App\Actions\Kilang;

use App\Models\Factory;
use App\Models\FinanceEntry;
use Illuminate\Support\Collection;

/** Generates a portable A4 report using the standard PDF Helvetica font. */
class FinancialReportPdf
{
    /** @param array<string, float|int|string> $report
     * @param  Collection<int, FinanceEntry>  $entries
     */
    public function render(Factory $factory, array $report, Collection $entries): string
    {
        $money = fn (float|int|string $amount): string => 'Rp '.number_format((float) $amount, 2, ',', '.');
        $rows = [
            ['LAPORAN KEUANGAN BIOJELAN', '', 'heading'],
            [$factory->factory_name, '', 'normal'], [$factory->address, '', 'normal'],
            ['Periode '.$report['from'].' s/d '.$report['to'], '', 'normal'],
            ['', '', 'normal'], ['RINGKASAN', '', 'heading'],
            ['Pemasukan penjualan', $money($report['income']), 'normal'],
            ['Pembelian minyak dari Agent', $money($report['purchase_cost']), 'normal'],
            ['Biaya operasional', $money($report['operating_expense']), 'normal'],
            ['Total pengeluaran', $money($report['total_expense']), 'normal'],
            ['Laba / rugi berbasis kas', $money($report['profit']), 'heading'],
            ['Minyak diterima Factory', number_format((float) $report['received_liter'], 3, ',', '.').' L', 'normal'],
            ['Minyak terjual', number_format((float) $report['sold_liter'], 3, ',', '.').' L', 'normal'],
            ['', '', 'normal'], ['DASAR PERHITUNGAN', '', 'heading'],
            ['Pemasukan berasal dari catatan penjualan Factory.', '', 'normal'],
            ['Biaya pembelian dihitung sekali saat pickup COMPLETED.', '', 'normal'],
            ['Transaksi Client-Agent adalah riwayat jaringan dan tidak dihitung dua kali.', '', 'normal'],
            ['Laporan kas belum memperhitungkan persediaan, pajak, dan penyusutan.', '', 'normal'],
            ['', '', 'normal'], ['CATATAN PENJUALAN DAN BIAYA', '', 'heading'],
        ];
        foreach ($entries as $entry) {
            $label = $entry->date->toDateString().' | '.($entry->type === 'SALE' ? 'Penjualan' : 'Biaya');
            $rows[] = [$label, $money($entry->amount), 'normal'];
            $description = preg_replace('/[\r\n\t]+/', ' ', $entry->description) ?? '';
            foreach (explode("\n", wordwrap($description, 78, "\n", true)) as $line) {
                $rows[] = [$line, '', 'small'];
            }
        }
        if ($entries->isEmpty()) {
            $rows[] = ['Belum ada catatan pada periode ini.', '', 'normal'];
        }
        $wrappedRows = [];
        foreach ($rows as [$left, $right, $style]) {
            $left = preg_replace('/[\r\n\t]+/', ' ', $left) ?? '';
            $width = $right === '' ? 84 : 52;
            foreach (explode("\n", wordwrap($left, $width, "\n", true)) as $index => $line) {
                $wrappedRows[] = [$line, $index === 0 ? $right : '', $style];
            }
        }
        $pages = array_chunk($wrappedRows, 34);
        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>', 4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
        $kids = [];
        foreach ($pages as $i => $pageRows) {
            $pageId = 5 + $i * 2;
            $streamId = $pageId + 1;
            $kids[] = "$pageId 0 R";
            $stream = "0.12 0.30 0.23 rg 40 780 515 4 re f\n";
            $y = 755;
            foreach ($pageRows as [$left, $right, $style]) {
                $font = $style === 'heading' ? 'F2' : 'F1';
                $size = $style === 'heading' ? 12 : ($style === 'small' ? 9 : 10);
                $stream .= '0.08 0.15 0.12 rg BT /'.$font.' '.$size.' Tf 40 '.$y.' Td ('.$this->escape($left).") Tj ET\n";
                if ($right !== '') {
                    $stream .= 'BT /F1 10 Tf 390 '.$y.' Td ('.$this->escape($right).") Tj ET\n";
                }
                $y -= 20;
            }
            $stream .= '0.4 0.4 0.4 rg BT /F1 8 Tf 40 35 Td (BioJelan | '.$this->escape(now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i').' WIB').' | Halaman '.($i + 1).' / '.count($pages).") Tj ET\n";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$streamId.' 0 R >>';
            $objects[$streamId] = '<< /Length '.strlen($stream).">>\nstream\n".$stream.'endstream';
        }
        $objects[2] = '<< /Type /Pages /Count '.count($pages).' /Kids ['.implode(' ', $kids).'] >>';
        ksort($objects);
        $bytes = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($bytes);
            $bytes .= "$id 0 obj\n$object\nendobj\n";
        }
        $xref = strlen($bytes);
        $bytes .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($objects as $id => $object) {
            $bytes .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        return $bytes.'trailer << /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\nstartxref\n$xref\n%%EOF\n";
    }

    private function escape(string $text): string
    {
        $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: '';

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
