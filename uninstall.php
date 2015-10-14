<?php
// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

delete_option('sm_separation_method');
delete_site_option('sm_separation_method');

?>
