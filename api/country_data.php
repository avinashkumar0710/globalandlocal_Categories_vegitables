<?php
require "../config/database.php";

header('Content-Type: application/json');

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Known country names for validation
    $validCountries = [
        'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Antigua and Barbuda', 
        'Argentina', 'Armenia', 'Australia', 'Austria', 'Azerbaijan', 'Bahamas', 
        'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin', 
        'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil', 'Brunei', 
        'Bulgaria', 'Burkina Faso', 'Burundi', 'Cabo Verde', 'Cambodia', 'Cameroon', 
        'Canada', 'Central African Republic', 'Chad', 'Chile', 'China', 'Colombia', 
        'Comoros', 'Congo', 'Costa Rica', 'Croatia', 'Cuba', 'Cyprus', 'Czech Republic', 
        'Denmark', 'Djibouti', 'Dominica', 'Dominican Republic', 'Ecuador', 'Egypt', 
        'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Eswatini', 'Ethiopia', 
        'Fiji', 'Finland', 'France', 'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 
        'Greece', 'Grenada', 'Guatemala', 'Guinea', 'Guinea-Bissau', 'Guyana', 'Haiti', 
        'Honduras', 'Hungary', 'Iceland', 'India', 'Indonesia', 'Iran', 'Iraq', 'Ireland', 
        'Israel', 'Italy', 'Jamaica', 'Japan', 'Jordan', 'Kazakhstan', 'Kenya', 'Kiribati', 
        'Korea', 'Kosovo', 'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia', 'Lebanon', 'Lesotho', 
        'Liberia', 'Libya', 'Liechtenstein', 'Lithuania', 'Luxembourg', 'Madagascar', 
        'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Mauritania', 
        'Mauritius', 'Mexico', 'Micronesia', 'Moldova', 'Monaco', 'Mongolia', 'Montenegro', 
        'Morocco', 'Mozambique', 'Myanmar', 'Namibia', 'Nauru', 'Nepal', 'Netherlands', 
        'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'North Macedonia', 'Norway', 'Oman', 
        'Pakistan', 'Palau', 'Palestine', 'Panama', 'Papua New Guinea', 'Paraguay', 'Peru', 
        'Philippines', 'Poland', 'Portugal', 'Qatar', 'Romania', 'Russia', 'Rwanda', 
        'Saint Kitts and Nevis', 'Saint Lucia', 'Saint Vincent and the Grenadines', 'Samoa', 
        'San Marino', 'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 'Serbia', 
        'Seychelles', 'Sierra Leone', 'Singapore', 'Slovakia', 'Slovenia', 'Solomon Islands', 
        'Somalia', 'South Africa', 'South Sudan', 'Spain', 'Sri Lanka', 'Sudan', 'Suriname', 
        'Sweden', 'Switzerland', 'Syria', 'Taiwan', 'Tajikistan', 'Tanzania', 'Thailand', 
        'Timor-Leste', 'Togo', 'Tonga', 'Trinidad and Tobago', 'Tunisia', 'Turkey', 
        'Turkmenistan', 'Tuvalu', 'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom', 
        'United States', 'Uruguay', 'Uzbekistan', 'Vanuatu', 'Vatican City', 'Venezuela', 
        'Vietnam', 'Yemen', 'Zambia', 'Zimbabwe'
    ];
    
    // Common variations and aliases
    $countryAliases = [
        'USA' => 'United States',
        'UK' => 'United Kingdom',
        'UAE' => 'United Arab Emirates',
        'Korea' => 'South Korea',
        'Britain' => 'United Kingdom',
        'Holland' => 'Netherlands',
        'Deutschland' => 'Germany',
        'Russian Federation' => 'Russia',
        'People Republic Of China' => 'China'
    ];
    
    // Fetch all tables
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Filter out sensitive tables
    $sensitiveTables = ['activity_log', 'admin_login'];
    $tables = array_filter($allTables, function($table) use ($sensitiveTables) {
        return !in_array($table, $sensitiveTables);
    });
    
    // Common country column names we might find
    $countryColumns = [
        'country', 'country_name', 'nation', 'market', 'destination', 
        'import_country', 'export_country', 'trading_partner', 'partner'
    ];
    
    $countries = [];
    
    // Check each table for country data
    foreach ($tables as $table) {
        // Get column names
        $colsStmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        $columns = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Look for country-related columns
        $countryColumn = null;
        foreach ($columns as $column) {
            $lowerColumn = strtolower($column);
            foreach ($countryColumns as $countryCol) {
                if (strpos($lowerColumn, $countryCol) !== false) {
                    $countryColumn = $column;
                    break 2;
                }
            }
        }
        
        // If we found a country column, fetch distinct country values
        if ($countryColumn) {
            $countryStmt = $pdo->query("SELECT DISTINCT `$countryColumn` FROM `$table` WHERE `$countryColumn` IS NOT NULL AND `$countryColumn` != ''");
            $countryRows = $countryStmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($countryRows as $country) {
                $country = trim($country);
                
                // Normalize the country name
                $normalizedCountry = ucwords(strtolower($country));
                
                // Check for aliases
                if (isset($countryAliases[$normalizedCountry])) {
                    $normalizedCountry = $countryAliases[$normalizedCountry];
                }
                
                // Validate against known countries
                if (in_array($normalizedCountry, $validCountries)) {
                    if (!isset($countries[$normalizedCountry])) {
                        $countries[$normalizedCountry] = [
                            'name' => $normalizedCountry,
                            'tables' => []
                        ];
                    }
                    if (!in_array($table, $countries[$normalizedCountry]['tables'])) {
                        $countries[$normalizedCountry]['tables'][] = $table;
                    }
                }
            }
        }
    }
    
    // Country to ISO code mapping for map visualization
    $countryCodes = [
        'United States' => 'US',
        'Canada' => 'CA',
        'Mexico' => 'MX',
        'Brazil' => 'BR',
        'Argentina' => 'AR',
        'Chile' => 'CL',
        'Peru' => 'PE',
        'Colombia' => 'CO',
        'Venezuela' => 'VE',
        'United Kingdom' => 'GB',
        'Germany' => 'DE',
        'France' => 'FR',
        'Italy' => 'IT',
        'Spain' => 'ES',
        'Netherlands' => 'NL',
        'Belgium' => 'BE',
        'Switzerland' => 'CH',
        'Austria' => 'AT',
        'Sweden' => 'SE',
        'Norway' => 'NO',
        'Denmark' => 'DK',
        'Finland' => 'FI',
        'Russia' => 'RU',
        'China' => 'CN',
        'India' => 'IN',
        'Japan' => 'JP',
        'South Korea' => 'KR',
        'Australia' => 'AU',
        'New Zealand' => 'NZ',
        'Indonesia' => 'ID',
        'Thailand' => 'TH',
        'Malaysia' => 'MY',
        'Singapore' => 'SG',
        'Philippines' => 'PH',
        'Vietnam' => 'VN',
        'Turkey' => 'TR',
        'Saudi Arabia' => 'SA',
        'UAE' => 'AE',
        'Egypt' => 'EG',
        'South Africa' => 'ZA',
        'Nigeria' => 'NG',
        'Kenya' => 'KE',
        'Israel' => 'IL',
        'Iran' => 'IR',
        'Iraq' => 'IQ',
        'Pakistan' => 'PK',
        'Bangladesh' => 'BD',
        'Sri Lanka' => 'LK',
        'Myanmar' => 'MM',
        'Cambodia' => 'KH',
        'Laos' => 'LA',
        'Brunei' => 'BN',
        'Papua New Guinea' => 'PG',
        'Fiji' => 'FJ',
        'Poland' => 'PL',
        'Czech Republic' => 'CZ',
        'Hungary' => 'HU',
        'Portugal' => 'PT',
        'Greece' => 'GR',
        'Ireland' => 'IE',
        'Ukraine' => 'UA'
    ];
    
    // Add ISO codes to countries
    foreach ($countries as &$country) {
        $country['iso_code'] = isset($countryCodes[$country['name']]) ? $countryCodes[$country['name']] : null;
        $country['table_count'] = count($country['tables']);
    }
    
    // Sort countries by table count (descending)
    usort($countries, function($a, $b) {
        return $b['table_count'] - $a['table_count'];
    });
    
    echo json_encode([
        'success' => true,
        'countries' => array_values($countries),
        'total_countries' => count($countries)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>