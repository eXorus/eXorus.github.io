---
extends: _layouts.post
section: content
title: 'Administrer un serveur dédié - part 4 : Premier container'
date: 2014-02-01
description: 
categories: [admin]
---

Après déjà 3 articles, nous arrivons enfin à notre premier container qui va nous permettre enfin d’héberger nos sites et rendre service, car c’est bien là le but d’un serveur rendre services aux autres.

## Créer le container

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
lxc-create -n CT101 -t debian
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Créer un container</span> </div> </div>Et voilà c’est fait, vous avez une Debian 7.4 à jour 🙂 le mot de passe est root par défaut on le changera rapidement.

Le nom de votre container c’est CT101 qu’on devra utiliser dans presque toutes les commandes de LXC.

———————————————————————————

25/05/2014 : Modification du masque réseau (/24 au lieu de rien avant)

———————————————————————————

Après l’installation le container est à l’arrêt donc démarrons le :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
ln -s /var/lib/lxc/CT101/config /etc/lxc/auto/CT101
lxc-start -n CT101 -d
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Démarrer un container</span> </div> </div>La première permet de démarrer automatiquement le container à chaque fois que le host démarre. Et la seconde est pour le démarrer maintenant car on veut faire mumuse avec 🙂

Nous allons nous connecter dessus pour commencer à l’utiliser

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
lxc-console -n CT101
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Se connecter à la console du container CT101</span> </div> </div>Quand on tape cette commande la phrase « Type &lt;Ctrl+a q&gt; to exit the console, &lt;Ctrl+a Ctrl+a&gt; to enter Ctrl+a itself » s’affiche.

Donc avec notre clavier on fait Ctrl+a puis Entrée pour voir apparaître le prompt du login et pouvoir saisir root (le mot de passe est par défaut root).

A ce moment là vous êtes connecté à votre container, vous pouvez le voir car le prompt a changé « root@CT101:~# »

Pour quitter le container et revenir au prompt du host il suffit de faire exit. On revient à ce moment là au prompt du login et on a plus qu’à saisir Ctrl+a puis q.

Le prompt change de nouveau pour nous indiquer que nous sommes sur le host.

La suite est déjà expliqué dans mon article [Administrer un serveur dédié – part 2 : Le socle](http://vincent.dauce.fr/administrer-un-serveur-dedie-part-2-le-socle/ "Administrer un serveur dédié – part 2 : Le socle") (Section : Premières actions et Sécuriser le service SSH)

## Configurer le réseau sur le host

On va commander un IP Failover sur la console Online, sur la page [Failover](https://console.online.net/fr/server/failover), cliquer sur le bouton « Commander des adresses IP », sélectionner celle que vous voulez en fonction de la beauté des chiffres 🙂 et valider avec le bouton « Commander d’IP Failover »

Après la commande on retour sur la page [Failover](https://console.online.net/fr/server/failover) pour cette fois assigner l’IP à un serveur. Les IP disponibles sont en vert dans la liste des IP Failovers. Il suffit de glisser-déposer l’IP qu’on vient d’acheter sur le serveur que l’on souhaite. Et cliquer sur le bouton « Mise à jour ». Un récapitulatif des modifications s’affichent puis cliquer sur le bouton « Mise à jour ».

Ma nouvelle adresse IP Failover est : CT101\_IP\_PUBLIC

Dans la configuration réseau que nous allons faire nous n’avons pas besoin d’assigner une adresse mac à notre IP car l’IP bien que publique n’est pas connecté en direct mais passe par du NAT.

On va configurer le réseau à partir du host donc déconnectez vous du container pour suivre les actions ci-dessous.

On va commencer par déclarer notre IP en tant qu’interface réseau virtuelle sur notre host. Ces quelques lignes sont à rajouter à la fin du fichier après le bridge que nous avons créé dans l’article [Administrer un serveur dédié – part 3 : Virtualisation](http://vincent.dauce.fr/administrer-un-serveur-dedie-part-3-virtualisation/ "Administrer un serveur dédié – part 3 : Virtualisation")

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
# Failover CT101 : CT101_IP_PUBLIC
auto eth0:x
iface eth0:x inet static
        address CT101_IP_PUBLIC
        netmask 255.255.255.255
```
```

<div class="code-embed-infos"> <span class="code-embed-name">vi /etc/network/interfaces</span> </div> </div>Il faut incrémenter le numéro x à chaque nouvelle IP. Donc eth0 est mon interface principale avec mon l’IP publique de mon serveur, eth0:0 sera l’interface virtuelle de ma première IP Failover CT101\_IP\_PUBLIC, eth0:1 sera l’interface virtuelle de ma seconde IP Failover CT102\_IP\_PUBLIC, eth0:2 sera l’interface virtuelle de ma troisième IP Failover CT103\_IP\_PUBLIC, … enfin on est limité à 5 par serveur chez Online.

Le netmask est à 255.255.255.255 comme l’indique Online dans sa [documentation sur l’IP Failover](http://documentation.online.net/fr/serveur-dedie/reseau/ip-failover).

Malheureusement cette configuration ne sera prise en compte qu’au prochain reboot, donc pour activer notre nouvelle interface maintenant tout de suite on exécute la commande suivante :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
ifconfig eth0:x CT101_IP_PUBLIC netmask 255.255.255.255
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Activer l'IP failover en live</span> </div> </div>## Configurer le réseau sur le container

On reste sur le host pour configurer le réseau du container c’est plus simple car on est déjà dessus. Mais c’est possible de le faire aussi en étant connecté sur le container.

Il s’agit donc ici de définir un réseau local entre notre container et le bridge.

Le fichier existe déjà avec l’interface local et l’interface eth0 que l’on va remplacer.

Nos modifications sont les suivantes :

- On ne touche pas à la boucle local
- L’interface principale passe en mode manuel (dhcp vers static), c’est à dire qu’on va spécifier les informations au lieu de laisser les serveurs DHCP faire le boulot 
    - address : IP privée de notre container (j’utilise toujours 192.168.0.xxx avec xxx le même numéro que le nom de mon container donc CT101 est sur l’IP privée 192.168.0.101 c’est plus facile à retenir). Il faut rajouter le masque réseau /24 indiquant que la boucle local est sur le dernier chiffre de l’IP (ou alors /16 pour indiquer que c’est les 2 derniers chiffres à prendre en compte)
    - broadcast : La même que ci-dessus avec un 255 à la fin
    - gateway : C’est l’IP privée de mon bridge que nous avons définit dans l’article précédent (address sur l’interface br0)

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
auto lo
iface lo inet loopback

auto eth0
iface eth0 inet static
        address 192.168.0.101
        netmask 255.255.255.0
        broadcast 192.168.0.255
        gateway 192.168.0.1
```
```

<div class="code-embed-infos"> <span class="code-embed-name">vi /var/lib/lxc/CT101/rootfs/etc/network/interfaces </span> </div> </div>Il y a aussi le fichier de configuration LXC pour le container en question à modifier. Le fichier permet de configurer des tas de choses mais nous allons nous concentrer uniquement sur le réseau. Normalement les directives ci-dessous n’existent pas donc ajouter les à la fin du fichier :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
# Network
lxc.network.type = veth
lxc.network.flags = up
lxc.network.link = br0
lxc.network.name = eth0
lxc.network.ipv4 = 192.168.0.101/24
lxc.network.veth.pair = vethCT101
```
```

<div class="code-embed-infos"> <span class="code-embed-name">vi /var/lib/lxc/CT101/config</span> </div> </div>On indique à LXC comment traiter le réseau de notre container :

- lxc.network.type : lxc permet d’avoir différent types de réseaux (phys, vlan, …) nous on a choisir veth c’est à dire l’utilisation d’un bridge que nous lui indiquerons dans la directive lxc.network.link
- lxc.network.flags : On le met à « up » pour activer le réseau
- lxc.network.link : on vient de dire à LXC de traiter le réseau avec un bridge on lui indique ici le nom de l’interface que nous avons mis dans /etc/network/interface du host
- lxc.network.name : on utilise l’interface eth0 de notre container, celle que nous venons de configurer ci-dessus avec l’IP 192.168.0.101
- lxc.network.ipv4 : cette fois c’est notre IP privée
- lxc.network.veth.pair : c’est le nom que nous donnons au lien, cf l’image de notre réseau dans l’article précédent

## Configurer le firewall

Maintenant que notre réseau est configuré nous allons devoir faire quelques mises à jours sur notre firewall :

- NAT : c’est à dire transformer tous les flux qui sortent de notre container avec l’IP 192.168.0.101 par CT101\_IP\_PUBLIC et inversement
- Autoriser les flux en fonction des services que l’on proposera dans notre container

Modifier le firewall que nous avons mis en place dans [Administrer un serveur dédié – part 2 : Le socle](http://vincent.dauce.fr/administrer-un-serveur-dedie-part-2-le-socle/ "Administrer un serveur dédié – part 2 : Le socle") (Mise en place d’un firewall)

Pour rajouter les 2 lignes suivantes : déclaration de l’IP publique et privée de notre container CT101.

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
HN_IP="xx.xx.xx.xx"

CT101_IP_PRIVATE="192.168.0.101"
CT101_IP_PUBLIC="CT101_IP_PUBLIC"

##########################
# Start the Firewall rules
##########################
```
```

<div class="code-embed-infos"> <span class="code-embed-name">vi /etc/init.d/firewall.sh</span> </div> </div>Et et un peu plus loin entre le bloc « Autoriser HTTP et HTTPS » et la fin du bloc fw\_start() :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
# Autoriser HTTP et HTTPS
iptables -t filter -A OUTPUT -p tcp --dport $HTTP_PORT -j ACCEPT
iptables -t filter -A INPUT -p tcp --dport $HTTP_PORT -j ACCEPT
iptables -t filter -A OUTPUT -p tcp --dport $HTTPS_PORT -j ACCEPT
iptables -t filter -A INPUT -p tcp --dport $HTTPS_PORT -j ACCEPT

# CT101 : Configuration NAT
iptables -A FORWARD -s $CT101_IP_PRIVATE -j ACCEPT
iptables -A FORWARD -d $CT101_IP_PRIVATE -j ACCEPT
iptables -t nat -A POSTROUTING -s $CT101_IP_PRIVATE -j SNAT --to $CT101_IP_PUBLIC
# CT101 : Autoriser HTTP
iptables -t nat -I PREROUTING -p tcp -d $CT101_IP_PUBLIC --dport $HTTP_PORT -j DNAT --to $CT101_IP_PRIVATE
iptables -I FORWARD -p tcp -d $CT101_IP_PRIVATE --dport $HTTP_PORT
# CT101 : Autoriser SSH
iptables -t nat -I PREROUTING -p tcp -d $CT101_IP_PUBLIC --dport $SSH_PORT -j DNAT --to $CT101_IP_PRIVATE
iptables -I FORWARD -p tcp -d $CT101_IP_PRIVATE --dport $SSH_PORT

}
fw_stop(){
```
```

<div class="code-embed-infos"> <span class="code-embed-name">vi /etc/init.d/firewall.sh</span> </div> </div>Il reste à relancer le firewall pour prise en compte des nouvelles règles :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
firewall.sh restart
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Relancer le firewall</span> </div> </div>Et relancer le container pour prendre en compte les changements réseaux et de configuration LXC :

<div class="code-embed-wrapper"> ```
<pre class="language-bash code-embed-pre" data-line-offset="0" data-start="1">```bash
lxc-stop -n CT101
lxc-list
lxc-start -n CT101 -d
```
```

<div class="code-embed-infos"> <span class="code-embed-name">Relancer le container</span> </div> </div>Et voilà, vous avez un container qui a accès à internet et qui peut héberger un serveur web par exemple 🙂

Ha on me dit que c’est dans le prochain article donc je vous laisse.

Vous pouvez répéter ce tuto autant de fois que vous voulez de container.

## <span style="color: #666666; font-family: Roboto, 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 15px; line-height: 25px;"> </span>