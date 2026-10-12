<?php

return [
    'batch_size' => (int) env('USER_IMPORT_BATCH_SIZE', 1000),
    'max_file_kb' => (int) env('USER_IMPORT_MAX_FILE_KB', 51200),
];
