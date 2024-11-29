<?php
require 'dbconn.php'; // Include your database connection

if (isset($_POST['searchTerm'])) {
    $searchTerm = $_POST['searchTerm'];

    // Build a query to search across multiple columns
    $stmt = $conn->prepare("
        SELECT 
            MIN(Id) AS Id, 
            Vendor_Id, 
            citycode, 
            country, 
            city, 
            cityaddress, 
            groupName
        FROM 
            filter_locations_hertz
        WHERE 
            groupName IS NOT NULL 
            AND groupName != '0' 
            AND (
                LOWER(groupName) LIKE LOWER(?) OR 
                LOWER(city) LIKE LOWER(?) OR 
                LOWER(cityaddress) LIKE LOWER(?)
            )
        GROUP BY 
            groupName
    ");

    $searchTerm = "%$searchTerm%";
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $stations = [];
    while ($row = $result->fetch_assoc()) {
        $stations[] = [
            'stationCode' => $row['citycode'],
            'stationName' => $row['cityaddress'],
            'city' => $row['city'],
            'group' => $row['groupName'],
            'vendorId' => $row['Vendor_Id'],
            'country' => $row['country']
        ];
    }

    // Return the result as JSON
    if (!empty($stations)) {
        echo json_encode($stations);
    } else {
        echo json_encode(['message' => 'No data found']);
    }
}
?>
