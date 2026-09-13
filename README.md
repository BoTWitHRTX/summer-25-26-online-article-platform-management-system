# InkWell — Article Management System (PHP + MySQL, MVC)

A teaching project for a 3-role article platform: **admin, author, reader**.
Written in plain PHP with procedural `mysqli` and prepared statements. No frameworks,
no Composer, no build step. Copy it into XAMPP and it runs.

Repo/folder name: `fall-25-26-article-management-system`

---

## 1. Install (XAMPP)

1. Copy the project folder into `C:\xampp\htdocs\`
   so it becomes `htdocs/fall-25-26-article-management-system/`.
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open `http://localhost/phpmyadmin` → **Import** → choose `database.sql` → **Go**.
4. Open `http://localhost/fall-25-26-article-management-system/`.
5. Sign in as the default admin: **admin / admin123**

The admin account is created automatically the first time a page loads
(see the bottom of `config/config.php`). Everyone else signs up on the register page.

If your MySQL uses a password, change `DB_PASS` in `config/config.php`.

---

## 2. Folder structure

```
fall-25-26-article-management-system/
├── index.php                  Front controller: the ONLY entry point (router)
├── database.sql               Schema
├── README.md
│
├── config/
│   └── config.php             DB connection, session settings, app constants
│
├── helpers/
│   └── helpers.php            esc(), CSRF, login guards, flash messages, validation
│
├── models/                    M — every SQL query lives here
│   ├── user_model.php         all 3 roles (one users table)
│   ├── article_model.php      catalogue + author's own articles
│   ├── interaction_model.php  likes, comments, ratings
│   ├── order_model.php        purchases + admin sales/profit reporting
│   ├── feedback_model.php     admin → author feedback
│   └── log_model.php          activity log
│
├── controllers/               C — request handling, validation, decisions
│   ├── auth_controller.php    login / register / logout / forgot password
│   ├── reader_controller.php
│   ├── author_controller.php
│   ├── admin_controller.php
│   └── ajax_controller.php    all JSON endpoints
│
├── views/                     V — HTML only
│   ├── partials/              header.php, footer.php (shared layout)
│   ├── auth/                  login.php, register.php, forgot_password.php
│   ├── reader/                dashboard.php, article.php
│   ├── author/dashboard.php
│   └── admin/dashboard.php
│
└── assets/
    ├── css/style.css
    └── js/app.js              validation, escaping, live search, AJAX tables
```

**The MVC rule used throughout:** a view never runs a query, and a model never
prints HTML. The controller sits in the middle: it reads `$_POST`, validates,
calls the model, then `require`s the view.

---

## 3. How the router works

Every URL looks like this:

```
index.php?page=<dashboard>&action=<what to do>&id=<row id>
```

| URL                                                                 | What happens                             |
| ------------------------------------------------------------------- | ---------------------------------------- |
| `index.php?page=login`                                              | Login page                               |
| `index.php?page=register`                                           | Signup page                              |
| `index.php?page=forgot`                                             | Forgot-password (security question) page |
| `index.php?page=reader`                                             | Reader dashboard (browse & search)       |
| `index.php?page=reader&action=view&id=4`                            | Open article 4, with comments/rating     |
| `index.php?page=author&action=edit&id=2`                            | Load article 2 into the author's form    |
| `index.php?page=admin&action=status&id=7&to=suspended&csrf_token=…` | Suspend account 7                        |
| `index.php?page=ajax&action=search_articles&q=php`                  | Returns JSON                             |
| `index.php?page=logout`                                             | Sign out                                 |

`index.php` loads config → helpers → models → controllers, checks the session
timeout, then sends the request to one controller. `require_role('reader')`
(or `author`/`admin`) blocks anyone who is not that role before the controller
even starts.

---

## 4. The three roles

Each role owns a CRUD entity and does Create, Read, Update, Delete and Search
where it makes sense on its own dashboard. The form sits at the top of the page;
the searchable table sits below it. Clicking **Edit** reloads the same page with
the row loaded into that same form.

| Role       | Manages (CRUD)                                            | Feature 1                                    | Feature 2                                                 | Feature 3                                             |
| ---------- | --------------------------------------------------------- | -------------------------------------------- | --------------------------------------------------------- | ----------------------------------------------------- |
| **Reader** | My comments (create/edit/delete on any published article) | Read & search articles, live AJAX search     | Like & comment on an article                              | Rate an article 1–5 stars                             |
| **Author** | My articles (create/edit/delete, draft or published)      | Compare 2+ of my own books side by side      | Set/update the price of any article                       | Save as draft or publish                              |
| **Admin**  | Feedback sent to authors                                  | Set how much a reader may spend on one order | Give feedback to an author, tied to an article or general | Sold articles & profit/loss, live-refreshed every 15s |

No feature appears on two dashboards.

### How the roles connect

- An **author** writes an article and chooses **draft** or **published**. Only
  published articles are visible to readers.
- A **reader** browses/searches published articles, likes, comments, rates, and
  can **order** one — but only if its price is within the **order limit** the
  admin has set for that reader.
- Every order a reader places shows up on the **admin's** sold-articles table,
  with revenue and an estimated profit/loss.
- The **admin** can send **feedback** to an author (optionally about one specific
  article); the author sees it read-only on their own dashboard.

---

## 5. Requirement checklist

| Requirement                 | Where to look                                                                                |
| --------------------------- | -------------------------------------------------------------------------------------------- |
| **MVC**                     | `models/`, `controllers/`, `views/`, routed by `index.php`                                   |
| **DB (MySQLi procedural)**  | every function in `models/` uses `mysqli_prepare`                                            |
| **Auth (session + cookie)** | `controllers/auth_controller.php`, `helpers/helpers.php`                                     |
| **PHP validation**          | the `if / elseif` chain at the top of every controller action                                |
| **JS validation**           | `validateForm()` in `assets/js/app.js`, called by `onsubmit`                                 |
| **AJAX / JSON**             | `controllers/ajax_controller.php` + `ajaxTable()` in `app.js`                                |
| **DOM manipulation**        | account-type switch on `views/auth/register.php` (shows/hides author bio vs. reader address) |
| **UI (HTML/CSS)**           | `views/`, `assets/css/style.css`                                                             |
| **Basic web security**      | see section 6                                                                                |
| **Feature completeness**    | CRUD + search + 3 features per role                                                          |

---

## 6. Security, and why each piece is there

| Attack            | Defence                                                                                                                                           | File                                         |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------- |
| SQL injection     | Prepared statements everywhere — user text is never glued into SQL                                                                                | all `models/`                                |
| Stolen passwords  | `password_hash()` on save, `password_verify()` on login                                                                                           | `user_model.php`                             |
| XSS (server)      | `esc()` wraps every value printed into HTML                                                                                                       | `helpers.php`, all views                     |
| XSS (client)      | `esc()` in JavaScript before any AJAX row is inserted                                                                                             | `app.js`                                     |
| CSRF              | A secret token in every POST form and every delete/order/like link                                                                                | `helpers.php`, all views                     |
| Session fixation  | `session_regenerate_id(true)` right after a successful login                                                                                      | `auth_controller.php`                        |
| Cookie theft      | `httponly` + `samesite=Lax` on the session cookie                                                                                                 | `config.php`                                 |
| Idle machines     | Automatic sign-out after 30 minutes                                                                                                               | `check_session_timeout()`                    |
| Wrong role        | `require_role()` before the controller; each AJAX action re-checks                                                                                | `index.php`, `ajax_controller.php`           |
| URL tampering     | A reader can only edit/delete their own comments; an author can only touch their own articles (`WHERE … AND reader_id = ?` / `AND author_id = ?`) | `interaction_model.php`, `article_model.php` |
| Username guessing | Wrong username and wrong password give the same message                                                                                           | `auth_controller.php`                        |
| Self-lockout      | The admin account can't be suspended or deleted (`WHERE … AND role != 'admin'` is baked into the query)                                           | `user_model.php`                             |
| Stale limits      | A reader's order limit is re-read from the DB at the moment of ordering, never trusted from the session cache                                     | `reader_controller.php`                      |

Two things worth saying out loud to students:

1. **JavaScript validation is a convenience, not a defence.** Anyone can turn
   JavaScript off. That is why every controller repeats the checks in PHP.
2. **"Remember me" only refills the username**, never the password.

---

## 7. Settings you can change

In `config/config.php`:

```php
define('DEFAULT_ORDER_LIMIT', 100.00); // starting spend cap for a new reader
define('SESSION_TIMEOUT', 1800);       // idle sign-out, in seconds
define('CURRENCY', '$');               // symbol shown next to prices
```

In `models/order_model.php`:

```php
define('PLATFORM_COST_RATE', 0.30); // assumed cost share of revenue, used for profit/loss
```

---

## 8. Test accounts

| Role   | Username                     | Password   |
| ------ | ---------------------------- | ---------- |
| Admin  | `admin`                      | `admin123` |
| Author | sign up on the register page |            |
| Reader | sign up on the register page |            |

Nobody can sign up as an admin — the register page only accepts Author/Reader,
and the controller checks that list again on the server. The one admin account
is created automatically by `config.php`.

---

## "Copyright (c) 2026 MD ABDULLAH AL SAIF, SIFAT ARA LIYA, FEEDIA MOMTAHNA MIM. All rights reserved."
