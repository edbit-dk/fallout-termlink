<?php

function getVersionInfo() {
    return file_get_contents('sys/var/version.txt');
}

// Function to get help information for commands
function getHelpInfo($command) {

    if(!isset($_SESSION['loggedIn'])) {
        $helpInfo = include 'sys/lib/help/guest.php';
    } else {
        $helpInfo = include 'sys/lib/help/auth.php';
    }

    $command = strtoupper($command);
    
    if (!empty($command)) {
        return isset($helpInfo[$command]) ? $helpInfo[$command] : "Command not found.";
    }
    $helpText = "HELP:\n";
    foreach ($helpInfo as $cmd => $description) {
        $helpText .= " $cmd $description\n";
    }
    return $helpText;
}

function scanNodes($number) {
    global $server;

    $nodes = $server['nodes'];
    
    if (!empty($number)) {
        return isset($nodes[$number]) ? $nodes[$number] : "Terminal not found.";
    }
    $terminal = "Searching PoseidoNet Comlinks Stations for Nodes...\n";

    foreach ($nodes as $node => $description) {
        $terminal .= " $node: $description\n";
    }
    return $terminal;
}

function listAccounts($data = 5) {
    global $server;

    $terminal = "RUN LIST/ACCOUNTS.F '{$data}'\n";

    if (file_exists("user/{$data}.json")) {
        $accounts = json_decode(file_get_contents("user/{$data}.json"), true);
        
        foreach ($accounts as $account => $info) {
            $terminal .= " $account: $info\n";
        }

        return $terminal;
    
    } else {
        $accounts = $server['accounts']; 
    }

    $i = 0;
    foreach ($accounts as $account => $password) {

        if(file_exists("user/{$account}.json")) {
            $user = json_decode(file_get_contents("user/{$account}.json"), true);
            $user_xp = $user['XP'];
            $terminal .= " $account ($user_xp XP): $password\n";
            
        }

        $i++;
        if($i == $data) {
            break;
        }

    }
    return $terminal;
}

function listUsers($data = '') {

    // Directory containing JSON files
$directory = 'user';

    // Open the directory
$dir = opendir($directory);

// Loop through each file in the directory
while (($file = readdir($dir)) !== false) {
    // Skip . and .. special files
    if ($file == '.' || $file == '..') {
        continue;
    }

    // Construct the full path to the file
    $filePath = $directory . '/' . $file;

    // Ensure the file is a regular file
    if (is_file($filePath)) {
        // Read the file contents
        $jsonContents = file_get_contents($filePath);

        // Decode the JSON data
        $data = json_decode($jsonContents, true);

        // Ensure the JSON data was decoded successfully
        if ($data === null) {
            echo "Failed to decode JSON from file: $file\n";
            continue;
        }

        // Extract and output the NAME and XP fields
        $name = $data['ID'] ?? 'Unknown';
        $xp = $data['XP'] ?? 0;

        echo "$name ($xp)\n";
    }
}

// Close the directory
closedir($dir);
}

function emailUser($data) {

    $from_user = $_SESSION['USER']['ID'];

    $parts = explode('<', trim($data), 2);
    $subject = strtoupper(explode(' ', $parts[0])[0]);
    $email = explode(' ', $parts[0])[1];
    $to_user = explode('@', $email)[0];
    $node = explode('@', $email)[1];
    $body = trim($parts[1]);

    // Check if the filename is empty
    if (empty($subject)) {
        return "ERROR: Subject Missing.";
    }

    // Construct the full path
    $path = "home/{$node}/{$to_user}/";

    // Check if the file exists, if not, create it
    if (!file_exists($path)) {
        return "ERROR: User Missing.";
    }

    $email = <<< EOT
    Subject: "{$subject}"
    To: {$to_user}
    From: {$from_user}

    {$body}
    EOT;

    // Write content to the file
    if (file_put_contents($path . $subject . '.mail', $email) !== false) {
        return "SENDING EMAIL: $subject";
    } else {
        return "ERROR: Failed Sending Email!";
    }
}