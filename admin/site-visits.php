<?php
session_start();
require_once '../config/db.php'; 

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.html');
    exit();
}

// Fetch all site visits with photos
$query = "SELECT sv.*, 
         GROUP_CONCAT(vp.photo_path) as photos,
         GROUP_CONCAT(vp.upload_time) as photo_times
         FROM site_visit_tracking sv 
         LEFT JOIN visit_photos vp ON sv.visit_id = vp.visit_id
         GROUP BY sv.visit_id
         ORDER BY sv.visit_time DESC";
$result = mysqli_query($conn, $query);

// Add error handling
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Fetch assigned site visit tasks
$task_query = "SELECT t.*, e.name as employee_name 
              FROM task_assigned t 
              JOIN employee e ON t.emp_id = e.emp_id 
              WHERE t.is_site_visit = 1 
              ORDER BY t.due_date ASC";
$task_result = mysqli_query($conn, $task_query);

if (!$task_result) {
    die("Task query failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Visit Tracking - Emplify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css">
    <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Arial', sans-serif;
            }
    
            :root {
                --primary-color: #00b4db;
                --secondary-color: #0083b0;
                --sidebar-width: 250px;
            }
    
            body {
                min-height: 100vh;
                display: flex;
                background: #f5f5f5;
                padding-left: var(--sidebar-width); /* Add this line */
            }
    
            .main-content {
                flex: 1;
                padding: 20px;
                width: 100%; /* Modified this line */
                margin-left: 0; /* Modified this line */
                position: relative;
            }
            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin: -20px -20px 30px -20px;
                background: white;
                padding: 20px;
                border-radius: 0;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
    
            .header h1 {
                margin: 0;
                padding: 0 20px;
                color: var(--primary-color);
            }
    
            .visit-card {
                background: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                transition: transform 0.3s, box-shadow 0.3s;
                border-top: 4px solid var(--primary-color);
                cursor: pointer;
            }
    
            .visit-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
    
            .visit-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            }
    
            .header .add-btn {
                margin-right: 20px;
            }
    
            /* Hide the top menu items */
            .navbar-nav.top-menu,
            .nav-item.top-menu,
            .nav-link.top-menu {
                display: none;
                margin-bottom: 30px;
                background: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .main-content {
                flex: 1;
                padding: 20px;
                width: 100%;
                margin-left: 0;
                position: relative;
            }
    
            h1 {
                color: var(--primary-color);
                margin-bottom: 20px;
                font-size: 24px;
            }
    
            .visit-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
                padding: 20px 0;
            }
    
            .visit-card {
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                padding: 15px;
                transition: transform 0.2s;
            }
    
            .visit-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }
    
            .photo-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-bottom: 15px;
            }
    
            .photo-item {
                position: relative;
                padding-top: 100%;
                overflow: hidden;
                border-radius: 4px;
            }
    
            .photo-item img {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.3s;
            }
    
            .photo-item:hover img {
                transform: scale(1.05);
            }
    
            .map-container {
                height: 150px;
                margin: 10px 0;
                border-radius: 4px;
                overflow: hidden;
            }
    
            .visit-info {
                margin-top: 10px;
            }
    
            .visit-info h3 {
                color: var(--primary-color);
                margin-bottom: 10px;
            }
    
            .visit-info p {
                margin: 5px 0;
                color: #666;
                display: flex;
                align-items: center;
                gap: 8px;
            }
    
            .visit-info i {
                color: var(--primary-color);
                width: 20px;
            }
    
            .more-photos {
                position: relative;
                cursor: pointer;
            }
    
            .more-photos::after {
                content: attr(data-count);
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5em;
                font-weight: bold;
            }
.section-title {
    margin: 30px 0 20px;
}

.section-title h2 {
    color: var(--primary-color);
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.task-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.task-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-left: 4px solid var(--primary-color);
}

.task-info h3 {
    color: var(--primary-color);
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.task-info p {
    margin: 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #666;
}

.task-info i {
    color: var(--primary-color);
    width: 20px;
    text-align: center;
}

.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.9rem;
    color: white;
    background: var(--primary-color);
    margin-top: 10px;
}

.status-badge.completed {
    background: #28a745;
}

.status-badge.pending {
    background: #ffc107;
}

.status-badge.assigned {
    background: var(--primary-color);
}
        </style>
    </head>
    <body>
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-map-marker-alt"></i> Site Visit Tracking</h1>
            </div>
            
            <!-- Add Site Visit Tasks Section -->
            <div class="section-title">
                <h2><i class="fas fa-tasks"></i> Assigned Site Visits</h2>
            </div>
            <div class="task-grid">
                <?php 
                if (mysqli_num_rows($task_result) > 0) {
                    while($task = mysqli_fetch_assoc($task_result)) { 
                ?>
                <div class="task-card">
                    <div class="task-info">
                        <h3><i class="fas fa-user"></i> <?php echo htmlspecialchars($task['employee_name']); ?></h3>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($task['location']); ?></p>
                        <p><i class="fas fa-calendar"></i> Due: <?php echo date('F j, Y', strtotime($task['due_date'])); ?></p>
                        <p><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($task['description']); ?></p>
                        <span class="status-badge <?php echo strtolower($task['status']); ?>"><?php echo $task['status']; ?></span>
                    </div>
                </div>
                <?php 
                    }
                } else {
                    echo "<p>No site visit tasks assigned.</p>";
                }
                ?>
            </div>
    
            <!-- Existing Site Visits Section -->
            <div class="section-title">
                <h2><i class="fas fa-camera"></i> Completed Site Visits</h2>
            </div>
            <div class="visit-grid">
            <?php 
            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) { 
                    $photos = $row['photos'] ? explode(',', $row['photos']) : [];
                    $photo_times = $row['photo_times'] ? explode(',', $row['photo_times']) : [];
                    $geo_location = explode(',', $row['geo_location']);
                    $latitude = isset($geo_location[0]) ? trim($geo_location[0]) : '';
                    $longitude = isset($geo_location[1]) ? trim($geo_location[1]) : '';
                ?>
                <div class="visit-card">
                    <?php if(!empty($photos)) { ?>
                        <div class="photo-grid">
                            <?php 
                            $display_photos = array_slice($photos, 0, 4);
                            foreach($display_photos as $index => $photo) {
                                $is_last = $index === 3 && count($photos) > 4;
                            ?>
                                <div class="photo-item <?php echo $is_last ? 'more-photos' : ''; ?>" 
                                     <?php echo $is_last ? 'data-count="+' . (count($photos) - 3) . '"' : ''; ?>>
                                    <a href="<?php echo htmlspecialchars($photo); ?>" 
                                       data-fancybox="gallery-<?php echo $row['visit_id']; ?>">
                                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="Site Visit Photo">
                                    </a>
                                </div>
                            <?php } ?>
                            <?php 
                            // Hidden photos for Fancybox gallery
                            if(count($photos) > 4) {
                                foreach(array_slice($photos, 4) as $photo) { ?>
                                    <a href="<?php echo htmlspecialchars($photo); ?>" 
                                       data-fancybox="gallery-<?php echo $row['visit_id']; ?>" 
                                       style="display: none;"></a>
                            <?php }
                            } ?>
                        </div>
                    <?php } ?>
                    
                    <div class="map-container" id="map-<?php echo $row['visit_id']; ?>"></div>
                    
                    <div class="visit-info">
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['geo_location']); ?></p>
                        <p><i class="fas fa-clock"></i> <?php echo date('F j, Y, g:i a', strtotime($row['visit_time'])); ?></p>
                        <?php if($row['remarks']) { ?>
                            <p><i class="fas fa-comment"></i> <?php echo htmlspecialchars($row['remarks']); ?></p>
                        <?php } ?>
                        <p><i class="fas fa-network-wired"></i> IP: <?php echo htmlspecialchars($row['ip_address']); ?></p>
                    </div>
                </div>
            <?php } 
        } else {
            echo "<p>No site visits found.</p>";
        }
        ?>
        </div>
    </div>
    
        <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
        <script>
            // Initialize Fancybox
            Fancybox.bind("[data-fancybox]", {
                // Custom options if needed
            });
    
            // Initialize maps for each visit
            <?php 
            mysqli_data_seek($result, 0);
            while($row = mysqli_fetch_assoc($result)) {
                $geo_location = explode(',', $row['geo_location']);
                $latitude = isset($geo_location[0]) ? trim($geo_location[0]) : '';
                $longitude = isset($geo_location[1]) ? trim($geo_location[1]) : '';
                
                if($latitude && $longitude) {
            ?>
                var map = L.map('map-<?php echo $row["visit_id"]; ?>').setView([<?php echo $latitude; ?>, <?php echo $longitude; ?>], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
                L.marker([<?php echo $latitude; ?>, <?php echo $longitude; ?>]).addTo(map);
            <?php 
                }
            } 
            ?>
        </script>
    </body>
    </html>