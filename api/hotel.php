<?php
header('Content-Type: application/json'); // supaya output JSON
require_once("../koneksi.php");

if (isset($_GET["location"]) && $_GET["location"] == "home") {
    $sql = "SELECT rt.*, (SELECT r.image FROM rooms r WHERE r.room_type_id = rt.id LIMIT 1) as image, (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.id AND r.status = 'available') as available_rooms FROM room_types rt ORDER BY price_per_night LIMIT 6";
} else {
    $sql = "SELECT rt.*, (SELECT r.image FROM rooms r WHERE r.room_type_id = rt.id LIMIT 1) as room_image, (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.id AND r.status = 'available') as available_rooms FROM room_types rt";
}

$result = $koneksi->query($sql);

$data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (isset($_GET["location"]) && $_GET["location"] == "home") {
            $row["image"] = $row["image"] != null ? explode(",", $row["image"]) : null;
        } else {
            $row["room_image"] = $row["room_image"] != null ? explode(",", $row["room_image"]) : null;
        }
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
        "message" => "Tidak ada data kamar"
    ], JSON_PRETTY_PRINT);
}

$koneksi->close();
