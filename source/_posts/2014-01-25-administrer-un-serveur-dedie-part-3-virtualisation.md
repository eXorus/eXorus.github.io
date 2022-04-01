---
extends: _layouts.post
section: content
title: 'Administrer un serveur dédié - part 3 : Virtualisation'
description: mise en place de la virtualisation avec LXC et configuration du réseau
date: 2014-01-25
categories: [admin]
---

On va passer à la virtualisation, c’est à la mode et c’est super efficace. L’objectif est d’avoir avec un serveur plusieurs serveurs.

Avant j’utilisais la solution OpenVZ mais depuis Debian Wheezy la solution préconisée est LXC même si elle ne semble pas encore très mature elle fonctionne parfaitement.

Les avantages de LXC par rapport à OpenVZ c’est :

- Le noyau qui est « mainline » donc pas besoin de le patcher comme pour OpenVZ, c’est juste un package Debian comme les autres
- C’est forcément le future, bien que pas totalement mature pour le moment il va devenir une référence plus tard
- OpenVZ même si il est gratuit est maintenu par une société Parallels qui vent des solutions de virtualisation

Voici donc l’architecture qu’on va mettre en place :

- On va installer LXC sur notre serveur (host)
- On va créer plusieurs containers (ie serveurs) pour différents usages: 
    - CT101 : Container pour héberger les sites internet en production
    - CT102 : Container pour héberger mes sites personnels (ma vie privée)
    - CT103 : Container pour héberger mes sites en développements

On va devoir configurer un réseau pour que notre host redirige les flux entrants et sortants vers le bon container. Pour cela on va s’appuyer sur des IP Failover que propose Online. Ces IP que tu loues à l’unité vont te permettre de faire croire au monde entier que tu as plusieurs serveurs.

Le host aura 2 IP, une IP publique HN\_IP et une privée 192.168.0.1 pour dialoguer avec les autres containers

Le CT101 aura aussi 2 IP, une IP publique (failover) CT101\_IP\_PUBLIC et une privée 192.168.0.101.

Même chose pour CT102, CT103, ….

## Installation de LXC

C’est très facile il suffit de lancer la commande et on demandera uniquement de valider le répertoire. On va laisser celui par défaut c’est à dire /var/lib/lxc

Installer LXC:
```bash
aptitude install lxc bridge-utils libvirt-bin debootstrap
```

LXC a besoin de [cgroups](http://fr.wikipedia.org/wiki/Cgroups) pour limiter les ressources du serveur sur chaque container donc on va l’activer :

Activer cgroups:
```bash
echo "cgroup /sys/fs/cgroup cgroup defaults 0 0" >> /etc/fstab
mount /sys/fs/cgroup
```

La première ligne permet de l’activer par défaut au démarrage du serveur et la seconde permet de l’activer maintenant.

Enfin il faut vérifier que l’installation de LXC est bonne et vérifier le résultat de la commande checkconfig

Vérifier l'installation de LXC:
```bash
lxc-checkconfig
```

## Modification du réseau

Surement la partie la plus dure car il y a plusieurs méthodes possible.

Après l’installation d’une Debian vous devriez avoir le réseau configuré de cette manière :

Réseau par défaut:
```bash
# The loopback network interface
auto lo
iface lo inet loopback

# The primary network interface
allow-hotplug eth0
iface eth0 inet dhcp
```

A modifier par :

vi /etc/network/interfaces:
```bash
# The loopback network interface
auto lo
iface lo inet loopback

# The primary network interface
auto eth0
iface eth0 inet static
       address xx.xx.xx.xx
       netmask 255.255.255.0
       network xx.xx.xx.0
       broadcast xx.xx.xx.255
       gateway xx.xx.xx.1
       post-up echo 1 > /proc/sys/net/ipv4/ip_forward

# The bridge network interface
auto br0
iface br0 inet static
       address 192.168.0.1
       netmask 255.255.255.0
       bridge_ports none
       bridge_stp off
       bridge_fd 0
       bridge_maxwait 5	
```

Une petite explication s’impose avant on avait un réseau avec 2 interfaces :

- La boucle local pour que le serveur se parle à lui même (localhost)
- L’interface principal avec notre IP publique que nous récupérons directement des serveurs DHCP d’Online.

Nos modifications sont les suivantes :

- On ne touche pas à la boucle local
- L’interface principale passe en mode manuel (dhcp vers static), c’est à dire qu’on va spécifier les informations au lieu de laisser les serveurs DHCP faire le boulot 
    - address : IP publique de votre serveur (disponible sur le panel Online)
    - network : La même que ci-dessus avec un 0 à la fin
    - broadcast : La même que ci-dessus avec un 255 à la fin cette fois
    - gateway : La même que ci-dessus avec un 1 à la fin cette fois
    - ip_forward : permet d’activer l’IP Forwarding ca va nous servir pour nos containers
- On rajoute une interface bridge, en gros un switch sur lequel on va connecter les autres containers. 
    - address : c’est l’IP privée de notre host

[![lxc-veth](http://vincent.dauce.fr/wp-content/uploads/2014/02/lxc-veth-300x167.png)](http://vincent.dauce.fr/wp-content/uploads/2014/02/lxc-veth.png)

Pour le moment nous avons mis en place que l’interface eth0 et br0 sur le host. Nous verrons dans un prochain article l’interface vethCT101 connecté avec l’interface eth0 du container en question.

Un très bon tuto de Albin Kauffman (d’où j’ai repris l’image en l’adaptant à ma terminologie) :

<http://www.linuxembedded.fr/2013/07/configuration-reseau-de-lxc/>

Merci à lui.

## Quelques commandes avec LXC

Lister les containers:
```bash
lxc-list
```

Démarrer le container CT101:
```bash
lxc-start -n CT101 -d
```

Se connecter sur le container CT101:
```bash
lxc-console -n CT101
```

Il y a visiblement plusieurs méthodes (« lxc-halt -n CT101 » ou « lxc-stop -n CT101 ») pour arrêter un container mais elles ne sont pas dès plus douces. La solution la plus propre consiste à se connecter dessus à l’arrêter puis à dire à LXC de la stopper.

Arrêter le container CT101:
```bash
#HOST> lxc-console -n CT101
#CT101> init 0
#HOST> lxc-stop -n CT101
```

Supprimer le container CT101:
```bash
lxc-destroy -n CT101
```
