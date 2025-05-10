<?php
// config.php - Configuration settings


$config = [
    'database' => [
        'host' => $servername_db,
        'username' => $username_db,
        'password' => $password_db,
        'dbname' => $dbname_db
    ],
    'app' => [
        'debug' => true,
        'log_path' => 'logs/'
    ]
];

// db_connection.php - Database connection class
class Database {
    private $conn;
    
    public function __construct($config) {
        $this->conn = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['dbname']
        );
        
        if ($this->conn->connect_error) {
            throw new Exception("Connection failed: " . $this->conn->connect_error);
        }
        
        $this->conn->set_charset("utf8mb4");
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// logger.php - Logging utility
class Logger {
    private $debugLogFile;
    private $errorLogFile;
    
    public function __construct($debugLogPath, $errorLogPath) {
        $this->debugLogFile = $debugLogPath;
        $this->errorLogFile = $errorLogPath;
        
        // Create log directories if they don't exist
        $debugDir = dirname($debugLogPath);
        $errorDir = dirname($errorLogPath);
        
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }
        
        if (!is_dir($errorDir)) {
            mkdir($errorDir, 0755, true);
        }
    }
    
    public function debug($message) {
        $this->log($this->debugLogFile, "DEBUG", $message);
    }
    
    public function error($message) {
        $this->log($this->errorLogFile, "ERROR", $message);
    }
    
    private function log($file, $level, $message) {
        $timestamp = date("Y-m-d H:i:s");
        $logEntry = "[$timestamp] [$level] $message\n";
        file_put_contents($file, $logEntry, FILE_APPEND);
    }
}

// functions.php - Utility functions
function get_providers_domain_url_json($file_path, $provider_id) {
    $data = json_decode(file_get_contents($file_path), true);


echo "<h1>file_path: ".$file_path."</h1>";
echo "<h1>data: ".json_encode($data)."</h1>";
echo "<h1>providers id: ".$data[0]['id']."</h1>";

    foreach ($data['providers'] as $provider) {
        if ($provider['id'] == $provider_id) {
            return $provider['domain_url'];
        }
    }
    
    return '';
}

function getProvidersNameById_JSON($file_path, $provider_id) {
    $data = json_decode(file_get_contents($file_path), true);
    
    foreach ($data['providers'] as $provider) {
        if ($provider['id'] == $provider_id) {
            return $provider['name'];
        }
    }
    
    return '';
}

// Sanitize input
function sanitize_input($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize_input($value);
        }
    } else {
        $input = htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

// Validate input
function validate_input($input, $type) {
    switch ($type) {
        case 'text':
            return filter_var($input, FILTER_SANITIZE_STRING);
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT);
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT);
        default:
            return $input;
    }
}