---
extends: _layouts.post
section: content
title: 'Administrer un serveur dédié - part 5 : Serveur Web'
date: 2014-02-10
description: 
categories: [admin]
---

Dans cet article nous allons voir comment déployer un serveur web, pour cela nous avons besoin de plusieurs briques :

- Apache : c’est le serveur web par excellence qui reste encore majoritaire sur le web. Avec lui on va pouvoir servir des pages web statiques HTML/CSS
- Php : Le langage que j’utilises le plus pour mes sites web.
- MySQL : La base de données qui est aussi prédominante (WordPress, Prestashop, …)
- SFTP : je n’utilise pas de serveur FTP car je n’en vois pas l’intérêt quand on a déjà SSH sur la machine autant activer SFTP.

Voilà bien sur on aurait pu prendre Nginx à la place d’Apache, MariaDB à la plase de MySQL, vsFTPd à la place de sftp … mais il faut faire un choix et on pourra toujours revenir dessus dans un prochain article.

Je vous conseil de rien n’installer sur le host pour le laisser le plus propre possible et d’utiliser un container. Nous souhaitons héberger nos sites sur CT101 que nous venons de créer donc c’est partit.

## Serveur Web – Apache

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
aptitude install apache2
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Installer Apache</span> </div> </div>Il va installer plusieurs packages car [apache2 est un metapaquet](https://packages.debian.org/wheezy/apache2).

A la fin de l’installation le serveur web est lancé et fonctionnel. On peut le voir rapidement en saisissant dans votre navigateur l’adresse IP Failover de votre container qui affichera une page web. Avant on avait connexion refusée.

Il y a aussi après le lancement un petit warning que nous allons corriger : « apache2: Could not reliably determine the server’s fully qualified domain name, using 127.0.0.1 for ServerName »

C’est très simple pour ne plus avoir les erreurs vous devez modifier le fichier suivant et ajouter cette ligne à la fin du fichier :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
ServerName localhost
```
```

<div class="code-embed-infos"> <span class="code-embed-name">vi /etc/apache2/apache2.conf</span> </div> </div>Vérifions aussi quelques configurations d’Apache pour voir si tout est en ordre :

Vérifier que la configuration d’Apache (normalement c’est celle par défaut) écoute bien sur le port 80. Et aussi sur le port 443 un peu plus bas dans le cas ou on voudrait déployer du SSL ce que nous ferons bientôt pour notre cloud.

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
NameVirtualHost *:80
Listen 80
```
```

<div class="code-embed-infos"> <span class="code-embed-name">vi /etc/apache2/ports.conf</span> </div> </div>Modifier en décommentant la ligne (supprimer le #) pour passer Apache2 en UTF-8 c’est la norme sur le web depuis quelques années maintenant et vous ne rencontrerez aucun problème en encodant tous les fichiers en UTF-8.

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
AddDefaultCharset UTF-8
```
```

<div class="code-embed-infos"> <span class="code-embed-name">vi /etc/apache2/conf.d/charset</span> </div> </div>Pour personnaliser un peu apache je vous conseil 2 modules qui sont presque indispensable pour moi :

- [rewrite](http://httpd.apache.org/docs/2.2/mod/mod_rewrite.html) : permet la réécriture d’URL, tous les sites l’utilisent de nos jours (au lieu d’avoir http://vincent.dauce.fr/index.php?article=1 nous avons http://vincent.dauce.fr/mon-premier-article)
- [userdir](http://httpd.apache.org/docs/current/mod/mod_userdir.html) : celui là est moins évident ça permet de créer un hébergement web par utilisateur. Pour chaque site hébergé sur ce serveur je vais créer un compte avec un mot de passe pour lui donner accès aux fichiers et avoir un espace web. Pour sécuriser le tout c’est moi qui l’activerait ou non par compte donc par exemple compte root n’a pas d’espace web 🙂

Pour activer les 2 modules ci-dessus nous utilisons :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
a2enmod rewrite
a2enmod userdir
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Activer les modules principaux sur Apache</span> </div> </div>Comme vu plus haut nous allons sécuriser le module userdir avec la configuration suivante (ajouter les lignes à la fin du fichier après ServerName) :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
# Configuration du module UserDir
UserDir disabled
UserDir /home/*/www
```
```

<div class="code-embed-infos"> <span class="code-embed-name">vi /etc/apache2/apache2.conf </span> </div> </div>Le module userdir est ainsi désactivé et sera activé au cas par cas selon nos besoins. Et nous définissons que les pages web seront dans le répertoire /home/\*/www avec \* le login du compte.

Pour vous donner une idée, si je veux héberger le site « toto.com » je vais créer un compte toto qui aura donc un répertoire personnel dans /home/toto. Si j’active pour ce compte l’espace web il pourra déposer des fichiers dans /home/toto/www et consultable via le lien CT101\_IP\_PUBLIC/~toto.

Justement pour éviter d’avoir à créer à chaque fois le dossier www nous allons automatiser la création et créer un dossier logs pour stocker les fichier log d’Apache par compte.

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
mkdir /etc/skel/www
mkdir /etc/skel/logs
echo " <strong>Nouvel espace web</strong> " > /etc/skel/www/index.html
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Automatiser la création du dossier www</span> </div> </div>Puis recharger la configuration et admirer que vous n’avez plus le message d’erreur :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
service apache2 reload
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Recharger apache2</span> </div> </div>## Serveur SQL – MySQL

Pour installer MySQL c’est très simple voir trop simple 🙂

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
aptitude install mysql-server
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Installer MySQL</span> </div> </div>On est encore dans le cas d’un metapaquet, on valide et c’est tout.

Je vous conseil de choisir un mot de passe complexe pour le root de MySQL, moi j’utilise 50 caractères avec majuscules, minuscules et chiffres (Pas de caractères spéciaux car j’ai souvent eu des problèmes après pour me connecter avec).

Il existe plein de générateur de mot de passe en ligne, je vous laisse choisir votre préféré moi j’ai pas trouvé. Surement une idée d’application à faire 🙂

Voilà le mien : a4HBSwpwdlF3LafNsPswKM6uZQWGIqrjnUGXkNcoYX3XGE5lZV

à conserver précieusement car c’est le compte root.

## Serveur App – PHP

Pour PHP aussi pas beaucoup de chose à faire (PHP 5.4) :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
aptitude install php5
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Installer PHP</span> </div> </div>Après comme pour Apache il y a des modules plus ou moins importants, voici les principaux :

- [php5-mysql](https://packages.debian.org/wheezy/php5-mysql) : plutôt indispensable celui là sinon comment vous connecter à MySQL que vous venez d’installer ? permet d’activer les fonctions mysql\_\* (ne plus les utiliser svp c’est trop horrible et obsolète depuis la version 5.5 de PHP), mysqli\_\* (c’est mieux mais bon) et enfin PDO (ça c’est la classe)
- [php5-curl](https://packages.debian.org/wheezy/php5-curl) : pour récupérer des fichiers via HTTP ou FTP
- [php5-gd](https://packages.debian.org/wheezy/php5-gd) : pour traiter des images (resize, traitements, …)
- [php5-mcrypt](https://packages.debian.org/wheezy/php5-mcrypt) : pour crypter des données sensibles
- [libssh2-php](https://packages.debian.org/wheezy/libssh2-php) : pour utiliser SSH dans les scripts PHP

[Prestashop](http://www.prestashop.com/fr/) la boutique en ligne et open source que je vous conseil a besoin de php5-curl pour activer Paypal, php5-gd pour retailler les images, php5-mcrypt pour crypter les données sensibles et bien évidemment php5-mysql.

[WordPress](http://wordpress.org/) le blog open source qui fait aussi CMS a besoin de libssh2-php pour pouvoir faire les mise à jour en automatique

Pour les installer tous il suffit de lancer :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
aptitude install php5-mysql php5-gd php5-mcrypt php5-curl libssh2-php
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Installer les modules PHP</span> </div> </div>Juste après ca vous n’avez rien à faire tout est déjà fonctionnel.

## Serveur Fichier – SFTP

Pour le serveur de fichier j’utilisais il y a très longtemps des serveurs FTP plus ou moins sécurisés jusqu’au jour ou j’ai découvert que SSH le faisait déjà très bien. Alors pourquoi installer un nouveau service alors qu’on en a déjà un installé et qui tourne ? en plus il y avait la gestion des comptes qui était complexe là c’est le même login et mot de passe.

Vous allez me dire mais j’ai pas envie de donner un accès SSH à mes utilisateurs et bien non on leur donne uniquement le SFTP ils peuvent rien faire de plus c’est donc totalement sécurisé.

Pour celà il faut juste configurer SSH pour permettre de rendre le service que l’on veut c’est à dire offrir aux utilisateurs un accès aux fichiers via un client FTP comme filezilla ou un partage de fichiers.

Il faut donc changer la ligne « Subsystem … » (avant on avait « Subsystem sftp /usr/lib/openssh/sftp-server ») : On indique autoriser le faux shell « internal-sftp » qui va permettre d’interdire l’accès au shell pour les utilisateurs.

Et rajouter quelques lignes après « UserPAM yes ». Pour autoriser le groupe www-data qui est celui d’Apache donc de nos sites.

- ChrootDirectory : les utilisateurs ne pourront pas remonter à la racine du serveur et explorer notre serveur. Ils sont bloqués dans leur dossier personnel
- X11Forwarding : on n’autorise pas le partage de bureau (application graphique à distance)
- AllowTcpForwarding : interdire aussi les tunnels SSH
- ForceCommand : pour forcer l’utilisation d’un faux shell et interdire donc un accès SSH

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
Subsystem sftp internal-sftp

# Set this to 'yes' to enable PAM authentication, account processing,
# and session processing. If this is enabled, PAM authentication will
# be allowed through the ChallengeResponseAuthentication and
# PasswordAuthentication. Depending on your PAM configuration,
# PAM authentication via ChallengeResponseAuthentication may bypass
# the setting of "PermitRootLogin without-password".
# If you just want the PAM account and session checks to run without
# PAM authentication, then enable this but set PasswordAuthentication
# and ChallengeResponseAuthentication to 'no'.
UsePAM yes
	AllowGroups www-data
	Match Group www-data
	        ChrootDirectory /home/%u
	        X11Forwarding no
	        AllowTcpForwarding no
	        ForceCommand internal-sftp
```
```

<div class="code-embed-infos"> <span class="code-embed-name">vi /etc/ssh/sshd\_config </span> </div> </div>Reste à recharger la configuration de notre service :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
service ssh reload
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Recharger SSH</span> </div> </div>Et voilà nous avons un SFTP sécurisé accessible uniquement aux utilisateurs appartenant au groupe www-data. Pour rappel le fichier de conf a déjà été modifié pour interdire au root de pouvoir se connecter et nous avons déjà changé le port par défaut qui était à 22 pour plus de sécurité.

Reste à rajouter des droits car sinon le SFTP interdira de se connecter.

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
chown -R root:www-data /home/
chmod -R 750 /home/
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Droits pour SFTP</span> </div> </div>