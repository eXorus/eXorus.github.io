---
id: 36
title: 'Php Sécurité – Découverte des Injections SQL et comment s’en protéger'
date: '2014-03-03T12:34:16+01:00'
author: Vincent
layout: post
guid: 'http://vincent.dauce.fr/?p=36'
permalink: /php-securite-injection-sql/
categories:
    - php
---

Nous allons étudier dans cet article les [injections SQL](http://fr.wikipedia.org/wiki/Injection_SQL) et apprendre à s’en protéger.

## Introduction

Cette faille est la plus importante et celle que nous connaissons le mieux. Mais avant avant de parler [des failles plus complexe comme XSS](http://vincent.dauce.fr/php-securite-faille-xss/ "Php Sécurité – Découverte de la faille XSS et comment s’en protéger") vérifions que vous avez déjà une base en sécurité. Nous allons étudier les impacts de cette faille, la comprendre et enfin s’en protéger.

Cette faille se situe entre l’application et sa base de données, le principe est d’envoyer une requête non prévue par le développeur pour compromettre la sécurité d’une application.

## Découverte de la faille

<span style="text-decoration: underline;">Exemple 1 : Se connecter en tant qu’administrateur sans connaitre le mot de passe</span>

Imaginer le code suivant pour vous connecter à votre site :

<div class="code-embed-wrapper"> ```
<pre class="language-php code-embed-pre" data-line-offset="0" data-start="1">```php
$login = $_POST['login'];
$password = $_POST['password'];

$result = mysql_query("SELECT user_id FROM users WHERE login = '".$login."' AND password = '".$password."'");
```
```

<div class="code-embed-infos"> </div> </div>La requête devrait fonctionner uniquement si le login et le mot de passe sont correcte mais c’est pas le cas. Pour l’instant notre requête ressemblerait à çà :

<div class="code-embed-wrapper"> ```
<pre class="language-sql code-embed-pre" data-line-offset="0" data-start="1">```sql
SELECT user_id FROM users WHERE login = 'admin' AND password = 'azerty'
```
```

<div class="code-embed-infos"> </div> </div>Mais nous pouvons utiliser une injection SQL pour se connecter sans mot de passe avec n’importe quel login.

Avec le login suivant : admin’–

et n’importe quel mot de passe car de toute manière il ne sera pas pris en compte la requête devient :

<div class="code-embed-wrapper"> ```
<pre class="language-sql code-embed-pre" data-line-offset="0" data-start="1">```sql
SELECT user_id FROM users WHERE login = 'admin'--' AND password = 'azerty'

--Equivalent à
SELECT user_id FROM users WHERE login = 'admin'
```
```

<div class="code-embed-infos"> </div> </div>Les caractères « — » sont interprétés en SQL comme le début d’un commentaire donc comme vous pouvez le voir ci-dessus la requête SQL n’a plus le sens que l’on souhaitait. Elle vérifie le login mais plus le mot de passe donc il suffit d’avoir le login d’une personne pour se connecter. Facile !!!

<span style="text-decoration: underline; line-height: 1.5em;">Exemple 2 : Supprimer des données</span>

Imaginons maintenant une page d’un blog http://monblog.fr/view.php?id=66 avec le code PHP suivant :

<div class="code-embed-wrapper"> ```
<pre class="language-php code-embed-pre" data-line-offset="0" data-start="1">```php
$id = $_GET['id'];

$result = mysql_query("SELECT post_text FROM posts WHERE post_id = '".$id."'");
```
```

<div class="code-embed-infos"> </div> </div>On pourrait penser que le risque est faible par rapport à notre premier exemple. Au pire le pirate pourra lire un autre post sur le blog et bien non. Si on change l’URL pour http://monblog.fr/view.php?id=65′;DROP TABLE posts;–

Ça va lire le post 65 mais en même temps supprimer la table avec tous les posts … bye bye le blog j’espère que vous avez des sauvegardes régulières.

La requête attendue est :

<div class="code-embed-wrapper"> ```
<pre class="language-sql code-embed-pre" data-line-offset="0" data-start="1">```sql
SELECT post_text FROM posts WHERE post_id = '66'
```
```

<div class="code-embed-infos"> </div> </div>La requête obtenue ou plutôt les requêtes obtenues sont :

<div class="code-embed-wrapper"> ```
<pre class="language-sql code-embed-pre" data-line-offset="0" data-start="1">```sql
SELECT post_text FROM posts WHERE post_id = '65';DROP TABLE posts;--'
```
```

<div class="code-embed-infos"> </div> </div>Donc comme nous avons pu le voir tous les caractères spécifiques à SQL doivent être protégés :

- « — » : qui permet de commenter tous ce qui est après
- « ; » : qui permet d’exécuter plusieurs requêtes les unes après les autres

## Protection

La protection est simple : **Never Trust User Input (Ne jamais faire confiance aux données des utilisateurs)**

Avant il fallait utiliser des fonctions spécifiques à PHP (mysql\_real\_escape\_string ou caster avec int) pour échapper les caractères mais ça c’était avant. Maintenant que l’extension mysql\_\* est obsolète vous devez utiliser PDO pour interagir avec une base de donnée.

[L’extension PDO](http://fr2.php.net/manual/fr/class.pdo.php) permet de gérer l’échappement des caractères pour protéger vos requêtes SQL des pirates à travers les requêtes préparées.

Si on revient sur nos 2 exemples ci-dessous nous devrions écrire les requêtes de cette manière :

<div class="code-embed-wrapper"> ```
<pre class="language-php code-embed-pre" data-line-offset="0" data-start="1">```php
$login = $_POST['login'];
$password = $_POST['password'];

$query = $pdo->prepare("SELECT user_id FROM users WHERE login = :login AND password = :password ");

$query->bindValue(':login', $login, PDO::PARAM_STR);
$query->bindValue(':password', $password, PDO::PARAM_STR);

$query->execute();
```
```

<div class="code-embed-infos"> </div> </div>Ou pour le second exemple :

<div class="code-embed-wrapper"> ```
<pre class="language-php code-embed-pre" data-line-offset="0" data-start="1">```php
$id = $_GET['id'];

$query = $pdo->prepare("SELECT post_text FROM posts WHERE post_id = :post_id");

$query->bindValue(':post_id', $id, PDO::PARAM_INT);

$query->execute();
```
```

<div class="code-embed-infos"> </div> </div>La requête SQL est écrite avec des labels commençant par « : ». Ensuite vous assignez des valeurs aux labels en indiquant le type des données :

- PARAM\_STR pour une chaîne de caractères
- PARAM\_INT pour un entier
- … [pour la liste exhaustive](http://www.php.net/manual/fr/pdo.constants.php)

` `

## Comment ça fonctionne ?

Toutes les requêtes préparées écrites avec PDO sont sécurisées car l’objectif de ces requêtes est justement de séparer les données de la structure de la requête.

Attention de bien utiliser les bindValue pour les paramètres de vos requêtes sinon vous ne serez pas protégé.

Attention sur les caractères « % » et « \_ » ne sont pas échappés donc dans le cas d’une requête avec LIKE comme opérateur si la variable comprend un des 2 caractères il sera transmis tel quel à la BDD.

Pour aller plus loin avec PDO je vous invite à lire le [tuto de Francois Mazerolle sur developpez](http://fmaz.developpez.com/tutoriels/php/comprendre-pdo/)

Merci à l’auteur ci-dessous qui m’a donnée envie d’écrire cette série d’article :

Source (English) : <http://www.sunnytuts.com/article/php-security-sql-injection>