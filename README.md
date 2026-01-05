# Stock Management System (SMS)

A production-ready Stock Management System built with pure PHP, HTML, CSS, and vanilla JavaScript. No frameworks, no external dependencies - just clean, secure, and maintainable code.

## 🚀 Features

### Core Functionality
- **Secure Admin Authentication** with role-based access control
- **Complete Product Management** (CRUD operations)
- **QR Code Generation** with printable labels
- **QR Code Scanner** using device camera
- **Real-time Stock Updates** via QR scanning
- **Comprehensive Stock History Logging**
- **Advanced Search & Filtering**
- **Data Export** (CSV & HTML formats)

### Security Features
- Password hashing with `password_hash()` and `password_verify()`
- CSRF protection on all forms
- Session management with regeneration
- PDO prepared statements only
- Input sanitization and output escaping
- SQL injection prevention
- XSS protection

### Technical Highlights
- **Pure PHP** - No Laravel, no frameworks
- **Vanilla JavaScript** - No React, no external libraries
- **Responsive Design** - Mobile-friendly interface
- **Database-driven** - MySQL with proper relationships
- **Production-ready** - Error handling, logging, validation

## 📋 Requirements

### Server Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- mod_rewrite enabled (for clean URLs)

### Browser Requirements
- Modern browser with ES6 support
- Camera access for QR scanning
- JavaScript enabled

## 🛠️ Installation

### 1. Download and Extract
```bash
# Extract the SMS files to your web directory
# For example: /var/www/html/sms/ or C:\xampp\htdocs\sms\
```

### 2. Database Setup
```sql
-- Create MySQL database
CREATE DATABASE stock_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import the database schema
mysql -u root -p stock_management < database_schema.sql
```

### 3. Configure Database Connection
Edit `config/database.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'stock_management');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 4. Set Directory Permissions
```bash
# Make QR code directory writable
chmod 755 assets/qrcodes/
chown www-data:www-data assets/qrcodes/  # On Ubuntu/Debian
```

### 5. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^/]+)/?$ $1.php [L,QSA]
```

#### Nginx
```nginx
location /sms/ {
    try_files $uri $uri/ $uri.php?$query_string;
}
```

## 🔐 Default Login Credentials

**Username:** `admin`  
**Password:** `admin123`

> ⚠️ **IMPORTANT**: Change the default password immediately after first login!

## 📁 File Structure

```
sms/
├── index.php                 # Admin Dashboard
├── login.php                 # Login page
├── logout.php                # Logout handler
├── database_schema.sql       # Database structure
├── README.md                 # This file
├── config/
│   └── database.php          # Database configuration
├── auth/
│   ├── auth_check.php        # Authentication middleware
│   └── csrf.php              # CSRF protection
├── products/
│   ├── list.php              # Product listing
│   ├── create.php            # Add new product
│   ├── edit.php              # Edit product
│   └── delete.php            # Delete product
├── qr/
│   ├── generate.php          # QR code generator
│   ├── scan.php              # QR scanner interface
│   └── qr_generator.php      # QR generation logic
├── api/
│   ├── lookup_product.php    # Product lookup API
│   └── update_product.php    # Product update API
├── logs/
│   └── stock_logs.php        # Stock history viewer
├── exports/
│   └── index.php             # Data export center
└── assets/
    ├── css/
    │   └── style.css         # Main stylesheet
    └── qrcodes/              # Generated QR codes
```

## 📱 QR Code System

### QR Code Generation
- Unique QR codes automatically generated for each product
- Format: `SMS_{UNIQUE_ID}_{SKU}`
- SVG format for scalability
- Printable labels with product information

### QR Code Scanning
- Uses device camera via HTML5 API
- Real-time product lookup
- Instant stock updates
- Manual QR entry fallback
- Works on mobile devices

### QR Code Usage
1. Print QR codes from the system
2. Attach to physical products/locations
3. Scan with any device camera
4. Update stock levels instantly
5. Track all changes automatically

## 🗄️ Database Schema

### Tables
- **admins** - System administrators
- **categories** - Product categories
- **products** - Product inventory
- **stock_logs** - All stock changes

### Key Features
- Foreign key relationships
- Automatic triggers for stock status
- Indexed columns for performance
- UTF-8 support for international characters

## 🔧 Configuration Options

### Security Settings
```php
// Session timeout (seconds)
$session_timeout = 1800; // 30 minutes

// Password requirements
$min_password_length = 8;

// CSRF token expiration
$csrf_token_expire = 3600; // 1 hour
```

### Application Settings
```php
// Default minimum stock level
$default_min_stock = 10;

// QR code image size
$qr_code_size = 300; // pixels

// Pagination
$products_per_page = 20;
$logs_per_page = 50;
```

## 📊 Usage Guide

### Adding Products
1. Navigate to **Products** → **Add Product**
2. Fill in required fields (Name, SKU, Category)
3. Set initial stock quantity and minimum level
4. Add optional details (location, supplier, etc.)
5. Save - QR code automatically generated

### Managing Stock
1. Use **QR Scanner** for quick updates
2. Or edit products individually
3. All changes automatically logged
4. View history in **Stock Logs**

### Generating Reports
1. Go to **Export Data**
2. Choose export type (Products, Logs, Low Stock)
3. Select format (CSV or HTML)
4. Download or print report

### QR Code Workflow
1. Generate QR code for product
2. Print and attach to physical item
3. Scan QR code when moving stock
4. Update quantities in real-time
5. Review changes in stock logs

## 🚀 Deployment Guide

### Shared Hosting
1. Upload all files via FTP
2. Import database via phpMyAdmin
3. Update database config
4. Set directory permissions
5. Test functionality

### VPS/Dedicated Server
1. Clone repository to web directory
2. Configure web server
3. Set up database
4. Configure SSL certificate
5. Set up automated backups

### Production Checklist
- [ ] Change default admin password
- [ ] Update database credentials
- [ ] Enable SSL/HTTPS
- [ ] Set proper file permissions
- [ ] Configure error logging
- [ ] Set up regular database backups
- [ ] Test QR scanner functionality
- [ ] Verify export functionality

## 🔍 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists and is accessible

**QR Scanner Not Working**
- Check browser permissions for camera access
- Ensure HTTPS is enabled (required for camera API)
- Try manual QR entry as fallback

**File Upload Errors**
- Check directory permissions on `assets/qrcodes/`
- Ensure web server has write access
- Verify disk space availability

**Session Issues**
- Check PHP session configuration
- Verify session directory permissions
- Clear browser cookies/cache

### Debug Mode
Enable debug logging by adding to `config/database.php`:
```php
// Enable debug mode
define('SMS_DEBUG', true);
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Performance Optimization
- Add database indexes for large datasets
- Enable PHP OPcache
- Use memcached for session storage
- Optimize images and QR codes
- Enable gzip compression

## 📈 Scaling Considerations

### For Large Inventories
- Implement database pagination
- Add search indexes
- Consider database clustering
- Implement caching layer

### For Multiple Users
- Add user roles and permissions
- Implement department/location filtering
- Add approval workflows
- Enhanced audit logging

### Advanced Features
- Barcode scanning support
- Mobile app integration
- API for third-party systems
- Advanced reporting dashboard
- Automated reorder points

## 🔒 Security Best Practices

### Regular Maintenance
- Update PHP and MySQL regularly
- Review security logs monthly
- Change passwords periodically
- Monitor failed login attempts

### Access Control
- Use strong passwords
- Limit admin accounts
- Implement IP restrictions if needed
- Regular security audits

### Data Protection
- Regular database backups
- Encrypt sensitive data
- Secure file uploads
- Monitor system logs

## 📞 Support

### Self-Help Resources
- Check this README for common issues
- Review error logs in server logs
- Test with different browsers/devices
- Verify database connectivity

### System Requirements Verification
```php
// Check PHP version
echo phpversion(); // Should be 7.4+

// Check required extensions
echo extension_loaded('pdo') ? 'PDO: OK' : 'PDO: Missing';
echo extension_loaded('pdo_mysql') ? 'PDO MySQL: OK' : 'PDO MySQL: Missing';
```

## 📄 License

This project is provided as-is for educational and production use. Feel free to modify and distribute according to your needs.

## 🤝 Contributing

This is a standalone system designed for production use. For enhancements:
1. Test thoroughly in development environment
2. Maintain security standards
3. Follow existing code patterns
4. Update documentation

## 📝 Changelog

### Version 1.0.0 (Initial Release)
- Complete product management system
- QR code generation and scanning
- Admin authentication and authorization
- Stock history logging
- Data export functionality
- Responsive web interface
- Production-ready codebase

---

**Stock Management System (SMS)**  
*Built with PHP, MySQL, HTML, CSS, and JavaScript*  
*No frameworks, no dependencies, just clean code.*