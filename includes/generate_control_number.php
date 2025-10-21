<?php
/**
 * Control Number Generator
 * Generates a unique control number for applicants in the format PSAU followed by 6 random digits
 */

/**
 * Generate a unique PSAU control number
 * @param PDO $conn Database connection
 * @return string The generated control number
 */
function generate_control_number($conn) {
    $prefix = 'PSAU';
    $unique = false;
    $control_number = '';
    
    // Keep generating until we find a unique one
    while (!$unique) {
        // Generate 6 random digits
        $random_digits = sprintf('%06d', mt_rand(0, 999999));
        $control_number = $prefix . $random_digits;
        
        // Check if the generated number already exists
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE control_number = :control_number");
        $stmt->bindParam(':control_number', $control_number);
        $stmt->execute();
        $result = $stmt->fetch();
        
        // If count is 0, the control number is unique
        if ($result['count'] == 0) {
            $unique = true;
        }
    }
    
    return $control_number;
} 