---Q8 TABLES
CREATE TABLE `hotels` (
  `idHotel` int(11) NOT NULL,
  `hotelName` varchar(255) NOT NULL,
  `hotelEmail` varchar(255) NOT NULL,
  `address_1` varchar(255) NOT NULL,
  `address_2` varchar(255) NOT NULL,
  `address_zip` varchar(255) NOT NULL,
  `address_city` varchar(255) NOT NULL,
  `address_country` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `geoLat` varchar(255) NOT NULL,
  `geoLng` varchar(255) NOT NULL,
  `imageUrl` varchar(2000) NOT NULL
)


INSERT INTO hotels (
    SELECT
        USER.ID AS idHotel,
        USER.display_name AS hotelName,
        USER.user_email AS hotelEmail,
        address_1.meta_value AS address_1,
        address_2.meta_value AS address_2,
        address_zip.meta_value AS address_zip,
        address_city.meta_value AS address_city,
        address_country.meta_value AS address_country,
        phone.meta_value AS phone,
        geo_lat.meta_value AS geoLat,
        geo_lng.meta_value AS geoLng,
        coverImage.meta_value AS imageUrl

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


)


-- clé étrangére ne marche pas



CREATE TABLE rooms (
    idRoom INT PRIMARY KEY NOT NULL,
    idHotel INT,
    type VARCHAR(20),
    roomName VARCHAR(255),
    price INT,
    bedrooms INT,
    bathrooms INT,
    surface INT,
    coverImageUrl VARCHAR(2000),
    FOREIGN KEY (idHotel) REFERENCES Hotels(idHotel)

)



INSERT INTO rooms (
    SELECT

    	posts.post_author AS idHotel,
        roomType.meta_value AS type,
    	posts.post_title AS roomName,
        MIN(CAST(price.meta_value AS UNSIGNED)) AS price,
    	CAST(bedrooms_count.meta_value AS UNSIGNED) AS bedrooms,
    	CAST(bathrooms_count.meta_value AS UNSIGNED) AS bathrooms,
        CAST(surface.meta_value AS UNSIGNED) AS surface,
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

)



CREATE TABLE reviews (
    idReview INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    idHotel INT,
    ratingCount INT,
    rating INT,
    FOREIGN KEY (idHotel) REFERENCES Hotels(idHotel)
)


INSERT INTO reviews (
    SELECT

    USER.ID AS hotelId,
    ROUND(AVG(rating.meta_value)) AS rating,
    COUNT(rating.meta_value) AS ratingCount,

     INNER JOIN wp_users AS USER
        ON
            USER.ID = posts.post_author



    FROM wp_postmeta AS rating

    WHERE rating.meta_key = "rating" AND hotelID =  

)


