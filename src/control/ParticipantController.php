<?php

namespace wish\control;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use wish\control\Auth;
use wish\control\Session;
use \wish\view\ParticipantView;
use \wish\models\Liste;
use \wish\models\Item;
use \wish\models\User;
use \wish\models\Reservation;

class ParticipantController
{
    private $c;

    function __construct(\Slim\Container $c = null)
    {
        $this->c = $c;
    }

    function displayIndex(Request $rq, Response $rs, array $args): Response
    {
        $session = new Session();

        $dateExp = new \DateTime("now");
        $listes = Liste::query()->orderBy('expiration')->where("visibilite", "!=", "null")->whereDate("expiration", ">", $dateExp)->get();

        $auteurs = array();
        if(!empty($listes[0]))
        {
            foreach($listes as $liste)
            {
                if($liste->user_id != null)
                    array_push($auteurs, $liste->user_id);
            }

            if(!empty($auteurs))
                $auteurs = User::query()->whereIn('user_id', $auteurs)->get();
        }

        $selectAuteur = "";
        $dateDebut = "";
        $dateFin = "";

        if(isset($rq->getParsedBody()['rechercherAuteur']))
        {
            if(!empty($rq->getParsedBody()['selectAuteur']))
            {
                $selectAuteur = htmlspecialchars($rq->getParsedBody()['selectAuteur']);
                $listes = Liste::query()->orderBy('expiration')->where("visibilite", "!=", "null")->where("user_id", "=", $selectAuteur)->whereDate("expiration", ">", $dateExp)->get();
            }
            else
                $session->write("flash", "<div class='danger'><p> Veuillez remplir le formulaire s'il vous plaît. </p></div>");
        }

        if(isset($rq->getParsedBody()['rechercherDate']))
        {
            if(!empty($rq->getParsedBody()['dateDebut']) && !empty($rq->getParsedBody()['dateFin']))
            {
                $dateDebut = htmlspecialchars($rq->getParsedBody()['dateDebut']);
                $dateFin = htmlspecialchars($rq->getParsedBody()['dateFin']);
                if($dateDebut <= $dateFin)
                {
                    $listes = Liste::query()->orderBy('expiration')->where("visibilite", "!=", "null")->whereDate("expiration", ">", $dateExp)->whereDate("expiration", ">=", $dateDebut)->whereDate("expiration", "<=", $dateFin)->get();
                }
                else
                    $session->write("flash", "<div class='danger'><p> Veuillez indiqué une date de Début inférieur à celle de Fin. </p></div>");
            }
            else
                $session->write("flash", "<div class='danger'><p> Veuillez remplir le formulaire s'il vous plaît. </p></div>");
        }

        $data = array("listes" => $listes, "auteurs" => $auteurs, "selectAuteur" => $selectAuteur, "dateDebut" => $dateDebut, "dateFin" => $dateFin, "container" => $this->c, "basePath" => $rq->getUri()->getBasePath(), "path" => $rq->getUri()->getPath());
        $v = new ParticipantView($data);
        $rs->getBody()->write($v->render(array("sujet" => "index")));
        return $rs;
    }

    function displayMesListes(Request $rq, Response $rs, array $args): Response
    {
        $session = new Session();
        $auth = new Auth($session);

        $listes_cookie = array();
        $listes_user = array();

        if(isset($_COOKIE["MyWishList"]))
        {
            $parts = explode("_", $_COOKIE["MyWishList"]);
            $ids = array_keys(array_flip($parts));

            $listes_cookie = Liste::query()->whereIn('no', $ids)->get()->toArray();
        }

        if($auth->user())
        {
            if(isset($rq->getParsedBody()["ajouterListe"]))
            {
                if(!empty($rq->getParsedBody()['ajoutTokenModif']))
                {
                    $tokenModif = htmlspecialchars($rq->getParsedBody()['ajoutTokenModif']);
                    $req_liste = Liste::query()->where("tokenModif", "=", $tokenModif)->first();
                    if($req_liste)
                    {
                        $liste = Liste::query()->where("tokenModif", "=", $tokenModif)->whereNull("user_id")->first();
                        if($liste)
                        {
                            Liste::query()->where("tokenModif", "=", $tokenModif)->update(array("user_id" => $_SESSION['auth']->user_id));
                        }
                        else
                            $session->write("flash", "<div class='danger'><p> La liste est déjà enregistrée à un autre compte (petit filou va). </p></div>");
                    }
                    else
                        $session->write("flash", "<div class='danger'><p> Aucune liste correspond au token de modification renseigné. </p></div>");
                }
                else
                    $session->write("flash", "<div class='danger'><p> Veuillez remplir le formulaire s'il vous plaît. </p></div>");
            }

            $listes_user = Liste::query()->where('user_id', '=', $_SESSION['auth']->user_id)->get()->toArray();
            $listes_cookie = array_diff_key($listes_cookie, $listes_user);
        }

        $data = array("listes_user" => $listes_user, "listes_cookie" => $listes_cookie, "container" => $this->c, "basePath" => $rq->getUri()->getBasePath(), "path" => $rq->getUri()->getPath());
        $v = new ParticipantView($data);
        $rs->getBody()->write($v->render(array("sujet" => "mesListes")));
        return $rs;
    }

    function displayDeconnexion(Request $rq, Response $rs, array $args): Response
    {
        $session = new Session();
        $auth = new Auth($session);

        $redirection = $this->c->router->pathFor('index');
        $auth->restrict($redirection);
        $auth->logout();

        $session->write("flash", "<div class='success'><p> Deconnexion réussie, à bientôt ! </p></div>");
        $redirection = $this->c->router->pathFor('index');
        header("Location: $redirection");
        exit();

        return $rs;
    }

    function displayCompte(Request $rq, Response $rs, array $args): Response
    {
        $session = new Session();
        $auth = new Auth($session);
        $redirection = $this->c->router->pathFor('connexionInscription');
        $auth->restrict($redirection);

        $user_id = $_SESSION['auth']->user_id;
        $user = User::query()->where("user_id", "=", "$user_id")->first();

        if(isset($rq->getParsedBody()['modifierMail']))
        {
            if(!empty($rq->getParsedBody()['modifMail']) && !empty($rq->getParsedBody()['modifMailAncienMdp']))
            {
                $mail = htmlspecialchars($rq->getParsedBody()['modifMail']);
                $mdp = htmlspecialchars($rq->getParsedBody()['modifMailAncienMdp']);
                if(mb_strtolower($user->user_mail) != mb_strtolower($mail))
                {
                    $req_mail = User::query()->where("user_mail", "=", "$mail")->get();
                    if(empty($req_mail[0]))
                    {
                        if(filter_var($mail, FILTER_VALIDATE_EMAIL))
                        {
                            if(password_verify($mdp, $user->user_mdp))
                            {
                                User::query()->where("user_id", "=", "$user_id")->update(["user_mail" => $mail]);
                                $session->write("flash", "<div class='success'><p> Votre email a bien été modifiée </p></div>");
                            }
                            else
                                $session->write("flash", "<div class='danger'><p> Votre mot de passe ne correspond pas </p></div>");
                        }
                        else
                            $session->write("flash", "<div class='danger'><p> Adresse email non valide </p></div>");
                    }
                    else
                        $session->write("flash", "<div class='danger'><p> Adresse email déjà utilisé sur un autre compte </p></div>");
                }
                else
                    $session->write("flash", "<div class='danger'><p> Vous avez indiqué une adresse email identique </p></div>");
            }
            else
                $session->write("flash", "<div class='danger'><p> Veuillez remplir le formulaire s'il vous plaît. </p></div>");
        }

        if(isset($rq->getParsedBody()['modifierMdp']))
        {
            if(!empty($rq->getParsedBody()['modifMdpNew']) && !empty($rq->getParsedBody()['modifMdpAncienMdp']))
            {
                $mdpNew = htmlspecialchars($rq->getParsedBody()['modifMdpNew']);
                $mdpOlder = htmlspecialchars($rq->getParsedBody()['modifMdpAncienMdp']);

                if(password_verify($mdpOlder, $user->user_mdp))
                {
                    if($mdpNew != $mdpOlder)
                    {
                        if(preg_match("/[a-z]/", $mdpNew) == 1)
                        {
                            if (preg_match("/[A-Z]/", $mdpNew) == 1)
                            {
                                if (preg_match("/[0-9]/", $mdpNew) == 1)
                                {
                                    $mdpNew = password_hash($mdpNew, PASSWORD_DEFAULT);
                                    User::query()->where("user_id", "=", "$user_id")->update(["user_mdp" => $mdpNew]);
                                    $session->write("flash", "<div class='success'><p> Votre mot de passe a bien été modifié </p></div>");

                                    $auth->logout();
                                    header("Location: $redirection");
                                    exit();
                                }
                                else
                                    $session->write("flash", "<div class='danger'><p> Votre mot de passe doit contenir un chiffre. </p></div>");
                            }
                            else
                                $session->write("flash", "<div class='danger'><p> Votre mot de passe doit contenir une lettre majuscule. </p></div>");
                        }
                        else
                            $session->write("flash", "<div class='danger'><p> Votre mot de passe doit contenir une lettre minuscule. </p></div>");
                    }
                    else
                        $session->write("flash", "<div class='danger'><p> Vous avez indiqué un mot de passe identique </p></div>");
                }
                else
                    $session->write("flash", "<div class='danger'><p> Votre mot de passe ne correspond pas </p></div>");
            }
            else
                $session->write("flash", "<div class='danger'><p> Veuillez remplir le formulaire s'il vous plaît. </p></div>");
        }

        if(isset($rq->getParsedBody()['suppCompte']))
        {
            if (!empty($rq->getParsedBody()['resultat']) && !empty($rq->getParsedBody()['suppCompteAncienMdp']))
            {
                $resultat = htmlspecialchars($rq->getParsedBody()['resultat']);
                $mdpOlder = htmlspecialchars($rq->getParsedBody()['suppCompteAncienMdp']);
                if($resultat == 42)
                {
                    if(password_verify($mdpOlder, $user->user_mdp))
                    {
                        User::query()->where("user_id", "=", "$user_id")->delete();
                        $dateExp = new \DateTime("now");
                        $req_listes = Liste::query()->where("user_id", "=", "$user_id")->whereDate("expiration", ">", $dateExp)->get();
                        $liste = array();
                        foreach($req_listes as $req_liste)
                            array_push($liste, $req_liste->no);
                        if(!empty($liste))
                        {
                            Item::query()->whereIn("liste_id", $liste)->delete();
                            Liste::query()->whereIn("no", $liste)->delete();
                        }
                        Liste::query()->where("user_id", "=", "$user_id")->whereDate("expiration", "<", $dateExp)->update(["user_id" => null]);

                        $session->write("flash", "<div class='success'><p> Votre compte a bien été supprimé </p></div>");
                        $auth->logout();
                        header("Location: $redirection");
                        exit();
                    }
                    else
                        $session->write("flash", "<div class='danger'><p> Votre mot de passe ne correspond pas </p></div>");
                }
                else
                    $session->write("flash", "<div class='danger'><p> Mauvais calcul, MyWishList.app va rediriger votre dossier scolaire en CE2. </p></div>");
            }
            else
                $session->write("flash", "<div class='danger'><p> Veuillez remplir le formulaire s'il vous plaît. </p></div>");
        }

        $user = User::query()->where("user_id", "=", "$user_id")->first();

        $data = array("user" => $user, "container" => $this->c, "basePath" => $rq->getUri()->getBasePath(), "path" => $rq->getUri()->getPath());
        $v = new ParticipantView($data);
        $rs->getBody()->write($v->render(array("sujet" => "compte")));
        return $rs;
    }

    function displayModifierListe(Request $rq, Response $rs, array $args): Response
    {
        $session = new Session();

        try
        {
            $liste = Liste::query()->where('tokenModif', '=', $args['tokenModif'])->firstOrFail();
        }
        catch(ModelNotFoundException $e)
        {
            $session->write("flash", "<div class='danger'><p> Aucune liste trouvée </p></div>");
            $redirection = $this->c->router->pathFor('mesListes');
            header("Location: $redirection");
            exit();
        }

        $items = Item::query()->where('liste_id', '=', "$liste->no")->get();

        $data = array("liste" => $liste, "items" => $items, "container" => $this->c, "basePath" => $rq->getUri()->getBasePath(), "path" => $rq->getUri()->getPath());
        $v = new ParticipantView($data);
        $rs->getBody()->write($v->render(array("sujet" => "modifierListe")));
        return $rs;
    }

    function modifierListeFormulaire(Request $rq, Response $rs, array $args): Response
    {
        $session = new Session();

        try
        {
            $liste = Liste::query()->where('tokenModif', '=', $args['tokenModif'])->firstOrFail();
        }
        catch(ModelNotFoundException $e)
        {
            $session->write("flash", "<div class='danger'><p> Aucune liste trouvée </p></div>");
            $redirection = $this->c->router->pathFor('mesListes');
            header("Location: $redirection");
            exit();
        }

        if(isset($rq->getParsedBody()["modifierListe"]))
        {
            if(!empty($rq->getParsedBody()['titreListe']) && !empty($rq->getParsedBody()['descriptionListe'] && !empty($rq->getParsedBody()['dateListe'])))
            {
                $dateListe = htmlspecialchars($rq->getParsedBody()['dateListe']);
                if(strtotime($dateListe) >= strtotime($liste->expiration))
                {
                    $titre = htmlspecialchars($rq->getParsedBody()['titreListe']);
                    $descriptionListe = htmlspecialchars($rq->getParsedBody()['descriptionListe']);

                    if(isset($rq->getParsedBody()['partageTokenListe']) && $rq->getParsedBody()['partageTokenListe']  == 1)
                        $partageTokenListe = bin2hex(random_bytes(32));
                    else
                        $partageTokenListe = null;

                    if(isset($rq->getParsedBody()['partagePubliqueListe']) && $rq->getParsedBody()['partagePubliqueListe'] == 1)
                        $partagePubliqueListe = 1;
                    else
                        $partagePubliqueListe = 0;

                    Liste::query()->where('tokenModif', '=', $liste->tokenModif)->update(array('titre' => $titre, 'description' => $descriptionListe, 'expiration' => $dateListe, "token" => $partageTokenListe,"visibilite" => $partagePubliqueListe));

                    $session->write("flash", "<div class='success'><p> Votre liste a bien été modifié ! </p></div>");
                }
                else
                    $session->write("flash", "<div class='danger'><p> Vous ne pouvez pas reculer la date d'éxpiration </p></div>");
            }
            else
                $session->write("flash", "<div class='danger'><p> Veuillez remplir le formulaire s'il vous plaît. </p></div>");
        }

        if(isset($rq->getParsedBody()["ajouterItem"]))
        {
            if (!empty($rq->getParsedBody()['nomItem']) && !empty($rq->getParsedBody()['descriptionItem'] && !empty($rq->getParsedBody()['prixItem'])))
            {
                $nomItem = htmlspecialchars($rq->getParsedBody()['nomItem']);
                $descriptionItem = htmlspecialchars($rq->getParsedBody()['descriptionItem']);
                $prixItem = htmlspecialchars($rq->getParsedBody()['prixItem']);

                if(!empty($rq->getParsedBody()['imgItem']))
                    $imgItem = htmlspecialchars($rq->getParsedBody()['imgItem']);
                else
                    $imgItem = null;

                if(!empty($rq->getParsedBody()['urlItem']))
                    $urlItem = htmlspecialchars($rq->getParsedBody()['urlItem']);
                else
                    $urlItem = null;

                if(isset($rq->getParsedBody()["cagnotteItem"]))
                    $cagnotteItem = 1;
                else
                    $cagnotteItem = 0;

                Item::query()->insert(["liste_id" => $liste->no, "nom" => $nomItem, "descr" => $descriptionItem, "img" => $imgItem, "url" => $urlItem, "tarif" => $prixItem, "cagnotte" => $cagnotteItem]);

                $session->write("flash", "<div class='success'><p> Vous avez ajouter un item à votre liste. </p></div>");

            }
            else
                $session->write("flash", "<div class='danger'><p> Veuillez remplir le formulaire s'il vous plaît. </p></div>");
        }

        if(isset($args["id"]))
        {
            $idItem = htmlspecialchars($args["id"]);
            if(isset($rq->getParsedBody()["modifierItem$idItem"])) {
                if(!empty($rq->getParsedBody()['nomItem'.$args["id"]]) && !empty($rq->getParsedBody()["descriptionItem$idItem"] && !empty($rq->getParsedBody()["prixItem$idItem"]))) {
                    $nomItem = htmlspecialchars($rq->getParsedBody()["nomItem$idItem"]);
                    $descriptionItem = htmlspecialchars($rq->getParsedBody()["descriptionItem$idItem"]);
                    $prixItem = htmlspecialchars($rq->getParsedBody()["prixItem$idItem"]);

                    if(!empty($rq->getParsedBody()["imgItem$idItem"]))
                        $imgItem = htmlspecialchars($rq->getParsedBody()["imgItem$idItem"]);
                    else
                        $imgItem = null;

                    if(!empty($rq->getParsedBody()["urlItem$idItem"]))
                        $urlItem = htmlspecialchars($rq->getParsedBody()["urlItem$idItem"]);
                    else
                        $urlItem = null;

                    if(isset($rq->getParsedBody()["cagnotteItem$idItem"]))
                        $cagnotteItem = 1;
                    else
                        $cagnotteItem = 0;

                    Item::query()->where("id", "=", $idItem)->update(["liste_id" => $liste->no, "nom" => $nomItem, "descr" => $descriptionItem, "img" => $imgItem, "url" => $urlItem, "tarif" => $prixItem, "cagnotte" => $cagnotteItem]);

                    $session->write("flash", "<div class='success'><p> Vous avez bien modifié un item. </p></div>");

                }
                else
                    $session->write("flash", "<div class='danger'><p> Veuillez remplir le formulaire s'il vous plaît. </p></div>");
            }
        }

        $liste = Liste::query()->where('tokenModif', '=', $args['tokenModif'])->first();
        $items = Item::query()->where('liste_id', '=', "$liste->no")->get();

        $reservations = Reservation::query()->get();

        $array_res = array();
        foreach($reservations as $reservation)
        {
            if(!array_key_exists($reservation->id_item, $array_res))
                $array_res[$reservation->id_item] = (float) $reservation->number_res;
            else
                $array_res[$reservation->id_item] = (float) $array_res[$reservation->id_item] + $reservation->number_res;
        }

        $data = array("liste" => $liste, "items" => $items, "array_res" => $array_res, "container" => $this->c, "basePath" => $rq->getUri()->getBasePath(), "path" => $rq->getUri()->getPath());
        $v = new ParticipantView($data);
        $rs->getBody()->write($v->render(array("sujet" => "modifierListe")));
        return $rs;
    }

    function displayCreerListe(Request $rq, Response $rs, array $args): Response
    {
        $data = array("titreListe" => "", "descriptionListe" => "", "dateListe" => "", "container" => $this->c, "basePath" => $rq->getUri()->getBasePath(), "path" => $rq->getUri()->getPath());
        $v = new ParticipantView($data);
        $rs->getBody()->write($v->render(array("sujet" => "creerListe")));
        return $rs;
    }

    function creerListeFormulaire(Request $rq, Response $rs, array $args): Response
    {
        $session = new Session();
        $auth = new Auth($session);

        if(isset($rq->getParsedBody()["creer"]))
        {
            if(!empty($rq->getParsedBody()['titreListe']) && !empty($rq->getParsedBody()['descriptionListe'] && !empty($rq->getParsedBody()['dateListe'])))
            {
                $dateListe = htmlspecialchars($rq->getParsedBody()['dateListe']);
                if(strtotime($dateListe) >= strtotime("+2 days"))
                {
                    $titre = htmlspecialchars($rq->getParsedBody()['titreListe']);
                    $descriptionListe = htmlspecialchars($rq->getParsedBody()['descriptionListe']);

                    $liste = Liste::query()->where("titre", "=", $titre)->get();
                    if(empty($liste[0]))
                    {
                        if($auth->user())
                            $id_user = $_SESSION['auth']->user_id;
                        else
                            $id_user = null;

                        $tokenModif = bin2hex(random_bytes(32));

                        $insertListe = Liste::query()->insertGetId(['user_id' => $id_user,'titre' => $titre, 'description' => $descriptionListe, 'expiration' => $dateListe, 'tokenModif' => $tokenModif]);

                        if(isset($_COOKIE["MyWishList"]))
                            setcookie("MyWishList", $_COOKIE["MyWishList"] . "_" . $insertListe, time() + 60 * 60 * 24 * 365);
                        else
                            setcookie("MyWishList", $insertListe, time() + 60 * 60 * 24 * 365);

                        $session->write("flash", "<div class='success'><p> Votre liste a bien été crée ! </p></div>");
                        $redirection = $this->c->router->pathFor("modifierListe", ['tokenModif' => $tokenModif]);
                        header("Location: $redirection");
                        exit();
                    }
                    else
                        $session->write("flash", "<div class='danger'><p> Veuillez choisir un autre titre. </p></div>");
                }
                else
                    $session->write("flash", "<div class='danger'><p> La date doit d'éxpiration doit être égal ou supérieur à 3 jours. </p></div>");
            }
            else
                $session->write("flash", "<div class='danger'><p> Veuillez remplir le formulaire s'il vous plaît. </p></div>");
        }


        $data = array("titreListe" => "", "descriptionListe" => "", "dateListe" => "", "container" => $this->c, "basePath" => $rq->getUri()->getBasePath(), "path" => $rq->getUri()->getPath());
        if(isset($rq->getParsedBody()['titreListe'])){ $data["titreListe"] = $rq->getParsedBody()['titreListe']; }
        if(isset($rq->getParsedBody()['descriptionListe'])){ $data["descriptionListe"] = $rq->getParsedBody()['descriptionListe']; }
        if(isset($rq->getParsedBody()['dateListe'])){ $data["dateListe"] = $rq->getParsedBody()['dateListe']; }

        $v = new ParticipantView($data);
        $rs->getBody()->write($v->render(array("sujet" => "creerListe")));
        return $rs;
    }

    function displayLireListe(Request $rq, Response $rs, array $args): Response
    {
        $session = new Session();

        if(!isset($args['id_liste']) && !empty($args['id_liste']))
        {
            $args_lis = true;
            try
            {
                $liste = Liste::query()->where('token', '=', $args['token'])->firstOrFail();
            }
            catch(ModelNotFoundException $e)
            {
                $session->write("flash", "<div class='danger'><p> Aucune liste trouvée </p></div>");
                $redirection = $this->c->router->pathFor('mesListes');
                header("Location: $redirection");
                exit();
            }
        }
        elseif(isset($args['id_liste']))
        {
            $args_lis = false;
            try
            {
                $liste = Liste::query()->where('no', '=', $args['id_liste'])->where('visibilite', '=', "1")->firstOrFail();
            }
            catch(ModelNotFoundException $e)
            {
                $session->write("flash", "<div class='danger'><p> Aucune liste trouvée </p></div>");
                $redirection = $this->c->router->pathFor('mesListes');
                header("Location: $redirection");
                exit();
            }
        }

        $reservations = Reservation::query()->get();
        $array_res = array();
        foreach($reservations as $reservation)
        {
            if(!array_key_exists($reservation->id_item, $array_res))
                $array_res[$reservation->id_item] = (float) $reservation->number_res;
            else
                $array_res[$reservation->id_item] = $array_res[$reservation->id_item] + (float) $reservation->number_res;
        }

        $liste_auteur = false;
        if(isset($_COOKIE["MyWishList"]))
        {
            $parts = explode("_", $_COOKIE["MyWishList"]);
            $ids_key = array_flip($parts);
            if(array_key_exists($liste->no, $ids_key))
                $liste_auteur = true;
        }
        elseif(isset($_SESSION['auth']))
        {
            if($_SESSION['auth']->user_id == $liste->user_id)
                $liste_auteur = true;
        }

        if(!$liste_auteur)
        {
            if(isset($args['id_item']) && isset($rq->getParsedBody()["reserverItem"]))
            {
                $id_item = $args['id_item'];
                if(!empty($rq->getParsedBody()['pseudo_res']))
                {
                    $pseudo_res = htmlspecialchars($rq->getParsedBody()['pseudo_res']);
                    try
                    {
                        $item_req = Item::query()->where("id", "=", $id_item)->firstOrFail();
                    }
                    catch(ModelNotFoundException $e)
                    {
                        $session->write("flash", "<div class='danger'><p> Aucun item trouvé. </p></div>");
                        $redirection = $this->c->router->pathFor('mesListes');
                        header("Location: $redirection");
                        exit();
                    }

                    $error = true;
                    if($item_req->cagnotte)
                    {
                        if(isset($rq->getParsedBody()["number_res"]) && !empty($rq->getParsedBody()["number_res"]))
                        {
                            $tarif = htmlspecialchars($rq->getParsedBody()["number_res"]);

                            if($tarif > 0)
                            {
                                $tarif_deja_reserve = $array_res[$id_item];
                                $montant_total = $item_req->tarif;

                                if($montant_total - ($tarif_deja_reserve + $tarif) < 0)
                                    $error = false;
                            }
                            else
                            {
                                $error = false;
                                $session->write("flash", "<div class='danger'><p> Vous devez fournir un montant positif non null. </p></div>");
                            }
                        }
                        else
                        {
                            $error = false;
                            $session->write("flash", "<div class='danger'><p> Aucun tarif renseigné. </p></div>");
                        }
                    }
                    else
                        $tarif = $item_req->tarif;

                    if($error)
                    {
                        if(!empty($reservations) || $item_req->cagnotte)
                        {
                            if(!empty($rq->getParsedBody()["message_res"]))
                                $message = htmlspecialchars($rq->getParsedBody()["message_res"]);
                            else
                                $message = null;

                            Reservation::query()->insert(['id_item' => $id_item, "nom_pseudo" => $pseudo_res, "message_res" => $message, "number_res" => $tarif, "date_res" => new \DateTime("now")]);
                            if($args_lis)
                                $redirection = $this->c->router->pathFor('lireListe', ['token' => $args["token"]]);
                            else
                                $redirection = $this->c->router->pathFor('lireListe', ['token' => 0, 'id_liste' => $args["id_liste"]]);

                            $session->write("flash", "<div class='success'><p> Vous avez bien reservé cet item. </p></div>");
                            header("Location: $redirection");
                            exit();
                        }
                        else
                            $session->write("flash", "<div class='danger'><p> Item déjà reservé. </p></div>");
                    }
                }
                else
                    $session->write("flash", "<div class='danger'><p> Veuillez remplir le formulaire s'il vous plaît. </p></div>");
            }
        }

        $items = Item::query()->where('liste_id', '=', $liste->no)->get();

        $reservations = Reservation::query()->get();

        $array_res = array();
        foreach($reservations as $reservation)
        {
            if(!array_key_exists($reservation->id_item, $array_res))
                $array_res[$reservation->id_item] = (float) $reservation->number_res;
            else
                $array_res[$reservation->id_item] = (float) $array_res[$reservation->id_item] + $reservation->number_res;
        }

        $reserve_liste = true;
        foreach($items as $item)
        {
            if(isset($array_res[$item->id]))
            {
                if(($item->tarif - $array_res[$item->id]) != 0)
                    $reserve_liste = false;
            }
            else
                $reserve_liste = false;
        }

        $data = array("liste" => $liste, "items" => $items, "array_res" => $array_res, "liste_auteur" => $liste_auteur, "reserve_liste" => $reserve_liste, "container" => $this->c, "basePath" => $rq->getUri()->getBasePath(), "path" => $rq->getUri()->getPath());
        $v = new ParticipantView($data);
        $rs->getBody()->write($v->render(array("sujet" => "lireListe")));
        return $rs;
    }

    function displayConnexionInscription(Request $rq, Response $rs, array $args): Response
    {
        $session = new Session();
        $auth = new Auth($session);

        if($auth->user())
        {
            $redirection = $this->c->router->pathFor('index');
            header("Location: $redirection");
            exit();
        }

        $inscription_form = 0;

        $data = array("pseudoOuMailConnexion" => "", "mdpConnexion" => "", "pseudoInscription" => "", "mailInscription" => "", "mdpInscription" => "", "mdpConfirmerInscription" => "", "container" => $this->c, "basePath" => $rq->getUri()->getBasePath(), "path" => $rq->getUri()->getPath());
        $v = new ParticipantView($data);
        $rs->getBody()->write($v->render(array("sujet" => "connexionInscription", "inscription_form" => $inscription_form)));
        return $rs;
    }

    function connexionInscriptionFormulaire(Request $rq, Response $rs, array $args): Response
    {
        $session = new Session();
        $auth = new Auth($session);

        $inscription_form = 0;
        if(isset($rq->getParsedBody()["inscription"]))
        {
            $inscription_form = 1;
            if(!empty($rq->getParsedBody()['pseudoInscription']) && !empty($rq->getParsedBody()['mdpInscription'] && !empty($rq->getParsedBody()['mdpConfirmerInscription']) && !empty($rq->getParsedBody()['mailInscription'])))
            {
                if($rq->getParsedBody()['mdpInscription'] === $rq->getParsedBody()['mdpConfirmerInscription'])
                {
                    $mdpInscription = htmlspecialchars($rq->getParsedBody()['mdpInscription']);
                    if(preg_match("/[a-z]/", $mdpInscription) == 1)
                    {
                        if(preg_match("/[A-Z]/", $mdpInscription) == 1)
                        {
                            if(preg_match("/[0-9]/", $mdpInscription) == 1)
                            {
                                $pseudoInscription = mb_strtolower(htmlspecialchars($rq->getParsedBody()['pseudoInscription']));
                                $mailInscription = mb_strtolower(htmlspecialchars($rq->getParsedBody()['mailInscription']));
                                if(filter_var($mailInscription, FILTER_VALIDATE_EMAIL))
                                {
                                    if(strlen($pseudoInscription) >= 4 && strlen($pseudoInscription) <= 16)
                                    {
                                        $mdpInscription = password_hash($mdpInscription, PASSWORD_DEFAULT);
                                        $userPseu = User::query()->where("user_pseudo", "=", $pseudoInscription)->get();
                                        if(empty($userPseu[0]))
                                        {
                                            $userMail = User::query()->where("user_mail", "=", $mailInscription)->get();
                                            if(empty($userMail[0]))
                                            {
                                                User::query()->insert(['user_pseudo' => $pseudoInscription, 'user_mail' => $mailInscription, 'user_mdp' => $mdpInscription, 'user_dateCreate' => new \DateTime("now")]);
                                                $user = User::query()->where("user_pseudo", "=", "$pseudoInscription")->first();
                                                $auth->connect($user);

                                                $session->write("flash", "<div class='success'><p> Bienvenue à vous $pseudoInscription ! </p></div>");
                                                $redirection = $this->c->router->pathFor('compte');
                                                header("Location: $redirection");
                                                exit();
                                            }
                                            else
                                                $session->write("flash", "<div class='danger'><p> Votre mail est déjà pris. </p></div>");
                                        }
                                        else
                                            $session->write("flash", "<div class='danger'><p> Votre pseudo est déjà pris. </p></div>");
                                    }
                                    else
                                        $session->write("flash", "<div class='danger'><p> Votre pseudo doit être en 4 et 16 caractères. </p></div>");
                                }
                                else
                                    $session->write("flash", "<div class='danger'><p> Votre email est invalide </p></div>");
                            }
                            else
                                $session->write("flash", "<div class='danger'><p> Votre mot de passe doit contenir un chiffre. </p></div>");
                        }
                        else
                            $session->write("flash", "<div class='danger'><p> Votre mot de passe doit contenir une lettre majuscule. </p></div>");
                    }
                    else
                        $session->write("flash", "<div class='danger'><p> Votre mot de passe doit contenir une lettre minuscule. </p></div>");
                }
                else
                    $session->write("flash", "<div class='danger'><p> Les deux mots de passe ne correspondent pas. </p></div>");
            }
            else
                $session->write("flash", "<div class='danger'><p> Veuillez remplir le formulaire s'il vous plaît. </p></div>");
        }

        if(isset($rq->getParsedBody()['connexion']))
        {
            if(!empty($rq->getParsedBody()['pseudoOuMailConnexion']) && !empty($rq->getParsedBody()['mdpConnexion']))
            {
                $mdpConnexion = htmlspecialchars($rq->getParsedBody()['mdpConnexion']);
                $pseudoOuMailConnexion = mb_strtolower(htmlspecialchars($rq->getParsedBody()['pseudoOuMailConnexion']));

                $user = User::query()->where("user_pseudo", "=", $pseudoOuMailConnexion)->orWhere("user_mail", "=", $pseudoOuMailConnexion)->first();
                if($user)
                {
                    if(password_verify($mdpConnexion, $user->user_mdp))
                    {
                        $auth->connect($user);
                        $session->write("flash", "<div class='success'><p> Content de vous retrouver ! </p></div>");
                        $redirection = $this->c->router->pathFor('compte');
                        header("Location: $redirection");
                        exit();
                    }
                    else
                        $session->write("flash", "<div class='danger'><p> Le mot de passe ou l'identifiant ne correspond pas. </p></div>");
                }
                else
                    $session->write("flash", "<div class='danger'><p> Le mot de passe ou l'identifiant ne correspond pas. </p></div>");
            }
            else
                $session->write("flash", "<div class='danger'><p> Veuillez remplir le formulaire s'il vous plaît. </p></div>");
        }

        $data = array("pseudoOuMailConnexion" => "", "mdpConnexion" => "", "pseudoInscription" => "", "mailInscription" => "", "mdpInscription" => "", "mdpConfirmerInscription" => "", "container" => $this->c, "basePath" => $rq->getUri()->getBasePath(), "path" => $rq->getUri()->getPath());
        if(isset($rq->getParsedBody()['pseudoOuMailConnexion'])){ $data["pseudoOuMailConnexion"] = $rq->getParsedBody()['pseudoOuMailConnexion']; }
        if(isset($rq->getParsedBody()['mdpConnexion'])){ $data["mdpConnexion"] = $rq->getParsedBody()['mdpConnexion']; }
        if(isset($rq->getParsedBody()['pseudoInscription'])){ $data["pseudoInscription"] = $rq->getParsedBody()['pseudoInscription']; }
        if(isset($rq->getParsedBody()['mailInscription'])){ $data["mailInscription"] = $rq->getParsedBody()['mailInscription']; }
        if(isset($rq->getParsedBody()['mdpInscription'])){ $data["mdpInscription"] = $rq->getParsedBody()['mdpInscription']; }
        if(isset($rq->getParsedBody()['mdpConfirmerInscription'])){ $data["mdpConfirmerInscription"] = $rq->getParsedBody()['mdpConfirmerInscription']; }

        $v = new ParticipantView($data);
        $rs->getBody()->write($v->render(array("sujet" => "connexionInscription", "inscription_form" => $inscription_form)));
        return $rs;
    }
}
