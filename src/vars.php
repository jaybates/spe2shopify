<?php

  // Create Working Table for Import
    $workspace = "CREATE TABLE shopify_import
    SELECT
    products_accessories.a_id as aid,
    products_accessories.master_a_id_rel as mid,
    products_accessories.variance_additional_image as add_img,
    LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(CONCAT(TRIM(products_accessories.name_front), ' ', products_accessories.a_id), ':', ''), ')', ''), '(', ''), ',', ''), '\/', ''), '\"', ''), '?', ''), '\'', ''), '&', ''), '!', ''), '.', ''), ' ', '-'), '--', '-'), '--', '-')) as Handle,
    products_accessories.name_front as Title,
    products_accessories.description 'Body (HTML)',
    products_manufacturers.name as Vendor,
    GROUP_CONCAT(DISTINCT category.category_name) as Type,
    GROUP_CONCAT(DISTINCT device_models.device_model) as Tags,
    'TRUE' as Published,
    'Option' as 'Option1 Name',
    products_accessories.variance as 'Option1 Value',
    '' as 'Option2 Name',
    '' as 'Option2 Value',
    '' as 'Option3 Name',
    '' as 'Option3 Value',
    products_accessories.sku as 'Variant SKU',
    products_accessories.weight as 'Variant Grams',
    '' as 'Variant Inventory Tracker',
    '' as 'Varieant Inventory Policy',
    'manual' as 'Variant Fulfillment Service',
    products_accessories.price as 'Variant Price',
    products_accessories.msrp as 'Variant Compare At Price',
    'TRUE' as 'Variant Requires Shipping',
    'TRUE' as 'Variant Taxable',
    '' as 'Variant Barcode',
    '' as 'Image Src',
    '' as 'Image Position',
    products_accessories.name_front as 'Image Alt Text',
    '' as 'Gift Card',
    '' as 'Google Shopping / MPN',
    '' as 'Google Shopping / Age Group',
    '' as 'Google Shopping / Gender',
    '' as 'Google Shopping / Google Product Category',
    products_accessories.name_front as 'SEO Title',
    products_accessories.summary as 'SEO Description',
    '' as 'Google Shopping / AdWords Grouping',
    '' as 'Google Shopping / AdWords Labels',
    '' as 'Google Shopping / Condition',
    '' as 'Google Shopping / Custom Product',
    '' as 'Google Shopping / Custom Label 0',
    '' as 'Google Shopping / Custom Label 1',
    '' as 'Google Shopping / Custom Label 2',
    '' as 'Google Shopping / Custom Label 3',
    '' as 'Google Shopping / Custom Label 4',
    '' as 'Variant Image',
    'lb' as 'Variant Weight Unit'
    FROM
    products_accessories
    LEFT JOIN products_manufacturers
    ON products_manufacturers.manufacturer_id = products_accessories.manufacturer_id_rel
    LEFT JOIN category_product
    ON products_accessories.a_id = category_product.a_id_rel
    LEFT JOIN category
    ON category_product.category_id_rel = category.category_id
    LEFT JOIN category_products_cmpt
    ON products_accessories.a_id = category_products_cmpt.a_id_rel
    LEFT JOIN device_models
    ON device_models.device_id = category_products_cmpt.model
    WHERE products_accessories.discountinued = 0 AND products_accessories.a_id < 100000 AND products_accessories.name_front is not null AND TRIM(products_accessories.name_front) <> ''
    GROUP BY products_accessories.a_id";

  //SET PRIMART
  $set_pk = "ALTER TABLE shopify_import ADD PRIMARY KEY (aid);";

  // Correcting Variant Attributes
    $variant_attribs = "UPDATE shopify_import child, (SELECT DISTINCT handle, aid, mid
    FROM shopify_import
    WHERE handle IS NOT NULL AND handle != '') parent
    SET child.handle = parent.handle, Title = '', `Body (HTML)` = '', Vendor = '', Type = '', Tags = '', Published = '', `Option1 Name` = 'Option'
    WHERE child.mid = parent.aid
    AND child.mid != 0";

  // Correct Column Types
    $char_text = "ALTER TABLE shopify_import
    CHANGE COLUMN `Image Src` `Image Src` text,
    CHANGE COLUMN `Variant Image` `Variant Image` text";

  // Main SQL Query for Pulling Parent Product's Attributes
    $products = "SELECT aid, mid, add_img, `Title`, `Handle`, `Body (HTML)` FROM shopify_import";

  // Clean Options
    $clean_options = "UPDATE shopify_import SET `Option1 Name` = '' WHERE `Option1 Value` = ''";

  // Clean Up Image Link URLs from dev to live
    $production_img = "UPDATE shopify_import SET
    `Image Src` = REPLACE(`Image Src`, 'http://t172.dev.smartphoneexperts.com/', 'http://shop.mobilenations.com/'),
    `Variant Image` = REPLACE(`Variant Image`, 'http://t172.dev.smartphoneexperts.com/', 'http://shop.mobilenations.com/')";

  // Create table with only fields requires by shopify_import
    $final_draft = "CREATE TABLE import_to_shopify
    SELECT
    `Handle`,
    `Title`,
    `Body (HTML)`,
    `Vendor`,
    `Type`,
    `Tags`,
    `Published`,
    `Option1 Name`,
    `Option1 Value`,
    `Option2 Name`,
    `Option2 Value`,
    `Option3 Name`,
    `Option3 Value`,
    `Variant SKU`,
    `Variant Grams`,
    `Variant Inventory Tracker`,
    `Varieant Inventory Policy`,
    `Variant Fulfillment Service`,
    `Variant Price`,
    `Variant Compare At Price`,
    `Variant Requires Shipping`,
    `Variant Taxable`,
    `Variant Barcode`,
    `Image Src`,
    `Image Position`,
    `Image Alt Text`,
    `Gift Card`,
    `Google Shopping / MPN`,
    `Google Shopping / Age Group`,
    `Google Shopping / Gender`,
    `Google Shopping / Google Product Category`,
    `SEO Title`,
    `SEO Description`,
    `Google Shopping / AdWords Grouping`,
    `Google Shopping / AdWords Labels`,
    `Google Shopping / Condition`,
    `Google Shopping / Custom Product`,
    `Google Shopping / Custom Label 0`,
    `Google Shopping / Custom Label 1`,
    `Google Shopping / Custom Label 2`,
    `Google Shopping / Custom Label 3`,
    `Google Shopping / Custom Label 4`,
    `Variant Image`,
    `Variant Weight Unit`
    FROM shopify_import";
