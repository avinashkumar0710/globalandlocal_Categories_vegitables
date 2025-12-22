<?php
// includes/SimpleExcelReader.php
// Simple Excel/CSV reader - FIXED VERSION

class SimpleExcelReader {
    
    public static function read($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception('File not found');
        }
        
        // Detect file type by actual content, not just extension
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        // Also check extension as fallback
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Try to determine if it's CSV
        if ($extension === 'csv' || 
            $mimeType === 'text/csv' || 
            $mimeType === 'text/plain') {
            return self::readCSV($filePath);
        }
        
        // Try to determine if it's XLSX
        if ($extension === 'xlsx' || 
            $mimeType === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ||
            $mimeType === 'application/zip') {
            return self::readXLSX($filePath);
        }
        
        // If extension is xls (old Excel format)
        if ($extension === 'xls') {
            throw new Exception('Old Excel format (.xls) not supported. Please save as .xlsx or .csv');
        }
        
        throw new Exception('Unsupported file format. Please use .xlsx or .csv files.');
    }
    
    private static function readCSV($filePath) {
        $data = [];
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Try to detect delimiter
            $firstLine = fgets($handle);
            rewind($handle);
            
            // Count commas and semicolons
            $commas = substr_count($firstLine, ',');
            $semicolons = substr_count($firstLine, ';');
            $tabs = substr_count($firstLine, "\t");
            
            // Use the most common delimiter
            $delimiter = ',';
            if ($semicolons > $commas && $semicolons > $tabs) {
                $delimiter = ';';
            } elseif ($tabs > $commas && $tabs > $semicolons) {
                $delimiter = "\t";
            }
            
            // Read all rows
            while (($row = fgetcsv($handle, 10000, $delimiter)) !== false) {
                // Skip empty rows
                if (empty(array_filter($row, function($cell) { return trim($cell) !== ''; }))) {
                    continue;
                }
                $data[] = $row;
            }
            fclose($handle);
        }
        
        return $data;
    }
    
    private static function readXLSX($filePath) {
        // Check if ZIP extension is available
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZIP extension not enabled. Cannot read .xlsx files. Please use .csv format or enable ZIP extension in php.ini');
        }
        
        $zip = new ZipArchive();
        
        if ($zip->open($filePath) !== true) {
            throw new Exception('Cannot open Excel file. File may be corrupted.');
        }
        
        // Read shared strings
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        
        if ($sharedStringsXml !== false) {
            $xmlStrings = @simplexml_load_string($sharedStringsXml);
            if ($xmlStrings && isset($xmlStrings->si)) {
                foreach ($xmlStrings->si as $val) {
                    if (isset($val->t)) {
                        $sharedStrings[] = (string)$val->t;
                    } elseif (isset($val->r)) {
                        // Rich text
                        $text = '';
                        foreach ($val->r as $run) {
                            if (isset($run->t)) {
                                $text .= (string)$run->t;
                            }
                        }
                        $sharedStrings[] = $text;
                    }
                }
            }
        }
        
        // Read worksheet data
        $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        
        if ($worksheetXml === false) {
            throw new Exception('Cannot read worksheet data. Excel file may be corrupted.');
        }
        
        $xmlData = @simplexml_load_string($worksheetXml);
        
        if (!$xmlData) {
            throw new Exception('Cannot parse worksheet XML. Excel file may be corrupted.');
        }
        
        $data = [];
        
        if (isset($xmlData->sheetData) && isset($xmlData->sheetData->row)) {
            foreach ($xmlData->sheetData->row as $row) {
                $rowData = [];
                $maxCol = 0;
                
                if (isset($row->c)) {
                    foreach ($row->c as $cell) {
                        $cellValue = '';
                        
                        // Get cell reference (e.g., A1, B1)
                        $cellRef = (string)$cell['r'];
                        $colLetter = preg_replace('/[0-9]/', '', $cellRef);
                        $colIndex = self::columnIndexFromLetter($colLetter);
                        
                        // Keep track of max column
                        $maxCol = max($maxCol, $colIndex);
                        
                        // Get cell type
                        $cellType = isset($cell['t']) ? (string)$cell['t'] : '';
                        
                        if ($cellType === 's') {
                            // Shared string
                            if (isset($cell->v)) {
                                $stringIndex = (int)$cell->v;
                                $cellValue = isset($sharedStrings[$stringIndex]) ? $sharedStrings[$stringIndex] : '';
                            }
                        } elseif ($cellType === 'str') {
                            // Inline string
                            $cellValue = isset($cell->v) ? (string)$cell->v : '';
                        } elseif ($cellType === 'b') {
                            // Boolean
                            $cellValue = isset($cell->v) ? ((string)$cell->v === '1' ? 'TRUE' : 'FALSE') : '';
                        } else {
                            // Number or date
                            $cellValue = isset($cell->v) ? (string)$cell->v : '';
                            
                            // Check if it's a date
                            if (isset($cell['s'])) {
                                $styleId = (int)$cell['s'];
                                // If style indicates date format, convert Excel date to readable format
                                if ($styleId > 0 && is_numeric($cellValue) && $cellValue > 0) {
                                    // Excel dates are days since 1900-01-01
                                    // This is a simplified conversion
                                    $unixDate = ($cellValue - 25569) * 86400;
                                    if ($unixDate > 0) {
                                        $cellValue = date('Y-m-d H:i:s', $unixDate);
                                    }
                                }
                            }
                        }
                        
                        // Store cell value at correct column index
                        $rowData[$colIndex] = $cellValue;
                    }
                }
                
                // Fill missing columns with empty strings
                $finalRow = [];
                for ($i = 0; $i <= $maxCol; $i++) {
                    $finalRow[] = isset($rowData[$i]) ? $rowData[$i] : '';
                }
                
                // Skip completely empty rows
                if (!empty(array_filter($finalRow, function($cell) { return trim($cell) !== ''; }))) {
                    $data[] = $finalRow;
                }
            }
        }
        
        return $data;
    }
    
    private static function columnIndexFromLetter($letter) {
        $letter = strtoupper($letter);
        $index = 0;
        $length = strlen($letter);
        
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($letter[$i]) - ord('A') + 1);
        }
        
        return $index - 1; // Return 0-based index
    }
}
?>