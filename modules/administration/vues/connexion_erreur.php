<?php
echo "<h2>Confirmation de connexion</h2>";
echo "<p> Erreur dans l'identification : <b>".$nom_administrateur." </b>n'existe pas
 ou le mot de passe n'est pas bon.<br />";
echo "<p><a href='index.php?module=administration&amp;actionMod=connexion'>
Retour a la page de connexion</a><br>";
echo "<a href='index.php'>Retour a l'acceuil</a><p>";