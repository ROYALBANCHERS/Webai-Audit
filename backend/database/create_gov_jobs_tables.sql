-- Government Jobs Table
CREATE TABLE gov_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    department VARCHAR(255),
    description TEXT,
    qualification VARCHAR(255),
    vacancy_count INT DEFAULT 0,
    last_date_to_apply DATE,
    salary VARCHAR(255),
    location VARCHAR(255),
    source_url VARCHAR(1000),
    source_website VARCHAR(255),
    category VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    crawled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_source_url (source_url(255)),
    INDEX idx_last_date (last_date_to_apply),
    INDEX idx_category (category),
    INDEX idx_department (department),
    FULLTEXT idx_title_desc (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job Subscriptions Table
CREATE TABLE job_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    keywords JSON,
    departments JSON,
    qualifications JSON,
    locations JSON,
    notification_methods JSON,
    is_active TINYINT(1) DEFAULT 1,
    last_notified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_subscription (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job Matches Table (Track which jobs matched which users)
CREATE TABLE job_matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    match_score INT DEFAULT 0,
    is_notified TINYINT(1) DEFAULT 0,
    notified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES gov_jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_job_user (job_id, user_id),
    INDEX idx_user_notified (user_id, is_notified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crawl Sources Configuration
CREATE TABLE crawl_sources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    base_url VARCHAR(500) NOT NULL,
    crawl_urls JSON,
    selectors JSON,
    crawl_frequency INT DEFAULT 3600,
    is_active TINYINT(1) DEFAULT 1,
    last_crawled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert popular government job sources
INSERT INTO crawl_sources (name, base_url, crawl_urls, selectors, crawl_frequency) VALUES
('Employment News', 'https://employmentnews.gov.in', 
 '["https://employmentnews.gov.in/Category/Rss"]',
 '{"title": "item title", "link": "item link", "description": "item description", "pubDate": "item pubDate"}',
 3600),

('SSC Official', 'https://ssc.nic.in',
 '["https://ssc.nic.in/News/Updates"]',
 '{"title": ".news-title", "link": "a[href*=Detail]", "description": ".news-content", "date": ".news-date"}',
 7200),

('UPSC Official', 'https://upsc.gov.in',
 '["https://upsc.gov.in/whats-new"]',
 '{"title": ".views-field-title", "link": "a[href*=.pdf]", "description": ".views-field-body", "date": ".views-field-created"}',
 7200),

('Free Job Alert', 'https://www.freejobalert.com',
 '["https://www.freejobalert.com/government-jobs/"]',
 '{"title": ".post-title h3", "link": "a[href*=post]", "description": ".post-content", "date": ".post-date"}',
 3600);
