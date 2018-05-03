<?php

// Pull Variables File
require('./src/vars.php');

try {
    // PDO MYSQL Connection Params
    $host = '127.0.0.1';
    $db   = 'NEWNEW';
    $user = 'root';
    $pass = '';
    $charset = 'utf8';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $opt = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $opt);

    // Run create table for workspace from SPE DB export
    $workspace_create = $pdo->prepare($workspace);
    $workspace_create->execute();

    // SET PRIMARY KEY
    $set_primary = $pdo->prepare($set_pk);
    $set_primary->execute();

    // Remove unneeded Attributes for Variants
    $correct_variants = $pdo->prepare($variant_attribs);
    $correct_variants->execute();

    // Remove unneeded Attributes for Variants
    $convert_columns = $pdo->prepare($char_text);
    $convert_columns->execute();

    // Run SQL Query to pull Attributes for Parent Product
    $product_stmt = $pdo->query($products);

    // Loop through Parent Product Results
    while ($p = $product_stmt->fetch(PDO::FETCH_ASSOC)) {
        $aid = $p['aid'];
        $mid = $p['mid'];
        $add_img = $p['add_img'];
        $handle = to_prety_url($p['Handle']);
        $description = str_replace('"', '', str_replace('\'', '', preg_replace('/<[^>]*>/', '',strip_tags($p['Body (HTML)']))));

        if ($mid == 0) {
            print "Parent ID : ".$aid."\n";
            $url = "http://t172.dev.smartphoneexperts.com/images/product_images/accessories/large/$aid.jpg";
            if (isset($add_img) && !empty($add_img)) {
                $url2 = "http://t172.dev.smartphoneexperts.com/images/product_images/accessories/additional_images/$aid/large/$add_img";
            }
            else {
                $url2 = '';
            }
        } else {
            print "Child ID : ".$p['aid']."[".$p['mid']."]\n";
            $url = "http://t172.dev.smartphoneexperts.com/images/product_images/accessories/additional_images/$mid/large/$add_img";
        }

        $url_headers=get_headers($url, 1);

        if (isset($url_headers['Content-Type'])) {
            $type=strtolower($url_headers['Content-Type']);

            $valid_image_type=array();
            $valid_image_type['image/png']='';
            $valid_image_type['image/jpg']='';
            $valid_image_type['image/jpeg']='';
            $valid_image_type['image/jpe']='';
            $valid_image_type['image/gif']='';
            $valid_image_type['image/tif']='';
            $valid_image_type['image/tiff']='';
            $valid_image_type['image/svg']='';
            $valid_image_type['image/ico']='';
            $valid_image_type['image/icon']='';
            $valid_image_type['image/x-icon']='';

            if (isset($valid_image_type[$type])) {

                // Check Master ID to see if the Product is a Patent or Variant
                if ($mid == 0) {
                    print "Parent Image : ".$url."\n";
                    $stmt = "UPDATE shopify_import SET `Image Src` = :url, `Variant Image` = :url2, `Handle` = :handle, `Body (HTML)` = :description WHERE aid = :aid";
                    $sql = $pdo->prepare($stmt);
                    $sql->bindParam(':handle', $handle, PDO::PARAM_STR);
                    $sql->bindParam(':url', $url, PDO::PARAM_LOB);
                    $sql->bindParam(':url2', $url2, PDO::PARAM_LOB);
                    $sql->bindParam(':description', $description, PDO::PARAM_LOB);
                    $sql->bindParam(':aid', $aid, PDO::PARAM_STR);
                    $sql->execute();
                } else {
                    print "Child Image : ".$url."\n";
                    $stmt = "UPDATE shopify_import SET `Handle` = :handle, `Image Alt Text` = '', `SEO Title` = '', `SEO Description` ='', `Variant Image` = :url, `Option1 Name` = '' WHERE aid = :aid";
                    $sql = $pdo->prepare($stmt);
                    $sql->bindParam(':handle', $handle, PDO::PARAM_STR);
                    $sql->bindParam(':url', $url, PDO::PARAM_STR);
                    $sql->bindParam(':aid', $aid, PDO::PARAM_STR);
                    $sql->execute();
                }
            } else {
                print "No Image : ".$url."\n";
            }
        }
    }

    print "Replace Dev with Production URLs for Images\n";
    // Replace Dev with Production URLs for Images
    $prod_imgs = $pdo->prepare($production_img);
    $prod_imgs->execute();

    print "Clean Options Name if Options Value is empty\n";
    // Clean Options Name if Options Value is empty
    $options = $pdo->prepare($clean_options);
    $options->execute();

    print "Create Table with only the required fields\n";
    // Create Table with only the required fields
    $final = $pdo->prepare($final_draft);
    $final->execute();

} catch (PDOException $ex) {
    die(json_encode(array('outcome' => false, 'message' => 'Unable to connect.'.$ex)));
}


//friendly URL conversion
function to_prety_url($str){
    if($str !== mb_convert_encoding( mb_convert_encoding($str, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32') )
        $str = mb_convert_encoding($str, 'UTF-8', mb_detect_encoding($str));
    $str = htmlentities($str, ENT_NOQUOTES, 'UTF-8');
    $str = preg_replace('`&([a-z]{1,2})(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig);`i', '\1', $str);
    $str = html_entity_decode($str, ENT_NOQUOTES, 'UTF-8');
    $str = preg_replace(array('`[^a-z0-9]`i','`[-]+`'), '-', $str);
    $str = strtolower( trim($str, '-') );
    return $str;
}
