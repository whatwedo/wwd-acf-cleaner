<?php

namespace whatwedo\AcfCleaner;

/**
 * WP Hooks
 *
 * @since      1.0.0
 * @package    wwd-acf-cleaner
 */

class WP
{
    private $actionNonceName = WWDACFCLEANER_NAME . '-action-nonce';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);

        // add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_filter('script_loader_tag', [$this, 'addScriptAttribute'], 10, 3);

        add_action('wp_ajax_singleDiscovery', [$this, 'singleDiscovery']);
        //add_action('wp_ajax_cleanPost', [$this, 'cleanPost']);
        add_action('wp_ajax_batchDiscovery', [$this, 'batchDiscoveryRequest']);
        add_action('wp_ajax_batchCleanup', [$this, 'batchCleanupRequest']);
    }

    public function addAdminMenu()
    {
        $this->tool_menu_id = add_management_page(
            __('WWD ACF Cleaner by whatwedo', 'wwdac'),
            __('ACF Cleaner', 'wwdac'),
            'manage_options',
            'wwd-acf-cleaner',
            [$this, 'managementInterfaceRender']
        );
    }

    public function managementInterfaceRender()
    {

        echo '<div id="wwdac-app"></div>';

        /*
        $postId = 2832;
        $isDry = true;
        $discovery = new Discovery($postId, $isDry);
        print_r($discovery->getUnusedData());
        */

        /*
        $posts = QueryHelper::getBlogPosts(5);

        foreach ($posts as $post) {
            $discovery = new Discovery($post->getId(), true);

            echo '<h3>' . $post->getTitle() . ' (ID: ' . $post->getId() . ')</h3>';
            $unusedData = $discovery->getUnusedData();
            print_r($unusedData);
            echo 'Unused datasets: ' . sizeof($unusedData);
        }
        */
    }

    /*
        Enqueue admin script
     */

    public function enqueueAdminAssets()
    {
        if (get_current_screen()->id === 'tools_page_wwd-acf-cleaner') {
            wp_enqueue_script('vuejs', 'https://unpkg.com/vue@^3/dist/vue.global.prod.js');
            wp_register_script('wwdac-vuejs', WWDACFCLEANER_DIR_URL . 'assets/wwd-acf-cleaner.js', 'vuejs', true);

            wp_localize_script('wwdac-vuejs', 'wwdacData', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'action' => 'discoverPost',
                'nonce' => wp_create_nonce($this->actionNonceName),
                'postTypes' => Data::getAllCustomPostTypes(),
                'posts' => (new Data)->batchDiscovery(['post']),
            ]);
            wp_enqueue_script('wwdac-vuejs');

            wp_enqueue_style('tailwind-css', 'https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css');
        }
    }

    public function addScriptAttribute($tag, $handle, $src)
    {
        if ('wwdac-vuejs' !== $handle) {
            return $tag;
        }
        $tag = '<script type="module" src="' . esc_url($src) . '"></script>';

        return $tag;
    }

    public function singleDiscovery()
    {
        Helper::checkNonce($this->actionNonceName);

        $postId = $_REQUEST['postId'];
        $data = (new Data())->singleDiscovery($postId);

        Helper::returnAjaxData($data);
    }

    public function batchDiscoveryRequest()
    {
        Helper::checkNonce($this->actionNonceName);

        $postType = $_REQUEST['postType'];
        $paged = $_REQUEST['paged'];
        $batchData = (new Data())->batchDiscovery($postType, $paged, true);

        Helper::returnAjaxData($batchData);
    }

    public function batchCleanupRequest()
    {
        Helper::checkNonce($this->actionNonceName);

        $postType = $_REQUEST['postType'];
        $paged = $_REQUEST['paged'];
        $batchData = (new Data())->batchDiscovery($postType, $paged, true); // TODO: change this

        Helper::returnAjaxData($batchData);
    }
}