-- Setup DB Multi-Guru & Multi-Mapel (Plaintext passwords for local testing)
CREATE DATABASE IF NOT EXISTS bank_soal_smks;
USE bank_soal_smks;

DROP TABLE IF EXISTS student_answers;
DROP TABLE IF EXISTS exam_questions;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS exams;
DROP TABLE IF EXISTS teacher_subjects;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','guru','siswa') NOT NULL DEFAULT 'siswa',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE teacher_subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT NOT NULL,
  subject_id INT NOT NULL,
  UNIQUE KEY uniq_teacher_subject (teacher_id, subject_id),
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject_id INT NOT NULL,
  created_by INT,
  type ENUM('pg','esai') NOT NULL,
  question TEXT NOT NULL,
  option_a VARCHAR(255),
  option_b VARCHAR(255),
  option_c VARCHAR(255),
  option_d VARCHAR(255),
  option_e VARCHAR(255),
  answer_key VARCHAR(5),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  duration INT NOT NULL,
  start_time DATETIME NOT NULL,
  end_time DATETIME NOT NULL,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_id INT NOT NULL,
  question_id INT NOT NULL,
  UNIQUE KEY uniq_exam_question (exam_id, question_id),
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_id INT NOT NULL,
  student_id INT NOT NULL,
  question_id INT NOT NULL,
  answer TEXT,
  score DECIMAL(5,2),
  answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_answer (exam_id, student_id, question_id),
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- DATA MASTER
INSERT INTO subjects (name) VALUES
('Matematika'),
('Bahasa Indonesia'),
('Bahasa Inggris'),
('Produktif RPL'),
('PPKN');

-- USERS (plaintext password - local testing)
INSERT INTO users (name,email,password,role) VALUES
('Administrator','admin@smks.local','admin123','admin'),
('Ibu Guru Rina','rina@guru.local','guru123','guru'),
('Pak Guru Andi','andi@guru.local','guru123','guru'),
('Siswa Budi','budi@siswa.local','siswa123','siswa');

INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES
(2,1),
(2,4),
(3,2),
(3,3);

-- ✅ INSERT QUESTIONS (SUDAH DIPERBAIKI)
INSERT INTO questions
(subject_id, created_by, type, question,
 option_a, option_b, option_c, option_d, option_e, answer_key)
VALUES
(1,2,'pg','2 + 2 = ?','3','4','5','6',NULL,'B'),
(1,2,'pg','Hasil dari 5 x 6 adalah ...','28','30','26','36',NULL,'B'),
(2,3,'esai','Jelaskan perbedaan kata baku dan tidak baku.',
 NULL,NULL,NULL,NULL,NULL,NULL);

-- Tambahan user untuk testing login
INSERT INTO users (name,email,password,role) VALUES
('Test User','test@siswa.local','123456','siswa');
