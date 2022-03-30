---
id: 5
title: 'Administrer un serveur dédié &#8211; part 1 : Introduction'
date: '2014-01-15T00:07:46+01:00'
author: Vincent
layout: post
guid: 'http://vincent.dauce.fr/?p=5'
permalink: /administrer-un-serveur-dedie-part-1-introduction/
categories:
    - admin
---

L’idée de départ pour ce blog était de partager mon expérience pour administrer un serveur dédié, donc commençons par là.

J’ai depuis de nombreuses années maintenant une [dédibox](http://www.online.net/fr) pour héberger mes sites internet que je change régulièrement pour profiter des nouvelles offres, des nouveaux prix, d’un nouveau datacenter, …

Ça faisait 2 ans (2011-2013) que j’avais une [DEDIBOX PRO DELL](http://documentation.online.net/fr/serveur-dedie/offres/serveur-dedibox-pro-dell/start) et j’ai décidé il y a quelques mois de me relancer dans une migration pour profiter de plusieurs points :

- Le nouveau datacenter d’Online : DC2 vers [DC3](http://www.iliad-datacenter.fr/datacenters/dc3) (avec un réseau plus performant)
- Du matériel neuf
- Performance équivalente car derrière l’offre c’est un serveur Dell R210
- Et réinstaller mon système pour passer d’une Debian 6 vers un Debian 7

J’ai donc pris la [DEDIBOX LT 2014](http://www.online.net/fr/serveur-dedie/dedibox-lt2k14) ce qui me fait passer de 50 à 36 euros/mois soit une économie non négligeable de 168 euros par an 🙂

Je perd uniquement 1To dans l’histoire mais comme en 2 ans j’avais pas réussir à remplir le disque c’est pas très grave.

L’objectif de cette série d’articles sera de vous expliquer comment administrer un serveur dédié et mes choix :

- Un socle sous [Debian 7](http://www.debian.org/index.fr.html) avec des machines virtuelles [LXC](https://wiki.debian.org/LXC)
- Des histoires de réseaux pour avoir des IP publique avec les IP Failover d’Online
- Pas de FTP mais du SFTP car c’est quand même mieux à notre époque
- Un serveur Web avec Apache, son copain PHP et son ami MySQL
- Installer un cloud avec [Owncloud](http://owncloud.org/) pour stocker des fichiers en HTTPS
- ….

A bientôt donc pour le début du déploiement …