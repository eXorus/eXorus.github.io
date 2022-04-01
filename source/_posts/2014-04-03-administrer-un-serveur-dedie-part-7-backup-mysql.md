---
extends: _layouts.post
section: content
title: 'Administrer un serveur dédié - part 7 : Backup MySQL'
description: 
date: 2014-04-03
categories: [admin]
---

Sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, sauvegarder, …

Et oui on ne le répétera jamais assez il faut penser à sauvegarder vos données avant tout. Le 31 mars est la journée international de la sauvegarde donc parlons en. aujourd’hui.

La solution proposée ici est simple car nous allons sauvegarder uniquement les bases de données mais dans un prochain article nous pourrions voir comment sauvegarder directement un container. L’avantage serait de sauvegarder la base de données et les fichiers et de faciliter la restauration en cas de sinistre.

  
———————————————————————————

27/04/2014 : Ajout de l’expiration du mot de passe au bout de 100 jours pour plus de sécurité (cmd chage)

28/05/2014 : Modification de l’offre de sauvegarde gratuite d’Online, on passe de 10Go à 100Go cool !!!

———————————————————————————  
Nous allons mettre en place dans **chaque** **container** (ayant une base de données MySQL) un script qui va lister les base de données, les sauvegarder en local et les envoyer sur le FTP offert par Online.

## Création du compte système

Création et initilisation du mdp:
```bash
useradd -g www-data -m MySQLBackupManager
passwd MySQLBackupManager
chage -M 100 
```

Le compte sera MySQLBackupManager avec un mot de passe compliquée comme d’habitude 🙂

On va nettoyer le répertoire personnelle du compte pour ne laisser que le script nécessaire et les sauvegardes locales

Nettoyer le répertoire personnel:
```bash
su - MySQLBackupManager
rm -rf /home/MySQLBackupManager
mkdir scripts
mkdir tmp
mkdir logs
mkdir backups_daily
mkdir backups_weekly
```

Voilà tout est en place il reste le fameux scripts mais nous allons d’abord récupérer les informations nécessaires avant.

Tout le reste se passe avec le compte MySQLBackupManager

## Création du compte MySQL

Se connecter en root à MySQL:
```bash
mysql -h localhost -u root -p
```

Création du compte:
```sql
CREATE USER 'mysql-backup-manager'@'localhost' IDENTIFIED BY 'MON_PASSWORD';
FLUSH PRIVILEGES;
EXIT;
```

Remplacer par le mot de passe que vous souhaitez.

## Récupérer les informations du serveur distant

Dans la console Online, sur la liste des serveurs sélectionner votre serveur.

Dans l’onglet « Sauvegarde », activer le compte FTP. Online offre gratuitement un espace de stockage de <del>10Go</del> 100Go (depuis le 28/05/2014) n’est ce pas magnifique ? En plus c’est fait intelligement c’est à dire que votre serveur de backup est toujours dans un autre datacenter que votre serveur donc en cas d’accident (incendie) et bien vos données seront sauvées.

Après l’activation récupérer :

- l’adresse FTP : dedibackup-dc2.online.net
- le login : le nom de votre serveur sd-xxxxx
- le mot de passe

## Mise en place du script de sauvegarde

Récupérer le script sur mon github :

[MySQLBackupManager.sh](https://github.com/eXorus/eXorus/blob/master/MySQLBackupManager/MySQLBackupManager.sh "Afficher MySQLBackupManager.sh") [affichage brut](https://raw.github.com/eXorus/eXorus/master/MySQLBackupManager/MySQLBackupManager.sh "Back to MySQLBackupManager.sh"):
```bash
!/bin/bash

#---------------------------------------------------------------#
# Paramétrage de la connection MySQL                            #
#---------------------------------------------------------------#

#Nom de l'utilisateur qui lance le backup
user=mysql-backup-manager
#Machine sur laquelle on se connecte
host=localhost
#Mot de passe de l'utilisateur de backup
pass=mon_mot_de_passe_system

# Outil de dump
MYSQLDUMP=mysqldump
#Outil de check
MYSQLCHECK=mysqlcheck
# Options passées |  MYSQLDUMP
OPTIONS="--add-drop-database  --add-drop-table --complete-insert --routines --triggers --max_allowed_packet=250M --force"

#---------------------------------------------------------------#
# Paramétrage de la sauvegarde                                  #
#---------------------------------------------------------------#

# Répertoire temporaire pour stocker les backups
TEMPORAIRE="/home/MySQLBackupManager/tmp"

# Nom du serveur
MACHINE="$(hostname)"

# Date jour
DATE_DAILY="$(date +"%Y-%m-%d")"
#Retention des sauvegardes journalières
DAILY_RETENTION=15

# Date semaine
DATE_WEEKLY="$(date +"%U")"
#Retention des sauvegardes hebdomadaires
WEEKLY_RETENTION=200

# Nom des fichiers de backup
# Répertoire de destination du backup
REP_DAILY="backups_daily"
REP_WEEKLY="backups_weekly"
DESTINATION_DAILY="/home/MySQLBackupManager/"$REP_DAILY
DESTINATION_WEEKLY="/home/MySQLBackupManager/"$REP_WEEKLY
FICHIER_BACKUP_DAILY=$MACHINE"_BACKUP_MYSQL_"$DATE_DAILY".tar.gz"
FICHIER_BACKUP_WEEKLY=$MACHINE"_BACKUP_MYSQL_S"$DATE_WEEKLY".tar.gz"

#Informations FTP
LOGIN_FTP=sd-xxxx
PASS_FTP=mon_mot_de_passe_ftp
HOST_FTP=dedibackup-dc2.online.net
FTP_DAILY=$MACHINE"/"$REP_DAILY
FTP_WEEKLY=$MACHINE"/"$REP_WEEKLY

#---------------------------------------------------------------#
# Process de sauvegarde                                         #
#---------------------------------------------------------------#
# Création du répertoire temporaire
if [ -d $TEMPORAIRE ];
then
  echo "Le repertoire "$TEMPORAIRE" existe.";
else
  mkdir $TEMPORAIRE;
  echo "Création du repertoire "$TEMPORAIRE".";
fi

# On construit la liste des bases de données
BASES="$(mysql -u $user -h $host -p$pass -Bse 'show databases')"

# On lance le dump des bases
for db in $BASES
do
  if [ $db != "information_schema" ]; then
    #On lance un check et une analyse pour chaque base de données
    $MYSQLCHECK -u $user -h $host -p$pass -c -a $db
    # On lance un mysqldump pour chaque base de données
    $MYSQLDUMP -u $user -h $host -p$pass $OPTIONS $db -R > $TEMPORAIRE"/"$MACHINE"-"$db"-"$DATE_DAILY".sql";
  fi
done

# Création du répertoire de destination journalier
if [ -d $DESTINATION_DAILY ];
then
  echo "Le repertoire "$DESTINATION_DAILY" existe.";
else
  mkdir $DESTINATION_DAILY;
  echo "Création du repertoire "$DESTINATION_DAILY".";
fi

# Création de l'archive contenant tout les dump
#Cette archive est stockée dans le dossier défini pour la sauvegarde
cd $TEMPORAIRE
tar -cvzf $DESTINATION_DAILY"/"$FICHIER_BACKUP_DAILY *

# Création du répertoire de destination semaine
if [ -d $DESTINATION_WEEKLY ];
then
  echo "Le repertoire "$DESTINATION_WEEKLY" existe.";
else
  mkdir $DESTINATION_WEEKLY;
  echo "Création du repertoire "$DESTINATION_WEEKLY".";
fi

#Copie de la sauvagarde semaine
if [ -f $DESTINATION_WEEKLY"/"$FICHIER_BACKUP_WEEKLY  ];
then
    echo "La sauvegarde "$DESTINATION_WEEKLY"/"$FICHIER_BACKUP_WEEKLY" existe.";
else
    echo "Création de la sauvegarde "$DESTINATION_WEEKLY"/"$FICHIER_BACKUP_WEEKLY".";
    cp $DESTINATION_DAILY"/"$FICHIER_BACKUP_DAILY $DESTINATION_WEEKLY"/"$FICHIER_BACKUP_WEEKLY
fi

# On supprime le fichier
find $DESTINATION_DAILY -type f -mtime +$DAILY_RETENTION | xargs -r rm
find $DESTINATION_WEEKLY -type f -mtime +$WEEKLY_RETENTION | xargs -r rm

# On transfere l'archive par FTP
lftp $HOST_FTP<<SCRIPTFTP
user $LOGIN_FTP $PASS_FTP
mirror -R $DESTINATION_DAILY"/" $FTP_DAILY"/"
mirror -R $DESTINATION_WEEKLY"/" $FTP_WEEKLY"/"
du -hs /
bye
SCRIPTFTP

# On suprime le répertoire temporaire
if [ -d $TEMPORAIRE ]; then
  rm -Rf $TEMPORAIRE
fi
```

Récupérer, Déposer et donner les droits:
```bash
cd /home/MySQLBackupManager/scripts
wget https://raw.githubusercontent.com/eXorus/eXorus/master/MySQLBackupManager/MySQLBackupManager.sh
chmod 700 MySQLBackupManager.sh
vi MySQLBackupManager.sh
```

On récupère le script on ajout les droits uniquement pour le compte MySQLBackupManager et ensuite on l’édite pour modifier quelques informations :

- user /host / pass : pour se connecter à la machine ici nous sommes en local c’est donc plus simple mais ca fonctionne aussi avec un serveur distant
- LOGIN_FTP / PASS_FTP / HOST_FTP : pour se connecter au serveur distant (FTP) qui va récupérer les sauvegardes

C’est tout.

Pour que le script fonctionne nous devons installer 2 outils :

- aptitude install cron : pour automatiser la sauvegarde
- aptitude install lftp : pour faire du ftp sur le serveur distant

Ensuite nous devons configurer le cron (tâche planifiée qui va s’exécuter tous les jours à 4h du matin)

Crontab:
```bash
crontab -e
		0 4 * * * /home/MySQLBackupManager/scripts/MySQLBackupManager.sh >>/home/MySQLBackupManager/logs/MySQLBackupManager.log
```

Vous pouvez tester le script manuellement la première fois pour vérifier que tout fonctionne correctement :

Tester le script manuellement:
```bash
./MySQLBackupManager
```

Et vérifier avec les logs que tout se passe bien.

## Quoi sauvegarder ?

Voilà nos sauvegardes MySQL sont en place reste juste à indiquer les bases à sauvegarder. Pour cela il suffit de donner au compte mysql-backup-manager les droits suffisants.

Se connecter en root à MySQL:
```bash
mysql -h localhost -u root -p
```

Activer la sauvegarde de la BDD mydatabase:
```sql
GRANT SELECT , INSERT , LOCK TABLES ON `mydatabase` . * TO 'mysql-backup-manager'@'localhost';
FLUSH PRIVILEGES;
EXIT:
```

A reproduire sur toutes les bases à sauvegarder. J’ai mis à jour le post concernant la [création d’un espace web](http://vincent.dauce.fr/administrer-un-serveur-dedie-part-6-espace-web/ "Administrer un serveur dédié – part 6 : Espace Web") pour l’activer par défaut.

## Sauvegarde sur le FTP

Sur le FTP Online vous retrouverez la structure suivante :

- [CT101] 
    - backups_daily 
        - CT101_BACKUP_MYSQL_2014-01-28.tar.gz
        - CT101_BACKUP_MYSQL_2014-01-29.tar.gz
        - …
    - backups_weekly 
        - CT101_BACKUP_MYSQL_S04.tar.gz
        - CT101_BACKUP_MYSQL_S05.tar.gz
        - …
- [CT102] 
    - backups_daily
        - …
    - backups_weekly 
        - …

C’est largement suffisant pour avoir toujours la bonne sauvegarde au bon moment. Attention vous êtes limités à 100 fichiers sur le serveur FTP d’Online.

Dernière chose il faut penser à vérifier le lendemain que votre sauvegarde a bien fonctionner et essayer de la restaurer dans une base de données vide et voir que les données ne sont pas corrompues sinon tout le travail ci-dessous n’aura servi à rien.

« Save today, Tomorrow is too late »