my_loginCREATE DATABASE my_login ;
USE my_login ;
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    mot_de_passe VARCHAR(255),
    roleu ENUM('admin', 'formateur', 'etudiant')
);



SELECT *FROM utilisateurs;