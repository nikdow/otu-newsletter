<?php
/**
 * Plugin Name: OTU newsletter
 * Plugin URI: http://www.cbdweb.net
 * Description: Send email to paid and unpaid members
 * Version: 1.0
 * Author: Nik Dow, CBDWeb
 * License: GPL2
 */
require_once plugin_dir_path ( __FILE__ ) . 'options.php';
require_once plugin_dir_path ( __FILE__ ) . '../paid-memberships-pro/paid-memberships-pro.php';
/*
 * Newsletters
 */

define ( 'Newsletter_All_Classes', '' );
define ( 'Newsletter_Unknown_State', '' );
define ( 'Newsletter_Unfinancial', '' );

function cbdweb_newsletter_enqueue_scripts(  ) {
    global $post;
    if( $post && $post->post_type !== 'cbdweb_newsletter' ) return;
    wp_register_script( 'angular', "//ajax.googleapis.com/ajax/libs/angularjs/1.2.18/angular.min.js", 'jquery' );
//    wp_register_script( 'angular', "/skimobile/websrc/assets/deca1dd9/javascript/lib/angular-1.2.12.js", 'jquery' );
    wp_enqueue_script('angular');
    wp_register_script ( 'angular-dialog', get_stylesheet_directory_uri() . '/js/ngDialog.min.js', 'jquery' );
    wp_enqueue_script('angular-dialog');
    wp_enqueue_style('angular-dialog', get_stylesheet_directory_uri() . '/css/ngDialog.min.css' );
    wp_enqueue_style ( 'ngDialog-theme-default', get_stylesheet_directory_uri() . '/css/ngDialog-theme-default.min.css' );
    wp_enqueue_style ( 'ngDialog-theme-plain', get_stylesheet_directory_uri() . '/css/ngDialog-theme-plain.min.css' );
    wp_register_script('newsletter-admin', plugins_url( 'js/newsletter-admin.js' , __FILE__ ), array('jquery', 'angular') );
    wp_localize_script( 'newsletter-admin', '_main',
        array( 'post_url' => admin_url('post.php'),
               'ajax_url' => admin_url('admin-ajax.php'),
               'all_classes' => Newsletter_All_Classes,
               'unknown_state' => Newsletter_Unknown_State,
               'unfinancial' => Newsletter_Unfinancial,
        ) 
    ); 
    wp_enqueue_script( 'newsletter-admin' );
    wp_enqueue_style('newsletter_style', plugins_url( 'css/admin-style.css' , __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'cbdweb_newsletter_enqueue_scripts' );

add_action( 'init', 'create_cbdweb_newsletter' );
function create_cbdweb_newsletter() {
    $labels = array(
        'name' => _x('Newsletters', 'post type general name'),
        'singular_name' => _x('Newsletter', 'post type singular name'),
        'add_new' => _x('Add New', 'events'),
        'add_new_item' => __('Add New Newsletter'),
        'edit_item' => __('Edit Newsletter'),
        'new_item' => __('New Newsletter'),
        'view_item' => __('View Newsletter'),
        'search_items' => __('Search Newsletter'),
        'not_found' =>  __('No newsletters found'),
        'not_found_in_trash' => __('No newsletters found in Trash'),
        'parent_item_colon' => '',
    );
    register_post_type( 'cbdweb_newsletter',
        array(
            'label'=>__('Newsletters'),
            'labels' => $labels,
            'description' => 'Each post is one newsletter.',
            'public' => true,
            'can_export' => true,
            'exclude_from_search' => false,
            'has_archive' => true,
            'show_ui' => true,
            'capabilities' =>array(
                'edit_post'=>'otu_newsletter_edit',
                'edit_posts'=>'otu_newsletter_edit',
                'edit_others_posts'=>'otu_newsletter_edit',
                'publish_posts'=>'otu_newsletter_edit',
            ),
            'menu_icon' => "dashicons-megaphone",
            'hierarchical' => false,
            'rewrite' => false,
            'supports'=> array('title', 'editor' ) ,
            'show_in_nav_menus' => true,
        )
    );
}
/*
 * specify columns in admin view of signatures custom post listing
 */
add_filter ( "manage_edit-cbdweb_newsletter_columns", "cbdweb_newsletter_edit_columns" );
add_action ( "manage_posts_custom_column", "cbdweb_newsletter_custom_columns" );
function cbdweb_newsletter_edit_columns($columns) {
    $columns = array(
        "cb" => "<input type=\"checkbox\" />",
        "title" => "Subject",
        "cbdweb_col_class" => "Class",
        "cbdweb_col_state" => "State",
        "cbdweb_col_membertype" => "Subscriber",
    );
    return $columns;
}
function cbdweb_newsletter_custom_columns($column) {
    global $post;
    $newsletter_class = get_post_meta( $post->ID, 'cbdweb_newsletter_class' );
    $newsletter_state = get_post_meta( $post->ID, "cbdweb_newsletter_state" );
    $newsletter_membertype = get_post_meta ( $post->ID, "cbdweb_newsletter_membertype" );
    switch ( $column ) {
        case "title":
            echo $post->post_title;
            break;
        case "cbdweb_col_class":
            if ( ! is_array( $newsletter_class[0] ) ) {
                echo "&nbsp;";
            } else {
                $display_classes = [];
                foreach ( $newsletter_class[0] as $class ) {
                    if ( $class === Newsletter_All_Classes ) {
                        $display_classes[] = "All";
                    } else {
                        $display_classes[] = $class;
                    }
                }
                echo implode(', ', $display_classes );
            }
            break;
        case "cbdweb_col_state":
            if ( ! is_array( $newsletter_state[0] ) ) {
                echo "&nbsp;";
            } else {
                $display_states = [];
                foreach ( $newsletter_state[0] as $state ) {
                    if ( $state === Newsletter_Unknown_State ) {
                        $display_states[] = "Unknown";
                    } else {
                        $display_states[] = $state;
                    }
                } 
                echo implode(', ', $display_states );
            }
            break;
        case "cbdweb_col_membertype":
            if ( ! is_array( $newsletter_membertype[0] ) ) {
                echo "&nbsp;";
            } else {
                $display_membertypes = [];
                foreach ( $newsletter_membertype[0] as $membertype ) {
                    if ( $membertype === Newsletter_Unfinancial ) {
                        $display_membertypes[] = "Unfinancial";
                    } else {
                        $display_membertypes[] = $membertype;
                    }
                }
                echo implode ( ', ', $display_membertypes );
            }
            break;
    }
}
/*
 * Add fields for admin to edit newsletter custom post
 */
add_action( 'admin_init', 'cbdweb_newsletter_create' );
function cbdweb_newsletter_create() {
    add_meta_box('cbdweb_newsletter_meta', 'Newsletter', 'cbdweb_newsletter_meta', 'cbdweb_newsletter' );
}
function cbdweb_newsletter_meta() {
    global $post;
    $meta_class = get_post_meta( $post->ID, 'cbdweb_newsletter_class' ); // array in meta_value
    $meta_state = get_post_meta( $post->ID, 'cbdweb_newsletter_state' );
    $meta_membertype = get_post_meta ( $post->ID, 'cbdweb_newsletter_membertype' );
    
    echo '<input type="hidden" name="cbdweb-newsletter-nonce" id="cbdweb-newsletter-nonce" value="' .
        wp_create_nonce( 'cbdweb-newsletter-nonce' ) . '" />';
    global $wpdb;
    $data = array(); // all options: for drawing buttons
    $ngdata = array(); // transfers data from db to page, i.e. saved selections
    /*
     * get membership levels
     */
    $query = "SELECT id, name FROM $wpdb->pmpro_membership_levels";
    $membertypes = $wpdb->get_results ( $query, OBJECT );
    $data['membertypes'] = array();  // $data is for drawing the HTML in PHP, includes all possible objects
    $ngdata['membertypes'] = array();  // $ngdata is the meta data status for display and return, includes selected objects
    foreach( $membertypes as $membertype ) {
        $data['membertypes'][] = array('id'=>$membertype->id, 'name'=>$membertype->name );
        if ( is_array ( $meta_membertype[0] ) && in_array ( $membertype->id, $meta_membertype[0] ) )
            $ngdata['membertypes'][] = $membertype->id;
    }
    $data['membertypes'][] = array('id'=>'', 'name'=>"unfinancial");
    if ( is_array( $meta_membertype[0] ) && in_array ( Newsletter_Unfinancial, $meta_membertype[0] ) )
        $ngdata['membertypes'][] = Newsletter_Unfinancial;
    
    /*
     * get States
     */
//  look at all non-admin users and get their State    
    $query = "SELECT IF(u.meta_value=\"Overseas\", \"ZZ\", u.meta_value) as mv FROM $wpdb->usermeta u " .
            "LEFT JOIN $wpdb->usermeta m ON m.user_id=u.user_id AND m.meta_key=\"wp_my0ord_user_level\" " .
            "WHERE u.meta_key=\"pmpro_bstate\" AND u.meta_value!=\"\" AND m.meta_value=0 GROUP BY u.meta_value ORDER BY mv";
    $results = $wpdb->get_results ( $query, OBJECT );
    $states = array();
    foreach($results as $result) {
        $states[] = ($result->mv==="ZZ" ? "Overseas" : $result->mv);
    }
    $data['states'] = $states;
    $ngdata['states'] = array();
    foreach($data['states'] as $ab ) {
        if ( is_array( $meta_state[0] ) && in_array( $ab, $meta_state[0] ) )
            $ngdata['states'][] = $ab;
    }
    $data['states'][] = "Unknown";
    if ( is_array ( $meta_state[0] ) && in_array ( Newsletter_Unknown_State, $meta_state[0] ) )
        $ngdata['states'][] = Newsletter_Unknown_State;
    
    /*
     * get classes
     * for all classes the meta_value is blank, this is the only parameter with an explicit "all" setting
     */
    $query = "SELECT meta_value AS clss FROM $wpdb->usermeta WHERE meta_key=\"pmpro_class\" GROUP BY meta_value";
    $results = $wpdb->get_results ( $query, OBJECT );
    $classes = array();
    foreach ( $results as $result ) {
        $match = preg_match('/^([\d]+)\/([\d]+)([a-zA-Z]+)?$/', $result->clss, $matches );
        if( $match ) {
            $term = intval( $matches[1] );
            $year = intval( $matches[2] );
            $suffix = $matches[3] ? $matches[3] : "";
            $arr = array('term'=>$term, 'year'=>$year, 'suffix'=>$suffix );
            if( array_search ( $arr, $classes ) === false ) {
                $classes[] = $arr;
            }
        }
    }
    function sortclass($a, $b) {
        $va = $a['year'] * 1000 + $a['term'];
        $vb = $b['year'] * 1000 + $b['term'];
        if( $va === $vb && $a['suffix'] === $b['suffix'] ) return 0;
        if( $va === $vb ) return $a['suffix'] < $b['suffix'] ? -1 : 1;
        return ( $va < $vb ) ? -1 : 1;
    }
    usort ( $classes, "sortclass" );
    $clsses = array();
    foreach ( $classes as $class ) {
        $clsses[] =  $class['term'] . "/" . $class['year'] . $class['suffix'];
    }
    $data['clsses'] = $clsses;
    
    $ngdata['clsses'] = array();
    foreach($data['clsses'] as $ab ) {
        if ( is_array ( $meta_class[0] ) && in_array ( $ab, $meta_class[0] ) )
            $ngdata['clsses'][] = $ab;
    }
    if ( is_array ( $meta_class[0] ) && in_array( Newsletter_All_Classes, $meta_class[0] ) )
        $ngdata['clsses'][] = Newsletter_All_Classes;
    ?>
    <script type="text/javascript">
//        _data = <?=json_encode($data)?>;
        _ngdata = <?=json_encode($ngdata)?>;
    </script>
    <div class="fs-meta" ng-app="newsletterAdmin" ng-controller="newsletterAdminCtrl">

            <div id='clsses'>
                <div class='clss wider' ng-class='{selected: isClss("<?=Newsletter_All_Classes?>")}' ng-click='allclss(true)'>All</div>
                <?php
                foreach ( $clsses as $clss ) {
                    echo "<div class='clss' ng-class='{selected: isClss(\"" . $clss . "\")}' ng-click='toggleclss(\"" . $clss . "\")'>" . $clss . "</div>";
                }
                ?>
                <div class="clss wider" ng-click="allclss(false)">Clear all</div>
            </div>
        
            <div id='states'>
                <div class='state' ng-class='{selected: isState(main.unknown_state)}' ng-click='togglestate(main.unknown_state)'>Unknown</div>
                <?php
                foreach ( $states as $state ) {
                    echo "<div class='state' ng-class='{selected: isState(\"" . $state . "\")}' ng-click='togglestate(\"" . $state . "\")'>" . $state . "</div>";
                }
                ?>
            </div>
            <div id='membertypes'>
                <div class='membertype' ng-class='{selected: isMemberType(main.unfinancial)}' ng-click='togglemembertype(main.unfinancial)'>Unfinancial</div>
                <?php
                foreach ( $membertypes as $membertype ) {
                    echo "<div class='membertype' ng-class='{selected: isMemberType(\"" . $membertype->id . "\")}' ng-click='togglemembertype(\"" . $membertype->id . "\")'>" . $membertype->name . "</div>";
                }
                ?>
            </div>

            <ul>
                <li>Test addresses (leave blank to send bulk):</li>
                <li><input class='wide' name='cbdweb_newsletter_test_addresses'/></li>
                <li><button type="button" ng-click="sendNewsletter()">Send newsletter</button></li>
                <li ng-show="showLoading"><img src="<?php echo get_site_url();?>/wp-includes/js/thickbox/loadingAnimation.gif"></li>
                <li ng-show='showProgressNumber'>
                    {{email.count}} sent of {{email.total}}
                </li>
                <li ng-show='showProgressMessage'>
                    {{email.message}}
                </li>
            </ul>
    <input name='ajax_id' value="<?=$post->ID?>" type="hidden" />
    <?=wp_nonce_field( 'otu_sendNewsletter', 'otu-sendNewsletter', false, false );?>
    <input name='cbdweb_newsletter_send_newsletter' value='0' type='hidden' />
    <?php 
}

add_action ('save_post', 'save_cbdweb_newsletter');
 
function save_cbdweb_newsletter(){
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    global $post;
    
    if( 'cbdweb_newsletter' === $_POST['post_type'] ) {

    // - still require nonce

        if ( !wp_verify_nonce( $_POST['cbdweb-newsletter-nonce'], 'cbdweb-newsletter-nonce' )) {
            return $post->ID;
        }

        if ( !current_user_can( 'edit_post', $post->ID ))
            return $post->ID;

        // update post
        
        if ( isset ( $_POST["cbdweb_newsletter_class"] ) ) 
            update_post_meta($post->ID, "cbdweb_newsletter_class", $_POST["cbdweb_newsletter_class"] ); // array of classes or blank for all (all will include non-class subscribers such as staff)
        if ( isset ( $_POST["cbdweb_newsletter_state"] ) )
            update_post_meta($post->ID, "cbdweb_newsletter_state", $_POST["cbdweb_newsletter_state"] ); // is an array of states or blank for all. blanks is "unknown"
        if ( isset ( $_POST["cbdweb_newsletter_membertype"] ) )
                update_post_meta($post->ID, "cbdweb_newsletter_membertype", $_POST["cbdweb_newsletter_membertype"] ); // is an array of membertypes 0=unfinancial
        if( isset( $_POST['cbdweb_newsletter_send_newsletter']) && $_POST[ 'cbdweb_newsletter_send_newsletter' ] === '1' 
            &&
                current_user_can( 'otu_newsletter_send')
        ) {
            
            /* try to prevent WP from sending text/plain */
            add_filter( 'wp_mail_content_type', 'set_content_type' );
            function set_content_type( $content_type ){
                    return 'text/html';
            }
            
            $test_addresses = $_POST['cbdweb_newsletter_test_addresses'];
            session_write_close (); // avoid session locking blocking progess ajax calls
            update_post_meta($post->ID, "cbdweb_newsletter_progress", json_encode( array ( 'count'=>0, 'total'=>Count( $sendTo ), 'message'=>'querying the database' ) ) );
                    
            if( $test_addresses !== "" ) {
                
                $addressArray = explode(",", $test_addresses );
                $sendTo = array();
                foreach ( $addressArray as $address ) {
                    $sendTo[] = (object) array("name"=>"", "email"=>trim( $address ) );
                }
                
            } else {
                $class_requested = $_POST["cbdweb_newsletter_class"];
                $state_requested = $_POST["cbdweb_newsletter_state"];
                $membertype_requested = $_POST["cbdweb_newsletter_membertype"];

                $params = array();
                if ( ! $class_requested ) { // all classes, including ppl with no class
                    $class_requested = array("");
                }
                foreach ( $class_requested as $clss ) {
                    if( $clss != '' ) {
                        preg_match('/^([\d]+)\/([\d]+)([a-zA-Z]+)?$/', $clss, $matches );
                        $params[] = intval ( $matches[1] );
                        $params[] = intval ( $matches[2] ) . ( $matches[3] ? $matches[3] : "");
                        
                    }
                }

                if ( Count( $membertype_requested ) > 0 ) {
                    $membertypearr = array();
                    foreach ( $membertype_requested as $membertype ) {
                        $membertypearr[] = "%d";
                        $params[] = $membertype;
                    }
                    $membertypestr = join(",", $membertypearr );
                }
                if ( Count($state_requested)>0 ) {
                    $statearr = array();
                    foreach ( $state_requested as $state ) {
                        $statearr[] = "%s";
                        $params[] = $state;
                    }
                    $statestr = join(",", $statearr );
                }
                
                global $wpdb;
                
                $class_subquery = "";
                if ( $class_requested[0] !== Newsletter_All_Classes ) {
                    $class_list = [];
                    foreach ( $class_requested as $class ) {
                        $class_list[] = "   ( SUBSTRING_INDEX(c.meta_value, '/', 1)=%d AND SUBSTRING_INDEX(c.meta_value, '/', -1)=%s )";
                    }
                    $class_subquery = " AND ( " . implode(" OR ", $class_list ) . " )";
                }
                
                $query = 
                    "SELECT ume.meta_value as email" .
                    " FROM " . $wpdb->users . " u" .
                    " LEFT JOIN " . $wpdb->usermeta . " ume ON ume.user_id=u.ID AND ume.meta_key='pmpro_bemail'" .
                    " LEFT JOIN $wpdb->usermeta m ON m.user_id=u.ID AND m.meta_key='" . $wpdb->base_prefix . "user_level' " .
                    ( Count ( $class_requested ) == 0 ? "" : 
                        " LEFT JOIN $wpdb->usermeta c ON c.user_id=u.ID AND c.meta_key='pmpro_class'" ) .
                    ( Count ( $state_requested ) == 0 ? "" : 
                        " LEFT JOIN $wpdb->usermeta s ON s.user_id=u.ID AND s.meta_key='pmpro_bstate'" ) .
                    ( Count($membertype_requested)==0 ? "" : 
                        " LEFT JOIN $wpdb->pmpro_memberships_users p ON p.user_id=u.ID AND p.status='active'" ) .
                        " LEFT JOIN $wpdb->usermeta dnc ON dnc.user_id=u.ID AND dnc.meta_key='pmpro_do_not_contact'" .
                        " LEFT JOIN $wpdb->usermeta dc ON dc.user_id=u.ID AND dc.meta_key='pmpro_deceased'" .
                    " WHERE m.meta_value=0 AND dnc.meta_value!='1' AND dc.meta_value!='1' AND ume.meta_value IS NOT NULL" .
                    $class_subquery .
                    ( Count($membertype_requested)==0 ? "" : " AND IF(p.membership_id IS NULL, '" . Newsletter_Unfinancial . "' , p.membership_id) IN (" . $membertypestr . ")" ) .
                    ( Count($state_requested)==0 ? "" : " AND IF(s.meta_value = '', '" . Newsletter_Unknown_State . "' , s.meta_value) IN (" . $statestr . ")" );
                if ( Count ( $params ) > 0 ) {
                    $query = $wpdb->prepare ( $query, $params );
                }
                
                update_option ( 'Newsletter_query' ,$query );

                $sendTo = $wpdb->get_results ( $query );
            }
            $testing = false; // true on dev computer - not the same as test addresses from UI
            $count =0;
            foreach ( $sendTo as $one ) {
                $email = $one->email;
                if ( $testing ) $email = "nik@cbdweb.net";
                $subject = $post->post_title;
                if ( $testing ) $subject .= " - " . $one->email;
                $headers = array();
                $headers[] = 'From: ' . get_option('cbdweb-newsletter-sender-name') . " <" . get_option('cbdweb-newsletter-sender-address') . '>';
                $headers[] = "Content-type: text/html";
                $message = $post->post_content;
                $message = str_replace("\r\n", "<br>\r\n", $message );
                wp_mail( $email, $subject, $message, $headers );
                $count++;
                update_post_meta($post->ID, "cbdweb_newsletter_progress", json_encode( array ( 
                    'count'=>$count, 'total'=>Count( $sendTo ),
                    'message'=>'last email sent: ' . $email,
                ) ) );
                if ( $testing && $count > 15 ) break;
            }
            echo json_encode( array ( "success"=>"completed: " . $count . " emails" ) );
            die;
        }
    }
}

add_action( 'wp_ajax_cbdweb_newsletter_progress', 'cbdweb_newsletter_progress' );
function cbdweb_newsletter_progress() {
    $post_id = $_POST['post_id'];
    echo get_post_meta( $post_id, 'cbdweb_newsletter_progress', true );
    die;
}

add_filter('post_updated_messages', 'cbdweb_newsletter_updated_messages');
 
function cbdweb_newsletter_updated_messages( $messages ) {
 
  global $post, $post_ID;
 
  $messages['cbdweb_newsletter'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Newsletter updated. <a href="%s">View item</a>'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('Newsletter updated.'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Newsletter restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Newsletter published. <a href="%s">View Newsletter</a>'), esc_url( get_permalink($post_ID) ) ),
    7 => __('Newsletter saved.'),
    8 => sprintf( __('Newsletter submitted. <a target="_blank" href="%s">Preview newsletter</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('Newsletter scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview newsletter</a>'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Newsletter draft updated. <a target="_blank" href="%s">Preview newsletter</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );
 
  return $messages;
}
/*
 * label for title field on custom posts
 */

add_filter('enter_title_here', 'cbdweb_newsletter_enter_title');
function cbdweb_newsletter_enter_title( $input ) {
    global $post_type;

    if ( 'cbdweb_newsletter' === $post_type ) {
        return __( '' );
    }
    return $input;
}

add_filter( 'default_content', 'cbdweb_newsletter_content', 10, 2 );

function cbdweb_newsletter_content( $content, $post ) {

    if( $post->post_type !== "cbdweb_newsletter") {
        return $content;
    }
 
    $template = get_option( 'cbdweb-newsletter-template' );
    
    $today = date( get_option('time_format') );
    $unsubscribe = add_query_arg ( "email", "%email%", get_permalink( get_page_by_title( 'Unsubscribe' ) ) );
    
    $content = str_replace( 
                array( '{today}',
                    '{unsubscribe}',
                    ),
                array( $today,
                    $unsubscribe,
                    ),
                $template );
    return $content;
}
function otu_newsletter_activation() {
    add_role( 'otu_newsletter_editor', 'OTU Newsletter Editor', array(
        "otu_newsletter_send"=>true,
        "otu_newsletter_edit"=>true,
    ) );
}
function otu_newsletter_deactivation() {
    remove_role ( 'otu_newsletter_editor' );
}
register_activation_hook(__FILE__, 'otu_newsletter_activation');
register_deactivation_hook(__FILE__, 'otu_newsletter_deactivation');
