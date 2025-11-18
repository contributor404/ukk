<?php
session_start();
header('Content-Type: application/json'); // supaya output JSON
require_once("../koneksi.php");

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "empty",
        "message" => "Tidak ada data riwayat"
    ], JSON_PRETTY_PRINT);
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT 
            b.id,
            b.booking_code,
            b.check_in,
            b.check_out,
            b.total_price,
            b.status as booking_status,
            rt.name as room_type_name,
            (SELECT r.image FROM rooms r WHERE r.room_type_id = rt.id LIMIT 1) as room_image
          FROM bookings b
          JOIN rooms r ON b.room_id = r.id
          JOIN room_types rt ON r.room_type_id = rt.id
          WHERE b.user_id = $user_id
          ORDER BY b.id DESC";

$result = $koneksi->query($sql);

$data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row["room_image"] = $row["room_image"] != null ? explode(",", $row["room_image"]) : null;
        $data[] = $row;
    }
    echo json_encode([
        "status" => "success",
        "count" => count($data),
        "data" => $data
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        "status" => "empty",
        "message" => "Tidak ada data riwayat"
    ], JSON_PRETTY_PRINT);
}

$koneksi->close();
