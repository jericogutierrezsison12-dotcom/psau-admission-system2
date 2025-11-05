# PSAU AI-Assisted Admission System

A fully web-based, AI-assisted, and secure admission management system developed for **Pampanga State Agricultural University (PSAU)**.

This system automates document verification, program recommendations, and real-time admission tracking using **AI, OCR, and cloud technologies**.

---

## 📘 Project Overview

The **AI-Assisted Admission System** was designed to modernize PSAU’s traditional admission workflow by automating document verification, providing intelligent course recommendations, and enabling real-time status tracking.

It integrates:

* **Optical Character Recognition (OCR)** via **PaddleOCR** for document verification
* **Machine Learning (Random Forest)** for program recommendation
* **Firebase** for real-time data synchronization and email notifications
* **Google Cloud SQL** for secure database management
* **Render Cloud** for hosting the PHP-based web application

---

## ⚙️ Features

### 👩‍🎓 For Applicants

* **Secure Registration with Email OTP Verification**
* **Document Upload and AI-Based Verification (OCR)**
* **AI-Powered Course Recommendations** based on STANINE, GWA, strand, and hobbies
* **Real-Time Application Tracking** through Firebase Realtime Database
* **Automated Email Notifications** for application progress and exam schedules
* **Interactive Chatbot** for FAQs and applicant assistance

### 👨‍💼 For Admins & Admission Personnel

* **Administrative Dashboard** for managing applicants, exams, and enrollment
* **Automated Document Verification Review**
* **Program Assignment** based on AI recommendations and exam results
* **Exam & Enrollment Scheduling**
* **Capacity Monitoring** (total enrolled, available slots, and maximum capacity)
* **Activity Logs and Role-Based Access Control (RBAC)** for security

---

## 🧠 Technical Stack

| Component               | Technology Used                                        |
| ----------------------- | ------------------------------------------------------ |
| **Backend**             | PHP 7.4+ (via Render)                                  |
| **Frontend**            | HTML5, CSS3, JavaScript, Bootstrap 5                   |
| **Database**            | Google Cloud SQL (MySQL)                               |
| **Authentication**      | Firebase Authentication (Email/Password + OTP)         |
| **Realtime Updates**    | Firebase Realtime Database                             |
| **AI & OCR**            | PaddleOCR, RandomForestClassifier (Python)             |
| **Chatbot Hosting**     | Hugging Face Spaces                                    |
| **Email Notifications** | Firebase Cloud Functions with Gmail                    |
| **Security**            | reCAPTCHA v3, OTP Verification, RBAC, Password Hashing |
| **Deployment**          | Render Cloud Platform                                  |

---

## 🔐 Security Features

* **Email-based OTP Verification** for new users
* **Password Hashing** using PHP’s `password_hash()`
* **Google reCAPTCHA v3** for bot prevention
* **RBAC** to restrict access by role (admin, admission personnel, department personnel)
* **Encrypted Communication** (AES-256 for Google Cloud SQL, TLS in transit)
* **Activity Logging** for accountability and traceability

---

## 🗄️ Installation and Setup

### 1. Prerequisites

* XAMPP / Local PHP 7.4+ environment (for local testing)
* Firebase Project (free tier or Blaze)
* Google Cloud SQL Instance
* Python 3.8+ (for AI/OCR modules)
* Node.js (for Firebase Cloud Functions)

---

### 2. Database Setup

1. Start MySQL (via XAMPP or Google Cloud SQL)
2. Import the database schema:

   ```bash
   /database/psau_admission.sql
   ```

3. Default credentials:

   ```
   Host: localhost
   Username: root
   Password:
   Database: psau_admission
   ```

---

### 3. Firebase Setup

1. Create a project at https://console.firebase.google.com/
2. Enable:

   * **Authentication** (Email/Password)
   * **Realtime Database**
   * **Cloud Functions**

3. Set environment variables (do **not** expose sensitive credentials):

   ```bash
   firebase functions:config:set gmail.email="your_email@gmail.com" gmail.password=""
   ```

4. Deploy:

   ```bash
   firebase deploy --only functions
   ```

---

### 4. AI and OCR Setup

Install Python dependencies:

```bash
pip install paddleocr pdf2image difflib flask
```

Make sure **PaddleOCR** and **Tesseract** are correctly installed and accessible.

---

### 5. Deployment (Render)

1. Create a new **Web Service** on https://render.com/
2. Connect your GitHub repository
3. Environment:

   ```
   PHP_VERSION=7.4
   DATABASE_URL=mysql://username:password@host:3306/psau_admission
   FIREBASE_PROJECT_ID=your_project_id
   ```

4. Deploy automatically from your main branch.

---

## 🧩 System Architecture

* **Presentation Layer:** HTML, CSS, Bootstrap, JavaScript
* **Application Layer:** PHP backend with API routes for OCR, AI, and Firebase
* **Data Layer:** MySQL (Google Cloud SQL) + Firebase Realtime DB
* **AI Layer:** PaddleOCR & ML-based recommendation via Python Flask microservice
* **Integration Layer:** Firebase Cloud Functions for email notifications

---

## 🧱 File Structure

```
/admin                 → Admin interfaces
/public                → Applicant web pages
/includes              → PHP reusable modules (DB, auth, etc.)
/functions             → Firebase Cloud Functions for emails
/python                → AI services (OCR, recommender, chatbot)
/database              → SQL schema and seed data
/firebase_email.php    → Email notification logic
```

---

## 📊 Evaluation Results (from Study)

| Respondent Group | Mean Rating | Interpretation             |
| ---------------- | ----------- | -------------------------- |
| Students         | 3.24        | Agree (Good)               |
| Admin Staff      | 3.85        | Strongly Agree (Excellent) |
| IT Experts       | 3.71        | Strongly Agree (Excellent) |

Overall, the system demonstrated strong usability, functionality, and reliability, achieving an OCR accuracy of **79.25%**, real-time tracking accuracy of **85%**, and overall user satisfaction of **81%**.

---

## 💡 Key Innovations

* Automated **OCR-based document verification**
* **AI-driven course recommendations**
* **Real-time status updates** via Firebase
* **Cloud-based deployment** for scalability
* **Role-based security and transparency**

---

## 📜 License

**Proprietary – All Rights Reserved.**

Developed for **Pampanga State Agricultural University (PSAU)**.

Unauthorized use, reproduction, or distribution is prohibited.

---

Would you like me to also include a **Firebase Email Integration Section** (like your old README had) but updated to use **email-only OTP verification (no SMS)** and with safe blank credentials (for deployment reference)?