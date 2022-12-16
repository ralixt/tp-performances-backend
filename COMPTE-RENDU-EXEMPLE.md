Vous pouvez utiliser ce [GSheets](https://docs.google.com/spreadsheets/d/13Hw27U3CsoWGKJ-qDAunW9Kcmqe9ng8FROmZaLROU5c/copy?usp=sharing) pour suivre l'évolution de l'amélioration de vos performances au cours du TP 

## Question 2 : Utilisation Server Timing API

**Temps de chargement initial de la page** : 30.3 s

**Choix des méthodes à analyser** :

- `getMeta`: 4.02 s
- `getReviews`: 8.82 s
- `getMetas`: 4.27 s
- `getCheapestRoom` : 15.15 s



## Question 3 : Réduction du nombre de connexions PDO

**Temps de chargement de la page** : 29.31 s

**Temps consommé par `getDB()`** 

- **Avant** : 1.32 s

- **Après** : 4.16 ms


## Question 4 : Délégation des opérations de filtrage à la base de données

**Temps de chargement globaux** 

- **Avant** : 28.8 s

- **Après** : 22.3 s


#### Amélioration de la méthode `getMeta` et donc de la méthode `getMetas` :

- **Avant** : 3.18 s

```sql
SELECT * FROM wp_usermeta;
```

- **Après** : 1.52 s

```sql
SELECT meta_value FROM wp_usermeta WHERE user_id=:UserId AND meta_key=:metaKey ;
```



#### Amélioration de la méthode `getReviews` :

- **Avant** : 8.45 s

```sql
SELECT * FROM wp_posts, wp_postmeta WHERE wp_posts.post_author = :hotelId AND wp_posts.ID = wp_postmeta.post_id AND meta_key = 'rating' AND post_type = 'review';
```

- **Après** : 6.53 s

```sql
SELECT ROUND(AVG(meta_value)) AS rating, COUNT(meta_value) AS count FROM wp_posts, wp_postmeta WHERE wp_posts.post_author = :hotelId AND wp_posts.ID = wp_postmeta.post_id AND meta_key = 'rating' AND post_type = 'review';
```



#### Amélioration de la méthode `getCheapestRoom` :

- **Avant** : 15.66 s

```sql
SELECT * FROM wp_posts WHERE post_author = :hotelId AND post_type = 'room'
```

- **Après** : 12.43 s

```sql
SELECT

 -- posts.ID AS postID,
 posts.post_author AS postAuthor,
 -- USER.display_name AS hotelName,
 posts.post_title AS roomName,
 CAST(surface.meta_value AS UNSIGNED) AS surface,
 MIN(CAST(price.meta_value AS UNSIGNED)) AS price,
 CAST(bedrooms_count.meta_value AS UNSIGNED) AS bedrooms,
 CAST(bathrooms_count.meta_value AS UNSIGNED) AS bathrooms,
 roomType.meta_value AS roomType,
 coverImage.meta_value AS coverImage,
 

--  latData.meta_value AS lat,
--  lngData.meta_value AS lng
-- 
--  , 111.111
--      * DEGREES(ACOS(LEAST(1.0, COS(RADIANS( latData.meta_value ))
--      * COS(RADIANS( :userLat ))
--      * COS(RADIANS( lngData.meta_value - :userLng ))
--      + SIN(RADIANS( latData.meta_value ))
--      * SIN(RADIANS( :userLat ))))) AS distanceKM
 

FROM
 wp_posts AS posts


INNER JOIN wp_users AS USER
ON
    USER.ID = posts.post_author

INNER JOIN wp_postmeta AS surface
ON
     surface.post_id = posts.ID AND surface.meta_key = 'surface'

INNER JOIN wp_postmeta AS price
ON
     price.post_id = posts.ID AND price.meta_key = 'price'

INNER JOIN wp_postmeta AS bedrooms_count
ON
     bedrooms_count.post_id = posts.ID AND bedrooms_count.meta_key = 'bedrooms_count'

INNER JOIN wp_postmeta AS bathrooms_count
ON
     bathrooms_count.post_id = posts.ID AND bathrooms_count.meta_key = 'bathrooms_count'

INNER JOIN wp_postmeta AS roomType
ON
     roomType.post_id = posts.ID AND roomType.meta_key = 'type'

INNER JOIN wp_postmeta AS coverImage
ON
     coverImage.post_id = posts.ID AND coverImage.meta_key = 'coverImage'


--     
-- INNER JOIN wp_usermeta AS latData 
-- ON 
--     latData.user_id = user.ID AND latData.meta_key = 'geo_lat'
-- 
-- INNER JOIN wp_usermeta AS lngData 
-- ON 
--     lngData.user_id = user.ID AND lngData.meta_key = 'geo_lng'
-- 


WHERE
    posts.post_author = :hotelId AND post_type = 'room'
    AND surface.meta_value >= :surfaceMin AND surface.meta_value <= :surfaceMax
    AND price.meta_value >= :priceMin AND price.meta_value <= :priceMax
    AND bedrooms_count.meta_value >= :bedrooms
    AND bathrooms_count.meta_value >= :bathrooms
    AND roomType.meta_value IN ( :type )


GROUP BY posts.post_author

-- HAVING distanceKM <= :distance;
```



## Question 5 : Réduction du nombre de requêtes SQL pour `getMetas`

|                              | **Avant** | **Après** |
|------------------------------|-----------|-----------|
| Nombre d'appels de `getDB()` | 2201      | 601       |
 | Temps de `getMetas`          | 1.67 s    | 193.22 ms |

## Question 6 : Création d'un service basé sur une seule requête SQL

|                              | **Avant** | **Après** |
|------------------------------|-----------|-----------|
| Nombre d'appels de `getDB()` | 601       | NOMBRE    |
| Temps de chargement global   | 22.7 s    | 5.27 s    |

**Requête SQL**

```SQL
SELECT
    USER.ID AS hotelId,
    USER.user_email AS hotelEmail,
    USER.display_name AS hotelName,

    address_1.meta_value AS address_1,
    address_2.meta_value AS address_2,
    address_city.meta_value AS address_city,
    address_zip.meta_value AS address_zip,
    address_country.meta_value AS address_country,
    phone.meta_value AS phone,

    cheapestRoom.postID AS postID,
    cheapestRoom.roomName AS roomName,
    cheapestRoom.surface AS surface,
    cheapestRoom.price AS price,
    cheapestRoom.bedrooms AS bedrooms,
    cheapestRoom.bathrooms AS bathrooms,
    cheapestRoom.roomType AS roomType,

    ROUND(AVG(rating.meta_value)) AS rating,
    COUNT(rating.meta_value) AS ratingCount,

    cheapestRoom.coverImage AS coverURL,

    coverImage.meta_value AS coverImage,
    geo_lat.meta_value AS geo_lat,
    geo_lng.meta_value AS geo_lng,
    111.111 * DEGREES(
            ACOS(
                    LEAST(
                            1.0,
                            COS(RADIANS(geo_lat.meta_value)) * COS(RADIANS( :userLat )) * COS(
                                    RADIANS(geo_lng.meta_value - :userLng )
                             ) + SIN(RADIANS(geo_lat.meta_value)) * SIN(RADIANS( :userLat ))
                     )
             )
     ) AS distanceKM


FROM
 wp_users AS USER
    
    
INNER JOIN wp_posts AS post
ON
    USER.ID = post.post_author

INNER JOIN wp_usermeta AS address_1
ON
    USER.ID = address_1.user_id AND address_1.meta_key = "address_1"

INNER JOIN wp_usermeta AS address_2
ON
    USER.ID = address_2.user_id AND address_2.meta_key = "address_2"

INNER JOIN wp_usermeta AS address_city
ON
    USER.ID = address_city.user_id AND address_city.meta_key = "address_city"

INNER JOIN wp_usermeta AS address_zip
ON
    USER.ID = address_zip.user_id AND address_zip.meta_key = "address_zip"

INNER JOIN wp_usermeta AS address_country
ON
    USER.ID = address_country.user_id AND address_country.meta_key = "address_country"

INNER JOIN wp_usermeta AS phone
ON
    USER.ID = phone.user_id AND phone.meta_key = "phone"

INNER JOIN wp_usermeta AS geo_lat
ON
    USER.ID = geo_lat.user_id AND geo_lat.meta_key = "geo_lat"

INNER JOIN wp_usermeta AS geo_lng
ON
    USER.ID = geo_lng.user_id AND geo_lng.meta_key = "geo_lng"

INNER JOIN wp_usermeta AS coverImage
ON
    USER.ID = coverImage.user_id AND coverImage.meta_key = "coverImage"

INNER JOIN wp_postmeta AS rating
ON
    post.ID = rating.post_id AND rating.meta_key = "rating" AND post.post_type = "review"

INNER JOIN(
    SELECT
        posts.ID AS postID,
        posts.post_author AS postAuthor,
        posts.post_title AS roomName,
        CAST(surface.meta_value AS UNSIGNED) AS surface,
        MIN(CAST(price.meta_value AS UNSIGNED)) AS price,
        CAST(bedrooms_count.meta_value AS UNSIGNED) AS bedrooms,
        CAST(bathrooms_count.meta_value AS UNSIGNED) AS bathrooms,
        roomType.meta_value AS roomType,
        coverImage.meta_value AS coverImage

    FROM wp_posts AS posts
    
    INNER JOIN wp_users AS USER
    ON
        USER.ID = posts.post_author

    INNER JOIN wp_postmeta AS surface
    ON
        surface.post_id = posts.ID AND surface.meta_key = 'surface'

    INNER JOIN wp_postmeta AS price
    ON
        price.post_id = posts.ID AND price.meta_key = 'price'

    INNER JOIN wp_postmeta AS bedrooms_count
    ON
        bedrooms_count.post_id = posts.ID AND bedrooms_count.meta_key = 'bedrooms_count'

    INNER JOIN wp_postmeta AS bathrooms_count
    ON
        bathrooms_count.post_id = posts.ID AND bathrooms_count.meta_key = 'bathrooms_count'

    INNER JOIN wp_postmeta AS roomType
    ON
        roomType.post_id = posts.ID AND roomType.meta_key = 'type'

    INNER JOIN wp_postmeta AS coverImage
    ON
        coverImage.post_id = posts.ID AND coverImage.meta_key = 'coverImage'

    WHERE
        post_type = 'room'
        AND surface.meta_value >= :surfaceMin AND surface.meta_value <= :surfaceMax
        AND price.meta_value >= :priceMin AND price.meta_value <= :priceMax
        AND bedrooms_count.meta_value >= :bedrooms
        AND bathrooms_count.meta_value >= :bathrooms
        AND roomType.meta_value IN ( :type )

    GROUP BY posts.post_author
) AS cheapestRoom
    ON
        USER.ID = cheapestRoom.postAuthor

GROUP BY USER.ID

HAVING distanceKM < :distance;
```

## Question 7 : ajout d'indexes SQL

**Indexes ajoutés**

- `wp_postmeta` : `post_id`
- `wp_posts` : `post_author`
- `wp_usermeta` : `user_id, meta_key, meta_value`

**Imposible de créer un index avec plusieurs paramètres avec `wp_usermeta`, donc j'ai choisi de le faire sur `user_id`**

**Requête SQL d'ajout des indexes** 

```sql
CREATE INDEX postMeta_index ON wp_postmeta (post_id);
CREATE INDEX posts_index ON wp_posts (post_author);
CREATE INDEX usermeta_index ON wp_usermeta (user_id);
```

| Temps de chargement de la page | Sans filtre | Avec filtres |
|--------------------------------|-------------|--------------|
| `UnoptimizedService`           | 12.4 s      | 0.91 s       |
| `OneRequestService`            | 2.47 s      | 0.82 s       |
[Filtres à utiliser pour mesurer le temps de chargement](http://localhost/?types%5B%5D=Maison&types%5B%5D=Appartement&price%5Bmin%5D=200&price%5Bmax%5D=230&surface%5Bmin%5D=130&surface%5Bmax%5D=150&rooms=5&bathRooms=5&lat=46.988708&lng=3.160778&search=Nevers&distance=30)




## Question 8 : restructuration des tables

**Temps de chargement de la page**

| Temps de chargement de la page | Sans filtre | Avec filtres |
|--------------------------------|-------------|--------------|
| `OneRequestService`            | TEMPS       | TEMPS        |
| `ReworkedHotelService`         | TEMPS       | TEMPS        |

[Filtres à utiliser pour mesurer le temps de chargement](http://localhost/?types%5B%5D=Maison&types%5B%5D=Appartement&price%5Bmin%5D=200&price%5Bmax%5D=230&surface%5Bmin%5D=130&surface%5Bmax%5D=150&rooms=5&bathRooms=5&lat=46.988708&lng=3.160778&search=Nevers&distance=30)

### Table `hotels` (200 lignes)

```SQL
-- REQ SQL CREATION TABLE
```

```SQL
-- REQ SQL INSERTION DONNÉES DANS LA TABLE
```

### Table `rooms` (1 200 lignes)

```SQL
-- REQ SQL CREATION TABLE
```

```SQL
-- REQ SQL INSERTION DONNÉES DANS LA TABLE
```

### Table `reviews` (19 700 lignes)

```SQL
-- REQ SQL CREATION TABLE
```

```SQL
-- REQ SQL INSERTION DONNÉES DANS LA TABLE
```


## Question 13 : Implémentation d'un cache Redis

**Temps de chargement de la page**

| Sans Cache | Avec Cache |
|------------|------------|
| TEMPS      | TEMPS      |
[URL pour ignorer le cache sur localhost](http://localhost?skip_cache)

## Question 14 : Compression GZIP

**Comparaison des poids de fichier avec et sans compression GZIP**

|                       | Sans  | Avec  |
|-----------------------|-------|-------|
| Total des fichiers JS | POIDS | POIDS |
| `lodash.js`           | POIDS | POIDS |

## Question 15 : Cache HTTP fichiers statiques

**Poids transféré de la page**

- **Avant** : POIDS
- **Après** : POIDS

## Question 17 : Cache NGINX

**Temps de chargement cache FastCGI**

- **Avant** : TEMPS
- **Après** : TEMPS

#### Que se passe-t-il si on actualise la page après avoir coupé la base de données ?

REPONSE

#### Pourquoi ?

REPONSE
