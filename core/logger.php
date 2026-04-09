<?php

function logAppointment($conn, $appointment_id, $user_id, $action, $details=''){
    
    $stmt = $conn->prepare("
        INSERT INTO appointment_logs 
        (appointment_id, user_id, action, details, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    if(!$stmt) return;

    $stmt->bind_param("iiss", $appointment_id, $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}