<?php



return [

    'required' => ':attributeは必須です。',
    'date_format' => ':attributeの形式が正しくありません。',
    'after' => ':attributeには:dateより後の時刻を指定してください',

    'custom' => [
        'clock_in' => [
            'invalid_time' => '出勤時間もしくは退勤時間が不適切な値です',
        ],
        'break_start' => [
            'invalid_time' => '休憩時間が不適切な値です',
        ],
        'break_end' => [
            'invalid_time' => '休憩時間もしくは退勤時間が不適切な値です',
        ],
        'reason' => [
            'required' => '備考を記入してください',
        ],
    ],

    'attributes' => [
        'clock_in' => '出勤時間',
        'clock_out' => '退勤時間',
        'break_start' => '休憩開始時間',
        'break_end' => '休憩終了時間',
        'reason' => '備考',
    ],
];