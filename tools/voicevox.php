<?php
require_once 'Globals.php';

use \MikoPBX\Core\Asterisk\AGI;

// VOICEVOX API の設定
$voicevox_api_url = 'https://voicevox.yude.jp.eu.org';
$voicevox_text = 'あなたに情報をお届けするまで、しばらく時間がかかります。待ってね。';
$voicevox_speaker_id = 1; // 使用するスピーカーの ID

// weather.tsukumijima.net から天気の情報を取得
function getWeather() {
    $url = "https://weather.tsukumijima.net/api/forecast/city/120010";

    // APIからデータを取得
    $data = file_get_contents($url);
    
    // JSONデータをデコード
    $json = json_decode($data, true);
    
    // 必要なデータを取得
    $title = $json['title'];
    $bodyText = str_replace("\n", "", $json['description']['bodyText']);
    $bodyText = str_replace("　", "", $bodyText);

    $message = $title . "についてお知らせします。" . $bodyText;

    return $message;
}

// VOICEVOX API から音声クエリを生成する関数
function createVoicevoxAudioQuery($api_url, $text, $speaker_id) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . '/audio_query?text=' . urlencode($text) . '&speaker=' . $speaker_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    $audio_query = curl_exec($ch);
    curl_close($ch);

    return $audio_query;
}

// VOICEVOX API から音声を取得する関数
function getVoicevoxAudio($api_url, $audio_query, $speaker_id) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . '/synthesis?speaker=' . $speaker_id);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $audio_query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $audio_data = curl_exec($ch);
    curl_close($ch);

    return $audio_data;
}

// AGI スクリプトの開始
$agi = new AGI();
$agi->answer();

$agi->stream_file("zunda-please-wait");

// 音声クエリを生成
$agi->verbose('Generating audio query...');
$audio_query = createVoicevoxAudioQuery($voicevox_api_url, getWeather(), $voicevox_speaker_id);

// 音声クエリをテキストファイルとして保存（トラブルシューティング用）
$agi->verbose('Saving audio query to file...');
$temp_query_file = '/tmp/voicevox_audio_query.json';
file_put_contents($temp_query_file, $audio_query);

// 音声データを取得し、一時ファイルに保存する
$agi->verbose('Fetching audio data...');
$audio_data = getVoicevoxAudio($voicevox_api_url, $audio_query, $voicevox_speaker_id);
$temp_audio_file = tempnam("/offload/asterisk/sounds/ja-jp", 'voicevox_');
rename($temp_audio_file, $temp_audio_file . ".wav");
$temp_audio_file = $temp_audio_file . ".wav";

$result = file_put_contents($temp_audio_file, $audio_data);
if ( $result === 0 ) {
    $agi->verbose("failed to write audio data");
} else {
    $agi->verbose("saved audio data, " . $result . " byte(s) to " . $temp_audio_file);
}

// sox を使って音声データを適切なフォーマットに変換する
$agi->verbose('Converting audio data using sox...');
$converted_audio_file = tempnam("/offload/asterisk/sounds/ja-jp", 'voicevox_converted_');
rename($converted_audio_file, $converted_audio_file . '.wav');
$converted_audio_file = $converted_audio_file . ".wav";

$sox_command = "sox $temp_audio_file -t wav -r 8000 -b 16 -c 1 $converted_audio_file";
exec($sox_command);
chmod($converted_audio_file, 0777);

// 変換された音声ファイルを再生する
$agi->verbose('Playing converted audio file...');
$agi->stream_file(str_replace(".wav", "", basename($converted_audio_file)));

// 一時ファイルを削除する
$agi->verbose('Cleaning up temporary files...');
unlink($temp_audio_file);
unlink($converted_audio_file);

$agi->verbose('Hanging up...');
$agi->hangup();

?>
