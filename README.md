# 🎬 xGrab - Movie Discovery Platform

> A modern, responsive web application for discovering movies, tracking watchlists, and sharing reviews. Built with **PHP**, **MySQL**, and **TailwindCSS**.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4.svg?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1.svg?logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.0+-06B6D4.svg?logo=tailwindcss&logoColor=white)

## 📖 Overview

**xGrab** is a comprehensive movie database application that allows users to explore a vast collection of films, manage their personal watchlists, and engage with the community through reviews and ratings. 

Designed with a premium **"Netflix-style"** aesthetic, it features a responsive glassmorphism UI/UX, smooth animations, and a powerful admin dashboard for content management.

## ✨ Key Features

### 👤 **User Experience**
-   **Smart Authentication**: Secure login and registration system with role-based access (User/Admin).
-   **Dynamic Home Page**: Hero carousel with top-rated movies and "shimmer" loading effects.
-   **Advanced Search**: Real-time search suggestions and genre filtering.
-   **Personalization**: 
    -   ❤️ **Favorites**: Quickly save movies you love.
    -   📝 **Watchlists**: Create custom lists (e.g., "Weekend Watch", "Horror Night").
    -   ✅ **Watched History**: Track what you've seen.
-   **Social Features**: Write reviews, rate movies, and see what the community thinks.

### 🛠 **Admin Dashboard**
-   **User Management**: View, ban, or promote users.
-   **Content Moderation**: Review and flag user-submitted content.
-   **Analytics**: View platform stats (total users, active reviews, etc).

### 🎨 **Technical Highlights**
-   **Responsive Design**: Fully optimized for Mobile, Tablet, and Desktop using TailwindCSS.
-   **Performance**: Optimized SQL queries with proper indexing.
-   **Security**: SQL injection protection, session management, and password hashing (MD5 legacy support, upgradable).
-   **Clean Architecture**: Modular code structure separating logic, views, and configuration.

## 🚀 Getting Started

### Prerequisites
-   PHP 8.0 or higher
-   MySQL 5.7 or higher
-   Web Server (Apache/Nginx) - *Recommended to use MAMP/XAMPP for local development*

### Installation

1.  **Clone the repository**
    ```bash
    git clone https://github.com/Start-Tech-Academy/xGrab.git
    cd xGRAB
    ```

2.  **Database Setup**
    -   Create a new MySQL database named `movie`.
    -   Import the schema from `database/schema.sql`.
    ```bash
    mysql -u root -p movie < "database/schema.sql"
    ```

3.  **Configuration**
    -   Copy the example config file:
    ```bash
    cp includes/config.example.php includes/config.php
    ```
    -   Edit `includes/config.php` with your database credentials:
    ```php
    define('DB_USERNAME', 'your_username');
    define('DB_PASSWORD', 'your_password');
    ```

4.  **Run the Application**
    -   Point your web server (MAMP/XAMPP) to the project directory.
    -   Open your browser and navigate to `http://localhost/xGRAB`.

## 📂 Project Structure

```
xGRAB/
├── admin/          # Admin dashboard & management scripts
├── auth/           # Authentication logic
├── includes/       # Shared components (Nav, Footer, Config)
├── movies/         # Movie browsing & details pages
├── reviews/        # Review submission & moderation
├── uploads/        # User uploaded avatars & content
├── connect.php     # Database connection wrapper
├── index.php       # Landing page
└── image_handler.php # Image processing utility
```

## 🔒 Security Note
This project uses MD5 for password hashing for educational simplicity. For a production environment, it is highly recommended to upgrade to `password_hash()` and `password_verify()` (Bcrypt/Argon2).

## 📄 License
This project is open-source and available under the [MIT License](LICENSE).

---
*Built with ❤️ by Emir Furqan*
