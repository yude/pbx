#!/usr/bin/php

<?php

//
// adress_get.php
// Originally made by 108jiminy https://github.com/108jiminy
// Licensed under the MIT license
//

use MikoPBX\Core\Asterisk\AGI;
require_once 'Globals.php';

$agi = new AGI();

try {
    // 着信番号を取得
    $callerId = $agi->get_variable('CALLERID(num)', true);
    $agi->verbose("Caller ID: $callerId");

    // IP_ADDRESS を取得
    $ipAddress = $agi->get_variable('IP_ADDRESS', true);
    $agi->verbose("IP Address: $ipAddress");

    // Asterisk CLIコマンドを実行してエンドポイント情報を取得
    $command = "asterisk -rx 'pjsip show endpoints'";
    $output = [];
    exec($command, $output);

    // IPアドレスに対応するエンドポイント名を取得
    $endpoint = null;
    $currentEndpoint = null;
    foreach ($output as $line) {
        // エンドポイント名を取得
        if (preg_match('/^ Endpoint:\s+(\S+)/', $line, $matches)) {
            $currentEndpoint = $matches[1];
        }

        // Match 行でIPアドレスを確認
        if ($currentEndpoint && preg_match('/^\s+Match:\s+([\d\.\/]+)/', $line, $matches)) {
            $matchIP = $matches[1];
            // CIDR範囲のサポートが必要でない場合、単純比較
            if ($matchIP === "$ipAddress/32") {
                $endpoint = $currentEndpoint;
                break;
            }
        }
    }

    // 結果の作成
    if ($endpoint) {
        $result = "$callerId@$endpoint";
        $agi->verbose("Mapped Caller: $result");
    } else {
        $result = $callerId;
        $agi->verbose("Caller without Endpoint: $result");
    }

    // 結果をAsterisk変数に設定
    $agi->set_variable('RESULT', $result);

} catch (Exception $e) {
    $agi->verbose("Error: " . $e->getMessage());
    $agi->set_variable('RESULT', 'error');
}
