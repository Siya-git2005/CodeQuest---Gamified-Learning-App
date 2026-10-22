# ⚡ CodeQuest - Gamified College Learning App

## Setup Instructions (XAMPP)

### Step 1: Install XAMPP
Download from https://www.apachefriends.org and install.

### Step 2: Copy Project Files
Copy the `GamifiedLearningApp` folder to:
```
C:/xampp/htdocs/GamifiedLearningApp
```

### Step 3: Start XAMPP
- Open XAMPP Control Panel
- Start **Apache** and **MySQL**

### Step 4: Create Database
1. Open browser → go to http://localhost/phpmyadmin
2. Click "New" (left sidebar)
3. Name it: `gamified_learning` → click "Create"
4. Click "Import" tab → choose `database.sql` from the project folder
5. Click "Go"

### Step 5: Configure Database (if needed)
Open `config.php` and update if your MySQL password is different:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Change if you have a MySQL password
define('DB_NAME', 'gamified_learning');
```

### Step 6: Run the App
Open: http://localhost/GamifiedLearningApp

---

## 🔐 Credentials

### Admin Panel
- URL: http://localhost/GamifiedLearningApp/admin/admin_login.php
- Username: `Admin@123`
- Password: `Sss@2005`

### User Account
- Register at: http://localhost/GamifiedLearningApp/register.php

---

## 📁 Project Structure

```
GamifiedLearningApp/
├── config.php              # Database & app configuration
├── database.sql            # Database setup + seed data
├── index.php               # Landing page
├── login.php               # User login
├── register.php            # User registration
├── dashboard.php           # User dashboard
├── languages.php           # Language selection
├── levels.php              # Level list per language
├── lesson.php              # Lesson content + practice
├── profile.php             # User profile
├── leaderboard.php         # XP leaderboard
├── logout.php              # Logout
├── css/
│   └── style.css           # Main stylesheet
├── js/
│   └── main.js             # JavaScript
├── includes/
│   ├── header.php          # Sidebar header
│   └── footer.php          # Page footer
└── admin/
    ├── admin_login.php     # Admin login
    ├── admin_dashboard.php # Admin dashboard
    ├── manage_lessons.php  # Add/edit/delete lessons
    ├── manage_users.php    # View/delete users
    ├── view_progress.php   # View all progress
    └── admin_logout.php    # Admin logout
```

---

## 🎮 Features

- **8 Programming Languages**: C, C++, Java, Python, PHP, JavaScript, HTML, CSS
- **7 Levels per Language**: From basics to interview prep
- **Level Lock System**: Complete level N to unlock level N+1
- **XP System**: Earn points for each level completed
- **Hint & Solution**: Built-in hints and solutions per lesson
- **Leaderboard**: See top learners by XP
- **Admin Panel**: Full CRUD for lessons + user management
- **Responsive Design**: Works on mobile and desktop

---

## 🛠️ Tech Stack

- **PHP** (server-side)
- **MySQL** (database)
- **HTML5 / CSS3** (frontend)
- **Vanilla JavaScript** (interactivity)
- No external APIs required!
