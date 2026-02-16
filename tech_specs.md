# tech_specs.md

### 1. The Sitemap & File Structure
**Root & Auth**
* `/index.php` (Landing/PWA Install Check)
* `/login.php` (Auth Logic + Session Start)
* `/logout.php` (Session Destroy)
* `/manifest.json` (PWA Manifest)
* `/service-worker.js` (Offline Cache/Sync Logic)

**Admin Layouts (Global & Scoped)**
* `/src/includes/header.php` (Navigation + Role-based Visibility)
* `/src/includes/db_connect.php` (PDO Connection)
* `/src/includes/auth_check.php` (Middleware for Session/Role validation)

**Super Admin Pages (Global)**
* `/admin/dashboard.php` (Aggregated Stats)
* `/admin/roster.php` (Master Member List + Global Search)
* `/admin/audit_logs.php` (System History)
* `/admin/reports.php` (PDF Generator Interface)
* `/admin/settings.php` (Session Reset/New Year Reset)

**Tent Admin Pages (Scoped)**
* `/tent/dashboard.php` (Tent-specific Stats + Birthday Intelligence)
* `/tent/attendance.php` (The Sync-Check Engine)
* `/tent/members.php` (Tent-specific Roster)

**API/Endpoints**
* `/api/mark_attendance.php` (POST: Sync Logic)
* `/api/member_ops.php` (POST: Create/Update/Transfer)
* `/api/get_birthdays.php` (GET: Monthly/Weekly Filter)
* `/api/generate_report.php` (POST: PDF Output)

---

### 2. Component Specifications (Per Page)

**Screen Name: Attendance Engine ("Sync-Check")**
* **Core Components:**
    * `SearchBar`: Dynamic text input for name filtering.
    * `AttendanceTable`: List with `StatusBadge` and `ActionBtn`.
    * `RegistrationModal`: Conditional form for New Members.
* **Data Requirements:**
    * Fetch `Members` where `Current_Tent_ID == Session_Tent_ID`.
    * Fetch `Attendance_Log` for `current_date` to prevent duplicates.
* **State Management:**
    * *Local (JS):* `searchQuery`, `isOffline`, `syncQueue[]`.
    * *Global (PHP Session):* `Admin_Role`, `Assigned_Tent_ID`.

**Screen Name: Birthday Intelligence Center**
* **Core Components:**
    * `ToggleFilter`: Button group (This Week / This Month).
    * `BirthdayCard`: Displays Name, Date, and `CountdownBadge` (Days Remaining).
* **Data Requirements:**
    * Query `Members` using `MONTH(Birthdate)` and `DAYOFYEAR` logic.
* **State Management:**
    * *Local (JS):* `activeFilter` (default: 'month').

**Screen Name: Report Generator**
* **Core Components:**
    * `ReportTypeSelector`: Dropdown (Annual/Monthly/Tent-Specific).
    * `YearPicker`: Select dropdown (e.g., 2026).
    * `ProgressBar`: Visual feedback during PDF generation.
* **Data Requirements:**
    * Aggregated counts from `Attendance_Log` JOIN `Members`.
* **Logic:** Trigger `DomPDF` or `FPDF` stream on backend.

---

### 3. Interaction Logic (The Precision Layer)

**Flow: The Sync-Check Logic**
* **Input:** User types "John" in Search.
* **JS Logic:** Filter local table rows. If 0 results, show "Register New Member" button.
* **Action:** Click "Mark Present".
* **Network Logic:**
    * If `navigator.onLine`: POST to `/api/mark_attendance.php` -> Update DB -> Toast "Success".
    * If `Offline`: Push record to `localStorage.syncQueue` -> Update UI to "Pending" -> Service Worker listens for `online` event to flush queue.

**Flow: Birthday Intelligence (Leap Year Handling)**
* **Input:** Page Load.
* **PHP Logic:**
    ```php
    $isLeap = (date('L') == 1);
    $query = "SELECT * FROM Members WHERE MONTH(Birthdate) = $currentMonth";
    // If today is Feb 28 and !isLeap, include Feb 29 records in "This Week" filter.
    ```

**Flow: Profile Mutation (Conditional Fields)**
* **Input:** User changes `Status` dropdown.
* **UI Logic:**
    * `onSelectChange` -> If value !== 'Student', set `input#school` display to `none` and clear value.
* **Action:** Change `Tent_ID`.
* **Output:** POST to `/api/member_ops.php` -> Update `Current_Tent_ID` -> Insert into `Audit_Log` (User, Action, Timestamp).

**Flow: Session Reset (The "New Year" Nuclear Option)**
* **Input:** Click "Start New Year" in Super Admin Settings.
* **JS Logic:** Show `window.confirm` with red styling.
* **PHP Logic:**
    * `UPDATE Sessions SET Is_Active = 0` (Closes current context).
    * `INSERT INTO Sessions (Start_Date, Is_Active) VALUES (NOW(), 1)`.
    * Attendance metrics in UI now query where `Session_ID` is the new ID.

---

### 4. Edge Case Handling

| Scenario | Logic / Response |
| :--- | :--- |
| **Duplicate Entry** | Backend checks `Member_ID` + `Date` in `Attendance_Log`. If exists, return `409 Conflict`, UI disables button. |
| **PWA Installation** | `beforeinstallprompt` event listener captures event -> Shows custom "Install App" banner in Footer. |
| **Cross-Tent Access** | Every PHP file includes `auth_check.php` which validates `$_GET['tent_id'] === $_SESSION['assigned_tent_id']`. If fail, `header("Location: unauthorized.php")`. |
| **Data Integrity** | When `Status` changes from Student to Worker, the `School` field is nullified in the SQL `UPDATE` statement. |
