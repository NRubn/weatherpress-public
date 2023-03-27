<?php
/*
Plugin Name: Weatherpress
Plugin URI: https:/https://github.com/NRubn/weatherpress/
Description: Wetterdaten auf deine Wordpresseite
Version: 1.0.5
Author: Ruben
Author URI: https://google.de/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

$openweathermapkey = OPENWEATHERTOKEN;

function weatherpress_add_menu_page() {
    add_menu_page(
        'Weatherpress Settings', // Seitentitel
        'Weatherpress', // Menütitel
        'manage_options', // Berechtigungslevel
        'weatherpress-settings', // Slug der Seite
        'weatherpress_settings_page', // Callback-Funktion zum Rendern der Seite
        'dashicons-palmtree', // Icon-URL
        10 // Position im Menü
    );
}
add_action( 'admin_menu', 'weatherpress_add_menu_page' );

// Hier kommen die Funktionen des Plugins

// Funktion zum Aktivieren des Plugins
function weatherpress_activate() {
  // Code zum Aktivieren des Plugins
}
register_activation_hook( __FILE__, 'weatherpress_activate_activate' );

// Funktion zum Deaktivieren des Plugins
function weatherpress_activate_deactivate() {
  // Code zum Deaktivieren des Plugins
}
register_deactivation_hook( __FILE__, 'weatherpress_activate_deactivate' );

// Funktion zum Entfernen des Plugins
function weatherpress_activate_uninstall() {
  // Code zum Entfernen des Plugins
}
register_uninstall_hook( __FILE__, 'mein_plugin_uninstall' );

function weatherpress_settings_page() {
    
	echo "Start";
	if ( isset( $_POST['city_name'] ) ) {
		echo 'City: '.$_POST['city_name'].'<br>';
        $city_name = $_POST['city_name'];
    } else {
        $city_name = 'Dortmund';
    }
	
	
	?>
    <div class="wrap">
        <h1>My Plugin Settings</h1>
        <form method="post" action="admin.php?page=weatherpress-settings">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><span class="dashicons dashicons-location-alt"></span>Stadt:</th>
                    <td><input type="text" name="city_name" value="<?php echo $city_name ?>" /></td>
                </tr>
            </table>
			<?php submit_button(); ?>
        </form>
	<br>
	<p>Shortcode: "[weatherpress city="<?php echo $city_name ?>" unit="metric" icon="openweathermap"]"</p>
	<p>Mögliche Icons: icon="openweathermap", icon="emojie", icon="none"</p>
	<br>
    </div>
    <?php
	$citydata = citydatacurl($city_name);
	$citylat = $citydata[0]['lat'];
	$citylon = $citydata[0]['lon'];
	
	echo '<p>Koordinaten:<br>';
	echo 'latitute: '.$citylat;
	echo '<br>longitute: '.$citylon.'</p>';
	
	$weatherdata = weathercurl($citylat, $citylon);
	$html = generateweatheroutput($city_name);	
	echo '<br>';
	echo $html;
}

function citydatacurl($city_name){
	global $openweathermapkey;
	$url = 'https://api.openweathermap.org/geo/1.0/direct?q='.$city_name.'&appid='.$openweathermapkey;
	$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);
$responsejson = json_decode($response, true);
curl_close($curl);
return $responsejson;
}



function weathercurl($lat, $lon, $unit = 'metric'){
	global $openweathermapkey;
	$url = 'https://api.openweathermap.org/data/2.5/weather?lat='.$lat.'&lon='.$lon.'&appid='.$openweathermapkey.'&units='.$unit.'&lang=de';
	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => $url,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'GET',
	));
	
	$response = curl_exec($curl);
	$responsejson = json_decode($response, true);
	curl_close($curl);
	return $responsejson;
	
}

//Shortcode

function weatherpress_shortcode( $atts ) {
    // Verarbeite Shortcode-Attribute
    $atts = shortcode_atts( array(
        'city' => 'Dortmund',
        'unit' => 'metric',
        'icon' => 'openweathermap'
    ), $atts );
	
	$city_name = $atts['city'];
	$unit = $atts['unit'];
	$icon = $atts['icon'];

	$output = '';
	$output = generateweatheroutput($city_name, $unit, $icon);

    return $output;
}

// [weatherpress city="Your City" unit="metric" icon="true"]
add_shortcode( 'weatherpress', 'weatherpress_shortcode' );

// HTML erstellen:
function generateweatheroutput($city = "dortmund", $unit = "metric", $icon = "openweathermap"){
	
	$output = '';
	
	$citydata = citydatacurl($city);
	$citylat = $citydata[0]['lat'];
	$citylon = $citydata[0]['lon'];
	
	$weatherdata = weathercurl($citylat, $citylon, $unit);
	
    // Füge den Shortcode-Inhalt hinzu
	$sky = $weatherdata['weather'][0]['description'];
	$skyicon = $weatherdata['weather'][0]['icon'];
	$temp = $weatherdata['main']['temp'];
	
	//Prüfen ob openweathermap.org Icon erlaubt ist
	switch ($icon) {
    case 'openweathermap';
        $weathericon = '<img class="weatherpressicon" width="50" height="50" src="https://openweathermap.org/img/wn/'.$skyicon.'@2x.png">';
        break;
    case 'emojie';
        $weathericon = ' 🌡️';
        break;
    case 'none';
         $weathericon = ' ️';
     break;
	}
	
	if($temp<= 7){
		$itis ='cold';
	}elseif($temp>= 20){
		$itis ='hot';
	}else{
		$itis ='normal';
	};
	
	$output = '<div class="weatherpressapi '.$itis.'"><b>Das Wetter</b>';
	$output .= '<br>';
	$output .= '<div class="weatherpresscity">Stadt: '.$city.'</div>';
	$output .= '<div class="weatherpresssky">Himmel: '.$sky.$weathericon.'</div>';
	$output .= '<div class="weatherpresstemp">Temperatur: '.$temp.'</div>';
	$output .= '</div>';
	$output .= '<style>
	.weatherpresssky img.weatherpressicon{
    margin: -5px 0px 0px 10px;
    border-radius: 50%;
    margin: 0 10px;
	}
	.weatherpressapi{
	padding: 5px;
	}
	.weatherpressapi.cold {
    background: #0000ff3d;
	}
	.weatherpressapi.hot {
    background: #ff00003d;
	}
	
	.weatherpresscity {
    text-transform: capitalize;
	}
	
	</style>';
	
	return $output;
};


?>