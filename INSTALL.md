# Quick Installation Guide
## Stock Management System (SMS)

### 🚀 Quick Start (5 Minutes)

#### Step 1: Extract Files
Extract all SMS files to your web server directory:
- **XAMPP/WAMP**: `C:\xampp\htdocs\sms\`
- **Linux**: `/var/www/html/sms/`
- **Shared Hosting**: Upload to your domain folder

#### Step 2: Create Database
Open phpMyAdmin or MySQL command line:
```sql
CREATE DATABASE stock_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### Step 3: Import Database Schema
In phpMyAdmin:
1. Select your `stock_management` database
2. Click "Import"
3. Choose `database_schema.sql` file
4. Click "Go"

Or via command line:
```bash
mysql -u root -p stock_management < database_schema.sql
```

#### Step 4: Configure Database
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');        // Your MySQL host
define('DB_NAME', 'stock_management'); // Database name
define('DB_USER', 'root');             // Your MySQL username
define('DB_PASS', '');                 // Your MySQL password
```

#### Step 5: Set Permissions
Make QR code directory writable:
- **Windows**: Right-click `assets/qrcodes` → Properties → Security → Give "Full Control"
- **Linux**: `chmod 755 assets/qrcodes/`

#### Step 6: Access System
Open your browser and go to:
- **Local**: `http://localhost/sms/`
- **Domain**: `http://yourdomain.com/sms/`

**Default Login:**
- Username: `admin`
- Password: `admin123`

### ✅ Verification Checklist
- [ ] Database connection working
- [ ] Can login successfully
- [ ] Can create a product
- [ ] QR code generation works
- [ ] Camera scanner accessible
- [ ] Stock logs recording

### 🔧 Common Issues & Solutions

**"Database connection failed"**
→ Check database credentials in `config/database.php`

**"Permission denied" errors**
→ Set proper permissions on `assets/qrcodes/` directory

**QR Scanner not working**
→ Enable HTTPS or test camera permissions in browser

**Blank pages**
→ Check PHP error logs, enable error display for debugging

### 🚀 Production Deployment

For live websites:
1. **Enable HTTPS** (required for camera API)
2. **Change default password** immediately
3. **Set secure file permissions**
4. **Configure regular backups**
5. **Update database credentials**

### 📞 Need Help?
Check the complete `README.md` file for detailed documentation, troubleshooting, and advanced configuration options.