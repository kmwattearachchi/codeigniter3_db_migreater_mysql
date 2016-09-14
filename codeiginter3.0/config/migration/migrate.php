<?php
$system_path = 'system';
if (($_temp = realpath($system_path)) !== FALSE)
{
    $system_path = $_temp.DIRECTORY_SEPARATOR;
}
else
{
    // Ensure there's a trailing slash
    $system_path = strtr(
            rtrim($system_path, '/\\'),
            '/\\',
            DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
        ).DIRECTORY_SEPARATOR;
}
$root_path =  str_replace("//","",strval(preg_replace('/system/','',$system_path)));
define('BASEPATH',$root_path);
//include_once BASEPATH."/application/config/database.php";


$htaccess = file( BASEPATH . '/.htaccess');
//echo '<pre>';
foreach ($htaccess as $line) {
    if (preg_match('/SetEnv CI_ENV/i', $line)) {
        $data   =   explode(' ',$line);
        define('CI_ENV',$data[2]);
        define('ENVIRONMENT',$data[2]);
    }
}

if(!empty(CI_ENV)){
    if(file_exists(BASEPATH."/application/config/".CI_ENV."/database.php")){
        include_once BASEPATH."/application/config/".CI_ENV."/database.php";
    }else{
        include_once BASEPATH."/application/config/database.php";
    }
}else{
    include_once BASEPATH."/application/config/database.php";
}

/**
 * Project root path
 */
define('ROOT_PATH',$root_path);

/**
 * Class Migrations
 *
 * @author Kalana Wattearachchi
 */
class Migrations{
    /*
     * Database connection obj
     *
     * @var null|PDO
     */
    private $db_conn        =   null ;

    /*
     * DB host
     *
     * @var string
     */
    private $host           =   DATABASE_HOST ;

    /*
     * Migration db
     *
     * @var string
     */
    private $db             =   DATABASE_DB ;

    /*
     * DB user
     *
     * @var string
     */
    private $user           =   DATABASE_USER ;

    /*
     * DB password
     *
     * @var string
     */
    private $psw            =   DATABASE_PSW ;

    /*
     * Migration file directory
     *
     * @var string
     */
    private $migrations_path = '/sql';

    /**
     * Initialize PDO object
     *
     * @params null
     * @throws PDOException
     */
    function __construct(){
        try{
            /*if(!is_cli())
            {
                echo 'Only CLI requests are allowed!!!!!';
                exit();
            }*/
            $this->db_conn = new PDO("mysql:host=".$this->host.";dbname=".$this->db."", $this->user, $this->psw);
            $this->db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch (PDOException $ex){
            echo "Connection failed: " . $ex->getMessage();
            exit;
        }
    }

    /**
     * Run migrations
     *
     * @params null
     */
    public function doMigrations(){
        if($migrations = self::checkIsMigrationsExists($this->migrations_path)){

            //Is migration table exists.If not create the table
            $migrations_already_driven = $this->isMigrationTableExists();

            echo "####ENV : ".CI_ENV."#####DB :".DATABASE_DB."########Migration started :".date('Y-m-d h:m:s')."###################################";;
            foreach($migrations as $phase_key=>$phase){
                foreach($phase as $file_key=>$file){
                    $sql_file = ROOT_PATH.$this->migrations_path.'/'.$phase_key.'/'.$file;
                    if($this->isPreviouslyExecuted($migrations_already_driven,$file) == false){
                        echo "Executing :".$file;
                        $sql = file_get_contents($sql_file);
                        //if($this->db_conn->exec($sql)){
                            $this->db_conn->exec($sql);
                            if($this->updateMigrationsTbl($phase_key,$file)){
                                echo "Done.";
                            }
                        //}
                    }
                }
            }
            echo "##################Migration Finished :".date('Y-m-d h:m:s')."###################################";
        }
    }

    /**
     * Update executed migrations to table
     *
     * @param $phase_name release phase
     * @param $file sql file name
     * @return bool status
     */
    private function updateMigrationsTbl($phase_name,$file){
        try{
            $sql = "INSERT INTO `migrations` (`id`, `phase`, `file_name`, `execution_datetime`, `status`) VALUES (NULL, '$phase_name', '$file', '".date('Y-m-d h-m-s')."', 'executed')";
            $this->db_conn->exec($sql);
            return true;
        }catch (Exception $ex){
            echo "Update migrations tbl failed: " . $ex->getMessage();
        }
    }

    /**
     * Check is file already executed
     *
     * @param $migrations_already_driven Already driven migrations
     * @param $sql_file Current sql file
     * @return bool status
     */
    private function isPreviouslyExecuted($migrations_already_driven,$sql_file){
        if($migrations_already_driven){
            if(in_array($sql_file,$migrations_already_driven) == true){
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * Check is migrations exists in the project
     *
     * @param $migrations_path sql directory
     * @return array
     */
    private function checkIsMigrationsExists($migrations_path){
        // Check whether is there any sql files to run
        $migrations = self::dirToArray(ROOT_PATH.$migrations_path);

        if(!empty($migrations)){
            return $migrations;
        }else{
            echo "No migrations found";
        }
    }

    /**
     * Is migration table exists.If not create.
     * If exist return old migration details
     *
     * @return array
     */
    private function isMigrationTableExists(){

        $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
                  `id` int(5) NOT NULL AUTO_INCREMENT,
                  `phase` varchar(30) NOT NULL,
                  `file_name` varchar(255) NOT NULL,
                  `execution_datetime` datetime NOT NULL,
                  `status` varchar(20) NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `file_name` (`file_name`)
                );ALTER TABLE  `migrations` ADD UNIQUE (`file_name`);";
        $this->db_conn->exec($sql);

        $sql = 'SELECT * FROM migrations ORDER BY id';
        if($migrations  =  $this->db_conn->query($sql)->fetchAll(PDO::FETCH_ASSOC)){
            $executed_file_list =   [];
            foreach($migrations as $key=>$file){
                $executed_file_list[] = $file['file_name'];
            }
            return $executed_file_list;
        }
    }

    /**
     * Get all sql files in a directory
     *
     * @param $dir directory name
     * @return array
     */
    private function dirToArray($dir) {

        $result = array();

        $cdir = scandir($dir);
        foreach ($cdir as $key => $value)
        {
            if (!in_array($value,array(".","..")))
            {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
                {
                    $result[$value] = self::dirToArray($dir . DIRECTORY_SEPARATOR . $value);
                }
                else
                {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }
}


/*
 * Run migrations
 *
 * All the migration sql files will be executed to the system database
 * */
$migration = new Migrations();
$migration->doMigrations();