SELECT
    posts.ID AS postID,
    USER.display_name AS hotelName,
    posts.post_title AS roomName,
    surface.meta_value AS surface,
    CAST(price.meta_value AS DECIMAL(10,2)) AS price,
    bedrooms_count.meta_value AS bedrooms_count,
    bathrooms_count.meta_value AS bathrooms_count,
    TYPE.meta_value AS TYPE,
    coverImage.meta_value AS coverImage

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
    
   
INNER JOIN wp_postmeta AS TYPE
ON TYPE
    .post_id = posts.ID AND TYPE.meta_key = "type"
    
    
INNER JOIN wp_postmeta AS coverImage
ON
    coverImage.post_id = posts.ID AND coverImage.meta_key = "coverImage";