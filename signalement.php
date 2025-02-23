<!DOCTYPE html>
<html>
    <head>
        <title>Signalements</title>
        <meta charset="utf-8">
        <link href="css/style.css" rel="stylesheet" type="text/css" media="all" />
    </head>
    <body>
        <div id="header">Plate-forme collaborative de lutte contre les plantes invasives</div>

    <?php
    include("menu.php");
    ?>

        <form method="post" action="" enctype="multipart/form-data">

        <?php
            $exec =false ;
            ini_set( 'display_errors', 'on' );
            error_reporting( E_ALL );
            try{
                $BDD = new PDO('mysql:host=localhost;port=3308;dbname=bdd;charset=utf8', 'root', 'root');
                $BDD->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $BDD->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            }
            catch(Exception $e){
                die('Erreur :' . $e->getMessage());
            }
            $requeteJointure = $BDD->prepare('SELECT plantes.Nom_fr, utilisateurs.Pseudo, signalements.Ville, signalements.Coordonnees_GPS, signalements.Date_signalement, signalements.Commentaire, photosignalements.Photo
                                                    FROM signalements INNER JOIN plantes ON plantes.Id_plante=signalements.Id_plante
                                                                      INNER JOIN utilisateurs ON utilisateurs.Id_utilisateur=signalements.Id_utilisateur
                                                                      INNER JOIN photosignalements ON photosignalements.Id_signalement = signalements.Id_signalement 
                                                                      WHERE signalements.Id_signalement="'.$_GET["id"].'"');
                    
            $requeteJointure->execute();
            $signalement = $requeteJointure->fetch(); 

            $pos = strpos( $signalement['Coordonnees_GPS'], "-");
            $lat = doubleval(substr ($signalement['Coordonnees_GPS'], 0, $pos));
            $long = doubleval(substr ($signalement['Coordonnees_GPS'], $pos+1, strlen($signalement['Coordonnees_GPS'])));
           
            if(isset($_POST['modif'])){
                try{
                    $BDD = new PDO('mysql:host=localhost;port=3308;dbname=bdd;charset=utf8', 'root', 'root');
                    $BDD->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $BDD->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                }
                catch(Exception $e){
                    die('Erreur :' . $e->getMessage());
                }

                $requeteModif = $BDD->prepare('UPDATE signalements SET Verifier=:verif WHERE Id_signalement="'.$_GET["id"].'"');
                $exec=$requeteModif->execute(array(':verif'=> 1));
                
                 $score=$signalement['Nb_bon_signalement']+1;
                if ($score==5){
                    $requeteModifScore = $BDD->prepare('UPDATE utilisateurs SET Nb_bon_signalement=:score, Rang=:rang WHERE Id_utilisateur="'.$signalement['Id_utilisateur'].'"');
                    $exec=$requeteModifScore->execute(array(':score'=> $score, ':rang'=> 2));
                }
                else{
                    $requeteModifScore = $BDD->prepare('UPDATE utilisateurs SET Nb_bon_signalement=:score WHERE Id_utilisateur="'.$signalement['Id_utilisateur'].'"');
                    $exec=$requeteModifScore->execute(array(':score'=> $score));
                }

                $score2=$_SESSION['score']+1;
                if ($score2==10){
                    $requeteModifScore = $BDD->prepare('UPDATE utilisateurs SET Nb_bon_signalement=:score, Rang=:rang WHERE Id_utilisateur="'.$_SESSION['id'].'"');
                    $exec=$requeteModifScore->execute(array(':score'=> $score2, ':rang'=> 3));
                }
                else{
                    $requeteModifScore = $BDD->prepare('UPDATE utilisateurs SET Nb_bon_signalement=:score WHERE Id_utilisateur="'.$_SESSION['id'].'"');
                    $exec=$requeteModifScore->execute(array(':score'=> $score2));
                }
            }

            if(isset($_POST['supprimer'])){

                try{
                    $BDD = new PDO('mysql:host=localhost;port=3308;dbname=bdd;charset=utf8', 'root', 'root');
                    $BDD->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $BDD->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                }
                catch(Exception $e){
                    die('Erreur :' . $e->getMessage());
                }

                $requeteSupprime = $BDD->prepare('DELETE FROM signalements WHERE Id_signalement="'.$_GET["id"].'"');
                $exec=$requeteSupprime->execute();
                
                $score2=$_SESSION['score']+1;
                if ($score2==10){
                    $requeteModifScore = $BDD->prepare('UPDATE utilisateurs SET Nb_bon_signalement=:score, Rang=:rang WHERE Id_utilisateur="'.$_SESSION['id'].'"');
                    $exec=$requeteModifScore->execute(array(':score'=> $score2, ':rang'=> 3));
                }
                else{
                    $requeteModifScore = $BDD->prepare('UPDATE utilisateurs SET Nb_bon_signalement=:score WHERE Id_utilisateur="'.$_SESSION['id'].'"');
                    $exec=$requeteModifScore->execute(array(':score'=> $score2));
                }
            }

            if($exec){
                header('Location: listeSignalement.php?');
                exit();
            } 
        ?>    
            
            <h1 style="text-align:center"> Signalement </h1> <!-- récupéré dans bdd-->
            <div class="image">
                <img src="data:image/jpg;base64,<?php echo base64_encode($signalement['Photo']);?> " width = 300  > <!--mettre photo de la bdd et voir avec js pour faire des flèches pour faire défiler les images s'il y en a plusieurs-->
            </div>  
            <br/>
            
            <div class="renseignement">
                <div id="titre">
                La plante :
                </div>
                <output name="plante"><?php echo $signalement['Nom_fr']; ?> </output> 
            </div>
            <div class="renseignement">
                <div id="titre">
                L'utilisateur qui a signalé :
                </div>
                <output name="utilisateur"><?php echo $signalement['Pseudo']; ?> </output> 
            </div>
            <div class="renseignement">
                <div id="titre">
                Date : 
                </div>
                <output name="date"><?php echo $signalement['Date_signalement']; ?></output> 
            </div>
            <div class="renseignement">
                <div id="titre">
                Ville :
                </div>
                <output name="floraison"><?php echo $signalement['Ville']; ?></output> 
            </div>

            <!-- Carte -->   
            <div id=Map>
                <?php
                    include 'map_signalement.php';
                ?>
            </div>

            <!-- Commentaire -->   
            <div class="renseignement">
                <div id="titre">
                Commentaire :
                </div>
                <output name="fleur"><?php echo $signalement['Commentaire']; ?></output> 
            </div>
            
            <?php
           
                if($_SESSION['rang']==2 or $_SESSION['rang']==3){
                    echo("<button id=\"boutonmodif\" type=\"submit\" name=\"modif\">
                    Valider le signalement
                </button>
                <button id=\"boutonmodif\" type=\"submit\" name=\"supprimer\">
                    Supprimer le signalement
                </button>");
                }
              
            ?>
        </form>

        <footer>
        <div id="baspage"> Contact</div>
        </footer> 
    </body>
</html>
