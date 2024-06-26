<?php
session_start();
require_once 'config/config.php';
require_once BASE_PATH . '/lib/DynamicQrcode/DynamicQrcode.php';
require_once BASE_PATH . '/lib/StaticQrcode/StaticQrcode.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDbInstance();
    $json = json_decode(file_get_contents('php://input'), true);

    if($json["action"] == "download") {
        $params = $json['params'];
        $files = [];

        if (isset($json['type'])) {
            $type = filter_var($json['type'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        } else {
            echo json_encode([
                'data' => 'Type action field in the request.',
                'status' => 400
            ]);
            exit();
        }

        if (count($params) == 0) {
            echo json_encode([
                'data' => 'No qrcodes were selected.',
                'status' => 400
            ]);
            exit();
        }

        foreach ($params as $param) {
            $row = $db->where('id', $param);
            $row = $db->getOne("{$type}_qrcodes");
            @$files[] = SAVED_QRCODE_FOLDER . $row['qrcode'];
        }

        $zip = new ZipArchive();
        $uniqid = uniqid();
        $relative_dir = SAVED_QRCODE_FOLDER . 'zip/qrcodes_' . $uniqid . '.zip';
        @unlink($relative_dir);
        $url_path = SAVED_QRCODE_URL . 'zip/qrcodes_' . $uniqid . '.zip';
        $zip->open($relative_dir, ZipArchive::CREATE);

        foreach ($files as $file) {
            $download_file = @file_get_contents($file, true);
            $zip->addFromString(basename($file), $download_file);
        }

        $zip->close();

        echo json_encode([
            'data' => $url_path,
            'status' => 200
        ]);
        exit();
    } else if($json["action"] == "delete") {
        $params = $json['params'];
        $files = [];

        if (isset($json['type'])) {
            $type = filter_var($json['type'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        } else {
            echo json_encode([
                'data' => 'Type action field in the request.',
                'status' => 400
            ]);
            exit();
        }

        if (count($params) == 0) {
            echo json_encode([
                'data' => 'No qrcodes were selected.',
                'status' => 400
            ]);
            exit();
        }

        if($type == "dynamic")
            $instance = new DynamicQrcode();
        else if($type == "static")
            $instance = new StaticQrcode();
        else
            die("Type not allowed");

        foreach ($params as $param) {
            $a = 0;
            $instance->deleteQrcode($param, true);
        }

        echo json_encode([
            'action' => "delete",
            'data' => "Qrcode deleted",
            'status' => 200
        ]);
        exit();

    } else
        exit("Action not allowed");
} else {
    exit('Direct access to this script not allowed.');
}
?>