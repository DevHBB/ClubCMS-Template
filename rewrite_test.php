<?php
// Ce fichier est appelé par le JS pour tester si mod_rewrite fonctionne
// Il ne doit jamais être appelé directement par l'utilisateur
header('Content-Type: application/json');
echo json_encode(['rewrite' => true]);
