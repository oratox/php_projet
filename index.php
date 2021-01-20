<?php

require_once __DIR__ . '/vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \wish\conf\Eloquent;
use \wish\control\ParticipantController;

$config = require_once __DIR__ . '/src/conf/settings.php';

$c = new Slim\Container(['settings' => ['displayErrorDetails' => true]]);
$app = new Slim\App($c);

Eloquent::start(__DIR__ . '/src/conf/db.config.ini');


$app->post('/mesListes', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->displayMesListes($rq, $rs, $args);
})->setName("ajouterListe");

$app->get('/mesListes', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->displayMesListes($rq, $rs, $args);
})->setName("mesListes");

$app->get('/modifierListe/{tokenModif}', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->displayModifierListe($rq, $rs, $args);
})->setName("modifierListe");

$app->post('/modifierListe/{tokenModif}[/{id}]', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->modifierListeFormulaire($rq, $rs, $args);
})->setName("modifierListeFormulaire");

$app->get('/creerListe', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->displayCreerListe($rq, $rs, $args);
})->setName("creerListe");

$app->post('/creerListe', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->creerListeFormulaire($rq, $rs, $args);
})->setName("creerListeFormulaire");

$app->get('/lireListe[/{token}[/{id_liste}]]', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->displayLireListe($rq, $rs, $args);
})->setName('lireListe');

$app->post('/lireListe[/{token}[/{id_liste}[/{id_item}]]]', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->displayLireListe($rq, $rs, $args);
})->setName('reserverItem');

$app->get('/compte', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->displayCompte($rq, $rs, $args);
})->setName("compte");

$app->post('/compte', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->displayCompte($rq, $rs, $args);
})->setName("modifierInfosCompte");

$app->get('/deconnexion', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->displayDeconnexion($rq, $rs, $args);
})->setName("deconnexion");

$app->get('/connexionInscription', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->displayConnexionInscription($rq, $rs, $args);
})->setName("connexionInscription");

$app->post('/connexionInscription', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->connexionInscriptionFormulaire($rq, $rs, $args);
})->setName("connexionInscriptionFormulaire");

$app->get('[/]', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->displayIndex($rq, $rs, $args);
})->setName("index");

$app->post('[/]', function(Request $rq, Response $rs, array $args): Response
{
    $c = new ParticipantController($this);
    return $c->displayIndex($rq, $rs, $args);
})->setName("rechercherIndex");

$app->run();
