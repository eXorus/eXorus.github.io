---
extends: _layouts.post
section: content
title: 'Administrer un serveur dédié - part 6 : Espace Web'
description: ajout d'un premier site web
date: 2014-02-15
categories: [admin]
---

Vous souhaitez installer Prestashop, WordPress ou créer votre site de A à Z ? et bien il vous faut un espace web que l’on va créer maintenant.

Le socle est présent puisque dans l’article précédent nous avons terminé l’installation du serveur Web.

Cette procédure est à répéter autant de fois que vous voulez, par exemple pour ma part j’ai :

- Ce blog bien sur : [http://vincent.dauce.fr](http://vincent.dauce.fr/)
- La marque de mon amie avec : 
    - un site : <http://www.sage-et-sauvage.fr>
    - un blog : [http://blog.sage-et-sauvage.fr](http://blog.sage-et-sauvage.fr/)
    - une boutique en ligne : [http://shop.sage-et-sauvage.fr](http://shop.sage-et-sauvage.fr/)
- Le site d’une association : [http://www.actessen.fr](http://www.actessen.fr/)
- Le site d’une yourte : [http://yourteauborddeleau.com](http://yourteauborddeleau.com/)

  
———————————————————————————

27/04/2014 : Ajout de l’expiration du mot de passe au bout de 100 jours pour plus de sécurité (cmd chage)

29/06/2014 : Ajout de « Configurer le container avec un nom de domaine »

———————————————————————————

## Accès Web et SFTP

Pour créer un hébergement comme j’utilise le mode userdir, on commence par ajouter un compte « blogtuto » dans le groupe www-data (group d’Apache)

Ajouter un compte:
```bash
useradd -g www-data -m blogtuto
passwd blogtuto
chage -M 100 blogtuto
chown -R root:www-data /home/blogtuto/
chmod -R 750 /home/blogtuto/
chown -R blogtuto:www-data /home/blogtuto/www/
chmod -R 750 /home/blogtuto/www/
```

Avec ces quelques commande plein de choses sont faites :

- Création d’un compte
- Modification du mot de passe (pour en mettre un compliqué : 50 caractères avec majuscules, minuscules, chiffres et caractères spéciaux)
- Création d’un répertoire personnel /home/blogtuto avec 
    - /home/blogtuto/logs pour stocker les logs apache
    - /home/blogtuto/www pour stocker les fichiers web
- Création d’une page web de bienvenue : index.html dans le dossier www
- Mise à jour des droits pour autoriser Apache à servir les pages et le compte blogtuto à les modifier via le SFTP.

On peut déjà se connecter sur le SFTP (sftp://CT101_IP_PUBLIC:SSH_PORT) et modifier la page index.html ou créer des nouveaux fichiers dans le dossier www mais il n’est toujours pas possible de voir la page index.html car nous ne l’avons pas autorisé.

vi /etc/apache2/apache2.conf:
```bash
UserDir enabled blogtuto
```

La ligne est à rajouter juste après « UserDir /home/*/www », un espace doit séparer chaque compte donc à la fin on peut avoir des choses comme ca :

Configuration UserDir:
```bash
# Configuration du module UserDir
UserDir disabled
UserDir /home/*/www
UserDir enabled blogtuto blogtuto2 blogtuto3
```

Et recharger la configuration apache2

Recharger apache:
```bash
service apache2 reload
```

Saisir http://CT101_IP_PUBLIC/~blogtuto dans votre navigateur préféré et vous devriez voir la page index.html.

## Accès MySQL

On se connecte avec le compte root et le mot de passe que vous avez mis dans l’article précédent :

Création d'un accès MySQL:
```bash
mysql -h localhost -u root -p
mysql > create database blogtutoDB;
mysql > grant all privileges on blogtutoDB.* to blogtuto@localhost identified by 'mypasswd';
mysql > flush privileges;
mysql > exit;
```

Avec ces commandes nous avons créé une nouvelle base de données blogtutoDB et avons attribués tous les droits sur cette base uniquement à un nouvel utilisateur blogtuto que nous créons avec le mot de passe passwd.

Si vous avez déjà mis en place les sauvegardes MySQL avec le tuto [Administrer un serveur dédié – part 7 : Backup MySQL](http://vincent.dauce.fr/administrer-un-serveur-dedie-part-7-backup-mysql/ "Administrer un serveur dédié – part 7 : Backup MySQL"), pensez à rajouter les droits pour la sauvegarde automatique entre le grant initial et le flush privileges :

Ajouter la sauvegarde de la base en automatique:
```sql
GRANT SELECT , INSERT , LOCK TABLES ON `blogtutoDB` . * TO 'mysql-backup-manager'@'localhost';
```

## Configurer le container avec un nom de domaine

Une bonne pratique à avoir est de rajouter le nom de domaine dans le fichier host du container. Pour cela vous devez juste rajouter « blogtuto.com » sur la première ligne du fichier host après localhost :

vi /etc/hosts
Vous pouvez rajouter autant de domaines que vous le souhaitez séparé par des espaces.

## Configurer le virtual host Apache avec un nom de domaine

La méthode de userdir est sympathique quand on a pas nom de domaine mais quand on en a un alors il faut configurer Apache pour reconnaître ce domaine et rediriger vers la bonne home.

Il faut donc créer un virtual host (nouveau fichier)

vi /etc/apache2/sites-available/01-blogtuto.com:
```bash
<VirtualHost *:80>
        ServerAdmin blogtuto@localhost
        ServerName www.blogtuto.com
        ServerAlias blogtuto.com

        DocumentRoot /home/blogtuto/www
        <Directory /home/blogtuto/www>
                Options None
                AllowOverride None
        </Directory>

        ErrorLog /home/blogtuto/logs/error.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        CustomLog /home/blogtuto/logs/access.log combined

</VirtualHost>
```

Et pour le coup il y a du travail car beaucoup de configuration à faire et à expliquer.

Déjà le nom du fichier « 01-blogtuto.com » c’est une convention personnel :

- On commence par 01, puis 02, … : Les virtual host sont pris dans l’ordre avec Apache donc si plusieurs correspondent c’est le premier qui sera pris. Donc on essaye de les classer au mieux.
- Ensuite blogtuto.com pour la conf du domaine blogtuto.com c’est plus facile à ce souvenir mais c’est pas nécessaire techniquement tous les fichiers sont pris par apache

Pour le fichier de conf :

- VirtualHost *:80 : On peut avoir plusieurs balise dans un fichier mais je préfère en avoir qu’un. Ici on dit que ce virtual host écoutera sur le port 80 (http et donc pas https) sur n’importe qu’elle IP comme le container en a qu’un çà sera toujours la même mais si vous avez plusieurs IP sur le même container il faut préciser
- [ServerAdmin](http://httpd.apache.org/docs/2.2/fr/mod/core.html#ServerAdmin) : c’est l’adresse mail de contact qui recevra les messages d’erreurs donc mon utilisateur blogtuto@localhost
- [ServerName](http://httpd.apache.org/docs/2.2/fr/mod/core.html#ServerName) : La directive la plus importante qui va permettre d’identifier le virtual host à utiliser donc l’adresse la plus importante soit www.blogtuto.com ou blogtuto.com en fonction
- [ServerAlias](http://httpd.apache.org/docs/2.2/fr/mod/core.html#ServerAlias) : La directive alternative donc si vous avez mis www.blogtuto.com mettez blogtuto.com et inversement
- [DocumentRoot](http://httpd.apache.org/docs/2.2/fr/mod/core.html#DocumentRoot) : Indique la racine quand on appel http://www.blogtuto.com/index.html avec DocumentRoot = /home/blogtuto/www, Apache traduit par le fichier /home/blogtuto/www/index.html (Ne pas mettre de slash à la fin)
- [Directory](http://httpd.apache.org/docs/2.2/fr/mod/core.html#Directory) : On va mettre au moins une balise directory avec la même valeur que DocumentRoot et ensuite on peut rajouter d’autres si nécessaire. C’est dans cette directive qu’on va devoir mettre les configurations applicables pour un dossier/sous-dossier, … On reviendra plus tard sur les directives intéressantes à rajouter ou supprimer. On interdit tout pour le moment
- [ErrorLog](http://httpd.apache.org/docs/2.2/fr/mod/core.html#ErrorLog) et CustomLog : pour avoir des logs Apache dans le répertoire logs que nous avons créé. On se place au niveau warn pour recevoir un maximum d’information si on veut diagnostiquer un jour.

Pour prendre en compte nos modifications il faut activer le nouveau virtual host et recharger Apache :

Activer un site et recharger Apache:
```bash
a2ensite 01-blogtuto.com
service apache2 reload
```

## Encore plus loin avec Directory

### AllowOverride

J’ai pris l’habitude de ne jamais autoriser AllowOverride donc le mettre à None pour une question de performance. Cette directive permet d’utiliser les fameux fichier .htaccess qui permette de réécrire la configuration d’Apache à la volée. Seulement voilà ça demande des ressources à Apache pour contrôler l’existence du fichier et modifier la configuration à la volée. Quand on a accès au serveur et que la configuration ne change pas souvent et qu’on a pas besoin que l’utilisateur choisisse les réglages c’est l’idéal. Si on a vraiment besoin de cet liberté alors je vous conseil de rajouter une directive très stricte. Je vous invite aussi à lire la doc Apache sur le sujet « [Quand doit-on (ne doit-on pas) utiliser les fichiers .htaccess ?](http://httpd.apache.org/docs/2.2/fr/howto/htaccess.html#when) »

Donc pour résumer je n’utilise pas de fichiers htaccess. Toutes les directives que je trouve dans un htaccess je le rajoute dans la configuration de mon virtual host et comme ca je profite du cache d’Apache pour une configuration qui ne change jamais ou presque.

Pour WordPress :

Virtual Host Apache pour WordPress:
```bash
<Directory /home/blogtuto/www>
  Options +SymLinksIfOwnerMatch -FollowSymLinks -ExecCGI -Includes -IncludesNOEXEC -Indexes -MultiViews
  AllowOverride None

  # BEGIN WordPress
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.php$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.php [L]
  # END WordPress
</Directory>
```

###  Forcer le WWW ou non

Il ne faut jamais que vos sites soient accessibles avec 2 URL différentes sinon les statistiques sont faussés. Donc il faut choisir entre www.blogtuto.com ou blogtuto.com.

Pour rediriger toutes les requêtes blogtuto.com vers www.blogtuto.com :

Forcer WWW:
```bash
<Directory /home/blogtuto/www>
	Options +SymLinksIfOwnerMatch -FollowSymLinks -ExecCGI -Includes -IncludesNOEXEC -Indexes -MultiViews
	AllowOverride None

	# BEGIN Force WWW
	RewriteEngine On
	RewriteBase /
	RewriteCond %{HTTP_HOST} ^blogtuto.com [NC]
	RewriteRule ^(.*)$ http://www.blogtuto.com/$1 [L,R=301]
	# END Force WWW
</Directory>
```

Et inversement si vous voulez que toutes les requêtes www.blogtuto.com redirigent vers blogtuto.com :

Force NO WWW:
```bash
<Directory /home/blogtuto/www>
	Options +SymLinksIfOwnerMatch -FollowSymLinks -ExecCGI -Includes -IncludesNOEXEC -Indexes -MultiViews
	AllowOverride None

	# BEGIN Force NO WWW
	RewriteEngine On
	RewriteBase /
	RewriteCond %{HTTP_HOST} !^blogtuto.com [NC]
	RewriteRule ^(.*)$ http://blogtuto.com/$1 [L,R=301]
	# END Force NO WWW
</Directory>
```
