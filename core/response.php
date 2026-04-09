<?php

function respond($success, $message = '', $extra = []){
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}