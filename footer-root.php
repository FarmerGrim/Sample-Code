<?php
// the footer to be included on all our pages, excluding the individual listing pages. 
date_default_timezone_set("America/Chicago");
function newestListingsRoot() {
    $db = new mysqli("lin-mysql19.hostmanagement.net", "u1082926_susanho", "shorak2146S", "db1082926_susanhorak");
    $stmt = $db->prepare("SELECT beds, baths, halfbath, mls, address, sold, uc, displayprice FROM listings WHERE uc != 1 AND sold != 1 ORDER BY mls DESC LIMIT 3");
    $stmt->execute();

    $stmt->store_result();
    $num_of_rows = $stmt->num_rows;
    
    $stmt->bind_result($beds, $baths, $halfbath, $mls, $address, $sold, $uc, $displayprice);
    
    if ($num_of_rows > 0) {
        while($stmt->fetch()) {
            echo "<div class='newlist-contain'>
                      <div class='newlist-img'>
                          <a href='area-listings/idx.php?mls=".$mls."'><img src='listings/{$mls}/132/0.jpg' alt='{$address}' /></a>";
                          if ($uc  == "1") {
                                echo "
                                    <div class='newlist-uc'>
                                        Under Contract
                                    </div>";
                          }
            echo "
                      </div>
                      <div class='newlist-desc'>
                          <span><a href='area-listings/idx.php?mls=".$mls."'>{$address}</a><br /></span>
                          <span>{$displayprice}<br/></span>
                          <span>{$beds} beds / ";
                            if ($halfbath == 0) {
                                echo "{$baths} full baths<br/></span>";    
                            }
                            else if ($halfbath != 0) {
                                echo "{$baths} full baths / {$halfbath} half baths<br></span>";
                            }
            echo "
                      </div>
                  </div>    
            ";    

        }
    }
    $stmt->free_result();
    $stmt->close();
    $db->close();
}
?>
<div id="footer">
        <div id="new-listings">
            <div class="footer-content">
                <div class="footer-title" style="margin-bottom:15px;">Our Newest Listings</div>
                <?php		
                    newestListingsRoot();
                ?>
            </div>
        </div>
        <div id="links">
            <div class="footer-content">
                <div class="footer-title">Most Popular</div>
                <ul class="footer-list">
                    <li class="foot">
                        <a href="rentals/">&#9733; Rental Availability</a>
                    <li class="foot">
                        <a href="about-rentals/">&#9733; About Our Rentals</a>
                    <li class="foot">
                        <a href="listings/">&#9733; Residential Listings</a>
                    <li class="foot">
                        <a href="subdivisions/">&#9733; Subdivisions</a>
                    <li class="foot">
                        <a href="open-houses/">&#9733; Open Houses</a>
                    <li class="foot">
                        <a href="investment/">&#9733; Investment Listings</a>
                    <li class="foot">
                        <a href="about-columbia/">&#9733; About Columbia</a>    
                </ul>
            </div>
        </div>
        <div id="contact-footer">
            <div class="footer-content">
                <div class="footer-title">Contact Us</div>
                <ul class="footer-list">
                    <li class="foot">
                        <a href="tel://1-573-876-2849">&#9733; Office: (573) 876-2849</a>
                    <li class="foot">
                        <a href="tel://1-573-864-0160">&#9733; Cell: (573) 864-0160</a>
                    <li class="foot">
                        <a href="">&#9733; Fax: (573) 447-0155</a>
                    <li class="foot">
                        <a href="mailto:susan@susanhorak.com">&#9733; Email: susan@susanhorak.com</a> 
                </ul>                
                <div id="social-footer">
                    <a href="http://www.facebook.com/susanhorakgroup"><img src="images/sm-facebook.png" alt="Follow us on Facebook" /></a>
                    <a href="https://twitter.com/SusanHorak"><img src="images/sm-twitter.png" alt="Follow us on Twitter" /></a>
                    <a href="http://www.linkedin.com/in/susanhorak"><img src="images/sm-linkedin.png" alt="Follow us on LinkedIn" /></a>
                    <a href="https://plus.google.com/102699361142751448176"><img src="images/sm-googleplus.png" alt="Follow us on Google Plus" /></a>                      
                    <a href="http://pinterest.com/susanhorak/"><img src="images/sm-pintrest.png" alt="Follow us on Pinterest" /></a>
                    <a href="http://issuu.com/susanhorak"><img src="images/sm-issuu.png" alt="Follow us on Issuu" /></a>
                    <a href="http://susanhorak.blogspot.com/"><img src="images/sm-blogger.png" alt="Follow our blog" /></a>
                </div> 
            </div>
        </div>
        <div id="copy"> 
            Copyright &copy; <?php echo date("Y");?>, The Susan Horak Group.  Click <a href="mailto:graphics@susanhorak.com">HERE</a> to report an error.
        </div>
    </div>