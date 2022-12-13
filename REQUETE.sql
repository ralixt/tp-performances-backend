--utiliser dump et pas var_dump

SELECT * FROM wp_posts WHERE post_author = :hotelId AND post_type = 'room'



SELECT
	
    posts.ID AS postID,
    posts.post_author AS postAuthor,
    USER.display_name AS hotelName,
    posts.post_title AS roomName,
    CAST(surface.meta_value AS INT) AS surface,
    CAST(
        price.meta_value AS DECIMAL(10, 2)
    ) AS price,
    bedrooms_count.meta_value AS bedrooms,
    bathrooms_count.meta_value AS bathrooms,
    roomType.meta_value AS roomType,
    coverImage.meta_value AS coverImage,
    
    latData.meta_value AS lat,
    lngData.meta_value AS lng,
    
    111.111
        * DEGREES(ACOS(LEAST(1.0, COS(RADIANS( latData.meta_value ))
        * COS(RADIANS( :userLat ))
        * COS(RADIANS( lngData.meta_value - :userLng ))
        + SIN(RADIANS( latData.meta_value ))
        * SIN(RADIANS( :userLat ))))) AS distanceKM
    
FROM
    wp_posts AS posts
    
  
INNER JOIN wp_users AS USER
ON
    USER.ID = posts.post_author
    
INNER JOIN wp_postmeta AS surface
ON
    surface.post_id = posts.ID AND surface.meta_key = "surface"
   
INNER JOIN wp_postmeta AS price
ON
    price.post_id = posts.ID AND price.meta_key = "price"
    
INNER JOIN wp_postmeta AS bedrooms_count
ON
    bedrooms_count.post_id = posts.ID AND bedrooms_count.meta_key = "bedrooms_count"
    
INNER JOIN wp_postmeta AS bathrooms_count
ON
    bathrooms_count.post_id = posts.ID AND bathrooms_count.meta_key = "bathrooms_count"
    
INNER JOIN wp_postmeta AS roomType
ON
    roomType.post_id = posts.ID AND roomType.meta_key = "type"
    
INNER JOIN wp_postmeta AS coverImage
ON
    coverImage.post_id = posts.ID AND coverImage.meta_key = "coverImage"
    
    
INNER JOIN wp_usermeta AS latData 
ON 
    latData.user_id = user.ID AND latData.meta_key = 'geo_lat'

INNER JOIN wp_usermeta AS lngData 
ON 
    lngData.user_id = user.ID AND lngData.meta_key = 'geo_lng'

WHERE 
    posts.post_author = :hotelId AND post_type = 'room'
    AND surface.meta_value >= :surfaceMin AND surface.meta_value <= :surfaceMax
    AND price.meta_value >= :priceMin AND price.meta_value <= :priceMax
    AND bedrooms_count.meta_value >= :bedrooms
    AND bathrooms_count.meta_value >= :bathrooms
    AND roomType.meta_value IN ( :type )
    

GROUP BY posts.post_author

HAVING distanceKM < :distanceKM;

