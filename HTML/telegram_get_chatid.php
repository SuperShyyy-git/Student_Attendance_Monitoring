<?php
include __DIR__ . "/../config/db_connect.php";


$token = "8570035647:AAHzmsXlROqMT90aS5rUTIFRxT93R-o7USQ";

// GET UPDATES
$updates = file_get_contents("https://api.telegram.org/bot$token/getUpdates");
$updates = json_decode($updates, true);

if (!$updates["ok"]) {
    die("No updates found.");
}

foreach ($updates["result"] as $update) {

    if (!isset($update["message"]["text"])) continue;

    $text = trim($update["message"]["text"]);
    $chat_id = $update["message"]["chat"]["id"];

    // ====== FORMAT FIX FOR PH NUMBERS ======
    // If they send +639XXXXXXXXX → convert to 09XXXXXXXXX
    if (preg_match("/^\+639[0-9]{9}$/", $text)) {
        $text = "0" . substr($text, 3); // convert +639 → 09
    }
    // ========================================

    // CHECK IF THE TEXT IS A VALID PH MOBILE NUMBER (11 digits)
    if (preg_match("/^09[0-9]{9}$/", $text)) {

        // Check if guardian_contact exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE guardian_contact = ?");
        $stmt->bind_param("s", $text);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {

            // Save chat_id
            $stmt2 = $conn->prepare("UPDATE students SET chat_id = ? WHERE guardian_contact = ?");
            $stmt2->bind_param("ss", $chat_id, $text);
            $stmt2->execute();

            // Confirm message
            file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=Your+Telegram+is+now+linked+to+the+school+system.");
        } else {
            // Not found in DB
            file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=Mobile+number+not+found+in+school+records.");
        }
    } else {
        // Not a valid mobile number
        file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=Please+send+your+registered+mobile+number+(ex:+09123456789)");
    }
}

echo "DONE";
