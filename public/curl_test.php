<?php
// Check if cURL is available
if (function_exists('curl_version')) {
    $WebuneCurl = curl_version();
    echo '<ul>';
    
    if (isset($_GET['details'])) {
        echo '<h1>Full Details:</h1><pre>';
        print_r($WebuneCurl);
        echo '</pre>';
    } else {
        echo '<h1>Congratulations!!</h1> You have cURL enabled in your PHP.<br><br>';
        echo 'You have version: ' . $WebuneCurl['version'] . ' installed.<br>';
        echo 'These are the protocols your server cURL supports:<br>';
        echo '<ul>';
        
        // Iterate over protocols correctly
        foreach ($WebuneCurl['protocols'] as $protocol) {
            echo '<li>' . htmlspecialchars($protocol) . '</li>'; // Use htmlspecialchars to prevent XSS
        }
        echo '</ul>';
        echo '<p><a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?details=1"> Click Here to see Full Details</a></p>';
    }
} else {
    echo "<h1>ERROR - IT APPEARS YOU DON'T HAVE cURL ENABLED ON THIS SERVER</h1>";
}
?>
