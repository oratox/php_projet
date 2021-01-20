<?php

namespace wish\view;

use wish\control\Session;
use wish\models\Reservation;

class ParticipantView
{
    private $data;

    public function __construct($data = null)
    {
        $this->data = $data;
    }

    public function displayIndex(): String
    {
//        <div id="displayInfos">
//            <h3> Guide sur l'utilisation du site :  </h3>
//            <p> à compléter </p>
//        </div>
        $html = <<<END
            <h2> Page d'accueil </h2>
            <div id="displayListes">
                <h3> Les listes publiques non expirées :  </h3>
                <div id="rechercheListes">
                    <form method="POST" action="{$this->data["container"]->router->pathFor("rechercherIndex")}">
                        <label for="selectAuteur"> Rechercher par auteur : </label>
                        <select name="selectAuteur" required>
                            <option value="">--Choisiez un auteur--</option>
        END;

        foreach($this->data["auteurs"] as $auteur)
        {
            $selected = "";
            if($this->data["selectAuteur"] == $auteur->user_id)
                $selected = "selected";

            $html = $html . <<<END
                <option value="{$auteur->user_id}" $selected>{$auteur->user_pseudo}</option>
            END;
        }

        $html = $html . <<<END
                        </select>
                        <input type="submit" name="rechercherAuteur" value="Rechercher">
                    </form>
                    <form method="POST" action="{$this->data["container"]->router->pathFor("rechercherIndex")}">
                        <p> Rechercher par intervalle de date : </p>
                        <label for="dateDebut"> Début : </label>
        END;

        $html = $html . <<<END
                        <input type="date" name="dateDebut" id="dateDebut" value="{$this->data["dateDebut"]}" required>
                        <label for="dateFin"> Fin : </label>
                        <input type="date" name="dateFin" id="dateFin" value="{$this->data["dateFin"]}" required>
                        <input type="submit" name="rechercherDate" value="Rechercher">
                    </form>
                    <p> Annuler les recherche : <a href=""> ici </a></p>
                </div>

        END;


        foreach($this->data["listes"] as $liste)
        {
            $dateExpir = date("d/m/Y \à\ H\hi", strtotime($liste->expiration));

            $dateExpir = "<p>Expire le : $dateExpir </p>";

            $html = $html . <<<END
                <div class="displayListe">
                    <h4> {$liste->titre} </h4>
                    <p> Description : {$liste->description} </p>
                        $dateExpir
                    <p><a href="{$this->data["container"]->router->pathFor("lireListe", ['token' => 0, 'id_liste' => $liste->no])}"> Voir plus </a></p>
                 </div>
                END;
        }

        $html = $html . <<<END
            </div>
        END;

        return $html;
    }

    public function displayMesListes(): String
    {
        $html = <<<END
            <h2> Mes listes </h2>
            <div id="lienCreerListe">
                <h3> Vous voulez crée une liste de souhait ? C'est <a href="{$this->data["container"]->router->pathFor("creerListe")}" title="Créer une liste"> ici </a></h3>
            </div>
        END;

        //ntm
        if(isset($_SESSION['auth']))
        {
            $html = $html . <<<END
                <div id="mesListes">
                    <h3> Ajouter une liste non authentifiée: </h3>
                    <form method="POST" action="{$this->data["container"]->router->pathFor("ajouterListe")}">
                        <label for="ajoutTokenModif"> Token de modification : </label>
                        <input type="text" name="ajoutTokenModif" id="ajoutTokenModif" placeholder="Token de modification" required><br/><br/>
                        <input type="submit" name="ajouterListe" value="Créer ma liste">
                    </form>
                </div>
            END;
        }

        if(isset($_SESSION['auth']))
        {
            $html = $html . <<<END
                <div class="afficherListes">
                    <h3> Mes Listes authentifiées : </h3>
            END;

            if(!empty($this->data["listes_user"]))
            {
                foreach($this->data["listes_user"] as $liste_user)
                {
                    $html = $html . <<<END
                        <div class="afficherListe">
                            <p> Liste n°{$liste_user['no']} : <span><b>{$liste_user['titre']}</b></span></p>
                            <p> Lien pour modifier : <span><a href="{$this->data["container"]->router->pathFor("modifierListe", ['tokenModif' => $liste_user['tokenModif']])}" title="Modifier la liste en mode créateur"> ici </a></span></p>
                            <p> Token de modification : <span>{$liste_user['tokenModif']}</span></p>
                    END;

                    if($liste_user['token'] != null)
                    {
                        $html = $html . <<<END
                            <p> Lien pour visualiser  : <span><a href="{$this->data["container"]->router->pathFor("lireListe", ['token' => $liste_user['token']])}" title="Visualiser en mode partage la liste"> ici </a></span></p>
                            <p> Token de partage : <span>{$liste_user['token']}</span></p>
                        END;
                    }

                    $html = $html . <<<END
                        </div>
                    END;
                }
            }
            else
            {
                $html = $html . <<<END
                    <p class="no_liste"> Aucune liste créée ou ajouté sur votre compte </p>
                END;
            }

            $html = $html . <<<END
                </div>
            END;
        }

        $html = $html . <<<END
            <div class="afficherListes">
                <h3> Mes Listes non authentifiées : </h3>
        END;

        if(!empty($this->data["listes_cookie"]))
        {
            foreach($this->data["listes_cookie"] as $liste_cookie)
            {
                $html = $html . <<<END
                    <div class="afficherListe">
                        <p> Liste n°{$liste_cookie['no']} : <span><b>{$liste_cookie['titre']}</b></span></p>
                        <p> Lien pour modifier : <span><a href="{$this->data["container"]->router->pathFor("modifierListe", ['tokenModif' => $liste_cookie['tokenModif']])}" title="Modifier la liste en mode créateur"> ici </a></span></p>
                        <p> Token de modification : <span>{$liste_cookie['tokenModif']}</span></p>
                END;

                if($liste_cookie['token'] != null)
                {
                    $html = $html . <<<END
                        <p> Lien pour visualiser : <span><a href="{$this->data["container"]->router->pathFor("lireListe", ['token' => $liste_cookie['token']])}" title="Visualiser en mode partage la liste"> ici </a></span></p>
                        <p> Token de partage : <span>{$liste_cookie['token']}</span></p>
                    END;
                }

                $html = $html . <<<END
                    </div>
                END;
            }
        }
        else
        {
            if(isset($_SESSION['auth']))
            {
                $html = $html . <<<END
                    <p class="no_liste"> Il y a aucune liste non enregistrée sur votre compte </p>
                END;
            }
            else
            {
                $html = $html . <<<END
                    <p class="no_liste"> Il y a aucune liste de crée </p>
                END;
            }
        }

        $html = $html . <<<END
            </div>
        END;

        return $html;
    }

    public function displayCompte(): String
    {
        $html = <<<END
            <h2> Mon compte : </h2>
            <div id="modifierMail">
                <h3> Modifier mon adresse email </h3>
                <form method="POST" action="{$this->data["container"]->router->pathFor("modifierInfosCompte")}" autocomplete="off">
                    <label for="modifMail"> Email : </label>
                    <input type="email" name="modifMail" id="modifMail" value="{$this->data["user"]->user_mail}" required><br/><br/>
                    <label for="modifMailAncienMdp"> Ancien Mot de passe : </label>
                    <input type="password" name="modifMailAncienMdp" id="modifMailAncienMdp" placeholder="Ancien Mdp" value="" autocomplete="off" required><br/><br/><br/><br/>
                    <input type="submit" name="modifierMail" value="Modifier mon email">
                </form>
            </div>
            <div id="modifierMdp">
                <h3> Modifier mon mot de passe : </h3>
                <form method="POST" action="{$this->data["container"]->router->pathFor("modifierInfosCompte")}" autocomplete="off">
                    <label for="modifMdpNew"> Nouveau Mot de passe : </label>
                    <input type="password" name="modifMdpNew" id="modifMdpNew" minlenght="4" maxlenght="16" placeholder="Nouveau Mdp" required><br/><br/>
                    <label for="modifMdpAncienMdp"> Ancien Mot de passe : </label>
                    <input type="password" name="modifMdpAncienMdp" id="modifMdpAncienMdp" placeholder="Ancien Mdp" value="" autocomplete="off" required><br/><br/><br/>
                    <input type="submit" name="modifierMdp" value="Modifier mon mot de passe">
                </form>
            </div>
            <div id="suppCompte">
                <h3> Supprimer mon compte : </h3>
                <form method="POST" action="{$this->data["container"]->router->pathFor("modifierInfosCompte")}" autocomplete="off">
                    <label for="resultat"> Écris le résultat de "54-12" : </label>
                    <input type="number" name="resultat" id="resultat" step="1" placeholder="54-12 = ?" required><br/><br/>
                    <label for="suppCompteAncienMdp"> Ancien Mot de passe : </label>
                    <input type="password" name="suppCompteAncienMdp" id="suppCompteAncienMdp" placeholder="Ancien Mdp" value="" autocomplete="off" required><br/><br/><br/>
                    <input type="submit" name="suppCompte" value="Supprimer mon compte">
                </form>
            </div>
        END;

        return $html;
    }

    public function displayModifierListe(): String
    {
        $html = <<<END
              <h2> Modifier la liste n° {$this->data["liste"]->no} :</h2>
              END;

        if($this->data["liste"]->token != null)
        {
            $html = $html . <<<END
                <h4> Lien de partage : <a href="{$this->data["container"]->router->pathFor("lireListe", ['token' => $this->data["liste"]->token])}">{$this->data["container"]->router->pathFor("lireListe", ['token' => $this->data["liste"]->token])}</a> </h4><br/><br/>
            END;
        }

        $html = $html . <<<END
              <div id="modifierListe">
                <h3> Formulaire de modification <u>Liste</u> : </h3>
                <form method="POST" action="{$this->data["container"]->router->pathFor("modifierListeFormulaire", ["tokenModif" => $this->data["liste"]->tokenModif])}">
                  <label for="titreListe"> Titre : </label>
                  <input type="text" name="titreListe" placeholder="Le titre" id="titreListe" value="{$this->data["liste"]->titre}" required> <br/><br/>
                  <label for="descriptionListe"> Description : </label>
                  <input type="text" name="descriptionListe" placeholder="La description" id="descriptionListe" value="{$this->data["liste"]->description}" required> <br/><br/>
                  <label for="dateListe"> La date d'expiration : </label>
                  <input type="date" name="dateListe" placeholder="La date d'expiration" id="dateListe" value="{$this->data["liste"]->expiration}" required><br/><br/>
            END;

        if(empty($this->data['items'][0]))
        {
            $html = $html . <<<END
                   <p style="color: orange;"> Vous pouvez partager votre liste une fois que celle-ci contient au moins 1 item. </p>
            END;
        }
        else
        {
            $html = $html . <<<END
                   <label for="partageTokenListe"> Générer un token de partage </label>
            END;

            if($this->data["liste"]->token != null)
            {
                $html = $html . <<<END
                    <input type="checkbox" name="partageTokenListe" id="partageTokenListe" value="1" checked><br/><br/>
                END;
            }
            else
            {
                $html = $html . <<<END
                    <input type="checkbox" name="partageTokenListe" id="partageTokenListe" value="1"><br/><br/>
                END;
            }

            $html = $html . <<<END
                    <label for="partagePubliqueListe"> Partager la liste en publique </label>
            END;

            if($this->data["liste"]->visibilite != null)
            {
                $html = $html . <<<END
                    <input type="checkbox" name="partagePubliqueListe" id="partagePubliqueListe" value="1" checked>
                END;
            }
            else
            {
                $html = $html . <<<END
                    <input type="checkbox" name="partagePubliqueListe" id="partagePubliqueListe" value="1">
                END;
            }
        }

        $html = $html . <<<END
                <br/><br/><br/>
                <input type="submit" name="modifierListe" value="Modifier ma liste">
                </form>
              </div>
        END;

        $html = $html . <<<END
              <div id="ajouterItem">
                <h3> Formulaire ajout d'un item: </h3>
                <form method="POST" action="{$this->data["container"]->router->pathFor("modifierListeFormulaire", ["tokenModif" => $this->data["liste"]->tokenModif])}">
                  <label for="nomItem"> Titre : </label>
                  <input type="text" name="nomItem" placeholder="Le titre" id="nomItem" required> <br/><br/>
                  <label for="descriptionItem"> Description : </label>
                  <input type="text" name="descriptionItem" placeholder="La description" id="descriptionItem" required> <br/><br/>
                  <label for="prixItem"> La prix : </label>
                  <input type="number" name="prixItem" step="0.01" placeholder="Le prix de l'item" id="prixItem" required><br/><br/>
                  <label for="imgItem"> Url image (facultatif) : </label>
                  <input type="text" name="imgItem" placeholder="Url image de l'item" id="imgItem"> <br/><br/>
                  <label for="urlItem"> Url explicatif (facultatif) : </label>
                  <input type="text" name="urlItem" placeholder="Url représentant l'item" id="urlItem"> <br/><br/>
                  <label for="cagnotteItem"> Ouvrir une cagnotte : </label>
                  <input type="checkbox" name="cagnotteItem" value="1" id="cagnotteItem"><br/><br/><br/>
                  <input type="submit" name="ajouterItem" value="Ajouter cet item">
                  </form>
                </div>
            END;

        foreach($this->data["items"] as $item)
        {
            $html = $html . <<<END
              <div id="ajouterItem">
                <h3> Formulaire de modification <u>Item n°{$item->id}</u>: </h3>
                <div class="imgItem">
              END;

            if($item->img != null)
            {
                if(strpos($item->img, "http") >= 0)
                {
                    $html = $html . <<<END
                        <p><img src="{$item->img}" alt="image representatif de l'item"></p>
                     END;
                }
                else
                {
                    $html = $html . <<<END
                        <p><img src="{$this->data["basePath"]}/web/img/items/{$item->img}" alt="image representatif de l'item"></p>
                    END;
                }
            }

            $item_reste = $item->tarif;
            if(isset($this->data["array_res"][$item->id]))
                $item_reste = $item->tarif - $this->data["array_res"][$item->id];

            if($item_reste == $item->tarif)
            {
                $html = $html . <<<END
                    <p title="L'état de l'item"> L'item a été reservé par un internaute </p>
                    </div></div>
                END;
            }
            else
            {
                $html = $html . <<<END
                </div>
                <form method="POST" action="{$this->data["container"]->router->pathFor("modifierListeFormulaire", ["tokenModif" => $this->data["liste"]->tokenModif, "id" => $item->id])}">
                  <label for="nomItem{$item->id}"> Titre : </label>
                  <input type="text" name="nomItem{$item->id}" placeholder="Le titre" id="nomItem{$item->id}" value="{$item->nom}" required> <br/><br/>
                  <label for="descriptionItem{$item->id}"> Description : </label>
                  <input type="text" name="descriptionItem{$item->id}" placeholder="La description" id="descriptionItem{$item->id}" value="{$item->descr}" required> <br/><br/>
                  <label for="prixItem{$item->id}"> La prix : </label>
                  <input type="number" name="prixItem{$item->id}" step="0.01" placeholder="Le prix de l'item" id="prixItem{$item->id}" value="{$item->tarif}" required><br/><br/>
                  <label for="imgItem{$item->id}"> Url image (facultatif) : </label>
                  <input type="text" name="imgItem{$item->id}" placeholder="Url image de l'item" id="imgItem{$item->id}" value="{$item->img}"> <br/><br/>
                  <label for="urlItem{$item->id}"> Url explicatif (facultatif) : </label>
                  <input type="text" name="urlItem{$item->id}" placeholder="Url représentant l'item" id="urlItem{$item->id}" value="{$item->url}"> <br/><br/>
                  <label for="cagnotteItem{$item->id}"> Ouvrir une cagnotte : </label>
            END;

                if($item->cagnotte != null)
                {
                    $html = $html . <<<END
                  <input type="checkbox" name="cagnotteItem{$item->id}" value="1" id="cagnotteItem{$item->id}" checked>
                END;
                }
                else
                {
                    $html = $html . <<<END
                  <input type="checkbox" name="cagnotteItem{$item->id}" value="1" id="cagnotteItem{$item->id}">
                END;
                }

                $html = $html . <<<END
                  <br/><br/><br/>
                  <input type="submit" name="modifierItem{$item->id}" value="Modifier cet item">
                  </form>
                </div>
            END;
            }
        }

        return $html;
    }

    public function displayCreerListe(): String
    {
        $html = <<<END
            <h2> Créer ma liste : </h2>
              <div id="creerListe">
                <h3> Formulaire création : </h3>
                <form method="POST" action="{$this->data["container"]->router->pathFor("creerListeFormulaire")}">
                  <label for="titreListe"> Titre : </label>
                  <input type="text" name="titreListe" placeholder="Le titre" id="titreListe" value="{$this->data['titreListe']}" required> <br/><br/>
                  <label for="descriptionListe"> Description : </label>
                  <input type="text" name="descriptionListe" placeholder="La description" id="descriptionListe" value="{$this->data['descriptionListe']}" required> <br/><br/>
                  <label for="dateListe"> La date d'expiration : </label>
                  <input type="date" name="dateListe" placeholder="La date d'expiration" id="dateListe" value="{$this->data['dateListe']}" required> <br/><br/>
                  <input type="submit" name="creer" value="Créer ma liste">
                </form>
              </div>
         END;

        return $html;
    }

    public function displayLireListe(): String
    {
        $expiration = date("d/m/Y \à\ H\hi", strtotime($this->data["liste"]["expiration"]));
        if(!empty($this->data["liste"]["auteur"]))
            $auteur = $this->data["liste"]["auteur"];
        else
            $auteur = "incognito";

        $html = <<<END
            <h2 title="Information sur la page"> Visualiser la liste n°{$this->data["liste"]["no"]} : </h2>
            <div class="liste">
              <p class="auteur_liste" title="L'auteur de la liste"> Auteur de la liste : $auteur </p>
              <p class="date_liste" title="La date d'éxpiration de la liste"> Expire le $expiration </p>
              <p class="titre_liste" title="Le titre de la liste"> {$this->data["liste"]["titre"]} </p>
              <p class="description_liste" title="La description de la liste"> {$this->data["liste"]["description"]} </p><br/><br/>
        END;

        if($this->data["reserve_liste"])
        {
            $html = $html . <<<END
              <p title="informations sur la reservation des items" style="color: darkgreen;"> Tous les items sont reservés ! </p>
            END;
        }
        else
        {
            $html = $html . <<<END
              <p title="informations sur la reservation des items" style="color: orangered;"> Tous les items ne sont pas reservés ! </p>
            END;
        }

        $html = $html . <<<END
            </div>
            <div class="items">
        END;

        foreach($this->data["items"] as $item)
        {
            $html = $html . <<<END
                <div class="item">
                  <div class="item_flex">
                    <div class="img_item">
                END;
            if($item->img != null)
            {
                if (strpos($item->img, "http") >= 0)
                {
                    $html = $html . <<<END
                       <p title="L'image de l'item"><img src="{$item->img}" alt="Image item"/></p>
                    END;
                }
                else
                {
                    $html = $html . <<<END
                       <p title="L'image de l'item"><img src="{$this->data["basePath"]}/web/img/items/$item->img" alt="Image item"/></p>
                    END;
                }
            }

            $html = $html . <<<END
                    </div>
                    <div class="nom_item">
                      <p title="Le nom de l'item"> {$item->nom} </p>
                    </div>
                    <div class="etat_item">
                END;

            $item_reste = $item->tarif;
            if(isset($this->data["array_res"][$item->id]))
                $item_reste = $item->tarif - $this->data["array_res"][$item->id];

            if(!$this->data["liste_auteur"] || $this->data["reserve_liste"])
            {
                if($item_reste == 0)
                {
                    $html = $html . <<<END
                        <p title="L'état de l'item"> reservé </p>
                    END;
                }
                else
                {
                    $html = $html . <<<END
                        <p title="L'état de l'item"> non reservé </p>
                    END;
                }
            }
            else
            {
                $html = $html . <<<END
                        <p title="L'état de l'item"> état : ?? </p>
                END;
            }

            $prix = number_format($item->tarif, 2, ',', ' ');
            $html = $html . <<<END
                    </div>
                    <div class="voirPlus">
                      <span class="btn_voir_plus"> Voir plus </span>
                    </div>
                  </div>
                  <div class="desc_item" style="display: none;"><br/>
                    <p title="Le prix de l'item"> Tarif : {$prix}€ </p><br/>
                    <p title="La déscription de l'item"> Description : {$item->descr} </p><br/><br/>
            END;

            if(!$this->data["liste_auteur"] || $this->data["reserve_liste"])
            {
                $html = $html . <<<END
                    <div id="reservation_infos">
                END;

                $reservation_liste = Reservation::query()->where('id_item', '=', $item->id)->get();

                foreach($reservation_liste as $reservation_affi)
                {
                    $html = $html . <<<END
                        <div id="reservations_info_item">
                            <p> Nom du reservant : {$reservation_affi->nom_pseudo}  </p>
                            <p> message : {$reservation_affi->message_res} </p>
                            <p> prix : {$reservation_affi->number_res} </p>
                        </div>
                    END;
                }

                $html = $html . <<<END
                    </div>
                END;

                if($item_reste)
                {
                    $html = $html . <<<END
                    <div id="reserverForm">
                        <h3> Réserver un item : </h3>
                    END;

                    if(!empty($this->data["liste"]["token"]))
                    {
                        $html = $html . <<<END
                            <form method="POST" action="{$this->data["container"]->router->pathFor("reserverItem", ["token" => $this->data["liste"]["token"], "id_liste" => $this->data["liste"]["no"], "id_item" => $item->id])}">                   
                        END;
                    }
                    else
                    {
                        $html = $html . <<<END
                            <form method="POST" action="{$this->data["container"]->router->pathFor("reserverItem", ["token" => 0, "id_liste" => $this->data["liste"]["no"], "id_item" => $item->id])}">                   
                        END;
                    }

                    $html = $html . <<<END
                            <label for="pseudo_res"> Pseudonyme : </label>
                            <input type="text" name="pseudo_res" id="pseudo_res" required><br/><br/> 
                            <label for="message_res"> Message (facultatif) : </label>
                            <input type="text" name="message_res" id="message_res">
                END;

                    if($item->cagnotte)
                    {
                        $item_reste_max = number_format($item_reste, 2, ',', ' ');
                        $html = $html . <<<END
                            <br/><br/>
                            <label for="number_res"> Tarif (max : {$item_reste_max}€) : </label>
                            <input type="number" name="number_res" id="number_res" max="{$item_reste}" step="0.01" required><br/><br/>
                            <p style="color: orange; text-align: left;"> Cagnotte : Plusieurs personnes peuvent participer à la reservation. </p>
                    END;
                    }

                    $html = $html . <<<END
                            <br/><br/>
                            <input type="submit" name="reserverItem" value="reserver">
                        </form>
                    </div>
                END;
                }
            }
            else
            {
                $html = $html . <<<END
                    <p title="Modifier l'item"> Vous pouvez modifier l'item <a href="{$this->data["container"]->router->pathFor("modifierListe", ['tokenModif' => $this->data["liste"]["tokenModif"]])}"> ici </a> </p><br/>
                END;
                if($item->url != null)
                {
                    $html = $html . <<<END
                        <p>Lien externe : <a href="$item->url"> ici </a></p>
                    END;
                }
            }

            $html = $html . <<<END
                  </div>
                </div>
            END;
        }

        $html = $html . "</div>";

        return $html;
    }

    public function displayConnexionInscription(): String
    {
        $html = <<<END
            <h2> Accéder à son compte : </h2>
              <div id="connexion">
                <h3> Se connecter: </h3>
                <form method="POST" action="{$this->data["container"]->router->pathFor("connexionInscriptionFormulaire")}">
                  <label for="pseudoOuMailConnexion"> Pseudo ou mail : </label>
                  <input type="text" name="pseudoOuMailConnexion" placeholder="Pseudo ou mail" id="pseudoOuMailConnexion" value="{$this->data['pseudoOuMailConnexion']}" required> <br/><br/>
                  <label for="mdpConnexion"> Mot de passe : </label>
                  <input type="password" name="mdpConnexion" placeholder="mot de passe" minlenght="4" maxlenght="16" id="mdpConnexion" value="{$this->data['mdpConnexion']}" required> <br/><br/>
                  <input type="submit" name="connexion" value="Se connecter">
                </form>
                <p> Vous n'avez pas de compte ? C'est <span id="btn_inscription">ici</span></p>
              </div>
              <div id="inscription">
                <h3> S'inscrire: </h3>
                <form method="POST" action="{$this->data["container"]->router->pathFor("connexionInscriptionFormulaire")}">
                  <label for="pseudoInscription"> Pseudo : </label>
                  <input type="text" name="pseudoInscription" placeholder="pseudonyme" id="pseudoInscription" value="{$this->data['pseudoInscription']}" required> <br/><br/>
                  <label for="mailInscription"> Email : </label>
                  <input type="email" name="mailInscription" placeholder="email" id="mailInscription" value="{$this->data['mailInscription']}" required> <br/><br/>
                  <label for="mdpInscription"> Mot de passe : </label>
                  <input type="password" name="mdpInscription" placeholder="mot de passe" id="mdpInscription" value="{$this->data['mdpInscription']}" required> <br/><br/>
                  <label for="mdpConfirmerInscription"> Confirmer Mot de passe : </label>
                  <input type="password" name="mdpConfirmerInscription" minlenght="4" maxlenght="16" placeholder="confirmation mot de passe" id="mdpConfirmerInscription" value="{$this->data['mdpConfirmerInscription']}" required> <br/><br/>
                  <input type="submit" name="inscription" value="S'inscrire">
                </form>
                <p> Clique <span id="btn_connexion">ici</span> si tu as un compte</p>
              </div>
         END;

        return $html;
    }

    public function render(array $val)
    {
        $script = "";
        switch($val["sujet"]):
            case "mesListes":
                $content = $this->displayMesListes();
                break;
            case "modifierListe":
                $content = $this->displayModifierListe();
                break;
            case "creerListe":
                $content = $this->displayCreerListe();
                break;
            case "compte":
                $content = $this->displayCompte();
                break;
            case "lireListe":
                $script = "<script src='{$this->data["basePath"]}/web/scripts/toggleItems.js'></script>";
                $content = $this->displayLireListe();
                break;
            case "connexionInscription":
                $inscription_form = $val['inscription_form'];
                $script = "<script> var inscription_form = $inscription_form; </script> <script src='{$this->data["basePath"]}/web/scripts/toggleLogin.js'></script>";
                $content = $this->displayConnexionInscription();
                break;
            case "index":
            default:
                $content = $this->displayIndex();
        endswitch;

        $session = new Session();
        $flash = "";
        if($session->read('flash'))
        {
            $flash =  "<a href='{$this->data["basePath"]}/{$this->data["path"]}' title='Clique pour rafraîchir la page'>" . $session->read('flash') . "</a>";
            $session->delete('flash');
        }

        $html = <<<END
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8"/>
                <title> MyWishList.app </title>
                <meta name="description" content="Une application de liste de souhaits"/>
                <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
                <link rel="stylesheet" href="{$this->data["basePath"]}/web/css/commun.css"/>
                <link rel="stylesheet" href="{$this->data["basePath"]}/web/css/footer.css"/>
                <link rel="stylesheet" href="{$this->data["basePath"]}/web/css/connexionInscription.css"/>
                <link rel="stylesheet" href="{$this->data["basePath"]}/web/css/creerListe.css"/>
                <link rel="stylesheet" href="{$this->data["basePath"]}/web/css/footer.css"/>
                <link rel="stylesheet" href="{$this->data["basePath"]}/web/css/index.css"/>
                <link rel="stylesheet" href="{$this->data["basePath"]}/web/css/lireListe.css"/>
                <link rel="stylesheet" href="{$this->data["basePath"]}/web/css/ConnexionInscription.css"/>
                <title> Accueil </title>
            </head>
            <body>
              <header>
                <h1><a href="{$this->data["container"]->router->pathFor("index")}" title="Aller à la page d'accueil"> MyWishList.app </a></h1>
              </header>
              <nav>
                <p><a href="{$this->data["container"]->router->pathFor("index")}" title="Aller à la page d'accueil"> Page d'accueil </a></p>
                <p><a href="{$this->data["container"]->router->pathFor("mesListes")}" title="Aller à la pages Mes listes"> Mes listes </a></p>
        END;

        if(!isset($_SESSION['auth']))
        {
            $html = $html . <<<END
                <p><a href="{$this->data["container"]->router->pathFor("connexionInscription")}" title="Se connecter à son espace compte"> Se connecter </a></p>
            END;
        }
        else
        {
            $html = $html . <<<END
                <p><a href="{$this->data["container"]->router->pathFor("compte")}" title="Aller à mon espace compte"> Mon compte </a></p>
                <p><a href="{$this->data["container"]->router->pathFor("deconnexion")}" title="Se deconnecter de mon espace compte"> Se deconnecter </a></p>
            END;
        }

        $html = $html . <<<END
              </nav>
              <div id="wrap">
                $flash
                $content
              </div>
              <footer>
                 <p> Copyright &copy; 2021 | mywishlist.app - Nancy-Charlemagne </p>
              </footer>
              $script
            </body>
            </html>
        END;

        return $html;
    }
}