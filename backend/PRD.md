# Project Requirements Document (PRD)
**Project Name:** EASY GREEN
**Target Platform:** Mobile (Android & iOS) and Web
**Primary Language:** Arabic

---

## 1. Project Overview & Business Objectives

### 1.1 Summary
EASY GREEN is a digital system designed for farmers and agricultural authorities. It aims to improve the reporting of agricultural pests and problems by facilitating the collection of immediate and accurate field data. The system acts as a central hub to digitize field reporting, establish a national agricultural database, and support the broader digital transformation of the agricultural sector.

### 1.2 Problem Statement
Currently, agricultural problem reporting relies heavily on paper reports and individual phone calls. This traditional approach results in slow response times, loss of critical information, lack of an updated decision-making database, and poor spatial tracking of issues.

### 1.3 Value Proposition
The system delivers value across multiple domains:
* **Economic:** Reduces travel costs and the need for physical paper reporting.
* **Agricultural:** Enhances the efficiency of preventive interventions in the field.
* **Administrative:** Accelerates the processing of reports and decision-making.
* **Environmental:** Helps limit the spread of agricultural pests and subsequent environmental damage.

---

## 2. Target Audience & Stakeholders

* **Farmers:** The primary users who utilize their smartphones to rapidly report field problems.
* **Agricultural Inspectors / Observers:** Field agents responsible for verifying incoming reports, analyzing situations, and escalating accurate data.
* **Agricultural Administrations:** Personnel managing the platform to monitor field conditions, assign tasks, and formulate intervention strategies.
* **Higher Authorities (e.g., Ministry):** Officials utilizing aggregated platform data to monitor geographic trends and establish national preventive policies.
* **Tech Team:** Engineers responsible for maintaining system continuity, performance, and security.

---

## 3. User Requirements (Use Cases)

### 3.1 Farmer Flow
* Users must be able to create a new account or log in with existing credentials, and easily modify their profile.
* To submit a report, the user fills out a simple form specifying the problem type, additional notes, geographic location, and attached photos or videos.
* The geographic location can be captured automatically via GPS or selected manually from a map.
* Users can track the history of their submitted reports and monitor current status updates.

### 3.2 Inspector & Admin Flow
* Inspectors update the status of field reports (e.g., new, processing, closed).
* Admins use a centralized dashboard to review, sort, and analyze regional reports.

---

## 4. Functional Requirements

### 4.1 Authentication & Account Management
* Identity verification is conducted via email or phone number.
* The system supports password recovery, profile editing, and account deletion.
* Role-based access is implemented to assign appropriate permissions for farmers, inspectors, and administrators.

### 4.2 Report Processing & Offline Mode
* The system features a defined list of pests and problems for users to select from during report creation.
* Users can edit or delete a report before it begins processing.
* **Offline Mode:** Users can record reports without an active internet connection. Data is stored locally on the device and automatically synchronized to the server once network connectivity is restored.
* **Idempotency & Sync Safety:** The system uses unique client-side identifiers (`client_uuid`) to prevent duplicate report creation during retries on unstable connections.
* **Detailed Feedback:** Admins and Inspectors can provide specific feedback (`inspector_feedback`) when updating a report's status, which is visible to the farmer in the mobile app.

### 4.3 Interactive GIS Map
* The platform includes an interactive geographic map to visualize report distributions.
* Features include highlighting severely affected areas based on report density, zooming, searching, and utilizing specific colors and symbols to indicate severity levels.

### 4.4 Notifications & Alerts
* Real-time notifications are sent to users when the status of their report changes (via Firebase Cloud Messaging - FCM).
* The system broadcasts periodic alerts regarding agricultural campaigns and pest warnings.
* Administrators receive alerts when new reports are submitted from specific target regions.

### 4.5 Analytics & Dashboard
* The admin dashboard categorizes incoming reports by region, date, or problem type, and allows for the assignment of field verification officers.
* The system generates statistical reports and timelines, supporting data exports in PDF, Excel, and CSV formats.

---

## 5. Technical Requirements


### 5.1 Architecture Stack
* **Backend:** PHP 8.5+ (Laravel 12 Framework).
* **Frontend (Web):** Filament v5 (Admin), Livewire v4 (Public/Farmer Web).
* **Mobile Application:** Developed using React Native or Flutter (iOS and Android).
* **Database:** SQLite (Local/Dev), PostgreSQL (Production).
* **API:** RESTful API with Laravel Sanctum for mobile authentication.
* **Caching & Performance:** Report types are versioned to optimize mobile caching and reduce data usage.
* **Media:** Client-side image compression is enforced before upload to optimize data transfer.

### 5.2 Security Constraints
* All data must be encrypted during transit via SSL/TLS and at rest using AES-256.
* The system utilizes OAuth 2.0 for access management and mandates Two-Factor Authentication (2FA) for administrative accounts.
* Protections against SQL Injection (via input filtering) and XSS (via sensitive data encoding) are strictly enforced.
* Audit logs must record all sensitive operations.

### 5.3 Performance & Maintenance
* The system must support 1,000 concurrent active users without degradation.
* Standard response times must not exceed 2 seconds.
* The architecture must accommodate horizontal scaling.
* System performance is monitored via tools like Prometheus and Grafana.
* Automated data backups are conducted at least once daily, supported by an established contingency plan and backup server.

---

## 6. Acceptance Criteria & Success Indicators

* **Usability:** A user must be able to submit a comprehensive report (including location and media) in under one minute.
* **Real-time Operations:** Reports must appear on the administrative dashboard immediately after submission, and users must receive real-time notifications for status updates.
* **Offline Functionality:** The system must capture data and successfully cache it locally when completely disconnected from the network.
* **Business Impact:** The implementation should reduce report processing times by 50% within the first year and achieve a 30% annual growth in active users.
