<?php
/**
 * 数据库连接类 - 增强版（支持移动端兼容性）
 * 优化：实现单例模式避免重复创建连接
 */

class Database {
    private static $instance = null;
    private static $pdoInstance = null;
    private $host;
    private $db_name;
    private $username;
    private $password;
    
    // 私有构造函数，防止外部实例化
    private function __construct() {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
    }
    
    // 获取单例实例
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // 获取数据库连接（使用单例模式复用连接）
    public function getConnection() {
        // 如果已经有可用连接，直接返回
        if (self::$pdoInstance !== null) {
            try {
                // 检查连接是否有效
                self::$pdoInstance->query('SELECT 1');
                return self::$pdoInstance;
            } catch (PDOException $e) {
                // 连接已失效，尝试重新连接
                self::$pdoInstance = null;
            }
        }
        
        try {
            // 尝试使用当前配置连接
            self::$pdoInstance = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . DB_CHARSET,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 10, // 设置连接超时
                    // 开启持久连接
                    PDO::ATTR_PERSISTENT => true
                ]
            );
        } catch(PDOException $exception) {
            // 如果连接失败，尝试备用方案
            if (DEBUG_MODE) {
                echo "主连接失败: " . $exception->getMessage() . "<br>";
            }
            
            // 尝试备用主机配置
            $backupHosts = ['127.0.0.1', 'localhost'];
            foreach ($backupHosts as $host) {
                if ($host !== $this->host) {
                    try {
                        if (DEBUG_MODE) {
                            echo "尝试备用主机: $host<br>";
                        }
                        
                        self::$pdoInstance = new PDO(
                            "mysql:host=" . $host . ";dbname=" . $this->db_name . ";charset=" . DB_CHARSET,
                            $this->username,
                            $this->password,
                            [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                PDO::ATTR_EMULATE_PREPARES => false,
                                PDO::ATTR_TIMEOUT => 10,
                                PDO::ATTR_PERSISTENT => true
                            ]
                        );
                        
                        if (DEBUG_MODE) {
                            echo "备用主机连接成功: $host<br>";
                        }
                        
                        return self::$pdoInstance;
                    } catch(PDOException $backupException) {
                        if (DEBUG_MODE) {
                            echo "备用主机 $host 连接失败: " . $backupException->getMessage() . "<br>";
                        }
                        continue;
                    }
                }
            }
            
            // 所有连接尝试都失败
            if (DEBUG_MODE) {
                echo "所有连接尝试均失败: " . $exception->getMessage();
            } else {
                echo "数据库连接失败，请检查配置";
            }
            return null;
        }
        
        return self::$pdoInstance;
    }
    
    // 关闭数据库连接
    public function close() {
        self::$pdoInstance = null;
    }
    
    // 测试连接是否有效
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $stmt = $conn->prepare("SELECT 1");
                $stmt->execute();
                return true;
            }
        } catch(PDOException $e) {
            return false;
        }
        return false;
    }
}

// 全局数据库连接 - 确保$db是PDO对象
$database = Database::getInstance(); // 使用单例模式
$db = $database->getConnection();

// 为避免兼容性问题，确保$db是PDO对象而不是Database类
if (!($db instanceof PDO)) {
    // 如果第一次连接失败，尝试重新连接
    $db = $database->getConnection();
}

// 如果仍然无法连接，显示错误信息
if (!($db instanceof PDO)) {
    if (DEBUG_MODE) {
        echo "无法建立数据库连接，请检查配置文件和数据库服务";
    } else {
        echo "系统暂时无法访问，请稍后再试";
    }
    // 不退出，让应用程序决定如何处理
}

// 优化：设置PDO连接的SQL模式以提高性能
if ($db instanceof PDO) {
    try {
        // 禁用严格模式以提高性能
        $db->exec("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        // 优化连接字符集
        $db->exec("SET NAMES '" . DB_CHARSET . "' COLLATE '" . DB_CHARSET . "_general_ci'");
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            echo "数据库设置优化失败: " . $e->getMessage() . "<br>";
        }
    }
}
?>