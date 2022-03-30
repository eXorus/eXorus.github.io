---
extends: _layouts.post
section: content
title: Php Sécurité – Découverte de la faille XSS et comment s'en protéger
date: 2014-03-08
description: 
categories: [php]
---

Nous allons étudier dans cet article la faille [Cross-Site Scripting](http://fr.wikipedia.org/wiki/Cross-site_scripting) (XSS) et apprendre à s’en protéger.

## Introduction

Cette faille est la plus importante [après les injections SQL que nous avons déjà étudié](http://vincent.dauce.fr/php-securite-injection-sql/ "Php Sécurité – Découverte des Injections SQL et comment s’en protéger"). Donc il est nécessaire de la connaitre pour s’en protéger.

Le principe de cette faille est d’injecter des données spécifiques sur un site web. Celui-ci va l’afficher sans en contrôler la nature et provoquer une importante faille à tous les visiteurs qui l’afficherons.

## ———————————————————————————

07/06/2014 : Ajout du lien vers le XSS Game à la fin

## ———————————————————————————

## Découverte de la faille

<span style="text-decoration: underline;">Exemple 1 : Avec du code HTML (XSS stocké ou permanent)</span>

Sur la page d’inscription je saisi mon login avec du code HTML :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
<strong>eXorus</strong>
```
```

<div class="code-embed-infos"> </div> </div>La faille va se voir quand je vais me connecter car le site non protégé affiche mon login sans le traiter donc je vais voir mon login en gras : **eXorus**

Et les autres utilisateurs le verront aussi par exemple sur le forum ou l’on affiche le nom de l’auteur d’un post, toujours en gras.

Dans ce cas c’est pas très méchant et ca peut même être jolie si on décide de mettre du code HTML avec du CSS pour avoir de la couleur, …

<span style="text-decoration: underline;">Exemple 2 : Avec du code Javascript (XSS réfléchi ou non permanent)</span>

Imaginons un forum avec des posts sur plusieurs pages, quand je clique sur page suivante j’ai des URL de type :

http://www.monforum.fr/informatique?p=1

http://www.monforum.fr/informatique?p=2

http://www.monforum.fr/informatique?p=3

<span style="line-height: 1.5em;">Sur chaque page on m’indique que je suis sur la page 1 ou 2 ou 3 … mais cette donnée n’est pas protégée donc si je change moi même la valeur de la variable p dans l’URL la faille va se voir. Par exemple :</span>

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
http://www.monforum.fr/informatique?p=<script type="text/javascript">alert('Faille');</script>
```
```

<div class="code-embed-infos"> </div> </div>Quand je vais aller sur cette URL comme on affiche la variable p sans la protéger je vais avoir une popup Javascript qui va m’afficher « Faille ». Encore une fois on est pas très méchant et en plus l’attaquant va s’attaquer lui même car personne n’ira jamais sur cette URL … quoi que ça arrive des fois 🙂 c’est pour ca qu’il faut éviter de suivre les URL qu’on nous donne sans y réfléchir.

<span style="text-decoration: underline;">Conclusion</span>

Dans les 2 exemples ci-dessous nous avons été très gentil mais nous pouvons faire beaucoup plus de dégats par exemple au lieu simplement d’afficher un texte avec une couleur ou d’afficher une popup Javascript on peut rediriger le visiteur vers un site pirate qui imitera la site attaqué avec le même design. Donc le visiteur ne verra pas qu’il a changé de site et ensuite tout peut arriver.

Reprenons l’exemple 1 en plus méchant :

1. Je m’inscris sur un forum http://forum.fr avec le login :


<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
<script type=”text/javascript”>window.location.href=”http://forumpirate.fr/login";</script>
```
```

<div class="code-embed-infos"> </div> </div>1. Je vais écrire un premier post sur le forum intéressant pour toucher le plus de personnes possibles
2. Toutes les personnes qui vont voir mon post vont voir mon login et donc le Javascript va s’exécuter pour les rediriger vers la page http://forumpirate.fr/login
3. Sur ce nouveau forum pirate que j’ai créé moi même j’ai reproduit à l’identique le site initial http://forum.fr
4. Donc le visiteur ne va pas comprendre pourquoi il est déconnecté tout d’un coup en voulant lire un post intéressant mais il va pas chercher plus loin et il va saisir son login et mot de passe sur le forum pirate
5. Sur mon forum pirate le login et le mot de passe seront récupérés pour être sauvegardé dans ma base en clair pour pouvoir les lire et les utiliser contre eux

Dans le même genre mais au lieu de rediriger vers un site pirate on redirigerais sur une autre page du forum qui serait accessible uniquement aux administrateurs du forum comme une page pour supprimer un post.

L’URL pour supprimer un post est http://forum.fr/post-delete.php?id=66

Mais cette URL n’est autorisé que pour les comptes administrateurs donc si je suis un pirate et que je veux supprimer le post 66 il suffit de m’inscrire sur le forum avec le login :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
<script type=”text/javascript”>window.location.href=”http://forum.fr/post-delete.php?id=66";</script>
```
```

<div class="code-embed-infos"> </div> </div>Ecrire un nouveau post dans le forum et espérer qu’un administrateur passe. Pour les utilisateurs normaux ils seront redirigés vers l’URL mais comme ils n’ont pas les droits ça mettra un message d’erreur mais pour l’administrateur ça supprimera le post.

## Protection

La protection est simple : **Never Trust User Input (Ne jamais faire confiance aux données des utilisateurs)**

- <span style="line-height: 1.5em;">Le risque de cette faille est uniquement lors de l’affichage donc il faut nettoyer la donnée à afficher avant de l’afficher. </span>
- Nettoyer la donnée une seule fois
- Valider les données lors de la récupération : 
    - Limiter les caractères autorisés pour un login (alphanumérique)
    - Contrôler la forme d’une adresse mail (xxx@xxx.xxx)
    - Limiter le nombre de caractères un prénom de 200 caractères est ce que ça existe ?
    - Un chiffre est un chiffre donc ne pas permettre de mettre des lettres
    - …

<div class="code-embed-wrapper"> ```
<pre class="language-php code-embed-pre" data-line-offset="0" data-start="1">```php
$userInput = '<strong>eXorus</strong>';

// [Faille XSS] Affiche eXorus en gras
echo $userInput;

// [XSS Sécurisé] Affiche <strong>eXorus</strong>
echo htmlspecialchars($userInput, ENT_QUOTES);
```
```

<div class="code-embed-infos"> </div> </div>L’unique méthode magique à utiliser en PHP pour se protéger des failles XSS est [htmlspecialchars()](http://www.php.net/manual/fr/function.htmlspecialchars.php) avec le paramètre ENT\_QUOTES qui convertit les guillemets simples en plus.

## Comment ça fonctionne ?

Si tu affiches du code HTML, CSS ou Javascript il sera interprété par le navigateur du visiteur. Pour éviter cela on affiche les caractères qui ont des significations spéciales en HTML/CSS et Javascript sous forme d’entités HTML.

Pour le code ci-dessous <span style="text-decoration: underline;">dans le navigateur</span> nous allons voir :

**eXorus**

&lt;strong&gt;eXorus&lt;/strong&gt;

et <span style="text-decoration: underline;">dans le code source</span> nous allons voir :

&lt;strong&gt;eXorus&lt;/strong&gt;

&amp;lt;strong&amp;gt;eXorus&amp;lt;/strong&amp;gt;

De cette manière le code HTML/CSS et Javascript s’affiche proprement dans le navigateur sans être interprété (ce que nous recherchons).

Et merci à l’auteur ci-dessous qui m’a donné envie d’écrire sur cette faille :

<span style="line-height: 1.5em;">Source (English) : </span><http://www.sunnytuts.com/article/preventing-cross-site-scripting-xss>

Pour finir je vous laisse vous entrainer sur un XSS Game : <https://xss-game.appspot.com/>