<?php
/**
 * EGB Family — Backend formulaire de contact
 * Envoie un email à egbfamily63@gmail.com
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Uniquement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'message' => 'Méthode non autorisée.']));
}

// ── Anti-spam honeypot ─────────────────────────────────────
// Champ caché dans le formulaire : si rempli = bot
if (!empty($_POST['website'])) {
    http_response_code(400);
    exit(json_encode(['ok' => false, 'message' => 'Erreur.']));
}

// ── Récupération & nettoyage ───────────────────────────────
function clean(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$nom     = clean($_POST['nom']     ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$tel     = clean($_POST['tel']     ?? '');
$objet   = clean($_POST['objet']   ?? '');
$message = clean($_POST['message'] ?? '');

// ── Validation serveur ─────────────────────────────────────
$erreurs = [];

if (empty($nom))     $erreurs[] = 'Le nom est requis.';
if (!$email)         $erreurs[] = 'L\'adresse email est invalide.';
if (empty($objet))   $erreurs[] = 'L\'objet est requis.';
if (strlen($message) < 10) $erreurs[] = 'Le message est trop court.';

if (!empty($erreurs)) {
    http_response_code(422);
    exit(json_encode(['ok' => false, 'message' => implode(' ', $erreurs)]));
}

// ── Composition de l'email ─────────────────────────────────
$destinataire = 'egbfamily63@gmail.com';
$sujet        = '=?UTF-8?B?' . base64_encode('[EGB Family] ' . $objet) . '?=';

$corps = "Nouveau message reçu via le site web d'EGB Family.\n";
$corps .= str_repeat('─', 50) . "\n\n";
$corps .= "Nom     : {$nom}\n";
$corps .= "Email   : {$email}\n";
$corps .= "Tél     : " . (!empty($tel) ? $tel : 'Non renseigné') . "\n";
$corps .= "Objet   : {$objet}\n\n";
$corps .= "Message :\n{$message}\n\n";
$corps .= str_repeat('─', 50) . "\n";
$corps .= "Message envoyé depuis le site EGB Family.\n";

$headers  = "From: site@egbfamily.fr\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: EGB-Family-Contact-Form\r\n";

// ── Envoi ──────────────────────────────────────────────────
$envoye = mail($destinataire, $sujet, $corps, $headers);

if ($envoye) {
    // Email de confirmation à l'expéditeur
    $sujet_conf = '=?UTF-8?B?' . base64_encode('EGB Family — Votre message a bien été reçu') . '?=';
    $corps_conf  = "Bonjour {$nom},\n\n";
    $corps_conf .= "Nous avons bien reçu votre message et nous vous répondrons dans les plus brefs délais.\n\n";
    $corps_conf .= "Récapitulatif de votre message :\n";
    $corps_conf .= str_repeat('─', 40) . "\n";
    $corps_conf .= "Objet   : {$objet}\n";
    $corps_conf .= "Message : {$message}\n";
    $corps_conf .= str_repeat('─', 40) . "\n\n";
    $corps_conf .= "À bientôt à l'écurie !\n";
    $corps_conf .= "L'équipe EGB Family\n\n";
    $corps_conf .= "─────────────────────────────────────────\n";
    $corps_conf .= "EGB Family — Centre Équestre Associatif\n";
    $corps_conf .= "3 Chemin du Grand Beaulieu, 63000 Clermont-Ferrand\n";
    $corps_conf .= "Tél : 06 84 60 15 37\n";

    $headers_conf  = "From: EGB Family <noreply@egbfamily.fr>\r\n";
    $headers_conf .= "MIME-Version: 1.0\r\n";
    $headers_conf .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers_conf .= "Content-Transfer-Encoding: 8bit\r\n";

    mail($email, $sujet_conf, $corps_conf, $headers_conf);

    http_response_code(200);
    echo json_encode(['ok' => true, 'message' => 'Votre message a bien été envoyé ! Nous vous répondrons rapidement.']);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Une erreur est survenue lors de l\'envoi. Veuillez nous contacter directement par téléphone.']);
}
