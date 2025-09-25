<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for the wordcloud module.
 *
 * @package    mod_collabwordcloud
 * @copyright  2023 DNE - Ministere de l'Education Nationale
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['accessdenied'] = 'Accès refusé';
$string['activitynotfound'] = 'Activité introuvable';
$string['activitynotopenned'] = 'Activité n\'est pas ouverte';
$string['activitynotstarted'] = 'Ce nuage de mots ne sera pas disponible avant le {$a}.';
$string['activity_will_be_reseted'] = 'Attention, l\'activité va être réinitialisée si la longueur ou le nombre de mots requis sont modifié! La totalité des participations à cette activité seront perdues';
$string['activityclosed'] = 'Nuage de mots fermé le {$a}. Merci.';
$string['add'] = 'Ajouter';
$string['addword'] = 'Ajouter un mot';
$string['allowsubmitionfrom'] = 'Autoriser les réponses à partir du';
$string['allowsubmitionupto'] = 'Autoriser les réponses jusqu\'au';
$string['canceledit'] = 'Annuler la modification';
$string['collabwordcloud:addinstance'] = 'Ajouter une instance de wordcloud';
$string['collabwordcloud:manageword'] = 'Gérer les mots d\'un wordcloud';
$string['collabwordcloud:submitword'] = 'Proposer des mots dans un wordcloud';
$string['completionwords'] = 'Le participant doit proposer le nombre minimum de mots';
$string['completionwordsgroup'] = 'Mots requis';
$string['csv_date'] = 'Date';
$string['csv_user'] = 'Utilisateur';
$string['csv_word'] = 'Mot';
$string['description'] = 'Description';
$string['empty_wordcloud'] = 'Ce nuage de mots est vide';
$string['exportdata'] = 'Exporter les données';
$string['exporttoimage'] = 'Exporter l\'image';
$string['group'] = 'Groupe';
$string['instructions'] = 'Consigne';
$string['maxwordrequired_bigger_than_allowed'] = 'Le nombre de mots minimum ne peut pas être inférieur au nombre de mots maximum';
$string['maxwordrequired_tomany'] = 'Trop de mots requis';
$string['maxwordrequired_undefined'] = 'Le nombre de mots minimum est requis';
$string['maxwordsallowed_tomany'] = 'Le nombre de mots maximum est trop grand';
$string['maxwordsallowed_undefined'] = 'Nombre de mots maximum est requis';
$string['maxwordsallowed'] = 'Nombre maximum de mots demandés';
$string['maxwordsallowedsetting'] = 'Limite globale du nombre maximum de mots demandé à un participant';
$string['missingparams'] = 'Paramètres manquants';
$string['missingword'] = 'Vous devez saisir un mot !';
$string['modulename'] = 'Nuage de mots';
$string['modulename_help'] = 'Cette activité permet de produire collectivement un nuage de mots avec les caractéristiques suivantes :

* un nombre minimum et maximum de mots pour contribuer
* les mots peuvent être édités pour correction par les formateurs
* le nuage de mot peut être extrait sous forme d\'une image (.png)
* les contributions peuvent être exportées sous forme de fichier (.csv)
* possibilité de fonctionner avec des groupes et groupements
* les nuages sont mis à jour automatiquement.';
$string['modulenameplural'] = 'Nuages de mots';
$string['multi_submition'] = '{$a} participants ont transmis leurs propositions.';
$string['name'] = 'Nom du nuage de mots';
$string['newwordisthesame'] = 'Le nouveau mot est le même que l\'ancien, impossible de le renomer';
$string['nosubmition'] = 'Aucun participant n\'a réalisé l\'activité pour l\'instant.';
$string['noworddeleted'] = 'Aucun mot supprimé';
$string['nwordsdeleted'] = '{$a} mots supprimés';
$string['oldwordnotfound'] = 'Ancien mot introuvable';
$string['onesubmition'] = '{$a} participant a transmis ses propositions.';
$string['oneworddeleted'] = 'Un mot supprimé';
$string['pluginadministration'] = 'Administration du Nuage de mots';
$string['pluginconfig'] = 'Configuration du Nuage de mots';
$string['pluginname'] = 'Nuage de mots';
$string['privacy:metadata:wordcloud_words:groupid'] = 'L\'id du groupe auquel appartient l\'utilisateur.';
$string['privacy:metadata:wordcloud_words:timecreated'] = 'Horodatage de la création de l\'enregistrement.';
$string['privacy:metadata:wordcloud_words:timemodified'] = 'Horodatage de la dernière modification de l\'enregistrement.';
$string['privacy:metadata:wordcloud_words:userid'] = 'L\'id de l\'utilisateur.';
$string['privacy:metadata:wordcloud_words:word'] = 'Le mot entré par l\'utilisateur.';
$string['privacy:metadata:wordcloud_words'] = 'Informations sur les nuages de mots.';
$string['removeword'] = 'Supprimer le mot';
$string['resetsubmissions'] = 'Supprimer toutes les propositions';
$string['resetsubmissions_help'] = 'L\'ensemble des propositions des Nuages de mots de ce parcours seront supprimées';
$string['send'] = 'Envoyer';
$string['student_can_submit_from'] = 'Les participants pourront répondre à partir du {$a}';
$string['student_can_submit_upto'] = 'Les participants ne pourront plus répondre à partir du {$a}';
$string['student_cant_submit_since'] = 'Les participants ne peuvent plus répondre depuis le {$a}';
$string['submitions_wont_be_altered'] = 'Les propositions existantes ne seront pas impactées par la modification de cette valeur.';
$string['submitword_submit'] = 'Valider mes mots';
$string['timeend_before_start'] = 'La date de fin des réponses ne peut pas être avant le début de la période autorisée!';
$string['updateaword'] = 'Modifier un mot';
$string['updateword'] = 'Modifier';
$string['viewactivitynotallowed'] = 'Vous ne pouvez pas accèder à cette activité!';
$string['word'] = 'Mot';
$string['word_already_used'] = 'Vous avez déjà saisi ce mot !';
$string['word_nb'] = 'Mot n°';
$string['wordadded'] = 'Mot ajouté avec succès';
$string['wordalreadyexist'] = 'Vous avez déjà ajouté ce mot';
$string['wordcloud:addinstance'] = 'Ajout d\'un mot au nuage de mot';
$string['wordcloud:manageword'] = 'Gérer les mots d\'un nuage';
$string['wordcloud:submitword'] = 'Soumettre un mot au nuage de mot';
$string['wordisnotvalid'] = 'Le mot n\'est pas valide';
$string['wordistoolong'] = 'Le mot est trop long';
$string['wordmaxlenght'] = 'Longueur maximale des mots';
$string['wordmaxlenght_tolong'] = 'La longueur maximale des mots est trop longue';
$string['wordmaxlenght_undefined'] = 'La longueur maximale des mots est requise';
$string['wordmaxlenghtsetting'] = 'Limite globale de la longueur maximale des mots';
$string['wordnotfound'] = 'Mot introuvable';
$string['wordrequired'] = 'Nombre minimum de mots demandé';
$string['wordupdated'] = 'Mot modifié';
$string['wordusers'] = 'Utilisateurs qui ont ajouté le mot :';
$string['wordweight'] = 'Poids du mot';
