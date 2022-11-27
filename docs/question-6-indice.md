6. En exploitant le code SQL et PHP que vous avez écrit à la question 4, **créez un nouveau
   service `OneRequestHotelService` qui sera en mesure de requêter les hôtels avec les filtres en <u>1 seule requête
   SQL</u>**.

### ℹ️ Indice : Utiliser des sous-requêtes dans les `INNER JOIN`

En SQL, dans ce contexte précis (par pitié ne le faites pas en production), vous pouvez `INNER JOIN` sur une
sous-requête. L'avantage, c'est que vous aurez des noms de colonnes plus propres sur lesquels vous baser et déjà `CAST`.
Exemple où ici je récupère le l'hôtel (ID et nom) ainsi que l'ID de la chambre la moins chère et son prix :

```sql
SELECT
   user.ID AS hotelId,
   user.display_name AS hotelName,
   postData.ID AS cheapestRoomId,
   postData.price AS price

FROM
    wp_users AS USER
    
    -- room
    INNER JOIN (
      SELECT
         post.ID,
         post.post_author,
         MIN(CAST(priceData.meta_value AS UNSIGNED)) AS price
      FROM
         tp.wp_posts AS post
            -- price
            INNER JOIN tp.wp_postmeta AS priceData ON post.ID = priceData.post_id
            AND priceData.meta_key = 'price'
      WHERE
         post.post_type = 'room'
      GROUP BY
         post.post_author
   ) AS postData ON user.ID = postData.post_author

WHERE
    -- On peut déjà filtrer vu que valeur est déjà castée en numérique
    price < 100

LIMIT 3;
```
Ce qui me retourne :

|  `hotelId`  | `hotelName`       |  `cheapestRoomId`  |  `Price` |
|:-----------:|-------------------|:------------------:|---------:|
|      1      | Roy Gie           |         1          |       57 | 
|      2      | Carpentier SCOP   |        124         |       88 |
|      3      | Schmitt et Dupont |        237         |       99 |

### ℹ️ Indice : Calculer une distance entre deux points GPS en SQL

Vous pouvez utiliser la formule ci-dessous qui va calculer la distance en Kilomètres séparant deux coordonnées GPS. Attention ici à ne pas utiliser `CAST()`.
```sql
SELECT 
    111.111
        * DEGREES(ACOS(LEAST(1.0, COS(RADIANS( latData.meta_value ))
        * COS(RADIANS( :userLat ))
        * COS(RADIANS( lngData.meta_value - :userLng ))
        + SIN(RADIANS( latData.meta_value ))
        * SIN(RADIANS( :userLat ))))) AS distanceKM
```
Exemple dans une requête plus concrète
```sql
SELECT
    user.ID AS id,
    display_name AS name,
    latData.meta_value AS lat,
    lngData.meta_value AS lng,
    111.111
        * DEGREES(ACOS(LEAST(1.0, COS(RADIANS( latData.meta_value ))
        * COS(RADIANS( 46.9903264 ))
        * COS(RADIANS( lngData.meta_value - 3.163412 ))
        + SIN(RADIANS( latData.meta_value ))
        * SIN(RADIANS( 46.9903264 ))))) AS distanceKM

FROM
    wp_users AS USER
	
    -- geo coords
    INNER JOIN tp.wp_usermeta AS latData ON latData.user_id = user.ID AND latData.meta_key = 'geo_lat'
    INNER JOIN tp.wp_usermeta AS lngData ON lngData.user_id = user.ID AND lngData.meta_key = 'geo_lng'

-- Condition de distance maximale
HAVING 
    distanceKM < 50

ORDER BY 
    distanceKM ASC;
```