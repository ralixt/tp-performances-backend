<?php

namespace App\Services\Hotel;

use App\Common\FilterException;
use App\Common\SingletonTrait;
use App\Common\Timers;
use App\Entities\HotelEntity;
use App\Entities\PDOSingleton;
use App\Entities\RoomEntity;
use App\Services\Room\RoomService;
use Exception;
use PDO;
use PDOStatement;


class OneRequestHotelService extends AbstractHotelService
{
    use SingletonTrait;

    private Timers $timer;

    protected function __construct () {
        parent::__construct( new RoomService() );
        $this -> timer = Timers::getInstance();
    }




    /**
     * Récupère une nouvelle instance de connexion à la base de donnée
     *
     * @return PDO
     * @noinspection PhpUnnecessaryLocalVariableInspection
     */
    protected function getDB () : PDO {
        $id = $this -> timer ->startTimer("getDB");
        $pdo = PDOSingleton::get();
        $this ->timer ->endTimer("getDB", $id);
        return $pdo;
    }


    protected function buidQuery ( array $args ):PDOStatement{

        $id = $this -> timer ->startTimer("buidQuery");

        $query = 'SELECT
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
        geo_lng.meta_value AS geo_lng';

        if(isset($_GET['lat']) && isset($_GET['lng'])  && isset($args['distance']) ){
            $query .= ', 111.111
            * DEGREES(ACOS(LEAST(1.0, COS(RADIANS( geo_lat.meta_value ))
            * COS(RADIANS( :userLat ))
            * COS(RADIANS( geo_lng.meta_value - :userLng ))
            + SIN(RADIANS( geo_lat.meta_value ))
            * SIN(RADIANS( :userLat ))))) AS distanceKM';
        }

        $query .= " FROM wp_users AS USER
    
        INNER JOIN wp_posts AS post
        ON
            USER.ID = post.post_author
            
        INNER JOIN wp_usermeta AS address_1
        ON
            USER.ID = address_1.user_id AND address_1.meta_key = 'address_1'
            
        INNER JOIN wp_usermeta AS address_2
        ON
            USER.ID = address_2.user_id AND address_2.meta_key = 'address_2'
            
        INNER JOIN wp_usermeta AS address_city
        ON
            USER.ID = address_city.user_id AND address_city.meta_key = 'address_city'
            
        INNER JOIN wp_usermeta AS address_zip
        ON
            USER.ID = address_zip.user_id AND address_zip.meta_key = 'address_zip'
            
        INNER JOIN wp_usermeta AS address_country
        ON
            USER.ID = address_country.user_id AND address_country.meta_key = 'address_country'
            
        INNER JOIN wp_usermeta AS phone
        ON
            USER.ID = phone.user_id AND phone.meta_key = 'phone'
            
        INNER JOIN wp_usermeta AS geo_lat
        ON
            USER.ID = geo_lat.user_id AND geo_lat.meta_key = 'geo_lat'
            
        INNER JOIN wp_usermeta AS geo_lng
        ON
            USER.ID = geo_lng.user_id AND geo_lng.meta_key = 'geo_lng'
            
        INNER JOIN wp_usermeta AS coverImage
        ON
            USER.ID = coverImage.user_id AND coverImage.meta_key = 'coverImage'
            
        INNER JOIN wp_postmeta AS rating
        ON
            post.ID = rating.post_id AND rating.meta_key = 'rating' AND post.post_type = 'review'
                
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
                    coverImage.post_id = posts.ID AND coverImage.meta_key = 'coverImage' ";

        $whereClauses = [];


        $whereClauses[] = 'post_type = "room"';

        if ( isset( $args['surface']['min'] ))
            $whereClauses[] = 'surface.meta_value >= :surfaceMin';

        if ( isset( $args['surface']['max'] ))
            $whereClauses[] = 'surface.meta_value <= :surfaceMax';

        if ( isset( $args['price']['min'] ))
            $whereClauses[] = 'price.meta_value >= :priceMin';

        if ( isset( $args['price']['max'] ))
            $whereClauses[] = 'price.meta_value <= :priceMax';

        if ( isset( $args['rooms'] ))
            $whereClauses[] = 'bedrooms_count.meta_value >= :bedrooms';

        if ( isset( $args['bathRooms'] ))
            $whereClauses[] = 'bathrooms_count.meta_value >= :bathrooms';

        if ( isset( $args['types'] ) && ! empty( $args['types'] ))
            $whereClauses[] = "roomType.meta_value IN ('".implode("','", $args['types'])."')";


        if ( count($whereClauses) > 0 )
            $query .= " WHERE " . implode( ' AND ', $whereClauses );

        $query .= "     GROUP BY posts.post_author
                    ) AS cheapestRoom
                    ON 
                        USER.ID = cheapestRoom.postAuthor
                    
                    GROUP BY USER.ID";

        if ( isset($args['lat']) && isset($args['lng'])  && isset($args['distance']) ){
            $query .= " HAVING distanceKM < :distance";

        }
                    


        $stmt = $this->getDB()->prepare( $query );

        if ( isset($args['lat']) && isset($args['lng'])  && isset($args['distance']) ){

            $stmt->bindParam('userLat', $args['lat'], PDO::PARAM_STR);
            $stmt->bindParam('userLng', $args['lng'], PDO::PARAM_STR);
            $stmt->bindParam('distance', $args['distance'], PDO::PARAM_INT);
        }

        if ( isset( $args['surface']['min'] ))
            $stmt->bindParam('surfaceMin', $args['surface']['min'], PDO::PARAM_INT);

        if ( isset( $args['surface']['max'] ))
            $stmt->bindParam('surfaceMax', $args['surface']['max'], PDO::PARAM_INT);

        if ( isset( $args['price']['min'] ))
            $stmt->bindParam('priceMin', $args['price']['min'], PDO::PARAM_INT);

        if ( isset( $args['price']['max'] ))
            $stmt->bindParam('priceMax', $args['price']['max'], PDO::PARAM_INT);

        if ( isset( $args['rooms'] ))
            $stmt->bindParam('bedrooms', $args['rooms'], PDO::PARAM_INT);

        if ( isset( $args['bathRooms'] ))
            $stmt->bindParam('bathrooms', $args['bathRooms'], PDO::PARAM_INT);


        $this ->timer ->endTimer("buidQuery", $id);

        return $stmt;
    }



    /**
     * Construit une ShopEntity depuis un tableau associatif de données
     *
     * @throws Exception
     */
    protected function convertEntityFromArray ( array $args ) : HotelEntity {

        $id = $this -> timer ->startTimer("convertEntityFromArray");

        $hotel = ( new HotelEntity() )
            ->setId( $args['hotelId'] )
            ->setName( $args['hotelName'] )
            ->setMail( $args['hotelEmail'] )
            ->setAddress([
                'address_1' => $args['address_1'],
                'address_2' => $args['address_2'],
                'address_city' => $args['address_city'],
                'address_zip' => $args['address_zip'],
                'address_country' => $args['address_country'],
            ])
            ->setPhone( $args['phone'] )
            ->setGeoLat( $args['geo_lat'] )
            ->setGeoLng( $args['geo_lng'] )
            ->setImageUrl( $args['coverImage'] )
            ->setRatingCount( $args['ratingCount'] )
            ->setRating( $args['rating'] )

            ->setCheapestRoom((new RoomEntity())
                ->setId( $args['postID'] )
                ->setTitle( $args['roomName'] )
                ->setPrice( $args['price'] )
                ->setCoverImageUrl( $args['coverURL'] )
                ->setBedRoomsCount( $args['bedrooms'] )
                ->setBathRoomsCount( $args['bathrooms'] )
                ->setSurface( $args['surface'] )
                ->setType( $args['roomType'] )
            );

        if(isset($args['distanceKM'])){
            $hotel->setDistance($args['distanceKM']);
        }


        // Verification de la distance
        if ( isset( $args['lat'] ) && isset( $args['lng'] ) && isset( $args['distance'] ) ) {
            $hotel->setDistance( $this->computeDistance(
                floatval( $args['lat'] ),
                floatval( $args['lng'] ),
                floatval( $hotel->getGeoLat() ),
                floatval( $hotel->getGeoLng() )
            ) );

            if ( $hotel->getDistance() > $args['distance'] )
                throw new FilterException( "L'hôtel est en dehors du rayon de recherche" );
        }

        $this ->timer ->endTimer("convertEntityFromArray", $id);

        return $hotel;
    }


    /**
     * @param array $args
     * @return HotelEntity[]
     * @throws Exception
     */
    public function list(array $args = []): array
    {
        $id = $this -> timer ->startTimer("list");
        $stmt = $this->buidQuery($args);
        //dump($stmt);
        $stmt->execute();

        $results = [];
        foreach ( $stmt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
            try {
                $results[] = $this->convertEntityFromArray( $row );
            } catch ( FilterException ) {
                // Des FilterException peuvent être déclenchées pour exclure certains hotels des résultats
            }
        }

        $this ->timer ->endTimer("list", $id);

        return $results;
    }
}