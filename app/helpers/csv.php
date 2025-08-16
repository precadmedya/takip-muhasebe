<?php
function read_csv(string $file): array {
    $rows = [];
    if(($handle = fopen($file, 'r')) !== false) {
        $headers = fgetcsv($handle, 0, ',');
        if(!$headers) { fclose($handle); return $rows; }
        if(isset($headers[0]) && str_starts_with($headers[0], "\xEF\xBB\xBF")) {
            $headers[0] = substr($headers[0], 3);
        }
        while(($data = fgetcsv($handle, 0, ',')) !== false) {
            if(count($data) == 1 && $data[0] === null) continue;
            $row = [];
            foreach($headers as $i => $h) {
                $row[$h] = $data[$i] ?? null;
            }
            $rows[] = $row;
        }
        fclose($handle);
    }
    return $rows;
}
function output_csv(array $headers, array $rows, string $filename): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    echo "\xEF\xBB\xBF"; // BOM
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach($rows as $row) {
        $line = [];
        foreach($headers as $h) { $line[] = $row[$h] ?? ''; }
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}
?>
