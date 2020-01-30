<?php

class Tools extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->input->is_cli_request() or exit(":)");
    }

    public function index()
    {
        return $this->list();
    }

    public function list()
    {
        echo "List of Tools command:" . PHP_EOL . PHP_EOL;
        echo "1. make model <Model> [-m|--migration]" . PHP_EOL;
        echo "   -- to make a model. [-m|--migration] to create the migration file " . PHP_EOL . PHP_EOL;
        echo "2. make controller <Controller>" . PHP_EOL;
        echo "   -- to make a controller." . PHP_EOL . PHP_EOL;
        echo "3. make migration <Migration_name>" . PHP_EOL;
        echo "   -- to make a migration." . PHP_EOL . PHP_EOL;
        echo "4. migrate [reset]" . PHP_EOL;
        echo "   -- to run migration. [reset] to run migration from the beginning." . PHP_EOL . PHP_EOL;
        echo "5. seed" . PHP_EOL;
        echo "   -- to run the seeder. Seeder available on application/libraries/Seeder.php" . PHP_EOL . PHP_EOL;
        echo "6. list" . PHP_EOL;
        echo "   -- you currently run the command." . PHP_EOL . PHP_EOL;
    }

    public function migrate(...$argc)
    {
        $this->load->library('migration');
        foreach ($argc as $string) {
            if( $string == 'reset' ) {
                $this->migration->version("0");
                echo "Reset migration" . PHP_EOL;
                break;
            }
        }
        if ( $this->migration->latest() ) {
            echo "Success migrate" . PHP_EOL;
        } else {
            show_error($this->migration->error_string());
        }
    }

    public function make($type = null, ...$argc)
    {
        $type = strtolower($type);
        switch ($type) {
            case 'migration':
                if( is_dir( $migrationPath = APPPATH . DS . 'migrations' ) ) {
                    $this->makeMigration($migrationPath, $argc);
                    echo "Success make migrations" . PHP_EOL;
                } else {
                    echo "No migrations directory" . PHP_EOL;
                }
                break;
            case 'controller':
                if( is_dir( $controllerPath = APPPATH . DS . 'controllers' ) ) {
                    if( is_file($controllerPath . DS . $argc[0] . '.php') ) {
                        echo "The " . $argc[0] .  " controller is exists" . PHP_EOL;
                    } else {
                        $this->makeController($controllerPath, $argc);
                        echo "Success make controller." . PHP_EOL;
                    }
                }
                break;
            case 'view':
                
                break;
            case 'model':
                if( is_dir( $modelPath = APPPATH . DS . 'models' ) ) {
                    if( is_file( $modelPath . DS . $argc[0] . '.php' ) ) {
                        echo "The " . $argc[0] . " model is exists" . PHP_EOL;
                    } else {
                        $this->makeModel($modelPath, $argc);
                        foreach ($argc as $string) {
                            if( $string == '-m' OR $string == '--migration' ) {
                                $tableName = $this->generateTableName($argc[0]);
                                $this->make('migration', 'create_' . $tableName . '_table');
                                break;
                            }
                        }
                        echo "Success make model." . PHP_EOL;
                    }
                }
                break;
            default:
                # code...
                break;
        }
    }

    private function generateTableName($string)
    {
        $return = '';
        if( strlen($string) > 1 ) {
            for ($i=0; $i < strlen($string); $i++) { 
                $a = $string[$i];
                if( $i != 0 AND preg_match("/[A-Z]+/", $a, $output) ) {
                    $return .= "_";
                }
                if( $i == (strlen($string) - 1) AND $a == 'y' ) {
                    $a = 'ie';
                }
                $return .= $a;
            }
            $return .= 's';
        }
        return strtolower($return);
    }

    private function makeController($controllerPath, $argc)
    {
        $controllerFile = fopen( $controllerPath . DS . $argc[0] . ".php", "w") or die("Unable to open file!");
        $content = "<?php\n\nclass " . $argc[0] . " extends CI_Controller\n{\n";
        if( count($argc) > 1 ) {
            foreach ($argc as $key => $method) {
                if( $key != 0 ) {
                    $content .= $this->makeMethod($method);
                }
            }
        } else {
            $content .= $this->makeMethod("index");
        }
        $content .= "}";
        fwrite($controllerFile, $content);
        fclose($controllerFile);
    }

    private function makeMigration($migrationPath, $argc)
    {
        $timestamp = date('YmdHis');
        $checkName = $this->checkMigrationName($argc);
        $controllerFile = fopen( $migrationPath . DS . $timestamp . '_' . $argc[0] . ".php", "w") or die("Unable to open file!");
        $content = "<?php\n\nclass Migration_" . ucfirst($argc[0]) . " extends CI_Migration\n{\n";
            $content .= "\n    public function up()\n    {\n\n";
            if( $checkName !== false 
                AND isset( $checkName['function'] ) 
                AND $checkName['function'] == 'create' ) {
                    $content .= "        \$this->dbforge->add_field([\n            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true]\n            ]);\n";
                    $content .= "        \$this->dbforge->add_key('id', true);\n";
                    $content .= "        \$this->dbforge->create_table('".$checkName['name']."');\n";
            } else {
                $content .= "        //\n";
            }
            $content .= "    }\n";
            $content .= "\n    public function down()\n    {\n";
            if( $checkName !== false
                AND isset( $checkName['function'] ) 
                AND $checkName['function'] == 'create' ) {
                    $content .= "        \$this->dbforge->drop_table('".$checkName['name']."');\n";
                } else {
                    $content .= "        //\n";
                }
            $content .= "    }\n";
        $content .= "}";
        fwrite($controllerFile, $content);
        fclose($controllerFile);
    }

    private function checkMigrationName($argc)
    { 
        if( count($argc) <= 1 ) {
            $explode = explode("_", $argc[0]);
            if( count($explode) >= 3 ) {
                $lastIndex = count($explode) - 1;
                if( strtolower($explode[0]) == 'create' AND strtolower($explode[$lastIndex]) == 'table' ) {
                    unset($explode[0]); unset($explode[$lastIndex]);
                    $name = implode('_', $explode);
                    return ['function' => 'create', 'type' => 'table', 'name' => trim($name)];
                }
            }
        }
        return false;
    }

    private function makeMethod($name)
    {
        $string = "\n    public function " . $name . "()\n";
        $string .= "    {\n";
        $string .= "        // " . $name . " method\n";
        $string .= "    }\n";
        return $string;
    }

    private function makeModel($modelPath, $argc, $table = '')
    {
        $modelFile = fopen( $modelPath . DS . $argc[0] . ".php", "w") or die("Unable to open file!");
        $content = "<?php\n\nclass " . $argc[0] . " extends CI_Model\n{\n";
        if( $table == '' ) {
            $table = $this->generateTableName($argc[0]);
        }
        $content .= "    protected \$table = \"" . $table . "\";\n    ";
        if( count($argc) > 1 ) {
            foreach ($argc as $key => $method) {
                if( $key != 0 ) {
                    if( $method != '-m' AND $method != '--migration' ) {
                        $content .= $this->makeMethod($method);
                    }
                }
            }
        }
        $content .= "\n}";
        fwrite($modelFile, $content);
        fclose($modelFile);
        return true;
    }

    public function seed()
    {
        $this->load->library(['seeder']);
        try {
            $this->seeder->run();
            echo "Seeding completed" . PHP_EOL;
        } catch (\Exception $e) {
            echo "An error has occured " . $e->getMessage() . PHP_EOL;
        } 
    }
}
