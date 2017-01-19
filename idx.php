<?php if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start("ob_gzhandler"); else ob_start(); ?>
<?php
    date_default_timezone_set("America/Chicago");
    // Start Our Session, include the sparkAPI core.php file.
    session_start();
    require_once("../sparkapi4p2-master/lib/Core.php");

    // GET the MLS number from the url
    $listingID = $_GET['mls'];

    // Authentication function that verifies we have access to the feed
    function idxAuthenticate(&$api) {
        $api = new SparkAPI_APIAuth("cbor_510000372_key_1", "RvjqPj1tTQPIe7PoTHEq1");
        $api->SetApplicationName("Columbia Area Listings");

        $result = $api->Authenticate();
        if ($result === false) {
            // I'll add some better error handling sometime soon.
            echo "API Error Code: {$api->last_error_code}<br>\n";
            echo "API Error Message: {$api->last_error_mess}<br>\n";
            exit;
        }    
    }
    idxAuthenticate(&$api);
    
    // Creating the array $result by running the method 'GetListings' of the object $api.
    $result = $api->GetListings(
        array(
            '_limit' => 1,
            '_filter' => "ListingId Eq '".$listingID."'",
            '_expand' => "Photos,CustomFieldsExpanded,Documents,Units",
            '_select' => "BathsFull,BathsHalf,BedsTotal,BuildingAreaTotal,City,ListPrice,Latitude,Longitude,ListingId,MLSAreaMajor,MajorChangeType,MlsId,PostalCode,PropertyType,PropertyClass,PropertySubType,PublicRemarks,StateOrProvince,StreetName,StreetNumber,StreetDirPrefix,StreetSuffix,StreetAdditionalInfo,SubdivisionName,YearBuilt,ListOfficeName,ListAgentLastName,ListingUpdateTimestamp,ListOfficePhone,ListOfficeEmail"
          )
    );
    // Is there any data in $result?  If not, this means there is no active listing with that mls number.  So we need to display an error page, the contents of which is wrapped in the following if statement:
    if (empty($result)) {
?>
        <!doctype html>
        <html>
        <head>
            <title>Listing Not Found</title>
            <meta name="viewport" content="width=device-width" />
            <meta name="keywords" content="Susan Horak, Susan, Horak, Remax, Re/Max, Boone, Realty, Realtor, Real, Estate, Columbia, Mo, Missouri, residential, home, homes, house, houses, for, sale, condo, best" />
            <meta name="description" content="The Susan Horak Group: Re/Max Boone Realty // Listing Not Found" />
            <meta charset="utf-8" />
            <link rel="stylesheet" href="susanhorak-idx-not-found.css"/>
            <?include "../favicons.php" ?>
            
            <script>
                document.write('<style>.noscript { display: none; }</style>');
            </script>
            <style>
                div#logo-box {position:absolute;top:50%;left:50%;width:640px;height:200px;margin-top:-100px;margin-left:-320px;}
                div#logo-box img {margin:0 auto;}
                div#logo-box p {text-align:center;font-family: 'Lucida Grande', 'Lucida Sans Unicode', Verdana, Helvetica, Arial, sans-serif;color: #444;font-size:75%;}        
            </style>
        </head>
        <body>

        <div id="logo-box">
            <img src="../images/shg-logo.png" alt="The Susan Horak Group" />
            <p style="font-weight:bold;">The listing you were looking for with the MLS# of <?php echo $listingID; ?> could not be found.</p>
            <p>The listing is either under contract, closed, or otherwise unavailable.</p>
            <p><a href="https://www.susanhorak.com/area-listings" style="text-decoration:none;">Head back to the area listings search</a> <span style="font-weight:bold">OR</span> <a href="https://www.susanhorak.com/listings" style="text-decoration:none;">Check out Susan's listings.</a></p>
        </div>
            
        </body>
        </html>
<?php
    }
    // otherwise, we found some data so lets process it and display the page!
    else {
        
        // for each key in our result array, do the following.
        foreach ($result as $key => $listing) {
            // creating "shortcuts" to locations within the data response.
            $sf = $listing['StandardFields'];
            $cf = $listing['CustomFields'][0]['Main']; 
            $units = $sf['Units'];
            $photos = $sf['Photos'];
            $documents = $sf['Documents'];
            // Here, we populate a new array out of the base64 encoded url strings.  This is required to pass the photos through proxy.php.
            for ($i = 0; $i < count($photos); $i++) {
                $encodedPhoto = base64_encode($photos[$i]['Uri300']);
            }
        }
        // This is a great example of variable variables!  For each key in the array $sf, we create a variable thats name is taken from the array key, and its equal to the value.  This is how we use variables throughout the page called $bathsHalf, $StreetSuffix, and so on.
        foreach ($sf as $key => $value) {
            $$key = $value;
        }
        
        // Now we do some string processing!  
        
        // The data response like to return things in all caps.  From StreetName and StreetSuffix, we nest some functions.  strtolower() executes first, changing the string to all lowercase.  Then, ucwords fires, and the first character of each string is capitalized.
        $StreetName = ucwords(strtolower($StreetName));
        $StreetSuffix = ucwords(strtolower($StreetSuffix));
        $ListPriceFormatted = number_format($ListPrice);
        $subdivisionNameLower = ucwords(strtolower($SubdivisionName));
        $City = ucwords(strtolower($City));
        
        // We Create the FULL address out of the different address componants.  There is a sf response field that is something to the effect of "unparsed address" or something, but I like having control over how its formatted, so we build it from scratch here:
        $addressFormatted = $StreetNumber." ";

        if (!empty($StreetDirPrefix)) { 
            $addressFormatted .= $StreetDirPrefix; // If a directional marker exists, its added here.
        }
        $addressFormatted .= " ".$StreetName." ".$StreetSuffix; // Attach the street name and suffix, seperated by a space.
        if (!empty($StreetAdditionalInfo)) {
            $addressFormatted .= " unit ".$StreetAdditionalInfo; // If there is a unit number associated with the property, it is added here.
        }        
        
        // Lines 114 through 137 parse the custom fields response for the directions to the property, and whether the property is USDA eligable.  
        for ($i = 0; $i <= count($cf); $i++) {          
            if (isset($cf[$i])) {
                $cfKey = key($cf[$i]);
                if ($cfKey == "Remarks & Misc") {
                    for ($j = 0; $j < count($cf[$i][$cfKey]); $j++) {
                        if (array_key_exists('Directions', $cf[$i][$cfKey][$j])) {
                            $Directions = $cf[$i][$cfKey][$j]['Directions'];  
                        }
                    }
                }
            }
        }
        for ($i = 0; $i <= count($cf); $i++) {          
            if (isset($cf[$i])) {
                $cfKey = key($cf[$i]);
                if ($cfKey === "Will Sell") {
                    for ($j = 0; $j < count($cf[$i][$cfKey]); $j++) {
                        if (array_key_exists('USDA', $cf[$i][$cfKey][$j])) {
                            $usda = $cf[$i][$cfKey][$j]['USDA'];  
                        }
                    }
                }
            }
        }
        // Lines 139 through 172 connects to our MySQL database and checks if its our listing.
        $db = new mysqli("lin-mysql19.hostmanagement.net", "u1082926_susanho", "shorak2146S", "db1082926_susanhorak");
        $stmt = $db->prepare("SELECT mls FROM listings ORDER BY mls ASC");
        $stmt->execute();
        $stmt->store_result();
        $num_of_rows = $stmt->num_rows;    
        $stmt->bind_result($mls);

        $house = array();

        if ($num_of_rows > 0) {
            while($stmt->fetch()) {
                $house[] = $mls; // Fill the array "house" with all of the mls numbers in the listing table.
            }
        }
        if (in_array($ListingId, $house)) { // If the $ListingId variable is found in the array we just made, it exists in our database and is our listing.
            $ourListing = true; // Anytime we need to check if the listing is ours later on this page, we can simply check if this is true or false/null.
            
            // Assigning data from the table to usable php variables.
            $db2 = new mysqli("lin-mysql19.hostmanagement.net", "u1082926_susanho", "shorak2146S", "db1082926_susanhorak");
            $stmt2 = $db2->prepare("SELECT uc, sold, newlist, text, oh, ohtime, `ad-copy`, `issuu-embed`, `video-embed`, keywords, `for-rent`, `rental-price` FROM listings WHERE mls = ?");
            $stmt2->bind_param('i', $ListingId);
            $stmt2->execute();   
            $stmt2->bind_result($uc, $sold, $newlist, $text, $oh, $ohtime, $adcopy, $issuuembed, $videoembed, $keywords, $forrent, $rentalprice);
            $stmt2->fetch(); 
            
            // Closing our second connection to the database.
            $stmt2->free_result(); 
            $stmt2->close();
            $db2->close();
        }
        else { 
            $ourListing = null;
        }
        // Closing our initial connection to the database.
        $stmt->free_result();
        $stmt->close();
        $db->close(); 
        
        // So after all of that, we finally get to the doctype declaration.

?>
        
        <!doctype html>
        <html>
        <head>

        <title><?php echo $addressFormatted.", ".$City.", ".$StateOrProvince;?></title>
        <meta name="viewport" content="width=device-width" />
        <meta name="keywords" content="Susan Horak, Susan, Horak, Remax, Re/Max, Boone, Realty, Realtor, Real, Estate, Columbia, Mo, Missouri, residential, home, homes, house, houses, for, sale, condo, best" />
        <meta name="description" content="The Susan Horak Group: Re/Max Boone Realty // <?php echo $addressFormatted.", ".$City.", ".$StateOrProvince; ?>" />
        <meta charset="utf-8" />
        <link href='https://fonts.googleapis.com/css?family=Cabin:700%7CGreat+Vibes' rel='stylesheet'>
        <link rel="stylesheet" href="susanhorak-idx.css">
        <link rel="stylesheet" type="text/css" href="../css/swipebox.css" />
        <?include "../favicons.php" ?>
        <script>
            document.write('<style>.noscript { display: none; }</style>');
        </script>
        </head>

        <body onload="initialize()"> 
            <div id="page">        
                <div id="banner">
                    <div id="bannerleft">
                        <a href="https://www.susanhorak.com/area-listings"><img src="../images/header-2014.png" alt="Head back to Susan's Listing page" /></a>
                        <span id='or-see-ours' class='button-float'><a href="../listings" id='back-ours'>See Susan's Listings</a></span>
                    </div>	
                    <div id="bannerright">
                        <div id="gradient">
                            <p id="banner-address"><?php echo $addressFormatted; ?></p>
                            <p id="banner-citystate"><?php echo $City.", ".$StateOrProvince; ?></p>
                            <p id="banner-price">
                            <?php  
                                if (isset($ourListing)) {
                                    if ($forrent == 1 ) {
                                        echo "$".$ListPriceFormatted." <span class='rental-price'>or rent for ".$rentalprice."</span>";    
                                    }
                                    else {
                                        echo "$".$ListPriceFormatted;    
                                    }
                                }
                                else {
                                    echo "$".$ListPriceFormatted; 
                                }
                            ?>                        
                            </p>
                            <div id="remax">
                                <img src="../images/boone-realty.png" alt="remax boone realty">
                            </div>
                            <?php
                                if (isset($ourListing)) {
                                    if ($oh == 1) {
                                        echo "<div class='open-contain'>
                                                  <div class='icon'><img src='../images/openhouse-icon.png' alt='Open this Sunday' /></div>
                                                  <div class='open'>Open House</div>
                                                  <div class='this-sunday'>This Sunday</div>
                                                  <div class='time'>".$ohtime."</div>
                                              </div>";
                                    }
                                }
                            ?>
                        </div>
                    </div>
                </div>
                <div id="sharing-box">
                    <span class='st_facebook'></span>
                    <span class='st_twitter'></span>
                    <span class='st_linkedin'></span>
                    <span class='st_pinterest'></span>
                    <span class='st_googleplus'></span>
                    <span class='st_blogger'></span>
                    <span class='st_wordpress'></span>
                    <span class='st_sharethis'></span>
                </div>
                <div id="top-contain">
                    <div id="gallery" class="content">
                        <?php
                            if (isset($usda)) {
                                echo "
                                    <div id='usda-container'>
                                        Eligible for USDA loans!
                                    </div>";
                            }
                        ?>
                        <div class="slideshow-container">
                            <div id="loading" class="loader"></div>
                            <div id="slideshow" class="slideshow"></div>
                        </div>
                        <div id="gallery-launcher" class="launcher-container">
                            <?php                        
                                function getLarge($photos) {
                                    $encodedPhoto1024start = base64_encode($photos[0]['Uri1024']);
                                    echo "<a class='d-viewer' title='".$i."' href='https://www.susanhorak.com/proxy.php?url=".$encodedPhoto1024start."'>Launch the High-resolution gallery</a>";
                                    for ($i = 0; $i < count($photos); $i++) {
                                        $encodedPhoto1024 = base64_encode($photos[$i]['Uri1024']);
                                        if ($i != 0) {
                                            echo "<a style='display:none' class='d-viewer' title='".$i."' href='https://www.susanhorak.com/proxy.php?url=".$encodedPhoto1024."'></a>";
                                        }
                                    }
                                }
                                getLarge($photos);                        
                            ?>
                        </div>
                        <div id="m-gallery-launcher" class="mobile-fullscreen">
                            <?php
                                function getMobileLarge($photos) {
                                    $encodedPhoto640start = base64_encode($photos[0]['Uri640']);
                                    echo "<a class='m-viewer' title='".$i."' href='https://www.susanhorak.com/proxy.php?url=".$encodedPhoto640start."'>Launch the High-resolution gallery</a>";
                                    for ($i = 0; $i < count($photos); $i++) {
                                        $encodedPhoto640 = base64_encode($photos[$i]['Uri640']);                                    
                                        if ($i != 0) {
                                            echo "<a style='display:none' class='m-viewer' title='".$i."' href='https://www.susanhorak.com/proxy.php?url=".$encodedPhoto640."'></a>";
                                        }
                                    }
                                }
                                getMobileLarge($photos); 
                            ?>
                        </div>
                        <div id="home-details">
                            <table id="topdetail">
                                <tr class="toprow">
                                    <td>
                                        Bedrooms
                                    </td>
                                    <td>
                                        Bathrooms
                                    </td>
                                    <td>
                                        Sq. Ft. 
                                        <?php 
                                            if ($sf['PropertyType'] === "F") {
                                                echo "(Building) ";
                                            }

                                        ?>
                                        Total
                                    </td>
                                    <td>
                                        <?php
                                            if ($sf['PropertyType'] !== "F") {
                                                echo "Year Built";    
                                            }
                                            else {
                                                echo "Subdivision";
                                            }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detailcell">
                                        <?php
                                            if ($sf['PropertyType'] !== "F") { 
                                                if (!empty($BedsTotal)) {
                                                    echo $BedsTotal;
                                                }
                                                else {
                                                    echo " - ";
                                                }
                                            }
                                            else {
                                                foreach ($units as $unit) {
                                                    for ($j = 0; $j < count($unit['Fields']); $j++) {
                                                        if (array_key_exists("# Bed", $unit['Fields'][$j])) {
                                                            echo $unit['Fields'][$j]['# Bed'];

                                                        }
                                                        if (array_key_exists("# Units", $unit['Fields'][$j])) {
                                                            echo " (".$unit['Fields'][$j]['# Units'].") ";    
                                                        }
                                                    }

                                                    if ($unit != end($units)) {
                                                        echo " / ";   
                                                    }
                                                }
                                            }
                                        ?>
                                    </td>
                                    <td class="detailcell">
                                        <?php 
                                            if ($sf['PropertyType'] !== "F") {                                        
                                                if ($BathsHalf === 1 && $BathsFull != 0) {
                                                    echo $BathsFull." Full / ".$BathsHalf." Half";
                                                }
                                                else if ($BathsHalf > 1 && $BathsFull != 0){
                                                    echo $BathsFull." Full / ".$BathsHalf." Half";
                                                }
                                                else if ($BathsHalf === 0 && !empty($BathsFull)) {
                                                    echo $BathsFull." Full";
                                                }
                                                else if (empty($BathsFull) && empty($BathsHalf)) {
                                                    echo " - ";
                                                }
                                            }
                                            else {
                                                foreach ($units as $unit) {
                                                    for ($j = 0; $j < count($unit['Fields']); $j++) {
                                                        if (array_key_exists("# Bath", $unit['Fields'][$j])) {
                                                            echo $unit['Fields'][$j]['# Bath'];

                                                        }
                                                        if (array_key_exists("# Units", $unit['Fields'][$j])) {
                                                            echo " (".$unit['Fields'][$j]['# Units'].") ";    
                                                        }
                                                    }

                                                    if ($unit != end($units)) {
                                                        echo " / ";   
                                                    }
                                                }
                                            }

                                        ?>    
                                    </td>
                                    <td class="detailcell">
                                        <?php 
                                            if (!empty($BuildingAreaTotal)) {
                                                echo $BuildingAreaTotal." ft&sup2;";
                                            }
                                            else {
                                                echo " - ";
                                            }
                                        ?>
                                    </td>
                                    <td class="detailcell">
                                        <?php 
                                            if ($sf['PropertyType'] !== "F") {
                                                if (!empty($YearBuilt)) {
                                                    echo $YearBuilt;
                                                }
                                                else {
                                                    echo " - ";
                                                }
                                            }
                                            else {
                                                if (!empty($subdivisionNameLower)) {
                                                    echo $subdivisionNameLower;    
                                                }
                                                else {
                                                    echo " - ";
                                                }
                                            }
                                        ?>    
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div id="map-container"></div>
                        <div id="brokerage-information">
                            Listing is offered by <?php echo $ListOfficeName; ?>
                        </div>
                        <div id="brokerage-information-additional">
                            <?php
                                echo 
                                    "Phone: ".$ListOfficePhone."<br>
                                    E-Mail: ".$ListOfficeEmail."<br>
                                ";
                                    
                            ?>
                        </div>
                    </div>                
                </div>
                <div id="thumbs" class="navigation other">
                    <ul class="thumbs noscript">
                        <?php
                            function getSlideshow($photos) {
                                if (count($photos) > 0) {
                                    for ($i = 0; $i < count($photos); $i++) {
                                    $encodedPhoto300 = base64_encode($photos[$i]['Uri300']);
                                    $encodedPhoto640 = base64_encode($photos[$i]['Uri640']);
                                    echo "
                                        <li>
                                            <a class='thumb' href='https://www.susanhorak.com/proxy.php?url=".$encodedPhoto640."' title='".$i."'>
                                                <img src='https://www.susanhorak.com/proxy.php?url=".$encodedPhoto300."' alt='".$i."' />
                                            </a>
                                        </li>";
                                    }
                                }
                                else {
                                    echo "
                                        <li>
                                            <a class='thumb' href='https://www.susanhorak.com/proxy.php?url=".$encodedPhoto640."' title='".$i."'>
                                                <img src='../images/no-idx-photo.svgz' alt='".$i."' />
                                            </a>
                                        </li>";        
                                }
                            }
                            getSlideshow($photos);            
                        ?>
                    </ul>
                    <div style="clear: both;"></div>
                </div>
                <div style="clear: both;"></div>
                <div id="address">
                    <?php echo $addressFormatted; ?> - 
                    <?php echo "$".$ListPriceFormatted; ?>
                </div>
                <div id="copy">
                    <?php
                        if (isset($ourListing)) {
                            if(isset($adcopy)) {
                                echo $adcopy;
                                echo "<p class='directions'>Directions: ".$Directions."</p>";
                            }
                            else {
                                echo "<p>".$PublicRemarks."</p>";
                                echo "<p class='directions'>Directions: ".$Directions."</p>";    
                            }
                        }
                        else {                    
                            echo "
                                <p>".$PublicRemarks."</p>
                                <p class='directions'>Directions: ".$Directions."</p>
                            ";

                        }
                    ?>

                </div>
                <?php
                        function checkDocs($documents, $ourListing) {
                            echo "<div class='content-boxes";
                            if (empty($ourListing)) {
                                echo " not-ours-content-boxes";    
                            }
                            echo "' id='docs-box'>";
                                if (count($documents) > 7) {
                                    echo "<button type='button' class='doc-advance-button' id='doc-prev'></button>"; 
                                    echo "<button type='button' class='doc-advance-button' id='doc-next'></button>";
                                }
                                if (count($documents) > 0) {
                                    echo "<div id='doc-page-1' class='doc-page'>";
                                        for ($i = 0; $i <= 7; $i++) {
                                            if (!empty($documents[$i]['Uri'])) {
                                                echo "
                                                    <div class='doc-holder'>
                                                        <div class='doc-image'>
                                                            <a href='".$documents[$i]['Uri']."' target='_blank'><img src='../images/pdf-icon.png' alt='document'></a><div style='clear:both;'></div>
                                                            <a href='".$documents[$i]['Uri']."' target='_blank'>";
                                                            if (strlen($documents[$i]['Name']) > 15) {
                                                                echo substr($documents[$i]['Name'], 0, 15)."...";    
                                                            }
                                                            else{
                                                                echo $documents[$i]['Name']."...";    
                                                            }

                                                    echo "</a>
                                                        </div>
                                                    </div>    
                                                ";
                                            }
                                        }
                                    echo "</div>";
                                        if (count($documents) > 7) {
                                            echo "<div id='doc-page-2' class='doc-page'>";
                                                for ($i = 8; $i < count($documents); $i++) {
                                                    echo "
                                                        <div class='doc-holder'>
                                                            <div class='doc-image'>
                                                                <a href='".$documents[$i]['Uri']."' target='_blank'><img src='../images/pdf-icon.png' alt='document'></a><div style='clear:both;'></div>
                                                                <a href='".$documents[$i]['Uri']."' target='_blank'>";
                                                                if (strlen($documents[$i]['Name']) > 15) {
                                                                    echo substr($documents[$i]['Name'], 0, 15)."...";    
                                                                }
                                                                else{
                                                                    echo $documents[$i]['Name']."...";    
                                                                }

                                                    echo "</a>
                                                            </div>
                                                        </div>    
                                                    ";        
                                                }
                                            echo "</div>";
                                        }

                                }
                                else {
                                    echo "
                                        <div class='no-doc-holder'>
                                            <div class='no-docs'>
                                                Documents for this property are currently unavailable.
                                            </div>
                                        </div>    
                                    ";    
                                }
                            echo "</div>";
                        }
                        function checkFP($ListingId) {
                            $floorplan_large = "../listings/".$ListingId."/floorplan_large.png";
                            $floorplan = "../listings/".$ListingId."/floorplan.png";
                            if (file_exists($floorplan_large) && file_exists($floorplan)){
                                echo "
                                    <a href='../listings/".$ListingId."/floorplan'><img src='../listings/".$ListingId."/floorplan.png' alt='floorplan' /></a><br/>
                                    Click the image above to explore the floorplan with our interactive app!
                                ";    
                            }
                            
                            else if (file_exists($floorplan)){
                                echo "
                                    <img src='../listings/".$ListingId."/floorplan.png' alt='floorplan' /></a><br/>";    
                            }
                            else {
                                echo "We are currently working on the floorplan, and should have it up shortly!";    
                            }
                        }
                        if (isset($ourListing)) {
                            echo "
                                <div id='media-box'>
                                    <div id='media-content' class='media-container'>
                                        <div class='content-boxes' id='fp-box'>";                                    
                                        checkFP($ListingId);
                            echo "
                                        </div>";                                   

                                        checkDocs($documents, $ourListing);
                            echo "

                                        <div class='content-boxes' id='loan-box'>
                                            <div id='loan-position'>
                                                <a href='https://www.landmarkbank.com/SusanHorak'><div id='loantext'>Head to Landmark Bank's website to apply for a loan today!</div><br/></a>
                                                <a href='https://www.landmarkbank.com/SusanHorak'><img src='../images/395x83-Columbia.jpg' alt='Apply for a home loan online' /></a>
                                            </div>                        
                                        </div>
                                    </div>
                                    <div id='media-control' class='media-container'>
                                        <div id='fp-button' class='media-button'>
                                            <div class='selected-button interior-of-button'></div>
                                            <div class='button-text interior-of-button'>Floorplan</div>
                                        </div>
                                        <div id='docs-button' class='media-button'>
                                            <div class='selected-button interior-of-button'></div>
                                            <div class='button-text interior-of-button'>Documents</div>                                        
                                        </div>
                                        <div id='loan-button' class='media-button'>
                                            <div class='selected-button interior-of-button'></div>
                                            <div class='button-text interior-of-button'>Apply for a Loan</div>                                        
                                        </div>
                                    </div>
                                </div>
                            ";
                        }
                        else {
                            echo "
                                <div id='media-box'>
                                    <div id='media-content' class='media-container'>";
                                        checkDocs($documents, $ourListing);
                            echo "

                                    </div>                                
                                </div>
                            ";
                        }
                ?>
                <div id='contact-form-container'>
                    <form id="contact-us" method="POST" action="contact-us.php">
                        <fieldset >
                            <legend>Interested in this property?  Let us know!</legend>
                            <ol id="info">
                                <li>
                                    <label for="name">Name</label>
                                    <input id="name" name="name" placeholder="First and Last Name" required>
                                </li>
                                <li>
                                    <label for="email">Email</label>
                                    <input id="email" name="email" placeholder="example@domain.com" required>
                                </li>
                                <li>
                                    <label for="phone">Phone</label>
                                    <input id="phone" name="phone" placeholder="xxx-xxx-xxxx" required>
                                </li>                        
                            </ol>
                            <textarea id="message" name="message" rows="3" required>Please send me more information about <?php echo $StreetNumber." ".$StreetName." ".$StreetSuffix; ?>.</textarea>
                            <button id="send" type="submit">Send</button>
                            <div id="interested"></div>
                            <div id="are-you-human">
                                <img id="captcha" src="../securimage/securimage_show.php" alt="CAPTCHA Image" />
                                <input type="text" name="captcha_code" size="10" maxlength="6" />
                                <a href="#" onclick="document.getElementById('captcha').src = '../securimage/securimage_show.php?' + Math.random(); return false">[ Different Image ]</a>
                            </div>
                        </fieldset>                
                    </form>
                </div>
                <div id="payment-calc-container">
                    <div id="monpay-result">
                        <div id='monpay-fluff' class="fluff">Want to get an idea of what your monthly payment will be?  Fill out the fields below and click submit!</div>
                    </div>
                    <div id="monpay-form">
                        <form id="monpay-calc" name="monpay_calc">
                            <label class="budget-label">Home Price</label><input type="text" value="<?php echo "$".$ListPriceFormatted; ?>" class="homeprice budget-input" id="mp-home-price" name="mp_home_price" readonly>
                            <label class="budget-label" for="downpayment-amount">Down Payment in dollars</label><input type="text" placeholder="20% is assumed if left blank" name="downpayment_slider" id="downpayment-amount" class="budget-input">
                            <label class="budget-label">Program/Rate</label>
                                <select name="mp_prog_rate" id="mp-prog-rate" class="budget-input-drop">
                                    <option value="30">30 year fixed</option>
                                    <option value="15">15 year fixed</option>
                                    <option value="5">5 year ARM</option>
                                </select>
                            <label class="budget-label">Property Taxes</label><input type="text" value="1.2%" class="budget-input" id="mp-prop-taxes" name="mp_prop_taxes">
                            <label class="budget-label">Homeowners Ins.</label><input type="text" value="$800/yr" class="budget-input" id="mp-hom-insur" name="mp_hom_insur">
                            <label class="budget-label">HOA dues</label><input type="text" value="$0/mo" class="budget-input" id="mp-hoa-dues" name="mp_hoa_dues">
                            <input type="submit" name="mp_result" class="submit-button" id="mp-result" value="Submit">    
                        </form>
                        <div id="zillow-text">Mortgage data provided by</div>
                        <div id="zillow-image"><a href="http://www.zillow.com"><img src="https://www.zillow.com/widgets/GetVersionedResource.htm?path=/static/logos/zmm_logo_white.gif" alt="Zillow Real Estate Search" /></a></div>
                    </div>
                    <div id="breakdown-graph">

                    </div>
                    <div id="amort-graph">

                    </div>
                </div>
            <?php 
                if (isset($ourListing)) {
                    echo "
                        <div id='brochure-video-container'>
                            <div id='brochure' class='brochure-video-content'>
                                ".$issuuembed."
                            </div>
                            <div id='video' class='brochure-video-content'>
                                ".$videoembed."
                            </div>
                        </div>
                    ";
                }
                else { }
            ?>
            <?php
                echo "<h5 class='field-box left' id='general-detail-banner'>+ General Property Description</h5>";
                echo "<div id='top-detail-container' class='main-detail-container'>";
                for ($i = 0; $i <= count($cf); $i++) {          
                    if (isset($cf[$i])) {
                        $cfKey = key($cf[$i]);

                        if ($cfKey == "General Property Description") {
                            echo "

                                <div class='detail-container left'>             
                                    <table class='detail-table'>";                        
                                    for ($j = 0; $j < count($cf[$i][$cfKey]); $j++) { 
                                        echo "<tr class='detail-table-row'>";
                                        if (isset($cf[$i][$cfKey][$j])) {
                                            echo "<td class='detail-table-column left'>";
                                                echo key($cf[$i][$cfKey][$j]);
                                            echo "</td>";
                                            echo "<td class='detail-table-column right'>";
                                                echo current($cf[$i][$cfKey][$j]);
                                            echo "</td>";                                                             
                                        }
                                        echo "</tr>";
                                    }                        
                                echo "
                                </table> 
                            </div>";
                        }
                        else if ($cfKey == "Location Tax & Legal") {
                            echo "
                                <div class='detail-container right'>             
                                    <table class='detail-table'>";                        
                                    for ($j = 0; $j < count($cf[$i][$cfKey]); $j++) { 
                                        echo "<tr class='detail-table-row'>";
                                        if (isset($cf[$i][$cfKey][$j])) {
                                            echo "<td class='detail-table-column left'>";
                                                echo key($cf[$i][$cfKey][$j]);
                                            echo "</td>";
                                            echo "<td class='detail-table-column right'>";
                                                echo current($cf[$i][$cfKey][$j]);
                                            echo "</td>";                                                             
                                        }
                                        echo "</tr>";
                                    }                        
                                echo "
                                </table> 
                            </div>";            
                        }

                    }
                }
                echo "
                    </div>
                    <h5 class='field-box left' id='other-detail-banner'>+ Other Details</h5>
                    <div id='lower-detail-container' class='main-detail-container'>            
                ";
                echo "<div class='detail-container left'>";
                for ($i = 0; $i <= count($cf); $i++) {  
                    if (isset($cf[$i]) && $i <= 18) {
                        $cfKey = key($cf[$i]);
                        if ($cfKey != "General Property Description" && $cfKey != "Location Tax & Legal" && $cfKey != "Remarks & Misc" && $cfKey != "Rooms" && $cfKey != "Baths Full-3/4" && $cfKey != "Baths-Half") {
                            echo "        
                            <table class='detail-table'>";                        
                            echo "
                                <tr class='detail-table-row'>";
                                if (isset($cf[$i][$cfKey])) {
                                    echo "
                                    <td class='detail-table-column left'>".
                                        $cfKey
                                    ."</td>
                                    <td class='detail-table-column right'>";
                                    for ($j = 0; $j < count($cf[$i][$cfKey]); $j++) {
                                        echo key($cf[$i][$cfKey][$j])."; ";
                                    }
                                    echo "</td>";
                                }
                                echo "
                                </tr>
                            </table>";                             
                        }                
                    }            
                }
                echo "</div>";
                echo "<div class='detail-container right'>";
                for ($i = 0; $i <= count($cf); $i++) {            
                    if (isset($cf[$i]) && $i > 19) {
                        $cfKey = key($cf[$i]);
                        if ($cfKey != "General Property Description" && $cfKey != "Location Tax & Legal" && $cfKey != "Remarks & Misc" && $cfKey != "Rooms" && $cfKey != "Baths Full-3/4" && $cfKey != "Baths-Half") {
                            echo "        
                            <table class='detail-table'>";                        
                            echo "
                                <tr class='detail-table-row'>";
                                if (isset($cf[$i][$cfKey])) {
                                    echo "
                                    <td class='detail-table-column left'>".
                                        $cfKey
                                    ."</td>
                                    <td class='detail-table-column right'>";
                                    for ($j = 0; $j < count($cf[$i][$cfKey]); $j++) {
                                        echo key($cf[$i][$cfKey][$j])."; ";
                                    }
                                    echo "</td>";
                                }
                                echo "
                                </tr>
                            </table>";                             
                        } 
                    }              
                }
                echo "</div>"; 
                echo "</div>";
            ?>
            <div id="similar-homes-title">
                Similarly Priced Homes
            </div>
            <div id="similar-homes-container">
                <?php
                    function similarListings($api, $ListPrice, $ListingId) {

                        $lowerPrice = $ListPrice * .85 - 15000;
                        $upperPrice = $ListPrice * 1.15 + 23000;                
                        $similarLimit = 4;
                        $result = $api->GetListings(
                            array(
                                '_pagination' => 1,
                                '_limit' => $similarLimit,
                                '_page' => 1,
                                '_filter' => "PropertyType Eq 'A' And PropertySubType Eq 'Single Family' And ListingId Ne '" . $ListingId . "' And ListPrice Bt " . $lowerPrice . "," . $upperPrice,
                                '_expand' => "Photos",
                                '_select' => "BathsFull,BathsHalf,BedsTotal,ListPrice,ListingId,StreetName,StreetNumber,StreetSuffix,StreetDirPrefix,StreetAdditionalInfo,ListAgentLastName"
                              )
                        );
                        $resultIsOurs = $api->GetMyListings(
                            array(
                                '_pagination' => 1,
                                '_limit' => $similarLimit,
                                '_page' => 1,
                                '_filter' => "PropertyType Eq 'A' And PropertySubType Eq 'Single Family' And ListingId Ne '" . $ListingId . "' And ListPrice Bt " . $lowerPrice . "," . $upperPrice,
                                '_expand' => "Photos",
                                '_select' => "BathsFull,BathsHalf,BedsTotal,ListPrice,ListingId,StreetName,StreetNumber,StreetSuffix,StreetDirPrefix,StreetAdditionalInfo,ListAgentLastName"
                              )
                        );
                        $combinedResult = array();
                            
                        if (!empty($resultIsOurs)) {
                            foreach ($resultIsOurs as $key => $listing) {
                                $combinedResult[] = $listing;                   
                            }    
                        }
                        if (count($combinedResult) < $similarLimit) {
                            foreach ($result as $key => $listing) {
                                $combinedResult[] = $listing;
                                if (count($combinedResult) >= $similarLimit) {
                                    break;    
                                }
                            }
                        }


                        foreach ($combinedResult as $key => $listing) {
                            $sf = $listing['StandardFields'];
                            $photos = $sf['Photos'];
                            $encodedFront = base64_encode($photos[0]['Uri300']);                  

                            echo "
                                <div class='similar-home"; 
                                if ($sf['ListAgentLastName'] == "HORAK") { 
                                    echo " result-is-ours"; 
                                } 
                            echo "'>
                                    <div class='similar-img'>";
                            if (count($photos) > 0) {
                                echo "<a href='https://www.susanhorak.com/area-listings/idx.php?mls=".$sf['ListingId']."'><img src='https://www.susanhorak.com/proxy.php?url=".$encodedFront."' alt='front photo'></a>";
                            }
                            else {
                                echo "<a href='https://www.susanhorak.com/area-listing/idx.php?mls=".$sf['ListingId']."'><img src='../images/no-idx-photo.svgz' alt='front photo'></a>";    
                            }
                            if ($sf['ListAgentLastName'] == "HORAK") {
                                echo "
                                    <div id='our-listing-banner'>
                                        Our listing!    
                                    </div>
                                ";    
                            }
                            echo "
                                </div>
                                    <div class='similar-details'>
                                        <span class='address'>".$sf['StreetNumber'];
                                        if (!empty($sf['StreetDirPrefix'])) {
                                            echo " ".$sf['StreetDirPrefix']." ";
                                        }
                            echo " ".ucwords(strtolower($sf['StreetName']))." ".ucwords(strtolower($sf['StreetSuffix']));
                                        if (!empty($sf['StreetAdditionalInfo'])) {
                                            echo $sf['StreetAdditionalInfo'];
                                        }
                            echo "</span><br />
                                        $".number_format($sf['ListPrice'])."<br />".
                                        $sf['BedsTotal']." BR //";
                                        if ($sf['BathsHalf'] > 0) {
                                            echo $sf['BathsFull']." Full BA // ".$sf['BathsHalf']." Half BA";        
                                        } 
                                        else {
                                            echo $sf['BathsFull']." Full BA";
                                        }
                                    echo "
                                    </div>
                                </div> 
                            ";                
                        }                

                    }
                    similarListings($api, $ListPrice, $ListingId);
                ?>
            </div>    
            <div id="copyright-contain">
                <p>Copyright &copy; <?php echo date("Y");?>, The Susan Horak Group, RE/MAX Boone Realty.  The #1 Realtor at the #1 Real Estate company in Columbia, MO.</p>
            </div>
        </div>
        <script>
            (function() {
                function getScript(url,success){
                    var script=document.createElement('script');
                    script.src=url;
                    var head=document.getElementsByTagName('head')[0],
                        done=false;
                    script.onload=script.onreadystatechange = function(){
                      if ( !done && (!this.readyState || this.readyState == 'loaded' || this.readyState == 'complete') ) {
                        done=true;
                        success();
                        script.onload = script.onreadystatechange = null;
                        head.removeChild(script);
                      }
                    };
                    head.appendChild(script);
                }
                getScript('../js/idx-listing-master-min.js',function(){          
                    ;(function($) {
                        var defaults = {
                            mouseOutOpacity:   0.67,
                            mouseOverOpacity:  1.0,
                            fadeSpeed:         'fast',
                            exemptionSelector: '.selected'
                        };

                        $.fn.opacityrollover = function(settings) {
                            // Initialize the effect
                            $.extend(this, defaults, settings);

                            var config = this;

                            function animateMe(opacity, element) {
                                var $target = $(element);

                                if (config.exemptionSelector)
                                    $target = $target.not(config.exemptionSelector);	

                                $target.animate({ opacity: opacity }, config.fadeSpeed);
                            }

                            this.css('opacity', this.mouseOutOpacity)
                                .hover(
                                    function() {
                                        animateMe(config.mouseOverOpacity, this);
                                    },
                                    function() {
                                        animateMe(config.mouseOutOpacity, this);
                                    });

                            return this;
                        };
                    })(jQuery);
                    jQuery(document).ready(function($) {
                        $('div.navigation').css({'width' : '89%', 'margin' : '0 auto', 'padding-left' : '10px'});
                        $('div.content').css({'width' : '87.5%' ,'display' : 'block'});

                        var onMouseOutOpacity = 0.67;
                        $('#thumbs ul.thumbs li').opacityrollover({
                            mouseOutOpacity:   onMouseOutOpacity,
                            mouseOverOpacity:  1.0,
                            fadeSpeed:         'fast',
                            exemptionSelector: '.selected'
                        });

                        var gallery = $('#thumbs').galleriffic({
                            delay:                     2500,
                            numThumbs:                 8,
                            preloadAhead:              16,
                            enableTopPager:            true,
                            enableBottomPager:         true,
                            maxPagesToShow:            7,
                            imageContainerSel:         '#slideshow',
                            controlsContainerSel:      '#controls',
                            captionContainerSel:       '#caption',
                            loadingContainerSel:       '#loading',
                            renderSSControls:          false,
                            renderNavControls:         false,
                            nextPageLinkText:          'Next &rsaquo;',
                            prevPageLinkText:          '&lsaquo; Prev',
                            enableHistory:             false,
                            enableKeyboardNavigation:  false,
                            autoStart:                 false,
                            syncTransitions:           true,
                            defaultTransitionDuration: 600,
                            onSlideChangeOut:             function(prevIndex) {
                                this.find('ul.thumbs').children()
                                    .eq(prevIndex).fadeTo('fast', onMouseOutOpacity);
                            },
                            onSlideChangeIn:              function(nextIndex) {
                                this.find('ul.thumbs').children()
                                    .eq(nextIndex).fadeTo('fast', 1.0);
                            },
                            onPageTransitionOut:       function(callback) {
                                this.fadeTo('fast', 0.0, callback);
                            },
                            onPageTransitionIn:        function() {
                                this.fadeTo('fast', 1.0);
                            }
                        });
                    });    

                    $(document).ready(function() {
                        $('#general-detail-banner').click(function() {
                            $('#top-detail-container').toggle("fast");
                        });
                        $('#other-detail-banner').click(function() {
                            $('#lower-detail-container').toggle("fast");
                        });
                    });
                    $(document).ready(function() {
                        $( 'a.m-viewer' ).swipebox({
                            loopAtEnd: true,
                        });
                        $( 'a.d-viewer' ).swipebox({
                            loopAtEnd: true,
                        });
                    });
                    $(document).ready(function() {
                        $('.media-button').click(function() {
                            $('.selected-button',this).css({
                                'opacity' : 1,
                                '-webkit-transform' : 'scale(1.05)',
                                '-moz-transform' : 'scale(1.05)',
                                '-ms-transform' : 'scale(1.05)',
                                '-o-transform' : 'scale(1.05)',
                                'transform' : 'scale(1.05)'
                            }); 
                            $(this).siblings().children('.selected-button').css({
                                'opacity' : 0,
                                '-webkit-transform' : 'scale(.95)',
                                '-moz-transform' : 'scale(.95)',
                                '-ms-transform' : 'scale(.95)',
                                '-o-transform' : 'scale(.95)',
                                'transform' : 'scale(.95)'
                            });
                        });
                        $('#fp-button').click(function() {
                            $('#fp-box').css({
                                'opacity' : 1,
                                'z-index' : 2
                            });
                            $('#fp-box').siblings().css({
                                'opacity' : 0,
                                'z-index' : 1
                            });
                        });
                        $('#docs-button').click(function() {
                            $('#docs-box').css({
                                'opacity' : 1,
                                'z-index' : 2
                            });
                            $('#docs-box').siblings().css({
                                'opacity' : 0,
                                'z-index' : 1
                            });    
                        });
                        $('#loan-button').click(function() {
                            $('#loan-box').css({
                                'opacity' : 1,
                                'z-index' : 2
                            });
                            $('#loan-box').siblings().css({
                                'opacity' : 0,
                                'z-index' : 1
                            });    
                        });
                        $('#doc-page-2').hide();
                        $('#doc-next').click(function() {
                            $('#doc-page-1').toggle("fast");
                            $('#doc-page-2').toggle("fast");
                        });
                        $('#doc-prev').click(function() {
                            $('#doc-page-2').toggle("fast");
                            $('#doc-page-1').toggle("fast");
                        });
                    });                
                });
            })();
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAd1rhsRqRvGjYzwc46p-0Fnwt4lv840Lo&amp"></script>
        <script>
            function initialize() {
                "use strict";
                var myLatlng, mapOptions, map, marker;
                myLatlng = new google.maps.LatLng(<?php echo $Latitude; ?>, <?php echo $Longitude; ?>);
                mapOptions = {
                    zoom: 12,
                    center: myLatlng
                };
                map = new google.maps.Map(document.getElementById('map-container'), mapOptions);
                marker = new google.maps.Marker({
                    position: myLatlng,
                    map: map,
                    title: "<?php echo $StreetNumber." ".$StreetName." ".$StreetSuffix; ?>"
                });
            }
        </script>
        <script>
            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', 'UA-30319008-1']);
            _gaq.push(['_trackPageview']);

            (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
            })();
        </script>
        <script>var switchTo5x=true;</script>
        <script src="https://ws.sharethis.com/button/buttons.js"></script>
        <script>stLight.options({publisher: "8d1940fa-ae86-45d0-ba21-e668c9fee77c", doNotHash: false, doNotCopy: false, hashAddressBar: false});</script>   
        <!-- Start of Async HubSpot Analytics Code --> 
          <script type="text/javascript"> 
            (function(d,s,i,r) { 
              if (d.getElementById(i)){return;} 
              var n=d.createElement(s),e=d.getElementsByTagName(s)[0]; 
              n.id=i;n.src='//js.hs-analytics.net/analytics/'+(Math.ceil(new Date()/r)*r)+'/2315717.js'; 
              e.parentNode.insertBefore(n, e); 
            })(document,"script","hs-analytics",300000); 
          </script> 
        <!-- End of Async HubSpot Analytics Code -->

        </body>
        </html>
<?php
    }
?>