<?php
require_once 'Globals.php';

use \MikoPBX\Core\Asterisk\AGI;

// weather.tsukumijima.net から天気の情報を取得
function getWeather() {
    $url = "https://weather.tsukumijima.net/api/forecast/city/120010";

    // APIからデータを取得
    $data = file_get_contents($url);
    
    // JSONデータをデコード
    $json = json_decode($data, true);
    // $agi->verbose($json);
    
    // 必要なデータを取得
    $title = $json['title'];
    $bodyText = str_replace("\n", "", $json['description']['bodyText']);
    $bodyText = str_replace("　", "", $bodyText);

    // $message = $title . "についてお知らせします。" . $bodyText;
    // $agi->verbose($bodyText);
    return $bodyText;
}

// VOICEVOX API から音声を取得する関数
function getVoicevoxAudio($api_url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $audio_data = curl_exec($ch);
    curl_close($ch);

    return $audio_data;
}

// AGI スクリプトの開始
$agi = new AGI();
$agi->answer();

$agi->stream_file("zunda-please-wait");

$weather = getWeather();
$agi->verbose($weather);

// 音声データを取得し、一時ファイルに保存する
$agi->verbose('Fetching audio data...');
$voicevox_api_url = sprintf("https://deprecatedapis.tts.quest/v2/voicevox/audio/?key=J85-R6G475h1A4C&text=%s", $weather);
$agi->verbose($voicevox_api_url);
$audio_data = getVoicevoxAudio($voicevox_api_url);
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
