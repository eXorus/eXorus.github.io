---
id: 10
title: 'Administrer un serveur dédié – part 2 : Le socle'
date: '2014-01-20T18:18:02+01:00'
author: Vincent
layout: post
guid: 'http://vincent.dauce.fr/?p=10'
permalink: /administrer-un-serveur-dedie-part-2-le-socle/
categories:
    - admin
---

Après cette petite introduction, nous allons réellement commencer l’installation.

## <span style="line-height: 1.5;">Mes choix</span>

On va dans cet article voir comment utiliser l’interface Online qui est très intuitive donc vous devriez pas avoir besoin de moi. Puis configurer la Debian pour la sécuriser un peu avant de l’utiliser plus que ça.

Pourquoi une Debian ? car j’ai toujours aimé cette distribution qui est très stable, qui est la maman de beaucoup d’autres distribution avec une philosophie très appréciable ( non commerciale, collaborative et qui sort pas tous les 6 mois mais quand elle est prête). Donc un très bon choix pour un serveur qui se doit d’être sécurisé avec des mises à jours de sécurité très régulières.

———————————————————————————

27/04/2014 : Ajout de l’expiration du mot de passe au bout de 100 jours pour plus de sécurité (cmd chage)

22/06/2014 : Coquille sur update-rc.d qui prend uniquement le nom du fichier en paramètre et pas le chemin qui est toujours /etc/init.d

———————————————————————————

## Installation du serveur via la panel Online

Tout d’abord il faut se connecter à l’interface qui est d’ailleurs super bien faite contrairement à celle d’OVH je trouve.

<https://console.online.net>

Dans le menu **Serveur &gt; Liste de vos serveurs** choisir celui que vous venez de commander et cliquer dessus.

Cliquer sur le bouton **Installer** à droite dans le menu.

Choisir **Distributions serveur &gt; Debian 7 64 bits**

Laisser le **partitionnement par défaut**, nous serons donc en RAID1 soit une bonne sécurité car 2 disques durs et les données sont en double. Si un disque dur lâche, Online se charge de le changer et comme ça aucune perte de données et encore moins d’interruption de service.

On conserve le nom de la machine Online, on **choisit un mot de passe pour le root** et un mot de passe pour un compte utilisateur. Pas d’inquiétude on le changera tout à l’heure.

Puis valider les dernières étapes et **attendre 1h que l’installation se termine** correctement avant de vous connecter dessus pour la première fois sur votre nouveau serveur dédié.

##  Première actions

Comme je le disais précédemment on va commencer par changer les mots de passe car on ne sait jamais le mot de passe qu’on a mit la première fois c’était sur internet donc c’est pas sûr. Commande à utiliser pour le compte root et le compte utilisateur créé précédemment. On rajoute une expiration du mot de passe à 100 jours pour améliorer la sécurité.

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
passwd root
chage -M 100 root
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Mettre à jour les mots de passe</span> </div> </div>Ensuite on passe à la mise à jour de notre Debian, comme il y en a régulièrement il faut le faire. On verra plus tard comment l’automatiser. Par la même occasion vous remarquez qu’on utilise toujours « aptitude » au lieu « apt-get » pour la gestion des paquets sous Debian car c’est celui qui est [recommandé](http://www.debian.org/doc/manuals/debian-faq/ch-pkgtools.fr.html#s-aptitude). (Si aptitude n’est pas installé il suffit de faire « apt-get install aptitude »)

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
aptitude update
aptitude upgrade
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Première mise à jour</span> </div> </div>J’ai pris l’habitude de supprimer les services inutiles pour mes besoins car si on a moins de service alors on a plus de performance et moins de faille de sécurité.

Pour le moment la liste se compose de :

- telnet (protocole de communication assez ancien que je n’utilise pas)
- exim4 (agent de transport de courrier mais pas necessaire dans mon cas)

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
aptitude remove telnet
service exim4 stop
update-rc.d -f exim4 remove
rm /etc/init.d/exim4
aptitude purge exim4
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Supprimer les services inutiles</span> </div> </div>## Sécuriser le service SSH

Le service SSH est la première cible des attaques et le seul accès à votre serveur donc il faut le sécuriser un maximum pour éviter de se faire attaquer.

Si vous me croyez pas il suffit d’aller voir les logs après l’installation de votre serveur dans /var/log/auth.log.

On change le port par défaut car la plupart des robots utilise 22 pour essayer des milliards de login et password. On interdit au root de se connecter en SSH, ca veut dire qu’on sera toujours obligé de se connecter avec un compte utilisateur puis le compte root et on autorise au cas par cas les comptes. Ça permet d’éviter les robots qui utilisent root comme login. Les 2 dernières directives sont normalement déjà en place sur Debian 7.

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
Port xxxx (le modifier pour ne pas utiliser le port par défaut 22)
PermitRootLogin no
AllowUsers monlogin
PermitEmptyPasswords no
Protocol 2
```
```

<div class="code-embed-infos"> <span class="code-embed-name">vi /etc/ssh/sshd\_config</span> </div> </div>Puis on relance le service pour prendre en compte la nouvelle configuration. Comme nous n’avons pas encore mis en place un firewall vous devriez pouvoir encore vous connecter dessus 🙂

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
service ssh reload
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Relancer le service SSH</span> </div> </div>## Mise en place d’un firewall

Un firewall permet de contrôler les flux réseaux entrants et sortants de votre serveur. Il est primordial d’en avoir un et qu’il soit le plus restrictif possible, si votre machine héberge uniquement un serveur web alors on autorise uniquement le HTTP, ….

La mise en place est assez simple on va écrire un script qui sera exécuté à chaque démarrage du serveur et qui va inscrire les règles que l’on souhaite grâce à un logiciel bien connu dans le monde linux: iptables

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
#!/bin/sh
#
# Simple Firewall configuration.
#
# Author: eXorus
#
# chkconfig: 2345 9 91
# description: Activates/Deactivates the firewall at boot time
#
### BEGIN INIT INFO
# Provides:          firewall.sh
# Required-Start:    $syslog $network
# Required-Stop:     $syslog $network
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: Start firewall daemon at boot time
# Description:       Custom Firewall script
### END INIT INFO

##########################
# Configuration
##########################

SSH_PORT="xxxx"
FTP_PORT="21"
DNS_PORT="53"
MAIL_PORT="25"
NTP_PORT="123"
HTTP_PORT="80"
HTTPS_PORT="443"

HN_IP="xx.xx.xx.xx"


##########################
# Start the Firewall rules
##########################

fw_start(){
        # Ne pas casser les connexions etablies
        iptables -A INPUT -m state --state RELATED,ESTABLISHED -j ACCEPT
        iptables -A OUTPUT -m state --state RELATED,ESTABLISHED -j ACCEPT

        # Autoriser loopback
        iptables -t filter      -A INPUT        -i lo -s 127.0.0.0/8 -d 127.0.0.0/8 -j ACCEPT
        iptables -t filter      -A OUTPUT       -o lo -s 127.0.0.0/8 -d 127.0.0.0/8 -j ACCEPT

        # Autoriser le ping
        iptables -t filter      -A INPUT        -p icmp -j ACCEPT
        iptables -t filter      -A OUTPUT       -p icmp -j ACCEPT

        # Autoriser SSH
        iptables -t filter      -A INPUT        -p tcp --dport $SSH_PORT -j ACCEPT
        iptables -t filter      -A OUTPUT       -p tcp --dport $SSH_PORT -j ACCEPT

        # Autoriser NTP
        iptables -t filter      -A OUTPUT       -p udp --dport $NTP_PORT -j ACCEPT

        # Autoriser DNS
        iptables -t filter -A OUTPUT -p tcp --dport $DNS_PORT -j ACCEPT
        iptables -t filter -A OUTPUT -p udp --dport $DNS_PORT -j ACCEPT
        iptables -t filter -A INPUT -p tcp --dport $DNS_PORT -j ACCEPT
        iptables -t filter -A INPUT -p udp --dport $DNS_PORT -j ACCEPT

        # Autoriser HTTP et HTTPS
        iptables -t filter -A OUTPUT -p tcp --dport $HTTP_PORT -j ACCEPT
        iptables -t filter -A INPUT -p tcp --dport $HTTP_PORT -j ACCEPT
        iptables -t filter -A OUTPUT -p tcp --dport $HTTPS_PORT -j ACCEPT
        iptables -t filter -A INPUT -p tcp --dport $HTTPS_PORT -j ACCEPT

}

fw_stop(){
        # Vidage des tables et des regles personnelles
        iptables -t filter      -F
        iptables -t nat         -F
        iptables -t mangle      -F
        iptables -t filter      -X

        # Interdire toutes connexions entrantes et sortantes
        iptables -t filter      -P INPUT DROP
        iptables -t filter      -P FORWARD DROP
        iptables -t filter      -P OUTPUT DROP
}
fw_clear(){
        # Vidage des tables et des regles personnelles
        iptables -t filter      -F
        iptables -t nat         -F
        iptables -t mangle      -F
        iptables -t filter      -X

        # Accepter toutes connexions entrantes et sortantes
        iptables -t filter      -P INPUT ACCEPT
        iptables -t filter      -P FORWARD ACCEPT
        iptables -t filter      -P OUTPUT ACCEPT
}

fw_stop_ip6(){
        # Vidage des tables et des regles personnelles
        ip6tables -t filter     -F
        ip6tables -t mangle     -F
        ip6tables -t filter     -X

                # Interdire toutes connexions entrantes et sortantes
        ip6tables -t filter     -P INPUT DROP
        ip6tables -t filter     -P FORWARD DROP
        ip6tables -t filter     -P OUTPUT DROP
}

fw_clear_ip6(){
        # Vidage des tables et des regles personnelles
        ip6tables -t filter      -F
        ip6tables -t mangle      -F
        ip6tables -t filter      -X

        # Accepter toutes connexions entrantes et sortantes
        ip6tables -t filter      -P INPUT ACCEPT
        ip6tables -t filter      -P FORWARD ACCEPT
        ip6tables -t filter      -P OUTPUT ACCEPT
}

case "$1" in
        start|restart)
                echo -n "Starting firewall.."
                fw_stop_ip6
                fw_stop
                fw_start
                echo "done."
                ;;
        stop)
                echo -n "Stopping firewall.."
                fw_stop_ip6
                fw_stop
                echo "done."
                ;;
        clear)
                echo -n "Clearing firewall rules.."
                fw_clear_ip6
                fw_clear
                echo "done."
                ;;
        *)
                echo "Usage: $0 {start|stop|restart|clear}"
                exit 1
                ;;
esac

exit 0
```
```

<div class="code-embed-infos"> [vGy1V14y](http://pastebin.com/vGy1V14y "Afficher vGy1V14y") [affichage brut](http://pastebin.com/raw.php?i=vGy1V14y "Back to vGy1V14y") </div> </div>Il suffit juste de mettre votre IP au niveau de la variable HN\_IP et de bien préciser le port SSH (le même que celui au dessus) dans la variable SSH\_PORT.

Pour tester le script il suffit de le lancer une première fois et vérifier que tout fonctionne. Si c’est pas le cas redémarrer le serveur pour y avoir accès de nouveau.

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
firewall.sh start
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Relancer le firewall</span> </div> </div>Après l’avoir validé, il faut que le script se lance à chaque fois que le serveur démarre :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
chmod +x /etc/init.d/firewall.sh
chown root:root /etc/init.d/firewall.sh
update-rc.d firewall.sh defaults
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Relancer le firewall automatiquement</span> </div> </div>