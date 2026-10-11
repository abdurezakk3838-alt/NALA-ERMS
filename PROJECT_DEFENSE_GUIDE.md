# NALA Enhanced Records Management System (ERMS)
## University Project Defense Guide

This guide explains the project as it is implemented in this codebase. It is intended to support a project demonstration, written report preparation, and oral defense. Where a feature is a prototype or has not been independently validated, that is stated explicitly rather than presented as a production guarantee.

---

## 1. Project at a glance

**Project name:** NALA Enhanced Records Management System (ERMS)  
**Organization context:** National Archives and Library Agency (NALA)  
**Application type:** Browser-based, role-based records and workflow management system  
**Primary technologies:** PHP, MySQL, HTML, CSS, JavaScript  
**Local development environment:** XAMPP (Apache and MySQL)

The system provides one place to register, review, find, track, and preserve incoming letters, outgoing letters, and internal memos. It records workflow outcomes and supports operational administration such as user management, notifications, archiving, audit activity, and reports.

### Abstract

Organizations receive and produce records that must be registered, routed to responsible staff, reviewed, and retrieved later. Handling these steps through disconnected paper registers or informal communication can make it difficult to find a record, determine its status, or identify who acted on it. NALA ERMS is a web application prototype intended to centralize these activities. It stores record metadata and workflow state in a MySQL database, applies role and document-level access checks, and presents the work through browser-based modules. The application supports record registration, approval or rejection, outgoing-letter registration and dispatch, archive snapshots, search, notifications, and reports. It is implemented in PHP with PDO database access and client-side JavaScript. It is a project implementation, not evidence of formal certification or a production security assessment.

---

## 2. Problem, aim, and objectives

### Problem addressed

When correspondence is managed across paper files or separate spreadsheets, staff may have difficulty answering basic questions:

- Was a letter received, and when?
- Which reference number identifies it?
- Who is responsible for reviewing it?
- Was it approved, rejected, registered, or dispatched?
- Where can the record or its attachment be found?
- What actions were recorded during its lifecycle?

### Aim

To develop a centralized web-based system for registering and managing organizational correspondence and its workflow.

### Objectives

1. Register incoming letters, outgoing letters, and internal memos with reference and descriptive information.
2. Support role-oriented access to relevant modules and document actions.
3. Record review outcomes and remarks.
4. Support the multi-stage outgoing-letter workflow, including registration and dispatch.
5. Preserve completed records as archive snapshots and make them searchable.
6. Notify users about relevant document or workflow events.
7. Provide a dashboard, operational reports, and audit records to help users monitor activity.
8. Provide in-application guidance for common system tasks.

---

## 3. Scope and actors

The code defines the following application roles. Permissions are configured centrally in `includes/functions.php`; the interface and page router use those permissions to determine which modules a role can access. Individual operations may also apply document-level authorization checks.

| Role | Main intended responsibility | Main configured module access |
|---|---|---|
| Head Office | Handle office correspondence and participate in routing/review | Dashboard, incoming, outgoing, internal, records, workflow, archive, search, settings |
| Branch | Register and manage branch correspondence | Dashboard, incoming, outgoing, internal, search, settings |
| System Administrator | Manage system users and operational administration | Dashboard, correspondence, records, workflow, archive, search, users, audit, reports, settings |
| Department Manager | Review/manage department work and monitor operations | Dashboard, correspondence, records, workflow, search, reports, settings |
| Records Officer | Manage record-office processing, registration, and archives | Dashboard, correspondence, records, workflow, archive, search, settings |
| Director General | Participate in senior review and oversee records/workflow | Dashboard, incoming, outgoing, internal, workflow, archive, search, audit, reports, settings |
| Intermediary Officer | Limited entry point to the application | Dashboard |
| Archivist | Find and manage archived records and account settings | Dashboard, archive, settings |
| Staff Member | Create or access their work and search | Dashboard, outgoing, internal, search, settings |

All authenticated users can access their own notifications. The role list represents the application configuration, not a claim that every role is seeded as a database account. The base SQL seeds a subset; administrators can create additional users through User Management.

### Main modules

- **Dashboard:** summary cards and recent activity. The cards drill down into incoming, outgoing, all registered, in-process, pending, or completed records. In the current implementation, “completed” includes records whose status is `approved`, `completed`, or `dispatched`; “pending” includes `pending` and `pending_approval`.
- **Incoming:** register incoming letters, including sender, receiver, received date and time, classification/security metadata, and optional attachment; display current status and review outcome.
- **Outgoing:** create and track outgoing letters through review, registration, and dispatch.
- **Internal:** register and review internal memos.
- **Search:** search record fields such as reference number, sender/receiver, title, description, keywords, or sender’s unique number, subject to access checks.
- **Archive:** view archived copies and search archive information. An administrator can delete an archive snapshot; that action is distinct from deleting the live record.
- **Notifications:** display in-app workflow and document notices, with a timestamp and a link where available. Opening the notifications page marks notices as read.
- **Workflow:** configure and enable/disable workflow-rule records. The page stores rules such as trigger and action types; do not describe every configured rule as automatically executed unless that behavior is demonstrated for that specific rule.
- **Reports:** generate daily, monthly, workflow, and compliance-style report previews.
- **User Management:** create user accounts, reset passwords, and deactivate eligible accounts.
- **Audit:** view logged system actions where the role is permitted.
- **Settings/Profile:** application/account settings available to the role.
- **NALA Assistant:** in-app, keyword/intent-based help in English, Amharic, or Afaan Oromoo.

---

## 4. Architecture and request flow

The application follows a conventional PHP server-rendered web architecture:

```text
User's browser
   │  Page request, form submission, or JavaScript request
   ▼
Apache + PHP
   ├── Session/authentication and permission checks
   ├── Page router and module views
   ├── Action handlers and API endpoints
   ├── Shared functions (data access, workflow, audit, archive, notifications)
   └── PDO database connection
          ▼
       MySQL database
```

### Typical page request

1. A user signs in. The application validates the account and establishes a PHP session.
2. The browser requests `index.php?page=...`.
3. The central entry point checks authentication and calls the role-permission helper.
4. The selected `pages/*.php` file is included in the shared application layout.
5. The page loads or submits data through shared PHP functions, action handlers, or API endpoints.
6. PHP reads or writes MySQL data through PDO.
7. For standard forms, handlers process the request and redirect back with a success/error message. Some interactive actions return JSON and are handled by JavaScript.

### Main architectural files

| Location | Responsibility |
|---|---|
| `index.php` | Authenticated application entry point, page routing, layout, and assistant interface |
| `config/database.php` | PDO/MySQL connection configuration |
| `includes/functions.php` | Shared roles/permissions and data, workflow, archive, notification, and audit helpers |
| `includes/sidebar.php`, `includes/header.php` | Shared navigation and page header |
| `pages/` | Server-rendered application modules |
| `actions/` | Form/action handlers for create, review, registration, dispatch, administration, and related tasks |
| `api/` | JSON or direct-response endpoints such as search, assistant help, and attachment viewing |
| `assets/js/`, `assets/css/` | Browser-side interactions, language strings, and presentation |
| `database.sql` | Base MySQL schema and initial seed accounts |
| `sql/` | Supplementary schema migration(s) |

The project does not use a separate REST backend or a JavaScript single-page application. PHP performs most page rendering and business operations; JavaScript enhances selected interactions.

---

## 5. Data model

The base schema is defined in `database.sql`. The main record types are kept in separate tables because they have different fields and workflow needs.

| Table | Purpose | Examples of stored information |
|---|---|---|
| `users` | Application identities and role assignments | Username, name, email, password hash, role, department, profile photo |
| `incoming_letters` | Received correspondence | Reference, sender, receiver, title, received date, status, remarks, signature fields, attachment fields |
| `outgoing_letters` | Correspondence sent by NALA | Reference, sender/receiver, date, status, workflow stage, registration and dispatch details, signature/stamp fields |
| `internal_memos` | Internal correspondence | Reference, sender/receiver, date, status, remarks, signature fields, attachment fields |
| `audit_logs` | Recorded administrative and workflow actions | Actor, role, action, details, IP/user-agent fields, timestamp |
| `workflow_rules` | Configurable workflow-rule definitions | Name, trigger event, action type/configuration, active state |
| `archive_records` | Preserved snapshot of an archived record | Original type/id, reference, title, archived data, archiving actor/time, retention fields |

Some runtime helpers create or extend schema objects as needed. For example, the application includes runtime schema setup for notifications and some workflow fields, and the incoming-letter helper adds fields such as security level and received time when missing. There is also a supplementary user-administration SQL migration. Consequently, the base SQL file alone may not describe every column present in a fully initialized installation; installation procedures should include the applicable migration/setup steps.

### Relationships and data flow

The document tables hold the current/live record. An archive row stores a snapshot associated with the original record; it is not the same thing as the live row. Outgoing letters have additional workflow fields to track senior review, registration, and dispatch. Notifications link a user to a message about a record or action. Audit entries capture selected actions as a historical log.

The design primarily links records using their reference numbers and original table/id information rather than relying on a single normalized “all documents” table. This makes the three record types distinct and means shared operations are implemented through helper functions that know which source table to use.

### Attachments

The upload handler accepts supported image formats and common office/PDF extensions. File metadata and content may be held in the record table, with an upload copy also placed in the `uploads/` directory. Attachment viewing checks whether the logged-in user can view the associated document before returning the content. This is convenient for a local prototype, but duplicate storage and local filesystem handling should be reviewed before production deployment.

---

## 6. End-to-end workflows

### 6.1 Incoming letter

1. An authorized user opens Incoming and submits the letter details.
2. The system uses the supplied reference number or generates one if omitted.
3. The handler validates the received time and saves the record with an initial pending status.
4. If an attachment is included, the handler processes it and saves its name/content according to the upload implementation.
5. The system can notify the receiver and records a creation action in the audit log.
6. A reviewer with the required access reviews the record and approves or rejects it with a remark.
7. A completed review is archived as a snapshot; relevant users, including archivists for applicable completed outcomes, can receive notifications.
8. Authorized users can search for and view the record or its archived copy.

The received date/time represent when the letter was received; the database creation timestamp represents when the system row was created. These are not necessarily the same event.

### 6.2 Internal memo

1. An authorized user creates a memo with sender, receiver, title, date, description, and optional attachment.
2. The memo begins in a pending state.
3. The reviewer approves or rejects it and may enter a remark.
4. The result is reflected in status and, for completed outcomes, the archive/notification workflow is used where applicable.

### 6.3 Outgoing letter (multi-stage)

The outgoing workflow is more involved than a single approval:

1. **Draft/submission:** An authorized user creates the outgoing letter. It enters the configured review workflow.
2. **Head-office review/routing:** The head-review stage records the reviewer, time, and remark, and forwards the letter for senior decision.
3. **Director decision:** The Director can approve or reject. Approval records the decision/signature/stamp information and makes the approved document available to the following processing stage. Rejection records the outcome and routes it to the archive/notification flow.
4. **Records Office registration:** A Records Officer registers an approved outgoing item and records its registration reference and actor/time.
5. **Dispatch:** The Records Office records the dispatch method, recipient, reference, actor, and dispatch time. Dispatch updates the archived copy so it reflects final dispatch details.
6. **Retrieval:** The document remains searchable and its status/history can be reviewed by authorized users.

An approval means the document passed a review stage; it is not the same as registration or dispatch. In a defense, explain these as separate workflow events.

### 6.4 Archive

Archiving preserves a copy of record data for retrieval. The archive table carries the serialized snapshot and archive metadata. Deleting an archive entry removes the snapshot, not necessarily the original live record. The separate administrator-only live-letter deletion handler retains an existing archive copy, so the two delete operations have different consequences.

### 6.5 Notifications

Notifications are in-app messages intended to tell a user that a record or workflow event needs attention. Depending on the event, messages can include document title/reference, sender, and event details. They are not a guarantee that an external email or SMS has been delivered; external mail behavior depends on mail configuration and the particular operation.

---

## 7. Authentication, authorization, and data handling

### Authentication

The login flow looks up the account and verifies the submitted password against a stored password hash. Password creation/reset uses password hashing rather than storing a readable password. A successful login places user identity and role information into the PHP session.

### Authorization

The application has two related authorization layers:

1. **Module-level checks:** `hasPermission()` evaluates a role's configured permissions before the central router serves a page.
2. **Operation/document-level checks:** action handlers and shared helpers check who may review, edit, delete, or view a particular record.

This distinction matters: being able to open a module does not automatically grant permission to perform every action on every document.

### Database and output handling

The application uses PDO, and shared data functions generally use parameterized SQL statements. Server-rendered values are commonly escaped with `htmlspecialchars()` before display. Audit helpers record selected actions such as record and user changes.

### Defense wording

It is accurate to say the project implements password hashing, sessions, role-based module checks, document-level checks in relevant flows, prepared database operations, and selected audit logging. Do **not** claim that these alone prove the application is penetration-tested or production-secure. A production deployment would still need a security review, robust CSRF protection, secure session/cookie configuration, strict upload validation and storage controls, secrets/configuration management, backup/restore procedures, and monitoring.

---

## 8. Reports, dashboard, and assistant

### Dashboard

The dashboard summarizes record totals and provides links from summary cards to matching record lists. Its status grouping is intentionally a user-facing summary rather than a full workflow analytics model:

- **Incoming / Outgoing:** counts by record type.
- **Registered:** all three record categories combined.
- **In Process:** status exactly `in-process`.
- **Pending:** `pending` and `pending_approval`.
- **Completed:** `approved`, `completed`, and `dispatched`.

Explain the status definitions if asked why dashboard totals differ from a report that groups statuses differently.

### Reports

The report screen offers daily register, monthly summary, workflow performance, and compliance-style previews. These are generated from current application data. Some report labels describe intended operational/compliance topics; the labels are not independent certification, a formal records audit, or proof of compliance with an external standard. Validate report formulas against sample data before using their figures as institutional evidence.

### NALA Assistant

The assistant helps explain how to use system modules and common tasks. It supports English, Amharic, and Afaan Oromoo response modes, with basic automatic language detection. Its current implementation uses predefined answers and keyword/intent matching in PHP; it is **not** a generative AI model, it does not understand arbitrary questions with guaranteed accuracy, and it does not query the database to answer live record questions. Present it as a multilingual, rule-based in-application help assistant.

---

## 9. Installation and demonstration checklist

### Local setup (XAMPP)

1. Install/start XAMPP and start **Apache** and **MySQL**.
2. Place the project under the Apache document root, for example `C:\xampp\htdocs\nala-erms`.
3. Open phpMyAdmin and import `database.sql`. This creates the `nala_erms` database and the base tables/seed data.
4. Review `config/database.php` and set the local database connection values for the machine.
5. Apply any relevant supplementary migration under `sql/` when installing an older/base database, especially the user-administration migration. Runtime helpers also initialize selected schema additions.
6. Ensure the PHP version has PDO MySQL enabled and that the web process can write to the required upload directory.
7. Browse to `http://localhost/nala-erms/` and sign in using an account configured in the local database.
8. For any shared or deployed environment, replace demo/default account credentials, use a dedicated database account, and do not expose development configuration or uploaded files publicly.

The seeded accounts contain password hashes, not a safe universal set of credentials to publish. Use the project's local demonstration setup or create test users through the administrator account; never reuse demo credentials on a live system.

### Suggested defense demonstration

Prepare test records and users before the presentation so the workflow can be shown without relying on improvised data.

1. Sign in as an authorized user and point out the role-based navigation and dashboard counts.
2. Create an incoming letter with a unique reference, receiver, received date/time, and (optionally) a safe test attachment.
3. Show the pending item and its notification.
4. Sign in as the reviewing role, open the item, add a remark, and approve or reject it. Show the confirmation, changed status, and archive entry.
5. Create an outgoing letter and demonstrate head review, Director decision, Records Office registration, and dispatch as separate stages.
6. Search by reference number and show the record/attachment subject to access permissions.
7. Open Archive, inspect the stored details, and explain the difference between deleting an archive snapshot and deleting a live letter.
8. Show an audit entry and a report preview, explicitly identifying them as operational views.
9. Ask the assistant a supported help question in English, Amharic, or Afaan Oromoo and explain that responses are rule-based.

Use fictional names, emails, reference numbers, and non-sensitive attachment files during the defense.

---

## 10. Testing and validation status

The code changes immediately preceding this guide were syntax-checked for PHP and checked with the editor's diagnostics where applicable. This guide itself is documentation and does not require a code test. A full end-to-end test against a running XAMPP/MySQL installation has not been performed as part of preparing this guide.

For a formal defense or release, prepare a repeatable test matrix:

| Test area | Example test |
|---|---|
| Login | Valid password succeeds; invalid password fails; inactive user cannot proceed |
| Role access | Each role sees only its configured pages; direct URL access is denied/redirected |
| Incoming CRUD | Create, edit while eligible, validate received time, reject duplicate reference |
| Review | Authorized reviewer can approve/reject once; repeated or unauthorized action returns a clear message |
| Outgoing workflow | Head review → Director decision → registration → dispatch, with correct stage metadata |
| Archive | Completed record is present; snapshot shows final dispatch metadata; archive deletion does not delete the live record |
| Search and attachment | Search returns expected fields; unauthorized user cannot view a restricted record/attachment |
| Notifications/audit | Expected event creates a notice/log; notification read state behaves correctly |
| Reports/dashboard | Counts reconcile with a known set of test records and documented status definitions |
| Recovery | Database backup can be restored and attachments are included in the recovery plan |

Record actual results, test data, and date when these tests are run. Do not claim that every row passed unless it was actually executed.

---

## 11. Current limitations and future work

These points are useful in a defense because they show an understanding of the difference between a functioning prototype and a production records platform.

1. **Integration and deployment testing:** The current work has not been fully exercised against a clean, running production-like environment. A complete acceptance test should cover every role and workflow branch.
2. **Schema evolution:** Some schema setup occurs at runtime and some through SQL migrations. A versioned migration system would make upgrades and fresh installs more predictable.
3. **Security hardening:** Conduct a security review and add/verify CSRF protection, secure session settings, upload size/type/content controls, authorization coverage, and production secret handling.
4. **Attachment storage:** Current handling uses database content and local uploads in parts of the application. A production design should standardize storage, validate file content, prevent direct public access, and define backup/retention policies.
5. **Workflow-rule execution:** The interface can store and toggle workflow-rule definitions, but every rule type should be tested end-to-end before describing it as an automatically executed workflow.
6. **Assistant capability:** The multilingual assistant is keyword/intent-based with predefined answers. Future versions could use a vetted knowledge base or an approved language model, while preventing disclosure of sensitive records.
7. **Reports and compliance:** Reconcile report calculations against documented definitions and conduct an independent records/compliance review before making formal compliance claims.
8. **Audit quality:** Define which events must be logged, who may inspect them, how logs are protected from alteration, and how long they are retained.
9. **Operational resilience:** Add documented backup/restore, retention, disaster recovery, performance, and monitoring procedures.
10. **Accessibility and localization:** Test keyboard access, screen-reader behavior, responsive layout, and completeness/accuracy of all translated interface strings.

---

## 12. Sample oral defense presentation

> Good morning. My project is the NALA Enhanced Records Management System, a web-based prototype for organizing correspondence and its workflow. The problem it addresses is the difficulty of registering, routing, tracking, and retrieving letters when information is distributed across manual or disconnected processes.
>
> The system is implemented with PHP, MySQL, HTML, CSS, and JavaScript. PHP handles authentication, page routing, workflow actions, and database operations through PDO. MySQL stores users, incoming letters, outgoing letters, internal memos, archive snapshots, workflow-rule definitions, and audit information.
>
> Users work through role-oriented modules. Depending on their role, they can register records, review documents, manage outgoing processing, search, archive, or perform administrative tasks. Incoming letters and internal memos use review outcomes, while outgoing letters have additional stages: head-office review, Director decision, Records Office registration, and dispatch.
>
> The application also provides notifications, archive snapshots, a dashboard, report previews, and an in-application help assistant. The assistant can respond in English, Amharic, and Afaan Oromoo, but its current implementation is rule-based rather than generative.
>
> I would describe this as a functional project prototype rather than a certified production records platform. The next steps are full integration testing, security hardening, predictable database migrations, verified reporting, and operational backup and retention planning. Thank you.

---

## 13. Likely defense questions and suggested answers

### 1. Why did you choose a web-based system?

It allows authorized users to access a shared system through a browser without installing a separate desktop application on every workstation. The server centralizes application logic and data. Access still depends on deployment, network availability, and correct authorization configuration.

### 2. Why are incoming, outgoing, and internal records separate tables?

They share common concepts such as a reference, title, participants, and status, but their lifecycle fields differ. Outgoing letters, for example, need registration and dispatch data. Separate tables keep those distinctions explicit. Shared PHP helpers provide common operations where appropriate.

### 3. How is a user prevented from accessing an unauthorized module?

The central page router uses the session role and the permission map to decide whether a requested module is allowed. Relevant action handlers also check authorization, and document operations use record-level checks. Both layers matter; hiding a menu item alone would not be sufficient.

### 4. What is the difference between approval, registration, and dispatch?

Approval is a review decision. Registration assigns or records the formal records-office reference. Dispatch records how and to whom an approved outgoing document was sent. They are separate stages with separate metadata.

### 5. What is the purpose of the archive?

The archive preserves a snapshot for later retrieval after a workflow event. It is separate from the live record, so deleting an archive snapshot is not automatically the same as deleting the original record.

### 6. How does the system handle passwords?

It stores password hashes and verifies login attempts against those hashes. Passwords should not be stored or transmitted as readable values in the database. A deployed system still needs secure configuration, session protection, and credential rotation.

### 7. Does the assistant use a large language model?

No. The current assistant uses keyword/intent matching and predefined responses. It supports English, Amharic, and Afaan Oromoo response modes, but does not generate open-ended answers or retrieve live database records.

### 8. Are the reports proof that the organization complies with a records-management standard?

No. They are operational report previews produced from application data. A report label is not certification. Formal compliance claims require documented criteria, validated calculations, and an independent assessment.

### 9. What are the main limitations?

The main areas for future work are complete end-to-end testing, security hardening, consistent schema migrations, reliable attachment-storage controls, validation of reports and workflow automation, and operational backup/retention planning.

### 10. What would you improve first?

I would first establish a clean repeatable installation and automated end-to-end tests for each role and workflow stage. In parallel, I would perform a security review and standardize migrations and attachment storage. That would provide a reliable base for improving analytics and workflow configuration.

---

## 14. Quick glossary

| Term | Meaning in this project |
|---|---|
| Record | An incoming letter, outgoing letter, or internal memo managed by the application |
| Reference number | Identifier used to register and retrieve a record |
| Workflow stage | The current step in processing, especially for outgoing letters |
| Review outcome | Approval or rejection recorded by an authorized reviewer |
| Archive snapshot | Preserved copy of record data and archive metadata |
| Role permission | Rule determining which application modules a role may access |
| Document-level authorization | Rule determining whether a user may perform an operation on a particular record |
| Audit log | Historical entry for a selected user or workflow action |
| Rule-based assistant | Help feature that chooses predefined responses using matched words/intents |

---

## Source map for further study

Use these files as the primary implementation references while preparing to answer detailed questions:

- `database.sql` — base database tables and initial seed accounts.
- `includes/functions.php` — role permissions, shared data operations, workflow helpers, notifications, archiving, and audit logic.
- `index.php` — application entry point, page authorization, layout, and assistant UI.
- `login.php` and `actions/login_action.php` — login interface and authentication handler.
- `actions/add_incoming.php`, `actions/add_outgoing.php`, and `actions/add_internal.php` — record creation/update handlers.
- `actions/approve_letter.php`, `actions/reject_letter.php`, and `actions/forward_outgoing.php` — review and routing operations.
- `actions/register_outgoing.php` and `actions/dispatch_outgoing.php` — records-office processing and dispatch.
- `pages/archive.php` and `actions/delete_archive_record.php` — archive UI and archive-snapshot deletion.
- `api/search.php` and `api/view_attachment.php` — record search and permission-checked attachment viewing.
- `pages/dashboard.php`, `pages/reports.php`, and `actions/generate_report.php` — dashboard and report previews.
- `api/assistant.php` — rule-based multilingual help responses.
- `sql/2026_07_29_user_administration.sql` — supplementary user-administration schema migration.
