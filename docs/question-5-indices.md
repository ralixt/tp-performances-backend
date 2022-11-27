5. *Lisez jusqu'au bout avant de commencer !* **Analysez le code du `UnoptimizedHotelService` et repérez certaines
   portions de code qui pourraient être faite en SQL**. (*3 méthodes sont concernées, mais une est différente de celles
   trouvées à la question 2 ! Même si elle est proche*). **Implémentez ces requêtes dans le service et contrôlez que vos
   filtres fonctionnent avec les valeurs de l'image ci-dessous. Vous devriez avoir le même résultat. Dans votre compte
   rendu, saisissez le code SQL initial et son temps d'exécution grâce à vos `Timers`, puis notez vos nouvelles requêtes
   et leur temps d'exécution**.

### ℹ️ Indice : Comment obtenir plusieurs valeurs des tables `meta` dans la même requête ?

Vous pouvez faire des `INNER JOIN` avec alias. Par exemple :

```sql
SELECT 
    user.ID            AS id,
    user.display_name  AS name,
    latData.meta_value AS lat,
    lngData.meta_value AS lng

FROM 
    wp_users AS USER

    -- geo lat
    INNER JOIN tp.wp_usermeta AS latData 
        ON latData.user_id = user.ID AND latData.meta_key = 'geo_lat'
        
    -- geo lng
    INNER JOIN tp.wp_usermeta AS lngData 
        ON lngData.user_id = user.ID AND lngData.meta_key = 'geo_lng'
```

### ℹ️ Indice : Comment gérer l'écriture des `WHERE` en fonction des conditions de `$args` ?

Vous pouvez écrire votre requête SQL dans une variable `$query` jusqu'aux instructions `WHERE`. Créez ensuite un tableau `$whereClauses = []`, qui contiendra vos conditions. Ensuite, dans vos blocs `if`, si une entrée de `$arg` vous indique qu'il faut ajouter une condition, ajoutez votre instruction SQL dans votre tableau. Ensuite, si votre tableau a au moins une entrée, faites `query .= implode(' AND ', $whereClauses );`.

```php
// Requête de base jusqu'aux WHERE
$query = "SELECT * FROM tp.wp_posts AS post";

$whereClauses = [];

// Si l'utilisateur filtre sur cette donnée, alors on ajoute une condition SQL
if ( isset( $args['myFilter'] ) )
  $whereClauses[] = 'myFilter >= :myFilter';

// Si on a des clauses WHERE, alors on les ajoute à la requête
if ( count($whereClauses > 0) )
  $query .= " WHERE " . implode( ' AND ', $whereClauses );

// On récupère le PDOStatement
$stmt = $pdo->prepare( $query );

// On associe les placeholder aux valeurs de $args,
// on doit le faire ici, car nous n'avions pas accès au $stmt avant
if ( isset( $args['myFilter'] ) )
  $stmt->bindParam('myFilter', $args['myFilter'], PDO::PARAM_INT);

$stmt->execute();
```

### Contrôle des résultats de filtre
![](assets/controle-resultats-q5.png)
