<?php
class Database {
    private static $host = "";
    private static $port = ;
    private static $dbname = ""; // RDS database name
    private static $dbuser = "";   // RDS username
    private static $dbpass = "";   // RDS password
    
    private static $pdo = null;


    public static function connect() {
        if (self::$pdo === null) {
            $dsn = "pgsql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$dbname;
            try {
                self::$pdo = new PDO($dsn, self::$dbuser, self::$dbpass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (PDOException $e) {
                die("❌ Connection failed: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
?>
