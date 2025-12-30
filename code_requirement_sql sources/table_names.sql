USE movie;

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL, -- MD5 produces a 32-char string
    join_date DATE NOT NULL,
    profile_avatar VARCHAR(255),
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
);


-- Movies table
CREATE TABLE movies (
    movie_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    release_year INT NOT NULL,
    description VARCHAR(2000),
    poster_image VARCHAR(255),
    runtime INT,
    budget BIGINT DEFAULT 0,
    revenue BIGINT DEFAULT 0,
    original_language VARCHAR(10),
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    total_ratings INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_release_year (release_year),
    INDEX idx_rating (average_rating)
);

-- Genres table
CREATE TABLE genres (
    genre_id INT PRIMARY KEY AUTO_INCREMENT,
    genre_name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movie_Genres junction table (many-to-many)
CREATE TABLE movie_genres (
    movie_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (movie_id, genre_id),
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE CASCADE,
    INDEX idx_genre (genre_id)
);

-- Cast members table
CREATE TABLE cast_members (
    cast_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    photo_url VARCHAR(255),
    biography TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

-- Movie_Cast junction table
CREATE TABLE movie_cast (
    movie_id INT NOT NULL,
    cast_id INT NOT NULL,
    character_name VARCHAR(100),
    cast_order INT DEFAULT 0,
    PRIMARY KEY (movie_id, cast_id),
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
    FOREIGN KEY (cast_id) REFERENCES cast_members(cast_id) ON DELETE CASCADE,
    INDEX idx_cast_order (cast_order)
);

-- Crew members table
CREATE TABLE crew_members (
    crew_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    photo_url VARCHAR(255),
    biography TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

-- Movie_Crew junction table
CREATE TABLE movie_crew (
    movie_id INT NOT NULL,
    crew_id INT NOT NULL,
    role VARCHAR(50) NOT NULL,
    PRIMARY KEY (movie_id, crew_id, role),
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
    FOREIGN KEY (crew_id) REFERENCES crew_members(crew_id) ON DELETE CASCADE,
    INDEX idx_role (role)
);

-- Reviews table
CREATE TABLE reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    movie_id INT NOT NULL,
    user_id INT NOT NULL,
    review_text TEXT NOT NULL,
    rating_value DECIMAL(3,1) NOT NULL,
    like_count INT DEFAULT 0,
    is_spoiler BOOLEAN DEFAULT FALSE,
    is_flagged BOOLEAN DEFAULT FALSE,
    report_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_movie_review (user_id, movie_id),
    INDEX idx_movie (movie_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_likes (like_count)
);

-- Review reports table
CREATE TABLE review_reports (
    report_id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_review (review_id)
);

-- Watchlists table
CREATE TABLE watchlists (
    watchlist_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    watchlist_name VARCHAR(50) NOT NULL,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
);

-- Watchlist_Movies junction table
CREATE TABLE watchlist_movies (
    watchlist_id INT NOT NULL,
    movie_id INT NOT NULL,
    personal_notes TEXT,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (watchlist_id, movie_id),
    FOREIGN KEY (watchlist_id) REFERENCES watchlists(watchlist_id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
    INDEX idx_movie (movie_id),
    INDEX idx_date_added (date_added)
);

-- Movie trailers table
CREATE TABLE movie_trailers (
    trailer_id INT PRIMARY KEY AUTO_INCREMENT,
    movie_id INT NOT NULL,
    trailer_url VARCHAR(255) NOT NULL,
    trailer_type ENUM('teaser', 'official', 'behind_scenes') DEFAULT 'official',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
    INDEX idx_movie (movie_id)
);

-- Admin activity log table
CREATE TABLE admin_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    target_type VARCHAR(50),
    target_id INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_admin (admin_id),
    INDEX idx_created (created_at)
);

-- Password reset tokens table
CREATE TABLE password_reset_tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);

-- Insert some sample genres
INSERT INTO genres (genre_name) VALUES
('Action'),
('Comedy'),
('Drama'),
('Science Fiction'),
('Horror'),
('Thriller'),
('Romance'),
('Adventure'),
('Animation'),
('Documentary'),
('Crime'),
('Fantasy'),
('Mystery'),
('War'),
('Western');


-- Insert sample users using MD5 for passwords
INSERT INTO users (username, email, password_hash, join_date, profile_avatar, is_admin) VALUES
('john_doe', 'john@example.com', MD5('password123'), '2023-01-15', 'avatar1.jpg', FALSE),
('jane_smith', 'jane@example.com', MD5('password123'), '2023-02-20', 'avatar2.jpg', FALSE),
('movie_buff', 'buff@example.com', MD5('password123'), '2023-03-10', 'avatar3.jpg', FALSE),
('critic_mike', 'mike@example.com', MD5('password123'), '2023-04-05', 'avatar4.jpg', FALSE),
('admin_sarah', 'sarah@example.com', MD5('adminpass'), '2022-12-01', 'avatar5.jpg', TRUE),
('mod_alex', 'alex@example.com', MD5('modpass'), '2023-01-01', 'avatar6.jpg', TRUE),
('emma_watson', 'emma@example.com', MD5('password123'), '2023-05-12', 'avatar7.jpg', FALSE),
('chris_evans', 'chris@example.com', MD5('password123'), '2023-06-18', 'avatar8.jpg', FALSE);


CREATE TABLE IF NOT EXISTS favorites (
    favorite_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    entity_type ENUM('movie', 'cast', 'user') NOT NULL,
    entity_id INT NOT NULL,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, entity_type, entity_id),
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_date_added (date_added)
);

-- User watched movies table - independent of watchlists
CREATE TABLE IF NOT EXISTS user_watched_movies (
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    watched_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, movie_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_movie (movie_id),
    INDEX idx_watched_date (watched_date)
);


CREATE TABLE IF NOT EXISTS review_likes (
    like_id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_review_like (user_id, review_id),
    INDEX idx_review (review_id),
    INDEX idx_user (user_id)
);


ALTER TABLE movies 
ADD COLUMN xgrab_average_rating DECIMAL(3,2) DEFAULT 0.00,
ADD COLUMN xgrab_total_ratings INT DEFAULT 0;