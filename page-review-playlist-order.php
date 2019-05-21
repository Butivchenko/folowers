<?php
session_start();
$current_user = wp_get_current_user();
if (!isset($_SESSION['spotify_cartorder'])) {
    wp_redirect(get_settings('home'));
}
$id = $_REQUEST['uri'];
/* Spotify Application Client ID and Secret Key */
$client_id = '';
$client_secret = '';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . base64_encode($client_id . ':' . $client_secret)));
$result = curl_exec($ch);
$json = json_decode($result, true);
/* Get Spotify Artist Photo */
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.spotify.com/v1/playlists/" . $id);
$headers = array('Accept: application/json', 'Content-type: application/json', 'Authorization: Bearer ' . $json['access_token']);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$response = curl_exec($ch);
curl_close($ch);
$getresult = json_decode($response, true);
if (isset($_SESSION['spotify_cartorder'])) {
    $_SESSION['spotify_cartorder']['user_id'] = $id;
    $_SESSION['spotify_cartorder']['name'] = stripslashes(trim($getresult['name']));
    $_SESSION['spotify_cartorder']['image_url'] = $getresult['images'][0]['url'];
    $_SESSION['spotify_cartorder']['artist_url'] = trim($getresult['external_urls']['spotify']);
}
if (isset($_POST['btnPayment'])) {
    $product_data = $wpdb->get_row("SELECT * FROM `ds_product` WHERE `quantity`='" . $_POST['p_followers'] . "' AND `category`='Playlist Followers' AND `show_status`='1'");
    $terms_array = array();
    $terms_array = $_SESSION['spotify_cartorder'];
    $terms_array['product_id'] = $product_data->product_id;
    $terms_array['quantity'] = $product_data->quantity;
    $terms_array['follower_total'] = $product_data->price;
    $terms_array['order_total'] = $product_data->price;
    $terms_array['category'] = $product_data->category;
    $terms_array['order_code'] = substr(strtoupper($terms_array['email']), 0, 2) . rand(111111, 999999);
    $terms_array['order_status'] = '1';
    $order_through = get_user_meta(1, 'api_order', TRUE);
    $table_order = $wpdb->prefix . "order";
    $wpdb->insert($table_order, array('searchname' => $terms_array['searchname'], 'useremail' => $terms_array['email'], 'product_id' => $terms_array['product_id'], 'category' => $terms_array['category'], 'quantity' => $terms_array['quantity'], 'follower_total' => $terms_array['follower_total'], 'order_total' => $terms_array['order_total'], 'order_code' => $terms_array['order_code'], 'userid' => $terms_array['user_id'], 'displayname' => $terms_array['name'], 'artist_url' => $terms_array['artist_url'], 'image_url' => $terms_array['image_url'], 'order_status' => $terms_array['order_status'], 'order_through' => $order_through, 'order_date' => date('Y-m-d H:i:s')));
    unset($_SESSION['spotify_cartorder']);
    $apiarray = array();
    if ($order_through == 'bulkfollows') {
        if ($terms_array['quantity'] >= 100 AND $terms_array['quantity'] < 1000001) {
            $apiarray[1]['service'] = '250';
            $apiarray[1]['link'] = $terms_array['artist_url'];
            $apiarray[1]['quantity'] = $terms_array['quantity'];
            $apiarray[1]['username'] = $terms_array['user_id'];
        }
    } else if ($order_through == 'pennypanel') {
        if ($terms_array['quantity'] > 70000) {
            $result = (int)($terms_array['quantity'] / 70000);
            $remain = $terms_array['quantity'] % 70000;
            if ($remain > 1) {
                $endlooop = $result + 1;
            } else {
                $endlooop = $result;
            }
            for ($l = 1; $l <= $endlooop; $l++) {
                if ($l < $endlooop) {
                    $quantity = 70000;
                } else {
                    $quantity = $remain;
                }
                $apiarray[$l]['service'] = '653';
                $apiarray[$l]['link'] = $terms_array['artist_url'];
                $apiarray[$l]['quantity'] = $quantity;
                $apiarray[$l]['username'] = $terms_array['user_id'];
            }
        } else if ($terms_array['quantity'] >= 500) {
            $apiarray[1]['service'] = '653';
            $apiarray[1]['link'] = $terms_array['artist_url'];
            $apiarray[1]['quantity'] = $terms_array['quantity'];
            $apiarray[1]['username'] = $terms_array['user_id'];
        }
    } else {
        if ($terms_array['quantity'] > 100000) {
            $result = (int)($terms_array['quantity'] / 100000);
            $remain = $terms_array['quantity'] % 100000;
            if ($remain > 1) {
                $endlooop = $result + 1;
            } else {
                $endlooop = $result;
            }
            for ($l = 1; $l <= $endlooop; $l++) {
                if ($l < $endlooop) {
                    $quantity = 100000;
                } else {
                    $quantity = $remain;
                }
                $apiarray[$l]['service'] = '390';
                $apiarray[$l]['link'] = $terms_array['artist_url'];
                $apiarray[$l]['quantity'] = $quantity;
                $apiarray[$l]['username'] = $terms_array['user_id'];
            }
        } else {
            $apiarray[1]['service'] = '390';
            $apiarray[1]['link'] = $terms_array['artist_url'];
            $apiarray[1]['quantity'] = $terms_array['quantity'];
            $apiarray[1]['username'] = $terms_array['user_id'];
        }
    }
    $_SESSION['spotify_Apiorder']['followers'] = $apiarray;
    $paypal = array();
    $paypal['item_name'] = $terms_array['quantity'] . ' P. F.';
    $paypal['order_total'] = $terms_array['order_total'];
    $paypal['order_id'] = $terms_array['order_code'];
    $_SESSION['spotify_orderPayPal'] = $paypal;
    wp_redirect(get_settings('home') . '/proceed-to-payment/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Basic Page Needs -->
    <meta charset="utf-8">
    <!--[if IE]>
    <meta http-equiv="x-ua-compatible" content="IE=9"/><![endif]-->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Spotistar | The best place to buy spotify promotion">
    <meta name="author" content="spotistar">
    <title>
        <?php
        $get_id = get_the_id();
        global $page, $paged;
        wp_title('|', true, 'right');
        /* Add the blog name.*/
        bloginfo('name');
        $site_description = get_bloginfo('description', 'display');
        if ($site_description && (is_home() || is_front_page()))
            echo " | $site_description";
        if ($paged >= 2 || $page >= 2)
            echo ' | ' . sprintf(__('Page %s', 'web-thai'), max($paged, $page));
        ?>
    </title>
    <!-- Bootstrap -->
    <link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/assets/css/bootstrap.css">
    <link rel="stylesheet" type="text/css"
          href="<?php bloginfo('template_url'); ?>/assets/fonts/font-awesome/css/font-awesome.css">
    <link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/assets/fonts/fonts/fonts.css">
    <!-- Stylesheet -->
    <link rel="stylesheet" type="text/css" href="<?php bloginfo('stylesheet_url'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.css">
    <link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/assets/css/responsive.css">
    <link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/assets/css/range-slider-new.css">
    <script type="text/javascript" src="<?php bloginfo('template_url'); ?>/assets/js/modernizr.custom.js"></script>
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// --><!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>    <![endif]-->
    <?php wp_head(); ?>
</head>
<body>
<header id="header">
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <div class="logo"><a href="<?php echo get_settings('home'); ?>"><img
                                src="<?php bloginfo('template_url'); ?>/assets/img/newlogo.png" alt="logo"></a></div>
            </div>
        </div>
    </div>
</header>

<div class="checkoutDiv2">
    <div class="container">
        <div class="row">
            <div class="col-md-12 ">
                <div class="step2">
                    <?php
                    if (have_posts()) :
                        while (have_posts()) : the_post();
                            ?>
                            <h2>
                                <?php the_title(); ?>
                            </h2>
                        <?php
                        endwhile;
                    endif;
                    ?>
                    <form name="buynow" id="buynow" method="post"
                          action="<?php echo get_settings('home'); ?>/get-playlist-followers/">
                        <input name="searchuser" id="user" placeholder="Spotify playlist*" type="text">
                        <input type="hidden" name="offset" id="offset" value="0">
                    </form>
                    <h3><?php echo stripslashes(ucfirst($getresult['name'])); ?><br>
                        <small><?php echo stripslashes($getresult['owner']['display_name']); ?></small>
                    </h3>
                    <div class="imgbox" align="center"><img src="<?php echo $getresult['images'][0]['url']; ?>"
                                                            style="height:300px;"></div>
                    <div class="full"> <!-- Custom Package -->
                        <form name="buyfrm" id="bfrm" method="post">
                            <div class="price_range">
                                <div class="left_pricerange">
                                    <h3><span class="followers">
                    <div class="noUi-tooltip">500</div>
                    Followers </span><span id="huge-value">4.00</span>$
                                    </h3>
                                </div>
                                <div class="mid">
                                    <div id="price-slider" class="noUi-target noUi-ltr noUi-horizontal"
                                         data-default-value="500"></div>
                                </div>
                                <p id="more-discount">Add <span>
                  <output>450</output>
                  followers</span> and <span>save 25%</span></p>
                                <div class="right_pricerange">
                                    <input type="hidden" name="p_followers" id="p_followers" value="100"/>
                                </div>
                            </div>
                            <button type="submit" name="btnPayment" id="pay" class="btn btn-Submit2">PROCEED TO
                                PAYMENT
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="reviews">
    <div class="container">
        <div class="section-title text-center center">
            <?php
            $qryreview = array('page_id' => 42);
            $review = new WP_QUERY($qryreview);
            while ($review->have_posts()):
                $review->the_post();
                ?>
                <h2>
                    <?php the_title(); ?>
                </h2>
                <?php the_content(); ?>
            <?php
            endwhile;
            ?>
            <div class="clearfix"></div>
        </div>
        <div class="row">
            <?php
            $qry_testimonials = array('post_type' => 'testimonials', 'orderby' => 'order', 'order' => 'asc', 'posts_per_page' => 3);
            $testimonials = new WP_QUERY($qry_testimonials);
            if ($testimonials->have_posts()):
                $count = 1;
                while ($testimonials->have_posts()):
                    $testimonials->the_post();
                    $rating = get_post_meta(get_the_ID(), 'rating', TRUE);
                    ?>
                    <div class="col-md-4 col-sm-4">
                        <div class="rewBox">
                            <div id="rateYo<?= $count ?>"></div>
                            <?php the_content(); ?>
                            <h3>-
                                <?php the_title(); ?>
                            </h3>
                        </div>
                    </div>
                    <?php
                    $count++;
                endwhile;
            endif;
            ?>
        </div>
        <div class="clearfix"></div>
    </div>
</div>
<!-- Contact Section -->
<div id="footer2">
    <div class="container">
        <div class="pull-left fnav">
            <p>Copyright © <?php echo date('Y'); ?> <a href="<?php echo get_settings('home'); ?>">Spotistar</a>. All
                right reserved.</p>
        </div>
    </div>
</div>
<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/assets/js/jquery.1.11.1.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/assets/js/bootstrap.js"></script>
<!-- Price Range JavaScript -->
<script src="<?php bloginfo('template_url'); ?>/assets/js/global.js"></script>
<script src="<?php bloginfo('template_url'); ?>/assets/js/noUISlider.js"></script>
<?php
$select_products = $wpdb->get_results("SELECT * FROM `ds_product` WHERE `category`='Playlist Followers' AND `show_status`='1' ORDER BY `quantity` ASC");
$totalproduct = $wpdb->num_rows;
$max = $totalproduct - 1;
$default = 0;
if (isset($_SESSION['spotify_cartorder']['product_id'])) {
    $counter = 0;
    foreach ($select_products as $productObj) {
        if ($productObj->product_id == $_SESSION['spotify_cartorder']['product_id']) {
            $default = $counter;
        }
        $counter++;
    }
}
?>
<script>
    bigValueSlider.noUiSlider.on('update', function (values, handle) {
        bigValueSpan.innerHTML = price[values[handle]];
        var newvalue = range[values[handle]];
        $("#p_followers").val(newvalue);
        document.getElementById('more-discount').innerHTML = discount[values[handle]];
        if (newvalue >= 10000) {
            var result = newvalue / 1000;
            var showvalue = result + 'k';
        } else {
            var showvalue = newvalue;
        }
        $('.noUi-tooltip').text(showvalue.toLocaleString());
    });

    var range = [
        <?php
        foreach ($select_products as $productObj) {
            echo "'" . $productObj->quantity . "',";
        }
        ?>
    ];

    var price = [
        <?php
        foreach ($select_products as $productObj) {
            echo "'" . number_format($productObj->price, 2, '.', '') . "',";
        }
        ?>
    ];

    var discount = [
        <?php
        foreach ($select_products as $productObj) {
            echo "'" . stripslashes($productObj->title) . "',";
        }
        ?>
    ];

    bigValueSlider.noUiSlider.on('update', function (values, handle) {
        bigValueSpan.innerHTML = price[values[handle]];
        var newvalue = range[values[handle]];
        $("#p_followers").val(newvalue);
        document.getElementById('more-discount').innerHTML = discount[values[handle]];
        if (newvalue >= 10000) {
            var result = newvalue / 1000;
            var showvalue = result + 'k';
        } else {
            var showvalue = newvalue;
        }
        $('.noUi-tooltip').text(showvalue.toLocaleString());
        if (newvalue >= 500) {
            document.getElementById("pay").disabled = false;
        } else {
            document.getElementById("pay").disabled = true;
        }
    });
</script>
<!-- /Price Range JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.js"></script>
<script>
    $(function () {
        $("#rateYo").rateYo({
            rating: 2,
            fullStar: true
        });
    });

    <?php
    $qry_testimonials = array('post_type' => 'testimonials', 'orderby' => 'order', 'order' => 'asc', 'posts_per_page' => 3);
    $testimonials = new WP_QUERY($qry_testimonials);
    if($testimonials->have_posts()):
    $count = 1;
    while($testimonials->have_posts()):
    $testimonials->the_post();
    $rating = get_post_meta(get_the_ID(), 'rating', TRUE);
    ?>
    $("#rateYo<?=$count?>").rateYo({
        rating: <?=$rating?>,
        fullStar: true,
        readOnly: true
    });
    <?php
    $count++;
    endwhile;
    endif;
    ?>
</script>
<script>
    function toggleIcon(e) {
        $(e.target)
            .prev('.panel-heading')
            .find(".more-less")
            .toggleClass('fa-chevron-down fa-chevron-up');
    }

    $('.panel-group').on('hidden.bs.collapse', toggleIcon);
    $('.panel-group').on('shown.bs.collapse', toggleIcon);
</script>
<?php wp_footer(); ?>
</body>
</html>
