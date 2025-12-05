<?php
// test_upload.php - Debug file upload issues
require_once 'includes/SimpleExcelReader.php';

$info = [];
$data = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    $file = $_FILES['test_file'];
    
    // Collect debug information
    $info['File Name'] = $file['name'];
    $info['File Size'] = number_format($file['size']) . ' bytes (' . number_format($file['size'] / 1024, 2) . ' KB)';
    $info['File Type (Browser)'] = $file['type'];
    $info['Temp Name'] = $file['tmp_name'];
    $info['Error Code'] = $file['error'];
    $info['File Extension'] = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check if file exists
    $info['File Exists'] = file_exists($file['tmp_name']) ? 'Yes' : 'No';
    
    // Get MIME type
    if (file_exists($file['tmp_name'])) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $info['MIME Type (Server)'] = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    }
    
    // Check ZIP extension
    $info['ZIP Extension'] = class_exists('ZipArchive') ? 'Enabled ✅' : 'Disabled ❌';
    
    // Try to read the file
    if ($file['error'] === UPLOAD_ERR_OK && file_exists($file['tmp_name'])) {
        try {
            $data = SimpleExcelReader::read($file['tmp_name']);
            $info['Read Status'] = 'Success ✅';
            $info['Rows Read'] = count($data);
            if (!empty($data)) {
                $info['Columns'] = count($data[0]);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            $info['Read Status'] = 'Failed ❌';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Test - Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-warning">
                <h4 class="mb-0"><i class="bi bi-bug"></i> Upload Debug Test</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Purpose:</strong> This page helps identify why file uploads are failing.
                    Upload a test file to see detailed information.
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Upload Test File (.xlsx or .csv)</label>
                        <input type="file" class="form-control" name="test_file" accept=".xlsx,.csv" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Test Upload
                    </button>
                    <a href="upload.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Upload
                    </a>
                </form>

                <?php if (!empty($info)): ?>
                    <hr>
                    <h5>📊 Debug Information</h5>
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <?php foreach ($info as $key => $value): ?>
                                <tr>
                                    <th style="width: 30%"><?php echo $key; ?></th>
                                    <td><?php echo htmlspecialchars($value); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <strong>❌ Error:</strong> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($data !== null && !empty($data)): ?>
                        <h5 class="mt-4">✅ File Content Preview (First 10 rows)</h5>
                        <div style="overflow-x: auto;">
                            <table class="table table-bordered table-sm">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <?php foreach ($data[0] as $i => $header): ?>
                                            <th>Col <?php echo $i + 1; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $previewRows = array_slice($data, 0, 10);
                                    foreach ($previewRows as $rowIndex => $row): 
                                    ?>
                                        <tr>
                                            <td><?php echo $rowIndex + 1; ?></td>
                                            <?php foreach ($row as $cell): ?>
                                                <td><?php echo htmlspecialchars($cell); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <hr>
                <div class="alert alert-secondary">
                    <strong>PHP Configuration:</strong><br>
                    <strong>upload_max_filesize:</strong> <?php echo ini_get('upload_max_filesize'); ?><br>
                    <strong>post_max_size:</strong> <?php echo ini_get('post_max_size'); ?><br>
                    <strong>max_execution_time:</strong> <?php echo ini_get('max_execution_time'); ?>s<br>
                    <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
                    <strong>ZIP Extension:</strong> <?php echo class_exists('ZipArchive') ? '✅ Enabled' : '❌ Disabled'; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>