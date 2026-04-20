# Staff Manager

A WordPress plugin for accounting firms to manage client companies, their employees, and terminations — with built-in PDF generation and email delivery.

---

## Features

- **Client management** — Admins create client accounts (Kunden); each client only sees their own employees
- **Employee management** — Full employee profiles with Austrian-specific fields (SVNR, Staatsangehörigkeit, Dienstverhaltnisart)
- **PDF generation** — Generate employee data sheets as PDFs via DomPDF
- **Email delivery** — Send PDFs directly to the client and/or the bookkeeping address
- **Termination notices (Kündigung)** — Create and email termination PDFs from the employee screen
- **Role-based access** — `kunden_v2` role restricts clients to their own data only
- **No custom database tables** — All data stored in standard WordPress posts and meta

---

## Requirements

- WordPress 6.0+
- PHP 7.4+
- [Composer](https://getcomposer.org/) (for DomPDF)

---

## Installation

1. Clone or download the repository into your `wp-content/plugins/` directory:
   ```bash
   git clone https://github.com/Husein-Edris/staff-manager.git
   ```

2. Install PHP dependencies:
   ```bash
   cd staff-manager
   composer install
   ```

3. Activate the plugin in **WordPress Admin → Plugins**.

4. Configure the bookkeeping email under **Staff Manager → Settings**.

---

## Plugin Structure

```
staff-manager.php                        # Main plugin file — singleton bootstrap
includes/
├── class-employee-post-type.php         # Employee CPT (angestellte), ownership filtering
├── class-employee-meta-boxes.php        # Employee form fields, PDF/email UI
├── class-user-roles.php                 # kunden_v2 role, admin menu restrictions
├── class-admin-dashboard.php            # Settings page (email, PDF templates)
├── class-pdf-generator.php              # Employee PDF generation and email delivery
├── class-kuendigung-post-type.php       # Termination CPT (kuendigung)
├── class-kuendigung-handler.php         # Termination creation, meta box, email AJAX
└── class-kuendigung-pdf-generator.php   # Termination PDF generation
```

---

## Data Model

| Resource | Post Type | Notes |
|---|---|---|
| Employees | `angestellte` | Linked to employer via `employer_id` meta |
| Terminations | `kuendigung` | Linked to employee post |
| Clients | WordPress users | `kunden_v2` role |

### Key Employee Fields

`anrede`, `vorname`, `nachname`, `sozialversicherungsnummer`, `geburtsdatum`, `staatsangehoerigkeit`, `email`, `personenstand`, `adresse_strasse`, `adresse_plz`, `adresse_ort`, `eintrittsdatum`, `art_des_dienstverhaltnisses`, `bezeichnung_der_tatigkeit`, `arbeitszeit_pro_woche`, `gehaltlohn`, `arbeitstagen`, `anmerkungen`, `status`

---

## Configuration

Settings are stored in `wp_options`:

| Option Key | Description |
|---|---|
| `rt_employee_v2_buchhaltung_email` | Bookkeeping email for PDF delivery |
| `rt_employee_v2_company_address` | Company address shown in PDF footer |
| `rt_employee_v2_pdf_template_header` | Custom PDF header text |
| `rt_employee_v2_pdf_template_footer` | Custom PDF footer text |

---

## AJAX Endpoints

| Action | Description |
|---|---|
| `generate_employee_pdf` | Generate and download employee PDF |
| `generate_and_view_employee_pdf` | Generate and open employee PDF in browser |
| `email_employee_pdf` | Email employee PDF |
| `email_kuendigung` | Email termination PDF |

---


## Author

**Edris Husein** — [edrishusein.com](https://edrishusein.com)
