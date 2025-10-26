<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../config.php';

try {
    // Main query to get packages with destination info
    $query = "SELECT 
                p.id,
                p.destination_id,
                p.name,
                p.hotel_name,
                p.hotel_location,
                p.nights,
                p.event_name,
                p.event_description,
                p.event_date,
                p.duration,
                p.price,
                p.inclusions,
                p.exclusions,
                p.image_url,
                p.is_featured,
                p.description,
                d.name as destination_name,
                d.image_url as destination_image
              FROM packages p
              JOIN destinations d ON p.destination_id = d.id";

    $stmt = $pdo->query($query);
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response to match frontend expectations
    $formattedPackages = array_map(function($package) {
        return [
            'id' => $package['id'],
            'name' => $package['name'],
            'destination_id' => $package['destination_id'],
            'destination_name' => $package['destination_name'],
            'duration' => $package['duration'],
            'price' => number_format((float)$package['price'], 2, '.', ''),
            'inclusions' => $package['inclusions'],
            'exclusions' => $package['exclusions'],
            'is_featured' => (bool)$package['is_featured'],
            'image_url' => $package['image_url'],
            'description' => $package['description'],
            // Format hotels as array (even though DB stores single entry)
            'hotels' => $package['hotel_name'] ? [
                [
                    'hotel_name' => $package['hotel_name'],
                    'hotel_location' => $package['hotel_location'],
                    'nights' => $package['nights']
                ]
            ] : [],
            // Format events as array (even though DB stores single entry)
            'events' => $package['event_name'] ? [
                [
                    'event_name' => $package['event_name'],
                    'event_description' => $package['event_description'],
                    'event_date' => $package['event_date']
                ]
            ] : []
        ];
    }, $packages);

    echo json_encode([
        'success' => true,
        'data' => $formattedPackages
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>