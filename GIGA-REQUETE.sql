-- getMetas
SELECT meta_key, meta_value FROM wp_usermeta WHERE user_id=:UserId


-- getReviews
SELECT ROUND(AVG(meta_value)) AS rating, 
COUNT(meta_value) AS count 

FROM wp_posts, wp_postmeta 
WHERE wp_posts.post_author = :hotelId 
AND wp_posts.ID = wp_postmeta.post_id 
AND meta_key = 'rating' AND post_type = 'review';



-- getCheapestRoom
SELECT

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
    posts.post_author = :hotelId AND post_type = 'room'
    AND surface.meta_value >= :surfaceMin AND surface.meta_value <= :surfaceMax
    AND price.meta_value >= :priceMin AND price.meta_value <= :priceMax
    AND bedrooms_count.meta_value >= :bedrooms
    AND bathrooms_count.meta_value >= :bathrooms
    AND roomType.meta_value IN ( :type )
    
GROUP BY posts.post_author



-- GIGA Requete

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
	
    