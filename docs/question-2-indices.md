2. **Ouvrez le fichier `src/Common/Timers.php` et observez les commentaires de documentation pour comprendre comment fonctionne cette classe. Utilisez cette classe pour mesurer les temps d'exécution de 3 méthodes qui vous semblent particulièrement consommatrices de ressources dans le service `src/Services/Hotel/UnoptimizedHotelService.php`**. **Indiquez dans votre compte rendu le nom de ces méthodes et leur temps d'exécution sur une requête**.

### ℹ️ Indice : Comment bien choisir les fonctions à analyser
Il est inutile d'analyser de fonctions de haut niveau, visez des fonctions plus imbriquées. En effet, si vous mesurez des fonctions de haut niveau, elles paraîtront plus longues, car elles incluront leurs fonctions sous-jacentes. Vous serez donc biaisé en pensant que ce sont les fonctions de haut niveau qui sont à optimiser alors que ce sont les fonctions qu'elles appellent.

### Consulter les temps mesurés (Server Timing API)
- Ouvrez vos ChromeDevTools (Chrome, Brave, Edge, ...)
- Allez dans l'onglet"Network"
- Cliquez sur le type "*Doc*" 
- Cliquez sur la ligne `localhost` (cliquez dans la colonne `name` sinon ça ne marchera pas)
- Dans la fenêtre qui s'affiche, consultez l'onglet "*Timing*", puis observez la section "*Server Timing*".
