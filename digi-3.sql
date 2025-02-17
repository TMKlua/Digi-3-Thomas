-- Table users (Utilisateurs du système)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_first_name VARCHAR(35) NOT NULL,
    user_last_name VARCHAR(35) NOT NULL,
    user_email VARCHAR(35) UNIQUE NOT NULL,
    user_avatar VARCHAR(255) DEFAULT '/img/account/default-avatar.jpg',
    user_role ENUM('ROLE_ADMIN', 'ROLE_TEAM_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_LEAD_DEV', 'ROLE_DEV') NOT NULL,
    user_password VARCHAR(255) NOT NULL,
    user_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    user_updated_by INT,
    FOREIGN KEY (user_updated_by) REFERENCES users(id)
);

-- Table customers (Clients)
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(255) NOT NULL,
    customer_address_street VARCHAR(255) NOT NULL,
    customer_address_zipcode VARCHAR(35) NOT NULL,
    customer_address_city VARCHAR(255) NOT NULL,
    customer_address_country VARCHAR(35) NOT NULL,
    customer_vat VARCHAR(35),
    customer_siren VARCHAR(35),
    customer_reference VARCHAR(255),
    customer_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    customer_updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    customer_updated_by INT,
    FOREIGN KEY (customer_updated_by) REFERENCES users(id)
);

-- Table projects (Projets)
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_name VARCHAR(255) NOT NULL,
    project_description TEXT,
    project_status ENUM('draft', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    project_customer_id INT NOT NULL,
    project_manager_id INT NOT NULL,
    project_start_date DATE NOT NULL,
    project_end_date DATE,
    project_target_date DATE NOT NULL,
    project_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    project_updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    project_updated_by INT,
    FOREIGN KEY (project_customer_id) REFERENCES customers(id),
    FOREIGN KEY (project_manager_id) REFERENCES users(id),
    FOREIGN KEY (project_updated_by) REFERENCES users(id)
);

-- Table tasks (Tâches)
CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_name VARCHAR(255) NOT NULL,
    task_description TEXT,
    task_status ENUM('todo', 'in_progress', 'completed') NOT NULL DEFAULT 'todo',
    task_priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    task_complexity ENUM('low', 'medium', 'high'),
    task_project_id INT NOT NULL,
    task_assigned_to INT,
    task_start_date DATE,
    task_end_date DATE,
    task_target_date DATE NOT NULL,
    task_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    task_updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    task_updated_by INT,
    FOREIGN KEY (task_project_id) REFERENCES projects(id),
    FOREIGN KEY (task_assigned_to) REFERENCES users(id),
    FOREIGN KEY (task_updated_by) REFERENCES users(id)
);

-- Table work_logs (Temps passé sur les tâches)
CREATE TABLE work_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    work_task_id INT NOT NULL,
    work_user_id INT NOT NULL,
    work_hours DECIMAL(5,2) NOT NULL,
    work_date DATE NOT NULL,
    work_description TEXT,
    work_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (work_task_id) REFERENCES tasks(id),
    FOREIGN KEY (work_user_id) REFERENCES users(id)
);

-- Table task_comments (Commentaires sur les tâches)
CREATE TABLE task_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    comment_task_id INT NOT NULL,
    comment_user_id INT NOT NULL,
    comment_content TEXT NOT NULL,
    comment_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comment_task_id) REFERENCES tasks(id),
    FOREIGN KEY (comment_user_id) REFERENCES users(id)
);

-- Table task_attachments (Pièces jointes des tâches)
CREATE TABLE task_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    attachment_task_id INT NOT NULL,
    attachment_name VARCHAR(255) NOT NULL,
    attachment_path VARCHAR(255) NOT NULL,
    attachment_type VARCHAR(100) NOT NULL,
    attachment_size INT NOT NULL,
    attachment_uploaded_by INT NOT NULL,
    attachment_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attachment_task_id) REFERENCES tasks(id),
    FOREIGN KEY (attachment_uploaded_by) REFERENCES users(id)
);

-- Table parameters (Configuration du système)
CREATE TABLE parameters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    param_key VARCHAR(50) NOT NULL UNIQUE,
    param_value TEXT NOT NULL,
    param_description TEXT,
    param_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    param_updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    param_updated_by INT,
    FOREIGN KEY (param_updated_by) REFERENCES users(id)
);
