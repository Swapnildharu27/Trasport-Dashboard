# Transport Dashboard (PHP + MySQL)

A simple, self-contained PHP web app for managing transport/trip records:
view a dashboard, add/edit/delete records, and export the current view to Excel.

## Files

| File                     | Purpose                                             |
|---------------------------|------------------------------------------------------|
| `database.sql`             | Creates the database + `transport_records` and `trip_logs` tables (with sample rows) |
| `db_config.php`            | MySQL connection settings (edit this first)         |
| `partials/nav.php`         | Shared top navbar with two section tabs: Transport Records / Trip Log |
| `index.php`                | **Transport Records** dashboard: stats, search/filter, table, export link |
| `add_edit.php`             | Transport Records form (insert + edit)              |
| `delete.php`               | Deletes a transport record by id                    |
| `export_excel.php`         | Excel export for Transport Records (respects filters) |
| `trip_logs.php`            | **Trip Log** dashboard: diesel/KM/DEF/FASTag sheet, search/date-range filter, stats |
| `trip_log_form.php`        | Trip Log form (insert + edit), auto-calculates Total KM from Before/After KM |
| `trip_log_delete.php`      | Deletes a trip log record by id                     |
| `trip_log_export.php`      | Excel export for Trip Log (respects filters)         |
| `assets/style.css`         | Styling for all pages, including the tab bars        |

The app now has **two top-level sections**, switchable from the navbar:
- **Transport Records** — trip scheduling, driver/vehicle, route, billing (rate, GST, LR/invoice, supplier).
- **Trip Log** — per-trip diesel/KM/DEF/FASTag sheet: Date, Return Date, LR Number, Vehicle No.,
  Location, Sakharwadi Diesel, Rate, Amount, Advance, Driver Name, Before/After Diesel (Ltr),
  Before/After KM, Total KM (auto-filled if left blank), DEF, KL, Fast Tag Exp.

Each section has its own sub-tabs (Dashboard / + Add / Export to Excel) directly under the navbar.

## Requirements

- PHP 7.4+ (uses PDO, works fine on PHP 8.x too)
- MySQL / MariaDB
- A web server (Apache/Nginx) or just PHP's built-in server for testing

No Composer packages or external libraries are required — Excel export uses
a lightweight HTML-table technique that Excel opens natively.

## Setup

1. **Create the database and tables**
   ```bash
   mysql -u root -p < database.sql
   ```
   This creates `transport_db` with both the `transport_records` and `trip_logs`
   tables, each pre-loaded with a couple of sample rows.

2. **Configure the connection**
   Open `db_config.php` and update if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'transport_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. **Run it**
   - With PHP's built-in server (quick local testing):
     ```bash
     php -S localhost:8000
     ```
     then open http://localhost:8000/index.php
   - Or copy the whole folder into your web server's document root
     (e.g. `htdocs/transport-dashboard` for XAMPP) and browse to it.

## Features

- **Dashboard** (`index.php`): trip counts by status, searchable/filterable
  table of all records (search by vehicle, driver, source, destination;
  filter by status).
- **Add / Edit** (`add_edit.php`): one form handles both create and update,
  with server-side validation (required fields, phone number format, valid status).
- **Delete** (`delete.php`): removes a record with a confirmation prompt.
- **Excel export** (`export_excel.php`): downloads a `.xls` file of whatever
  is currently filtered/searched on the dashboard — click "Export to Excel"
  after searching/filtering to export just that subset.

## Security notes

- All queries use PDO prepared statements (no SQL injection).
- All output is escaped with `htmlspecialchars()` (no XSS).
- For production use, add authentication (login) before exposing this
  publicly, and consider moving `db_config.php` credentials to environment
  variables.

## Fields included

Trip details: vehicle no., driver name/contact, source, destination,
departure date/time, arrival time, status, remarks.

Billing/documentation details: **Supplier**, **LR Number**, **Invoice
Number**, **GST Number** (validated as 15 alphanumeric characters),
**Quantity**, **Rate**. These are searchable from the dashboard search box
and included in the Excel export. The dashboard also shows a "Total Rate
Value" stat card (sum of the `rate` column).

> **If you created the database before this update**, run the `ALTER TABLE`
> statement included at the bottom of `database.sql` to add the new columns
> to your existing table (instead of re-running the whole script).

## Customizing the fields

To adapt the schema further:
1. Edit the `CREATE TABLE` statement in `database.sql`.
2. Update the `$record` array and form fields in `add_edit.php`.
3. Update the `<th>`/`<td>` columns in `index.php` and `export_excel.php`.
