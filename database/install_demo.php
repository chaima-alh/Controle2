<?php
/** Exécuter : php install_demo.php — crée enseignant/étudiant démo + comptes (mdp: demo1234) */
require dirname(__DIR__) . '/includes/Database.php';
$config = require dirname(__DIR__) . '/config/database.php';
$pdo = Database::getConnection($config);
$pwd = password_hash('demo1234', PASSWORD_DEFAULT);

$pdo->exec("INSERT IGNORE INTO enseignants (id,nom_fr,prenom_fr,cin,email) VALUES (1,'Alami','Hassan','AB123456','h.alami@ensah.ma')");
$pdo->exec("INSERT IGNORE INTO etudiants (id,nom_fr,prenom_fr,identifiant,massar,email,classe_id,niveau_actuel) VALUES (1,'Idrissi','Youssef','ETU001','M123456789','y@test.ma',1,'2A')");

$st = $pdo->prepare('INSERT INTO comptes (login,mot_de_passe,role,personne_type,personne_id,enabled) VALUES (?,?,?,?,?,1) ON DUPLICATE KEY UPDATE mot_de_passe=VALUES(mot_de_passe)');
$st->execute(['hassanalami', $pwd, 'enseignant', 'enseignant', 1]);
$st->execute(['youssefidrissi', $pwd, 'etudiant', 'etudiant', 1]);

if ((int)$pdo->query('SELECT COUNT(*) FROM modules')->fetchColumn() === 0) {
    $pdo->exec("INSERT INTO modules (code,titre,niveau) VALUES ('WEB101','Développement Web','2A')");
    $pdo->exec("INSERT INTO elements (module_id,code,titre) VALUES (1,'PHP','PHP & MySQL')");
    $pdo->exec('INSERT IGNORE INTO classe_module (classe_id,module_id) VALUES (1,1)');
}
echo "Demo OK — enseignant: hassanalami / etudiant: youssefidrissi / mdp: demo1234\n";
