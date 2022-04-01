---
extends: _layouts.post
section: content
title: Mon premier projet Open Source php-mime-mail-parser
description: 
date: 2014-03-08
categories: [php]
featured: true
---

Contrairement aux autres articles celui ci sera moins technique, je vais vous raconter une histoire comment j’ai créé mon premier projet Open Source [php-mime-mail-parser](https://github.com/eXorus/php-mime-mail-parser).

Ce projet m’a permis pour la première fois de :

- Partager mon code et obtenir des retours
- Utiliser GitHub pour distribuer mon code
- Mettre en place des tests unitaires
- Mettre en place un package avec Composer

## Histoire

A mon travail nous utilisions [www.yopmail.com](www.yopmail.com) pour se créer des adresses mails fictives sans avoir à créer de compte et en pouvant quand même lire les emails. Comme tous les développeurs Web, quand il s’agit de tester un site il faut créer plusieurs comptes, vérifier les emails de confirmation de compte, les emails de commandes, … et yopmail est bien pratique pour ca.

Mais nous avions un problème, c’est que les automates qui effectuaient les tests automatisés la nuit était pris pour des robots malveillants donc Yopmail affichait des [captchas](http://fr.wikipedia.org/wiki/CAPTCHA) et donc on se retrouvait avec des tests en erreur le matin.

Curieux comme je suis j’ai voulu comprendre comment Yopmail fonctionnait et si il était possible de reproduire ce système. Je suis entrée dans le monde des mails et du parsing 🙂

Après ma petite analyse j’ai compris qu’il me fallait :

- Un serveur mail (Postfix) pour recevoir les mails et les envoyer sur un script PHP
- Un script PHP pour parser le mail et l’insérer dans une base de données en récupérant : 
    - l’expéditeur
    - le destinataire
    - le titre
    - le corps du mail format texte et html
    - les fichiers attachés
    - …
- Une application Web pour visualiser les mails en base de données

Le plus compliqué fut le script pour parser un mail en PHP et c’est là que j’ai découvert [php-mime-mail-parser](https://code.google.com/p/php-mime-mail-parser/)

Projet Open Source sur Google Code qui semblait correspondre à mes attentes. J’ai donc commencé à télécharger cette classes PHP pour l’utiliser dans mon application Web. Mon application a commencé à être utilisée par les équipes de tests à mon boulot et les anomalies sont arrivées essentiellement sur le parsing.

Comme le code de cette classe était complexe (ie : il fallait connaitre les [standards des mails](http://fr.wikipedia.org/wiki/Multipurpose_Internet_Mail_Extensions)) je me contentais bien souvent d’essayer de trouver le correctif dans les commentaires [des anomalies de cette classe](https://code.google.com/p/php-mime-mail-parser/issues/list).

Au bout d’un moment ça commençait à être vraiment complexe je modifiait toujours le même fichier car il y en a qu’un, les gens parlaient de ligne à modifier mais comme c’était pas la première modification ça correspondait plus et puis je ne savais jamais si un correctif n’entraînait pas d’autres régressions ou écrasait un correctif que j’avais mis en place quelques semaine plus tôt.

C’était le bordel et ça ne pouvait plus continuer comme ça surtout pour quelqu’un qui prône la Qualité dans son boulot.

## La naissance d’un fork

La solution créer un fork sur GitHub qui est la plateforme à la mode pour partager son code. Ma méthode pour résoudre ma problématique :

- Créer un bug sur GitHub quand je constate un problème
- Créer un test unitaire qui reproduit ce bug
- Corriger le bug
- Relancer mes tests unitaires pour voir que tout est vert
- Commit

J’ai donc téléchargé à nouveau le code du projet, je l’ai déposé sur Github et j’ai commencé à lire tous les bugs sur Google Code pour essayer de les reproduire.

Voici donc le bébé sur GitHub : [eXorus/php-mime-mail-parser](https://github.com/eXorus/php-mime-mail-parser)

A ce jour il y a plus de 20 tests vérifiant 53 assertions, j’en suis fier. Merci à Juan Treminio qui m’a permis d’apprendre les tests unitaires sous PHP avec son article [Unit Testing Tutorial](https://jtreminio.com/2013/03/unit-testing-tutorial-introduction-to-phpunit/) en 6 parties.

## La contribution

La première fois que l’on reçoit une contribution sur un projet Open Source est indescriptible, c’était il y a 5 mois avec le message suivant :

> Over the last few months, I've tested around 10 PHP email parsing solutions. eXorus, you've nailed it! My assessment is that your modifications to MimeMailParser.class.php have made it the most effective php email parser around in terms of performance, foreign character encoding, attachment handling, and ease of use (once MailParse is installed).

> I've reposted your code with nothing but minor formatting and syntax tweaks. Ignore if you wish :)


J’étais heureux et fier que l’on trouve mon projet intéressant. Ça reste un petit projet mais de temps en temps je reçois des modifications à effectuer ou des forks qui se créés à partir de mon code. J’essaye de regarder si il y a des bonnes idées à reprendre on ne sait jamais.

## Distribuer mon package

La mode est d’utiliser [Composer](https://getcomposer.org/) pour distribuer des packages PHP donc j’ai proposé mon package sur [Packagist](https://packagist.org/packages/exorus/php-mime-mail-parser) pour faciliter son utilisation.

Et a ce jour le package a été installé 455 fois dont 61 fois sur le dernier mois. Un petit succès pour moi.

## La suite

Il reste des contributions à traiter et vérifier, rendre le code plus propre et respectueux des standards PHP.

Etudier comment fonctionne les releases pour éviter de toujours développer sur la branche master.

Et surement plein d’autres choses, mais avant tout il faut du temps.