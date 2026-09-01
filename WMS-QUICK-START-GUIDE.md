# WMS - Warehouse Management System
## Quick Start Guide

---

## 🚀 **Getting Started**

### **1. Run Both Backend & Frontend Together**

Navigate to project directory and execute:

```powershell
cd D:\Coding-Project
.\run-wms.bat
```

This will automatically:
- Start Laravel backend on `http://localhost:8000`
- Start Next.js frontend on `http://localhost:3000`
- Open two terminal windows for monitoring logs

---

### **2. First-Time Setup (If Fresh Database)**

#### Backend Setup:
```bash
cd D:\Coding-Project\warehouse-be

# Clear any old migrations/seeders issues
php artisan migrate:fresh --seed

# Generate Passport encryption keys
php artisan passport:key --force
```

---

## 🔑 **Default Login Credentials**

| Role | Email | Password | Permissions |
|------|-------|----------|-------------|
| **Super Admin** | `admin@example.com` | `password` | Full system access |
| **Operator** | `operator@example.com` | `password` | Basic operations only |

### **New Warehouse Roles Available:**
- **Admin Gudang** - Warehouse-level management
- **Kepala Gudang** - Manager with reporting access  
- **Petugas Gudang** - Staff with basic permissions
- **Checker** - Stock verification specialist
- **Kurir/Driver** - Delivery management

> To assign these roles, login as Super Admin → Pengaturan → Users

---

## 📊 **New Features Overview**

### **✅ Minimum Stock Alert System**

**Location:** Dashboard → Right sidebar "Stok Rendah" card

**Features:**
- Real-time low stock monitoring
- Color-coded urgency levels:
  - 🔴 **Red**: Critical (0 stock)
  - 🟡 **Yellow**: Warning (at/below min_stok)
  - 🟢 **Green**: Safe (above minimum)
- Click individual items to navigate to detail page
- Summary statistics displayed when many items affected

**Configuration:**
- Threshold based on `min_stok` field in each barang
- Auto-updates from transaction records
- Works with all warehouse locations

---

### **✅ Enhanced PDF Exports**

All reports now support professional PDF export:

#### **1. Surat Jalan (Delivery Note)**
- **Access:** Transaction Detail → Print Surat Jalan button
- **Types:** Barang Masuk & Barang Keluar
- **Features:** Company header, itemized tables, signature areas

#### **2. Laporan Reports (Backend PDF Service)**

**Available Report Types:**

| Report Type | Endpoint | PDF Template |
|-------------|----------|--------------|
| **Barang Masuk** | `/api/laporan/barang-masuk/export/pdf` | `laporan-barang-masuk.blade.php` |
| **Barang Keluar** | `/api/laporan/barang-keluar/export/pdf` | `laporan-barang-keluar.blade.php` |
| **Absensi** | `/api/laporan/absensi/export/pdf` | `laporan-absensi.blade.php` |

**PDF Template Features:**
- Professional layout with company header
- Date range and print timestamp
- Summary statistics grid
- Color-coded status indicators
- Formatted data tables
- Footer with document info

---

## 📁 **Project Structure**

```
D:\Coding-Project\
├── run-wms.bat                    # ⭐ Start both servers (use this!)
├── warehouse-fe/                  # Frontend (Next.js 16)
│   ├── src/
│   │   ├── components/
│   │   │   └── dashboard/
│   │   │       └── low-stock-alert.tsx     # NEW: Stock alert component
│   │   └── app/
│   │       ├── (dashboard)/
│   │       │   ├── master/satuan/          # NEW: Satuan management
│   │       │   ├── master/lokasi-rak/      # NEW: Rak management
│   │       │   └── (laporan)/
│   │       │       ├── barang-masuk/       # NEW: Laporan report
│   │       │       ├── barang-keluar/      # NEW: Laporan report
│   │       │       └── absensi/            # NEW: Laporan report
│   │       └── dashboard/page.tsx          # Updated with stock alerts
│   └── package.json
│
├── warehouse-be/                 # Backend (Laravel 12)
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   ├── BarangController.php              # Enhanced with filter_low_stock
│   │   │   └── LaporanController.php             # Ready for PDF exports
│   │   ├── Exports/PdfReports/                   # NEW: PDF services
│   │   │   ├── LaporanBarangMasukPdf.php
│   │   │   ├── LaporanBarangKeluarPdf.php
│   │   │   └── LaporanAbsensiPdf.php
│   │   └── Exports/LaporanBarangMasukExport.php  # NEW: Excel export
│   ├── database/migrations/
│   │   └── *_add_stok_saat_ini_to_barang_table.php  # NEW column
│   ├── resources/views/pdf/reports/              # NEW: Blade templates
│   │   ├── laporan-barang-masuk.blade.php
│   │   ├── laporan-barang-keluar.blade.php
│   │   └── laporan-absensi.blade.php
│   └── composer.json
│
└── README.md
```

---

## 🎯 **Navigation Guide**

### **Main Menu Structure:**

```
📊 Dashboard
   ├── Operational Overview
   ├── KPI Cards
   ├── Charts & Trends
   └── 🆕 Stok Rendah Alerts (NEW!)

📦 Master Data
   ├── Barang           ✅ Existing
   ├── Gudang           ✅ Existing
   ├── Kategori         ✅ Existing
   ├── Supplier         ✅ Existing
   ├── Customer         ✅ Existing
   ├── Satuan           ✅ NEW - Standalone page
   └── Lokasi Rak       ✅ NEW - Standalone page

📝 Inventory Transactions
   ├── Barang Masuk     ✅ Existing + Surat Jalan PDF
   ├── Barang Keluar    ✅ Existing + Surat Jalan PDF
   ├── Mutasi Stok      ✅ Existing
   └── Stok Opname      ✅ Existing

📄 Laporan (Reports)
   ├── Pergerakan Stok  ✅ Existing combined view
   ├── Barang Masuk     ✅ NEW - Dedicated report page + PDF
   ├── Barang Keluar    ✅ NEW - Dedicated report page + PDF
   ├── Absensi          ✅ NEW - Dedicated report page + PDF
   └── Selisih Opname   ✅ Existing

⏰ Absensi
   ├── Jadwal Shift     ✅ Existing
   ├── Petugas          ✅ Existing
   ├── Presensi         ✅ Existing
   ├── Cuti-Izin        ✅ Existing
   └── Rekap            ✅ Existing

⚙️ Pengaturan
   ├── Users            ✅ Existing + New Roles
   ├── Roles & Permission ✅ Existing + Expanded perms
```

---

## 🛠️ **Development Commands**

### **Frontend (Next.js):**
```bash
cd D:\Coding-Project\warehouse-fe

npm run dev          # Development server (port 3000)
npm run build        # Production build
npm run typecheck    # TypeScript checking
npm run lint         # ESLint
npm run format       # Prettier formatting
```

### **Backend (Laravel):**
```bash
cd D:\Coding-Project\warehouse-be

php artisan serve               # Development server (port 8000)
php artisan migrate:fresh --seed # Reset database
php artisan make:controller Api/ExampleController --resource
php artisan make:model Barang -mcr # Model with migration/controller/resources
php artisan test               # Run PHPUnit tests
```

---

## 🔧 **Troubleshooting**

### **Issue: Migration errors**
```bash
# Solution: Clear cache and re-run migrations
php artisan config:clear
php artisan route:clear
php artisan migrate:fresh --seed
```

### **Issue: Passport token errors**
```bash
# Solution: Regenerate encryption keys
php artisan passport:key --force
php artisan migrate
```

### **Issue: Port already in use**
```bash
# Change port when starting server
php artisan serve --port=8888
npm run dev -- --port=3001
```

### **Issue: Build errors**
```bash
# Frontend: Clean node_modules and reinstall
cd D:\Coding-Project\warehouse-fe
Remove-Item -Recurse -Force node_modules
npm install

# Backend: Clear compiled classes
cd D:\Coding-Project\warehouse-be
Remove-Item bootstrap/cache/*.php -Force
php artisan optimize
```

---

## 📱 **API Endpoints Reference**

### **Authentication:**
- `POST /api/login` - Login user
- `POST /api/logout` - Logout user  
- `GET /api/me` - Get current user details
- `POST /api/refresh` - Refresh access token

### **Stock Management:**
- `GET /api/barang?filter_low_stock=true` - Get items below minimum stock
- `GET /api/barang` - List all items
- `PUT /api/barang/{id}` - Update item (includes stok_saat_ini)

### **Reports:**
- `GET /api/laporan/barang-masuk` - Laporan barang masuk
- `GET /api/laporan/barang-keluar` - Laporan barang keluar
- `GET /api/laporan/absensi` - Laporan absensi

---

## 📈 **System Requirements**

### **Minimum:**
- PHP 8.2+
- Node.js 20.9+
- MySQL 8.0+ / MariaDB 10.4+
- Composer 2.x
- npm or yarn

### **Recommended:**
- 8GB RAM
- SSD storage
- Modern browser (Chrome/Edge/Firefox latest)

---

## 🎉 **Success Checklist**

Before considering your instance ready for production:

- [ ] Database migrated successfully (`php artisan migrate:fresh --seed`)
- [ ] Both servers running without errors (`run-wms.bat`)
- [ ] Can login with default credentials
- [ ] All master data pages accessible
- [ ] Low stock alerts appear on dashboard (if items have min_stok set)
- [ ] Can generate surat jalan PDF
- [ ] All report pages load correctly
- [ ] User roles working properly

---

## 📞 **Support**

For issues or questions:
1. Check error logs in opened terminal windows
2. Review `backend.log` and `frontend` console
3. Verify `.env` configuration files
4. Check database connection settings

---

## 🚀 **Next Steps**

1. **Customize Data:** Add your actual warehouses, products, and staff
2. **Test Workflows:** Try complete inventory cycles (incoming → outgoing → mutation)
3. **Configure Roles:** Assign appropriate permissions to team members
4. **Set Up Alerts:** Configure minimum stock thresholds for all products
5. **Print Reports:** Test all PDF generation features
6. **Backup Plan:** Set up regular database backups

---

**Happy Warehousing! 📦✨**

*Generated by WMS System v1.0*
