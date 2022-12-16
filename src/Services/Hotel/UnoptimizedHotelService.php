<?php

namespace App\Services\Hotel;

use App\Common\FilterException;
use App\Common\SingletonTrait;
use App\Common\Timers;
use App\Entities\HotelEntity;
use App\Entities\RoomEntity;
use App\Services\Room\RoomService;
use Exception;
use PDO;
use App\Entities\PDOSingleton;

/**
 * Une classe utilitaire pour récupérer les données des magasins stockés en base de données
 */
class UnoptimizedHotelService extends AbstractHotelService {
  
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
  
  
  /**
   * Récupère une méta-donnée de l'instance donnée
   *
   * @param int    $userId
   * @param string $key
   *
   * @return string|null
   */
  /*protected function getMeta ( int $userId, string $key ) : ?string {
      $id = $this -> timer ->startTimer("getMeta");
      $db = $this->getDB();
      $stmt = $db->prepare( "SELECT meta_value FROM wp_usermeta WHERE user_id=:UserId AND meta_key=:metaKey ;" );
      $stmt->execute([
          "UserId" => $userId,
          "metaKey" => $key
      ]);

      $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
      $output = $result[0]['meta_value'];


      $this ->timer ->endTimer("getMeta", $id);
      return $output;
  }*/
  
  
  /**
   * Récupère toutes les meta données de l'instance donnée
   *
   * @param HotelEntity $hotel
   *
   * @return array
   * @noinspection PhpUnnecessaryLocalVariableInspection
   */
  protected function getMetas ( HotelEntity $hotel ) : array {
      $id = $this -> timer ->startTimer("getMetas");

      $stmt = $this->getDB()->prepare( "SELECT meta_key, meta_value FROM wp_usermeta WHERE user_id=:UserId ;" );
      $stmt->execute(["UserId" => $hotel->getId()]);
      $getHotelMeta = $stmt ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);

      //dump($getHotelMeta);

      $metaDatas = [
          'address' => [
              'address_1' =>  $getHotelMeta['address_1'],
              'address_2' => $getHotelMeta['address_2'],
              'address_city' => $getHotelMeta['address_city'],
              'address_zip' => $getHotelMeta['address_zip'],
              'address_country' => $getHotelMeta['address_country'],
          ],
          'geo_lat' =>  $getHotelMeta['geo_lat'],
          'geo_lng' =>  $getHotelMeta['geo_lng'],
          'coverImage' =>  $getHotelMeta['coverImage'],
          'phone' =>  $getHotelMeta['phone'],
      ];

      $this ->timer ->endTimer("getMetas", $id);

      return $metaDatas;
  }
  
  
  /**
   * Récupère les données liées aux évaluations des hotels (nombre d'avis et moyenne des avis)
   *
   * @param HotelEntity $hotel
   *
   * @return array{rating: int, count: int}
   * @noinspection PhpUnnecessaryLocalVariableInspection
   */
  protected function getReviews ( HotelEntity $hotel ) : array {
      $id = $this -> timer ->startTimer("getReviews");
    // Récupère tous les avis d'un hotel
    $stmt = $this->getDB()->prepare( "SELECT ROUND(AVG(meta_value)) AS rating, COUNT(meta_value) AS count FROM wp_posts, wp_postmeta WHERE wp_posts.post_author = :hotelId AND wp_posts.ID = wp_postmeta.post_id AND meta_key = 'rating' AND post_type = 'review';");
    $stmt->execute( [ 'hotelId' => $hotel->getId() ] );
    $reviews = $stmt->fetchAll( PDO::FETCH_ASSOC );
    
    // Sur les lignes, ne garde que la note de l'avis

    
    $output = [
      'rating' => $reviews[0]['rating'],
      'count' => $reviews[0]['count']
    ];

      $this ->timer ->endTimer("getReviews", $id);
    return $output;
  }
  
  
  /**
   * Récupère les données liées à la chambre la moins chère des hotels
   *
   * @param HotelEntity $hotel
   * @param array{
   *   search: string | null,
   *   lat: string | null,
   *   lng: string | null,
   *   price: array{min:float | null, max: float | null},
   *   surface: array{min:int | null, max: int | null},
   *   rooms: int | null,
   *   bathRooms: int | null,
   *   types: string[]
   * }                  $args Une liste de paramètres pour filtrer les résultats
   *
   * @throws FilterException
   * @return RoomEntity
   */
    protected function getCheapestRoom ( HotelEntity $hotel, array $args = [] ) : RoomEntity {
        $id = $this -> timer ->startTimer("getCheapestRoom");

        $query = "SELECT

        posts.post_author AS postAuthor,
        
        posts.post_title AS roomName,
        CAST(surface.meta_value AS UNSIGNED) AS surface,
        MIN(CAST(price.meta_value AS UNSIGNED)) AS price,
        CAST(bedrooms_count.meta_value AS UNSIGNED) AS bedrooms,
        CAST(bathrooms_count.meta_value AS UNSIGNED) AS bathrooms,
        roomType.meta_value AS roomType,
        coverImage.meta_value AS coverImage";

        /*if(isset($_GET['lat']) && isset($_GET['lng'])  && isset($_GET['search']) && isset($args['distance']) ){
            $query .= ", 111.111
            * DEGREES(ACOS(LEAST(1.0, COS(RADIANS( latData.meta_value ))
            * COS(RADIANS( :userLat ))
            * COS(RADIANS( lngData.meta_value - :userLng ))
            + SIN(RADIANS( latData.meta_value ))
            * SIN(RADIANS( :userLat ))))) AS distanceKM";
        }*/


        $query .=" FROM wp_posts AS posts
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
            coverImage.post_id = posts.ID AND coverImage.meta_key = 'coverImage'";

        $whereClauses = [];
        $hotelID = $hotel->getId();


        $whereClauses[] = 'posts.post_author = :hotelId AND post_type = "room"';

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

        $query .= ' GROUP BY posts.post_author';

        /*if ( isset($args['lat']) && isset($args['lng'])  && isset($args['distance']) )
            $query .= ' HAVING distanceKM <= :distance';*/

        //dump($query);


        $stmt = $this->getDB()->prepare( $query );



        /*if ( isset($args['lat']) && isset($args['lng'])  && isset($args['distance']) ){

            $stmt->bindParam('userLat', $args['lat'], PDO::PARAM_STR);
            $stmt->bindParam('userLng', $args['lng'], PDO::PARAM_STR);
            $stmt->bindParam('distance', $args['distance'], PDO::PARAM_INT);
        }*/





        $stmt->bindParam('hotelId', $hotelID, PDO::PARAM_INT);

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


        $stmt->execute();
        $filteredRooms = $stmt->fetchAll();


        // Si aucune chambre ne correspond aux critères, alors on déclenche une exception pour retirer l'hôtel des résultats finaux de la méthode list().
        if ( count( $filteredRooms ) < 1 )
            throw new FilterException( "Aucune chambre ne correspond aux critères" );


        // Trouve le prix le plus bas dans les résultats de recherche
        $filteredRooms = $filteredRooms[0];
        $cheapestRoom = new RoomEntity();
        $cheapestRoom->setId($filteredRooms['postAuthor']);
        $cheapestRoom->setTitle($filteredRooms['roomName']);
        $cheapestRoom->setSurface($filteredRooms['surface']);
        $cheapestRoom->setPrice($filteredRooms['price']);
        $cheapestRoom->setBedRoomsCount($filteredRooms['bedrooms']);
        $cheapestRoom->setBathRoomsCount($filteredRooms['bathrooms']);
        $cheapestRoom->setType($filteredRooms['roomType']);
        $cheapestRoom->setCoverImageUrl($filteredRooms['coverImage']);



        $this ->timer ->endTimer("getCheapestRoom",$id);

        return $cheapestRoom;
    }
  
  
  /**
   * Calcule la distance entre deux coordonnées GPS
   *
   * @param $latitudeFrom
   * @param $longitudeFrom
   * @param $latitudeTo
   * @param $longitudeTo
   *
   * @return float|int
   */
  protected function computeDistance ( $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo ) : float|int {
    return ( 111.111 * rad2deg( acos( min( 1.0, cos( deg2rad( $latitudeTo ) )
          * cos( deg2rad( $latitudeFrom ) )
          * cos( deg2rad( $longitudeTo - $longitudeFrom ) )
          + sin( deg2rad( $latitudeTo ) )
          * sin( deg2rad( $latitudeFrom ) ) ) ) ) );
  }
  
  
  /**
   * Construit une ShopEntity depuis un tableau associatif de données
   *
   * @throws Exception
   */
  protected function convertEntityFromArray ( array $data, array $args ) : HotelEntity {
    $hotel = ( new HotelEntity() )
      ->setId( $data['ID'] )
      ->setName( $data['display_name'] );
    
    // Charge les données meta de l'hôtel
    $metasData = $this->getMetas( $hotel );
    $hotel->setAddress( $metasData['address'] );
    $hotel->setGeoLat( $metasData['geo_lat'] );
    $hotel->setGeoLng( $metasData['geo_lng'] );
    $hotel->setImageUrl( $metasData['coverImage'] );
    $hotel->setPhone( $metasData['phone'] );
    
    // Définit la note moyenne et le nombre d'avis de l'hôtel
    $reviewsData = $this->getReviews( $hotel );
    $hotel->setRating( $reviewsData['rating'] );
    $hotel->setRatingCount( $reviewsData['count'] );
    
    // Charge la chambre la moins chère de l'hôtel
    $cheapestRoom = $this->getCheapestRoom( $hotel, $args );
    $hotel->setCheapestRoom($cheapestRoom);
    
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
    
    return $hotel;
  }
  
  
  /**
   * Retourne une liste de boutiques qui peuvent être filtrées en fonction des paramètres donnés à $args
   *
   * @param array{
   *   search: string | null,
   *   lat: string | null,
   *   lng: string | null,
   *   price: array{min:float | null, max: float | null},
   *   surface: array{min:int | null, max: int | null},
   *   bedrooms: int | null,
   *   bathrooms: int | null,
   *   types: string[]
   * } $args Une liste de paramètres pour filtrer les résultats
   *
   * @throws Exception
   * @return HotelEntity[] La liste des boutiques qui correspondent aux paramètres donnés à args
   */
  public function list ( array $args = [] ) : array {
    $db = $this->getDB();
    $stmt = $db->prepare( "SELECT * FROM wp_users" );
    $stmt->execute();
    
    $results = [];
    foreach ( $stmt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
      try {
        $results[] = $this->convertEntityFromArray( $row, $args );
      } catch ( FilterException ) {
        // Des FilterException peuvent être déclenchées pour exclure certains hotels des résultats
      }
    }
    
    
    return $results;
  }
}