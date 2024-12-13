<?php

//
// fax.php
// Originally made by 108jiminy https://github.com/108jiminy
// Licensed under the MIT license
//

require_once 'Globals.php';

use \MikoPBX\Core\Asterisk\AGI;

$agi     = new AGI();
$faxFile = "/tmp/" .$agi->get_variable("CDR(linkedid)", true).'.tiff'; // 一時ファイル
$caller  = $agi->get_variable("CALLERID(num)", true); // 発信者番号

// 保存先ディレクトリ
$saveDir = "/mikopbx/fax_files/";
if (!file_exists($saveDir)) {
    mkdir($saveDir, 0777, true); // 保存ディレクトリがない場合は作成
}

// FAX受信の実行
$agi->exec("ReceiveFax", "{$faxFile},d"); 
$result  = $agi->get_variable("FAXOPT(status)", true); // 受信結果を取得

if ($result === 'SUCCESS' && file_exists($faxFile)) {
    // 保存用ファイル名を設定
    $savedFile = $saveDir . date("Ymd_His") . "_from_{$caller}.tiff";

    // 一時ファイルを保存先に移動
    rename($faxFile, $savedFile);

    $agi->verbose("FAX successfully saved to {$savedFile}");
} else {
    $agi->verbose("FAX reception failed or file not found.");
}

// スクリプト終了前に1秒待機
sleep(1);