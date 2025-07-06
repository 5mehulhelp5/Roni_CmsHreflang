# 🏗️ Roni_CmsHreflang

## ✨ Description

The **Roni_CmsHreflang** module automatically generates `<link rel="alternate" hreflang="...">` tags for CMS pages in Magento 2, enhancing international SEO and helping search engines correctly identify localized page versions.

🔗 **Problem Solved:**  
By default, Magento 2 does not generate hreflang tags for CMS pages. This creates SEO issues for stores with multiple languages or regions.

🚀 **Solution:**  
This module identifies the current CMS page, checks which store views are associated with it, and dynamically outputs the correct hreflang tags.

---

## 📦 Structure & Architecture

- **Namespace:** `Roni\CmsHreflang`
- **Standards Followed:** PSR-12, SOLID, Magento Coding Standards
- **Design Patterns:** Block Component, Dependency Injection, Fail-safe Logging

---

## 🔥 Features

- ✔️ Automatically generates hreflang tags for CMS pages
- ✔️ Supports unlimited store views
- ✔️ Detects `use_store_code_in_url` settings
- ✔️ Retrieves `general/locale/code` for accurate language-region formatting
- ✔️ Skips inactive CMS pages
- ✔️ Logs errors without breaking frontend rendering

---

## 🔧 How It Works

### ✅ Execution Flow

1. Verifies if the current page is a CMS page (`cms_page_view`)
2. Retrieves the **identifier** via the `cms_page` block
3. Validates if the CMS page is active for the current store
4. Fetches all store views assigned to this page (including `All Store Views`)
5. For each store:
   - Validates if the page is active in that store
   - Retrieves locale configuration (e.g., `pt_BR`, `en_US`) and converts to hreflang format (`pt-BR`)
   - Checks if store code is included in URLs (`web/url/use_store`)
   - Builds the final URL for that store
   - Outputs the hreflang tag:

```html
<link rel="alternate" hreflang="pt-BR" href="https://example.com/pt_br/about-us" />
```

---

## 🛠️ Technologies & Best Practices Used

| Item                          | Strategy / Decision                                   |
|------------------------------|--------------------------------------------------------|
| ✔️ Dependency Injection       | Reduces coupling, improves testability                |
| ✔️ Fail-safe Error Handling   | All errors are logged without exposing them to users  |
| ✔️ PSR-12 + Magento Standards | Clean, readable, and standardized code                |
| ✔️ Single Responsibility      | Each method has a clear and single responsibility     |
| ✔️ Clean Code                 | Semantic names, small methods, no hardcoded strings   |
| ✔️ ResourceModel for DB       | Fast and accurate access to `cms_page_store` table    |
| ✔️ Config Awareness           | Fully respects Magento's store and locale settings    |

---

## 🏟️ Main Technical Component

- **Block:** `Roni\CmsHreflang\Block\Hreflang`  
  👉 Responsible for all logic and rendering of hreflang tags.

---

## 🏦 Architectural Decisions

- **Why not use `PageRepository::getById()` directly?**  
  That method is limited when called across stores. `CollectionFactory` ensures proper filtering by store scope.

- **Why query the `cms_page_store` table directly?**  
  The repository API doesn't provide reliable access to page-store mapping. SQL is faster and more accurate.

- **Why log errors instead of throwing?**  
  To avoid frontend failures and ensure proper diagnostics in Magento’s log system.

---

## 🏠 Installation

### Option 1: Via Composer (local path)

```bash
composer require roni/module-cms-hreflang
```

### Option 2: Manual Installation

1. Copy the module to: `app/code/Roni/CmsHreflang`
2. Run the following commands:

```bash
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

---

## ⚙️ Configuration

No manual setup required. The module works automatically for CMS pages that:

- Are active in at least one store view
- Have friendly URLs set
- Are associated with specific store views (or all stores)

---

## 🧠 Example

### CMS Page "about-us" across 3 store views:

| Store View | Locale | URL                                           |
|------------|--------|-----------------------------------------------|
| pt_br      | pt-BR  | `https://example.com/pt_br/about-us`          |
| en_us      | en-US  | `https://example.com/en_us/about-us`          |
| en_gb      | en-GB  | `https://example.com/en_gb/about-us`          |

**Generated Tags:**

```html
<link rel="alternate" hreflang="pt-BR" href="https://example.com/pt_br/about-us" />
<link rel="alternate" hreflang="en-US" href="https://example.com/en_us/about-us" />
<link rel="alternate" hreflang="en-GB" href="https://example.com/en_gb/about-us" />
```

---

## 👨‍💼 Author

**Roni Clei J Santos**  
📧 [roneclay@gmail.com](mailto:roneclay@gmail.com)  
🔗 [LinkedIn](https://www.linkedin.com/in/roni-clei-santos/) | [GitHub](https://github.com/roneclay)

---

## 📝 License

[MIT License](https://opensource.org/licenses/MIT)

---

## ☕ Support this project

If this module helped you, consider supporting:

- 🌎 [Buy Me a Coffee (global)](https://coff.ee/roneclay9)
- 🇧🇷 Pix (Brazil): `a3a7aea8-39c5-46b0-94cb-da030549eaa2`
