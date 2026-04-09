<?php

function getPostData(){
    $data = $_POST;

    if(empty($data)){
        $json = file_get_contents("php://input");
        $data = json_decode($json, true) ?? [];
    }

    return $data;
}

function sanitize($conn, $value){
    return $conn->real_escape_string(trim($value));
}