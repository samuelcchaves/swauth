use sw_auth;

CREATE TABLE roles (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    role_name        VARCHAR(100) NOT NULL UNIQUE,
    role_description VARCHAR(150) NULL,
    level            INT NOT NULL UNIQUE,      -- ex: admin=100, manager=75, user=50, viewer=25
    requires_mfa     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT = 10;
 
INSERT INTO roles (role_name, role_description, level, requires_mfa) VALUES
    ('admin',   'Acesso total ao sistema',                    100, TRUE),
    ('manager', 'Gestão operacional, sem administração',       75, FALSE),
    ('user',    'Utilizador padrão',                           50, FALSE),
    ('viewer',  'Apenas consulta, sem permissões de escrita',  25, FALSE);
 
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(254) NOT NULL UNIQUE,  
    first_name VARCHAR(50) NULL,
    last_name VARCHAR(50) NULL,
    email_verified BOOLEAN NOT NULL DEFAULT FALSE,
    password_hash VARCHAR(255) NOT NULL,          -- Argon2id encoded string
    password_changed_at TIMESTAMP NULL,
    must_change_password BOOLEAN NOT NULL DEFAULT FALSE,
    failed_login_count INT NOT NULL DEFAULT 0,
    locked_until TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 
    CONSTRAINT FK_USERS_ROLE
        FOREIGN KEY (role_id) REFERENCES roles(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT = 1000;

CREATE TABLE login_audit (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    email_attempted VARCHAR(254) NOT NULL,
    ip VARCHAR(45) NOT NULL,            
    success BOOLEAN NOT NULL,
    failure_reason VARCHAR(100) NULL,                
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 
    CONSTRAINT FK_AUDIT_USER
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL        
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE mfa_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mfa_type ENUM('totp') NOT NULL DEFAULT 'totp',
    secret_encrypted  VARBINARY(255) NOT NULL,   -- encriptado a nível de aplicação, chave fora da BD
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
 
    CONSTRAINT FK_MFA_USER
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE mfa_backup_codes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code_hash CHAR(64) NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 
    CONSTRAINT FK_BACKUP_USER
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE sessions (
    id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
    session_token_hash  CHAR(64) NOT NULL UNIQUE,   -- SHA-256 hex do token bruto
    user_id             INT NOT NULL,
    user_agent          VARCHAR(255) NULL,
    ip_created          VARCHAR(45) NOT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at        TIMESTAMP NULL,
    expires_at          TIMESTAMP NOT NULL,
    revoked_at          TIMESTAMP NULL,
 
    CONSTRAINT FK_SESSIONS_USER
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE password_reset_tokens (
    id  BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    t_status ENUM('pending', 'used', 'superseded') NOT NULL DEFAULT 'pending',
    expires_at TIMESTAMP NOT NULL,
    resolved_at TIMESTAMP NULL,    
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 
    CONSTRAINT FK_RESET_USER
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE email_verification_tokens (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    t_status ENUM('pending', 'used', 'superseded') NOT NULL DEFAULT 'pending',
    expires_at TIMESTAMP NOT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 
    CONSTRAINT FK_VERIFY_USER
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;






