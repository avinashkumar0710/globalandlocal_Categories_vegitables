# Database Manager - Installation Guide

## 📁 Project Structure
```
database-manager/
├── config/
│   └── database.php
├── includes/
│   └── functions.php
├── vendor/               (created by Composer)
├── composer.json
├── login.php
├── index.php
├── upload.php           (NEW - Excel Upload)
├── api.php
└── logout.php
```

## 🚀 Installation Steps

### Step 1: Install Composer (if not already installed)
Download from: https://getcomposer.org/download/

### Step 2: Install Dependencies
Open terminal/command prompt in your project folder and run:
```bash
composer install
```

This will install the PhpSpreadsheet library needed for Excel file processing.

### Step 3: Configure Database
Edit `config/database.php` and update your credentials:
```php
private $host = "localhost";
private $db_name = "your_database_name";
private $username = "your_username";
private $password = "your_password";
```

### Step 4: Set File Upload Permissions
Make sure your PHP configuration allows file uploads:

Edit `php.ini`:
```ini
file_uploads = On
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

### Step 5: Access the Application
1. Open browser: `http://localhost/your-project/login.php`
2. Login with: **admin** / **admin123**
3. Click "Upload Excel" button to import data

## 📊 Excel Upload Features

### Automatic Column Detection
- First row = Column names
- Data types auto-detected (INT, VARCHAR, TEXT, DATE, etc.)
- Creates table structure automatically

### Supported File Formats
- `.xlsx` (Excel 2007+)
- `.xls` (Excel 97-2003)
- `.csv` (Comma Separated Values)

### Excel File Example
```
| name    | email              | age | created_at |
|---------|--------------------|-----|------------|
| John    | john@email.com     | 25  | 2024-01-15 |
| Sarah   | sarah@email.com    | 30  | 2024-01-16 |
| Michael | michael@email.com  | 28  | 2024-01-17 |
```

### Usage Options

**Option 1: Create New Table**
- Check "Create New Table"
- System creates table with auto-detected columns
- Imports all data

**Option 2: Add to Existing Table**
- Uncheck "Create New Table"
- Excel columns must match existing table columns
- Appends data to existing table

## ✨ Features Available After Upload

### Table Management
- ✅ Delete entire table
- ✅ Rename table
- ✅ View table structure

### Column Management
- ✅ Add new columns
- ✅ Delete columns
- ✅ View column types and properties

### Row/Data Management
- ✅ View all rows
- ✅ Add new rows manually
- ✅ Edit existing rows
- ✅ Delete rows

## 🔒 Security Notes

1. **Change Default Login** in `login.php`:
```php
$valid_username = "your_username";
$valid_password = "your_secure_password";
```

2. **Production Recommendations**:
   - Use password hashing (`password_hash()`)
   - Add CSRF protection
   - Implement rate limiting
   - Use HTTPS
   - Restrict file upload types
   - Add user roles and permissions

## ⚠️ Troubleshooting

### "Class PhpOffice\PhpSpreadsheet not found"
**Solution**: Run `composer install` in project directory

### "File upload failed"
**Solution**: Check PHP file upload settings and folder permissions

### "Table already exists"
**Solution**: Uncheck "Create New Table" or use different table name

### "Column mismatch error"
**Solution**: Ensure Excel columns match existing table structure

## 📝 Sample Excel Templates

### Template 1: Users Table
```
name | email | phone | city | country
```

### Template 2: Products Table
```
product_name | price | quantity | category | supplier
```

### Template 3: Orders Table
```
order_id | customer_name | product | amount | order_date | status
```

## 🎯 Best Practices

1. **Column Names**: Use lowercase with underscores (e.g., `first_name`)
2. **Data Validation**: Clean data before upload
3. **Backup**: Always backup database before bulk imports
4. **Testing**: Test with small files first
5. **Monitoring**: Check import results and error messages

## 📞 Support

For issues or questions:
- Check error messages in the upload page
- Review PHP error logs
- Verify database connection
- Ensure Composer dependencies are installed