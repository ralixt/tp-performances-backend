# TP Optimisation des Performances Backend

Compétences mobilisées :
- Optimisations SQL (nombre de requêtes, structuration des tables, ...)
- Mise en place de systèmes de caches variés
- Analyse des performances avec Timing API
- Analyse des performances avec APM NewRelic
- Configuration NGINX
- Optimisation du code PHP

Vous allez travailler sur une application de moteur de recherche d'hôtel (un peu à la AirBnB mais en moins bien). La version dont vous disposez est largement sous-optimisée, pour ne pas dire catastrophique. Tout au long du TP, vous allez travailler pour améliorer les performances de cette dernière.

### Mise en route du TP & Explications
[Voir les instructions](/docs/setup.md)


### ⚠️ ATTENTION ⚠️
Pour chaque question numérotée, vous devrez effectuer un commit pour que je puisse évaluer votre travail.

Vous créerez également un fichier "TP.md" qui vous servira de compte rendu où vous noterez certaines réponses aux questions et que vous versionnerez sur Git.

## Partie 1 : Faire fonctionner l'application
- Décompressez l'archive `src/assets/images.zip` pour avoir un dossier `src/assets/images`.
- Lancez Docker avec la commande `docker compose up`
- Ouvrez PHPMyAdmin sur `http://localhost:8080` et importez le fichier `./database.sql.gz` dans la base de données `tp`.
- Ouvrez l'application sur `http://localhost`

## Partie 2 : Mesurer les performances

1. **Installez l'extension navigateur [*Page load time*](https://chrome.google.com/webstore/detail/page-load-time/fploionmjgeclbkemipmkogoaohcdbig) et affichez-la constamment dans votre navigateur**.

Vous disposez d'une classe utilitaire `src/Common/Timers.php`. Elle permet d'effectuer des mesures de performances de certaines portions de code et de les visualiser dans le navigateur.

2. **Ouvrez le fichier `src/Common/Timers.php` et observez les commentaires de documentation pour comprendre comment fonctionne cette classe. Utilisez cette classe pour mesurer les temps d'exécution de 3 méthodes qui vous semblent particulièrement consommatrices de ressources dans le service `src/Services/Hotel/UnoptimizedHotelService.php`**. **Indiquez dans votre compte rendu le nom de ces méthodes et leur temps d'exécution sur une requête**.

> Pour consulter les temps mesurés, ouvrez vos ChromeDevTools (Chrome, Brave, Edge, ...) et dans l'onglet "Network", filtrez sur le type "Doc". Cliquez sur la ligne
`localhost` (dans la colonne `name` sinon ça ne marchera pas) et dans la fenêtre qui s'affiche, consultez l'onglet "Timing", puis observez la section "Server Timing".

**<div style="text-align:center">COMMIT</div>**

3. **Configurez l'APM NewRelic pour obtenir un monitoring plus précis**.
- Copiez le fichier `./.env-sample` en `/.env`
- Connectez-vous à [NewRelic](https://one.eu.newrelic.com/).
- Dans la barre latérale noire choisissez "*Add data*" puis "*PHP*".
- Cliquez sur "*Begin installation*" puis sélectionnez "*On host standard*"
- Dans la section "*1 Give your application a name*" saisissez `TP performances Backend`
- Dans la section "*2 Install the agent*" choisissez "*apt*" et dans le texte de l'item "*6. Configure your license key and application name*" copiez votre license key `sed -i -e "s/REPLACE_WITH_REAL_KEY/__LICENCE_KEY__` et saisissez-la dans l'entrée `NEW_RELIC_LICENSE_KEY` de votre `.env`.
- Dans la section "*4 Connect with your logs and infrastructure*" dans l'onglet "*Linux*" copiez la valeur de `NEW_RELIC_API_KEY` et `NEW_RELIC_ACCOUNT_ID` et reportez-les dans votre `.env`.
- **Reconstruisez le container Docker `backend` pour qu'il prenne en compte les modifications du fichier `.env` en exécutant la commande `docker compose up --build backend`**.
- Sur la page de NewRelic, Cliquez sur "*See your data*" et actualisez la page `http://localhost` pour déclencher un envoi de données.

## Partie 3 : Optimiser la base de données

![](docs/assets/singleton-db.png)

4. **Commencez par réduire le nombre de connexions `PDO` dans votre application. Deux de vos services les utilisent `UnoptimizedHotelService` et `RoomService`. Créez un Singleton <u>sans utiliser le `SingletonTrait`</u> pour votre base de données et utilisez-le dans vos services. Notez dans votre compte rendu par combien vous avez amélioré le temps de chargement de la page.**
  
**<div style="text-align:center">COMMIT</div>**

5. **Analysez le code du `UnoptimizedHotelService` et repérez certaines portions de code qui pourraient être faite en SQL**. (*entre 5 et 4 méthodes sont concernées*). **Dans votre compte rendu, listez les opérations (pas besoin de mettre le code) pour lesquelles une délégation à la base de données vous semble pertinente**. **Implémentez ces requêtes dans le service**.

**<div style="text-align:center">COMMIT</div>**

6. **Inspectez la structure des tables de la base de données. Outre le fait que les types soient horribles, il n'y a surtout aucun index. Ajoutez des indexes sur les colonnes qui vous semblent pertinentes. Notez dans votre compte rendu les colonnes que vous avez choisies et notez aussi l'amélioration du temps de chargemnt que vous observez.** 

**<div style="text-align:center">COMMIT</div>**

7. **En utilisant NewRelic (instructions ci-dessous), repérez une méthode du service `UnoptimizedHotelService` qui est appelé un grand nombre de fois. Indiquez dans votre compte rendu son nom et son nombre d'appels. Réécrivez là de sorte à diviser par 10 le nombre d'appels à la base de données, vous pourrez utiliser PHP pour associer les données récupérées).**
- Pour analyser une requête, rendez-vous sur [NewRelic](https://one.eu.newrelic.com/)
- Dans la barre latérale noire, cliquez sur "*All entities*" puis dans la liste sur "*TP performances Backend*".
- Dans le menu latéral gauche de la section centrale, cliquez sur "*Transactions*" qui se trouve dans la section "*Monitor*".
- Si vous n'avez aucune transaction, n'hésitez pas à augmenter la plage horaire en haut à droite de l'écran. Il peut y avoir un certain délai entre l'exécution d'une requête et son affichage dans NewRelic, n'hésitez pas à utiliser les `Timers` pour des mesures moins précises mais plus rapides.
- Dans la section "*Top 20 transactions*" cliquez sur "*index.php*". Vous devriez voir dans la section "*Breakdown table*" des informations intéressantes, mais peut-être pas la méthode que vous vouliez.
- Dans votre code PHP, ajoutez la ligne `newrelic_add_custom_tracer(__METHOD__);` au début des méthodes que vous voulez inspecter. Elles devraient être mesurées par la suite.

**<div style="text-align:center">COMMIT</div>**

8. **Si vous avez laissé vos timers de la question 2., vous devriez savoir quelles sont les 3 grosses méthodes qui sont consommatrices de ressources. Implémentez un système de cache pour réduire l'occurrence de ces calculs. Notez dans votre compte rendu l'amélioration du temps de la requête**.
- Installez la librairie [Symfony Cache](https://symfony.com/doc/current/components/cache.html) en suivant les instructions de la page. Pour avoir accès à Composer, utilisez le container Docker `backend` en allant dans l'onglet "*terminal*" de Docker Desktop sur la page du container. *Pro tips : utilisez la commande `bash` pour avoir un meilleur terminal (navigation au clavier, historique de commandes, couleurs, autocompletion, ...)*.
- Créez une classe `App\Common\Cache` en suivant l'approche Singleton et en vous basant sur le schéma UML suivant : <div>![](docs/assets/cache-singleton.png)</div>La classe `TagAwareAdapterInterface` est dans le namespace `Symfony\Component\Cache\Adapter\TagAwareAdapterInterface`.
- Paramétrez un cache basé sur le système de fichiers en suivant les instructions de la page [Filesystem Cache Adapter](https://symfony.com/doc/current/components/cache/adapters/filesystem_adapter.html) et l'utilisation générale de la librairie [Symfony Cache](https://symfony.com/doc/current/components/cache.html). Votre cache devra être stocké dans le dossier `/tmp/app-cache/`.
- Utilisez maintenant votre système de cache dans votre service. **ATTENTION**, vous devez choisir avec soin quelles données seront mises en cache. Toutes ne doivent pas l'être, car elles peuvent être changées en fonction des valeurs saisies dans les filtres. Vous ne pouvez par exemple pas mettre toute la méthode `list()` en cache. Mais si vous avez bien fait votre travail à la question 2, vous savez quelles données mettre en cache. Vous devez également avoir des clés de cache uniques, tirez parti par exemple de l'ID de l'hôtel. 

**<div style="text-align:center">COMMIT</div>**

9. **Modifiez votre système de Cache pour cette fois-ci implémentez un cache Redis. Vous trouverez la documentation nécessaire sur la page [Redis Cache Adapter](https://symfony.com/doc/current/components/cache/adapters/redis_adapter.html). Vous devriez constater une amélioration des performances de chargement comparé à la méthode basée sur les fichiers système**.
- L'hôte de la base Redis n'est pas `localhost` mais `redis` dans notre contexte Docker compose. Votre DSN devrait donc être `redis://redis`.