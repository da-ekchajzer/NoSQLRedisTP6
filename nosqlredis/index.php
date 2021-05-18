<?php



if($redis->exists('mot')){

}else{
   //formulaire entrer mot
}
//Si mot existe
    // Choisir une lettre
    //Si dans mot
        //Montrer la lettre
        // Si mot decouvert
            // Fin de partis gagné

    // Sinon si elle est dans la liste des lettres existante
        //continue

    //Sinon
       //nbErreur++
       // Si nbErreur == 10
            // Fin de partis perdu

//Si mot existe pas
    // Choisir un mot
    // Test si mot choisis

// mise à jour de la valeur

$redis->set('message', 'Hello world');

// recuperation de la valeur
$value = $redis->get('message');

// affichage de la valeur
print($value);
echo ($redis->exists('message')) ? "Oui" : "Non";

//suppression de la clé
$redis->del('message');