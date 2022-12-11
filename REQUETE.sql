SELECT
	posts.ID AS postID,
    user.display_name AS hotelName,
    posts.post_title AS roomName,
    surface.meta_value AS surface,
    price.meta_value AS price,
    bedrooms_count.meta_value AS bedrooms_count,
    bathrooms_count.meta_value AS bathrooms_count,
    type.meta_value AS type,
    coverImage.meta_value AS coverImage,
    posts.guid AS guid
    
    
    
FROM wp_posts AS posts

INNER JOIN wp_users AS USER
	ON user.ID = posts.post_author

INNER JOIN wp_postmeta AS price
	ON price.post_id = posts.ID AND price.meta_key = "price"

    
INNER JOIN wp_postmeta AS coverImage
	ON coverImage.post_id = posts.ID AND coverImage.meta_key = "coverImage"
    
    
INNER JOIN wp_postmeta AS bedrooms_count
	ON bedrooms_count.post_id = posts.ID AND bedrooms_count.meta_key = "bedrooms_count"
    
INNER JOIN wp_postmeta AS bathrooms_count
	ON bathrooms_count.post_id = posts.ID AND bathrooms_count.meta_key = "bathrooms_count"
    
INNER JOIN wp_postmeta AS surface
	ON surface.post_id = posts.ID AND surface.meta_key="surface"

INNER JOIN wp_postmeta AS type
	ON type.post_id = posts.ID AND type.meta_key="type";