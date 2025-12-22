// app/Controllers/TableController.php
class TableController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function index() {
        $tables = $this->getAllTables();
        require_once 'app/Views/tables/index.php';
    }
    
    public function view($tableName) {
        $data = $this->getTableData($tableName);
        require_once 'app/Views/tables/view.php';
    }
}