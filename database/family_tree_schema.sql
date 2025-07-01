-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(32),
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)ENGINE=InnoDB;

-- Families table
CREATE TABLE families (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
)ENGINE=InnoDB;

-- Members table
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    family_id INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    gender ENUM('male', 'female', 'other') DEFAULT 'other',
    birth_date DATE,
    death_date DATE,
    notes TEXT,
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES families(id)
)ENGINE=InnoDB;

-- Relationships table
CREATE TABLE relationships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    family_id INT NOT NULL,
    member_id INT NOT NULL,
    related_member_id INT NOT NULL,
    relationship_type ENUM('parent', 'child', 'partner', 'sibling') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES families(id),
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (related_member_id) REFERENCES members(id)
)ENGINE=InnoDB;

-- Invitations table (for future use)
CREATE TABLE invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    family_id INT NOT NULL,
    invited_email VARCHAR(255),
    invite_code VARCHAR(64) NOT NULL,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES families(id)
)ENGINE=InnoDB; 