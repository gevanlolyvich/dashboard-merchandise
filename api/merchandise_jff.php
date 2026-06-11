<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM merchandise_jff ORDER BY id ASC");
    $data = $stmt->fetchAll();
    echo json_encode(['data' => $data]);
    exit;
}

if ($method === 'DELETE') {
    if ($_SESSION['role'] === 'user') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    $pdo->exec("TRUNCATE TABLE merchandise_jff");
    echo json_encode(['message' => 'Semua data berhasil dihapus']);
    exit;
}

if ($method === 'POST') {
    if ($_SESSION['role'] === 'user') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'File tidak ditemukan atau gagal diupload']);
        exit;
    }

    $file = $_FILES['excel_file']['tmp_name'];
    $rows = parseXLSX($file);

    if (!$rows || count($rows) < 2) {
        http_response_code(400);
        echo json_encode(['error' => 'Gagal membaca file Excel atau data kosong']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO merchandise_jff 
        (kode_barang, tipe_kategori, nama_barang, ukuran_varian, satuan, hpp, harga_ritel, harga_institusi, margin, 
         stok_awal, barang_masuk, barang_terjual, day_1_jff, stok_akhir, pendapatan, produksi, day_2, pendapatan_2, day_3, pendapatan_3, import_date) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE())");

    $inserted = 0;
    $dataRows = [];

    foreach ($rows as $r) {
        $kode = trim($r[0] ?? '');
        if ($kode === '' || $kode === 'Kode Barang' || stripos($kode, 'total') !== false) {
            continue;
        }

        $dataRows[] = $r;
    }

    $pdo->beginTransaction();
    try {
        foreach ($dataRows as $r) {
            $stmt->execute([
                trim($r[0] ?? ''),
                trim($r[1] ?? ''),
                trim($r[2] ?? ''),
                trim($r[3] ?? ''),
                trim($r[4] ?? ''),
                toNumeric($r[5] ?? 0),
                toNumeric($r[6] ?? 0),
                toNumeric($r[7] ?? 0),
                0,
                toNumeric($r[9] ?? 0),
                toNumeric($r[10] ?? 0),
                toNumeric($r[11] ?? 0),
                toNumeric($r[12] ?? 0),
                toNumeric($r[13] ?? 0),
                toNumeric($r[14] ?? 0),
                toNumeric($r[16] ?? 0),
                toNumeric($r[18] ?? 0),
                toNumeric($r[21] ?? 0),
                toNumeric($r[23] ?? 0),
                toNumeric($r[24] ?? 0),
            ]);
            $inserted++;
        }
        $pdo->commit();
        echo json_encode(['message' => "Berhasil mengimport $inserted data", 'count' => $inserted]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menyimpan data: ' . $e->getMessage()]);
    }
    exit;
}

function toNumeric($val) {
    $val = trim($val);
    if ($val === '' || $val === '-' || $val === '0') return 0;
    if (strpbrk($val, '#') !== false) return 0;
    $val = str_replace(',', '', $val);
    return floatval($val);
}

function colIndex($ref) {
    preg_match('/^([A-Z]+)/', $ref, $m);
    $col = $m[1] ?? 'A';
    $idx = 0;
    for ($i = 0; $i < strlen($col); $i++) {
        $idx = $idx * 26 + (ord($col[$i]) - ord('A') + 1);
    }
    return $idx - 1;
}

function parseXLSX($path) {
    if (!class_exists('ZipArchive')) return null;

    $zip = new ZipArchive;
    if ($zip->open($path) !== true) return null;

    $sharedStrings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        $ss = simplexml_load_string($ssXml);
        foreach ($ss->si as $si) {
            $text = '';
            if ($si->t) {
                $text = (string)$si->t;
            } elseif ($si->r) {
                foreach ($si->r as $run) {
                    $text .= (string)$run->t;
                }
            } elseif ($si->rPh) {
                foreach ($si->rPh as $run) {
                    $text .= (string)$run->t;
                }
            }
            $sharedStrings[] = $text;
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if (!$sheetXml) { $zip->close(); return null; }

    $sheet = simplexml_load_string($sheetXml);
    if (!$sheet) { $zip->close(); return null; }

    $rows = [];
    $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $sheet->registerXPathNamespace('s', $ns);

    foreach ($sheet->sheetData->row as $row) {
        $maxCol = 0;
        $cellMap = [];

        foreach ($row->c as $cell) {
            $ref = (string)$cell['r'];
            if ($ref === '') continue;

            $ci = colIndex($ref);
            $type = (string)$cell['t'];
            $value = '';

            if ($type === 's') {
                $idx = intval((string)$cell->v);
                if ($idx >= 0 && $idx < count($sharedStrings)) {
                    $value = $sharedStrings[$idx];
                }
            } elseif ($type === 'inlineStr') {
                if ($cell->is && $cell->is->t) {
                    $value = (string)$cell->is->t;
                } elseif ($cell->is && $cell->is->r) {
                    foreach ($cell->is->r as $run) {
                        $value .= (string)$run->t;
                    }
                }
            } elseif ($type === 'e') {
                $value = '0';
            } else {
                $value = (string)$cell->v;
            }

            $cellMap[$ci] = $value;
            if ($ci > $maxCol) $maxCol = $ci;
        }

        $cells = [];
        for ($i = 0; $i <= $maxCol; $i++) {
            $cells[] = $cellMap[$i] ?? '';
        }

        $rows[] = $cells;
    }

    $zip->close();
    return $rows;
}
